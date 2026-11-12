import fs from "fs";
import http from "http";
import https from "https";
import path, { dirname } from "path";
import { fileURLToPath } from "url";
import express from "express";
import cors from "cors";
import bodyParser from "body-parser";
import { rimraf } from "rimraf";
import moment from "moment-timezone";
import qrimg from "qr-image";
import axios from "axios";
import cron from "node-cron";
import spintax from "spintax";
import Boom from "@hapi/boom";
import { Server } from "socket.io";
import util from "util";
import { execFile } from "child_process";

import config from "./../config.js";
import Common from "./common.js";

const execFileAsync = util.promisify(execFile);

/* -------------------------------------------------------------------------- */
/*                                                             ESM + Globals                                                                */
/* -------------------------------------------------------------------------- */
const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

// Baileys v7 ESM import
import * as baileys from "baileys";
const {
    default: makeWASocket,
    useMultiFileAuthState,
    DisconnectReason,
    Browsers,
    fetchLatestBaileysVersion,
} = baileys;

/* -------------------------------------------------------------------------- */
/*                                                                    Logger                                                                        */
/* -------------------------------------------------------------------------- */
const isDebug = true;

const COLORS = {
    reset: "\x1b[0m",
    gray: "\x1b[90m",
    red: "\x1b[31m",
    green: "\x1b[32m",
    yellow: "\x1b[33m",
    blue: "\x1b[34m",
    magenta: "\x1b[35m",
    cyan: "\x1b[36m",
    bold: "\x1b[1m",
};

const debugDump = (label, data, inst = null, depth = 4) => {
    if (!isDebug) return;
    const prefix = tag(inst ? COLORS.magenta + `[${inst}]${COLORS.reset} ` : "");
    console.log(`${prefix}${COLORS.yellow}[DEBUG] ${label}${COLORS.reset}`);
    console.log(util.inspect(data, {
        depth,
        colors: true,
        maxArrayLength: 100,
        breakLength: 120,
        compact: false,
    }));
};

const ICONS = {
    ok: "[OK]",
    info: "[INFO]",
    warn: "[WARN]",
    err: "[ERR]",
    bolt: "[EVT]",
    msg: "[MSG]",
};

const ts = () => moment().format("HH:mm:ss");
const tag = (t) =>
    `${COLORS.bold}${COLORS.cyan}[WAZIPER]${COLORS.reset}${COLORS.gray}[${ts()}]${COLORS.reset} ${t}`;

const cleanLogMessage = (msg) => String(msg ?? "").replace(/[^\x20-\x7E]/g, "").replace(/\s{2,}/g, " ").trim();

const logger = {
    log: (color, icon, msg, inst) => {
        if (!isDebug) return;
        const safeMsg = cleanLogMessage(msg);
        console.log(
            `${tag(inst ? COLORS.magenta + `[${inst}]${COLORS.reset} ` : "")}${color}${icon} ${safeMsg}${COLORS.reset}`
        );
    },
    info: (m, i) => isDebug && logger.log(COLORS.blue, ICONS.info, m, i),
    ok:   (m, i) => isDebug && logger.log(COLORS.green, ICONS.ok, m, i),
    warn: (m, i) => isDebug && logger.log(COLORS.yellow, ICONS.warn, m, i),
    err:  (m, i) => isDebug && logger.log(COLORS.red, ICONS.err, m, i),
    evt:  (m, i) => isDebug && logger.log(COLORS.cyan, ICONS.bolt, m, i),
    msg:  (m, i) => isDebug && logger.log(COLORS.bold, ICONS.msg, m, i),
};

/* -------------------------------------------------------------------------- */
/*                                                             Express + IO                                                                 */
/* -------------------------------------------------------------------------- */
const app = express();
const server = http.createServer(app);
const io = new Server(server, { cors: { origin: "*" } });

app.use(cors(config.cors));
app.use(bodyParser.urlencoded({ extended: true, limit: "50mb" }));
app.use(bodyParser.json({ limit: "50mb" }));

/* -------------------------------------------------------------------------- */
/*                                                        Global State Storage                                                        */
/* -------------------------------------------------------------------------- */
const bulks = {};
const chatbots = {};
const limit_messages = {};
const stats_history = {};
const sessions = {};
const contact_cache = {};
const new_sessions = {};
const session_dir = path.join(__dirname, "../sessions/");
let verify_next = 0;
let verify_response = false;
let verified = false;
let chatbot_delay = 1000;
let BULK_WORKING = false;
let EMAIL_CRON_WORKING = false;

const ensureContactCache = (instance_id) => {
    if (!contact_cache[instance_id]) {
        contact_cache[instance_id] = {};
    }

    return contact_cache[instance_id];
};

const normalizeChatId = (value) => {
    const chatId = String(value || "").trim().toLowerCase();
    return chatId !== "" ? chatId : "";
};

const normalizePhone = (value) => {
    const phone = Common.get_phone(String(value || "")).replace(/\D+/g, "").trim();
    return phone !== "" ? phone : "";
};

const cacheContactIdentity = (instance_id, payload = {}) => {
    const store = ensureContactCache(instance_id);
    const ids = [
        payload.id,
        payload.jid,
        payload.lid,
        payload.chat_id,
        payload.remoteJid,
        payload.participant,
        payload.senderLid,
    ]
        .map(normalizeChatId)
        .filter(Boolean);

    const phone = [
        payload.phone,
        payload.senderPn,
        payload.participantPn,
        payload.jid,
    ]
        .map(normalizePhone)
        .find(Boolean) || "";

    const name = String(
        payload.name ||
        payload.notify ||
        payload.pushName ||
        payload.verifiedName ||
        payload.shortName ||
        ""
    ).trim();

    if (ids.length === 0) {
        return null;
    }

    const base = ids.reduce((carry, id) => carry || store[id], null) || {};
    const next = {
        ...base,
        id: ids[0],
        name: name || base.name || "",
        phone: phone || base.phone || "",
    };

    ids.forEach((id) => {
        store[id] = { ...next, id };
    });

    if (phone) {
        const phoneId = `${phone}@s.whatsapp.net`;
        store[phoneId] = {
            ...store[phoneId],
            ...next,
            id: phoneId,
            phone,
        };
    }

    return next;
};

const getCachedContactIdentity = (instance_id, ...candidates) => {
    const store = ensureContactCache(instance_id);

    for (const candidate of candidates) {
        const chatId = normalizeChatId(candidate);
        if (chatId && store[chatId]) {
            return store[chatId];
        }

        const phone = normalizePhone(candidate);
        if (phone) {
            const phoneId = `${phone}@s.whatsapp.net`;
            if (store[phoneId]) {
                return store[phoneId];
            }
        }
    }

    return null;
};

/* -------------------------------------------------------------------------- */
/*                                            MESSAGE RETRY CACHE             */
/* -------------------------------------------------------------------------- */
const messageRetryStore = new Map();

const saveCachedMessage = (msgId, message) => {
    if (!msgId || !message) return;
    if (messageRetryStore.size > 3000) {
        const oldestKeys = Array.from(messageRetryStore.keys()).slice(0, 300);
        for (const k of oldestKeys) {
            messageRetryStore.delete(k);
        }
    }
    messageRetryStore.set(msgId, message);
};

const getCachedMessage = (msgId) => {
    if (!msgId) return undefined;
    return messageRetryStore.get(msgId);
};

const extractMessageText = (msg) => {
    if (!msg) return "";
    let m = msg.message || msg;
    if (m?.ephemeralMessage?.message) m = m.ephemeralMessage.message;
    if (m?.viewOnceMessage?.message) m = m.viewOnceMessage.message;
    if (m?.viewOnceMessageV2?.message) m = m.viewOnceMessageV2.message;
    if (m?.documentWithCaptionMessage?.message) m = m.documentWithCaptionMessage.message;

    if (m?.conversation) return m.conversation;
    if (m?.extendedTextMessage?.text) return m.extendedTextMessage.text;
    if (m?.imageMessage?.caption) return m.imageMessage.caption;
    if (m?.videoMessage?.caption) return m.videoMessage.caption;
    if (m?.templateButtonReplyMessage?.selectedDisplayText) return m.templateButtonReplyMessage.selectedDisplayText;
    if (m?.templateButtonReplyMessage?.selectedId) return m.templateButtonReplyMessage.selectedId;
    if (m?.buttonsResponseMessage?.selectedDisplayText) return m.buttonsResponseMessage.selectedDisplayText;
    if (m?.buttonsResponseMessage?.selectedButtonId) return m.buttonsResponseMessage.selectedButtonId;
    if (m?.listResponseMessage) {
        return (m.listResponseMessage.title || "") + " " + (m.listResponseMessage.description || "") + " " + (m.listResponseMessage.singleSelectReply?.selectedRowId || "");
    }
    return "";
};

/* -------------------------------------------------------------------------- */
/*                                                         MAIN WAZIPER CORE                                                            */
/* -------------------------------------------------------------------------- */
const WAZIPER = {
    io,
    app,
    server,

    /* ---------------------- Core Connection Builder ---------------------- */
    makeWASocket: async function (instance_id) {
	    const sessionRoot = path.join(__dirname, "../sessions");
	    const sessionPath = path.join(sessionRoot, instance_id);

	    /* -------------------------- CREATE SESSION DIR -------------------------- */
	    try {
	        if (!fs.existsSync(sessionRoot)) fs.mkdirSync(sessionRoot, { recursive: true });
	        if (!fs.existsSync(sessionPath)) fs.mkdirSync(sessionPath, { recursive: true });
	        fs.accessSync(sessionPath, fs.constants.W_OK);
	    } catch (err) {
	        logger.err(`Cannot access/create session folder: ${err.message}`, instance_id);
	        throw err;
	    }

	    /* -------------------------- LOAD AUTH STATE -------------------------- */
	    const { state, saveCreds } = await useMultiFileAuthState(sessionPath);

	    let waVersion = [2, 3000, 1043857760];
	    try {
	        const vInfo = await fetchLatestBaileysVersion();
	        if (vInfo && vInfo.version) waVersion = vInfo.version;
	    } catch (vErr) {
	        logger.warn(`Could not fetch latest Baileys version: ${vErr.message}`, instance_id);
	    }

	    const WA = makeWASocket({
	        version: waVersion,
	        auth: state,
	        printQRInTerminal: false,
	        connectTimeoutMs: 60_000,
	        defaultQueryTimeoutMs: 45_000,
	        browser: Browsers.macOS("Desktop"),
	        receivedPendingNotifications: true,
	        markOnlineOnConnect: true,
	        syncFullHistory: false,
	        getMessage: async (key) => {
	            if (key && key.id) {
	                const msg = getCachedMessage(key.id);
	                if (msg) {
	                    logger.ok(`[Retry] Provided cached message for retry request: ${key.id}`, instance_id);
	                    return msg;
	                }
	            }
	            logger.warn(`[Retry] Message not found in cache for key: ${key?.id}`, instance_id);
	            return undefined;
	        },
	        patchMessageBeforeSending: (msg) => {
                return msg;
            },
	    });

	    /* --------------------------- STATE VARIABLES --------------------------- */
	    WA.status = "booting"; // booting | qr | open | closed
	    WA.qrcode = null;
	    WA.qrTs = 0;
	    WA._reconnecting = false;

	    /* ------------------------ SAFE REMOVE FUNCTION ------------------------ */
	    const safeRemove = async (dir) => {
	        try {
	            if (fs.existsSync(dir)) {
                // Let Baileys release file handles before removing the session folder
	                await new Promise((r) => setTimeout(r, 1000));
                // Skip folder removal if creds.json is still locked by the OS
	                const credsFile = path.join(dir, "creds.json");
	                try {
	                    fs.accessSync(credsFile, fs.constants.R_OK);
	                } catch {
	                    logger.warn("creds.json locked skip delete", instance_id);
	                    return;
	                }

	                await rimraf(dir, { maxRetries: 5, retryDelay: 300 });
	                logger.warn(`Session folder removed: ${dir}`, instance_id);
	            }
	        } catch (err) {
	            if (err.code !== "ENOENT")
	                logger.warn(`Safe remove failed: ${err.message}`, instance_id);
	        }
	    };

	    /* ------------------------ CONNECTION HANDLER ------------------------ */
	    WA.ev.on("connection.update", async ({ connection, lastDisconnect, qr }) => {
	        try {
	            if (qr) {
	                WA.qrcode = qr;
	                WA.qrTs = Date.now();
	                WA.status = "qr";
	                new_sessions[instance_id] = Date.now() / 1000 + 300;
	                logger.info("QR generated", instance_id);
	            }

	            switch (connection) {
	                case "open":
	                    WA.status = "open";
	                    WA.qrcode = null;
	                    delete new_sessions[instance_id];
	                    sessions[instance_id] = WA;

	                    logger.ok("Connected", instance_id);
	                    if (!WA.user.name) WA.user.name = Common.get_phone(WA.user.id);

	                    try {
	                        const session = await Common.db_get("whatsapp_sessions", [
	                            { instance_id },
	                            { status: 0 },
	                        ]);
	                        if (session) {
	                            WA.user.avatar = await WAZIPER.get_avatar(WA);
                            let account = await Common.db_query(
                                `SELECT * FROM accounts
                                 WHERE token = '${instance_id}'
                                   AND login_type = 2
                                   AND social_network IN ('whatsapp_unofficial')
                                 ORDER BY (social_network = 'whatsapp_unofficial') DESC, id DESC
                                 LIMIT 1`
                            );
                            if (!account) {
                                const pid = Common.get_phone(WA.user.id, "wid");
                                account = await Common.db_query(
                                    `SELECT * FROM accounts
                                     WHERE pid = '${pid}'
                                       AND team_id = '${session.team_id}'
                                       AND category = 'profile'
                                       AND login_type = 2
                                       AND social_network IN ('whatsapp_unofficial')
                                     ORDER BY (social_network = 'whatsapp_unofficial') DESC, id DESC
                                     LIMIT 1`
                                );
                            }
	                            await Common.update_status_instance(instance_id, WA.user);
	                            await WAZIPER.add_account(instance_id, session.team_id, WA.user, account);
	                        }
	                    } catch (err) {
	                        logger.warn(`Failed DB sync after connect: ${err.message}`, instance_id);
	                    }
	                    break;

	                case "close":
	                    WA.status = "closed";
	                    const code =
	                        lastDisconnect?.error?.output?.statusCode ||
	                        lastDisconnect?.error?.status ||
	                        0;

	                    const msg = lastDisconnect?.error?.message || "unknown";
	                    logger.warn(`Connection closed (code: ${code}) ${msg}`, instance_id);

	                    const isLoggedOut =
	                        code === DisconnectReason.loggedOut ||
	                        (code === 401 && msg.includes("logged out"));

	                    if (isLoggedOut) {
                        // Logged out: remove the stale session so a fresh login can start
	                        await safeRemove(sessionPath);
	                        delete sessions[instance_id];
	                        delete chatbots[instance_id];
	                        delete bulks[instance_id];
	                        logger.warn("Logged out waiting for new login", instance_id);
	                    } else if ([408, 515, 500, 0].includes(code)) {
                        // Temporary timeout or reset: keep the auth folder and reconnect in place
	                        logger.info(`Temporary disconnect (${code}) keeping session`, instance_id);

	                        if (!WA._reconnecting) {
	                            WA._reconnecting = true;
	                            setTimeout(async () => {
	                                logger.info("Reconnecting existing session...", instance_id);
	                                try {
	                                    await WAZIPER.session(instance_id, true);
	                                } catch (err) {
	                                    logger.warn(`Reconnect failed: ${err.message}`, instance_id);
	                                }
	                                WA._reconnecting = false;
	                            }, 4000);
	                        }
	                    } else {
	                        logger.warn(`Unhandled disconnect (${code}) skip delete`, instance_id);
	                    }
	                    break;

	                default:
	                    break;
	            }
	        } catch (err) {
	            logger.err(`connection.update error: ${err.message}`, instance_id);
	        }
	    });

	    /* ---------------------------- MESSAGE EVENTS --------------------------- */
	    WA.ev.on("messages.upsert", async (messages) => {
	        try {
	            if (!messages?.messages) return;

	            const list = messages.messages;
	            if (!Array.isArray(list) || list.length === 0) return;

	            for (const m of list) {
	                if (m?.key?.id && m?.message) {
	                    saveCachedMessage(m.key.id, m.message);
	                }
	            }

	            const enrichedMessages = [];

	            for (const message of list.slice(0, 10)) {
	                const chat_id = message.key.remoteJid;
	                const webhookMessage = JSON.parse(JSON.stringify(message));
                    const participant = message.key?.participant || "";
                    const senderPn = message.key?.senderPn || "";
                    const participantPn = message.key?.participantPn || "";
                    const cachedIdentity = cacheContactIdentity(instance_id, {
                        id: chat_id,
                        participant,
                        senderPn,
                        participantPn,
                        pushName: message.pushName,
                    }) || getCachedContactIdentity(instance_id, chat_id, participant, senderPn, participantPn);

	                if (chat_id === "status@broadcast") {
	                    enrichedMessages.push(webhookMessage);
	                    continue;
	                }

	                if (chat_id && (chat_id.includes("@s.whatsapp.net") || chat_id.includes("@c.us"))) {
	                    webhookMessage._chat = {
	                        ...(webhookMessage._chat || {}),
	                        type: "direct",
	                        id: chat_id,
	                        phone: cachedIdentity?.phone || Common.get_phone(chat_id) || "",
                            name: cachedIdentity?.name || message.pushName || "",
	                    };
	                }

                    if (chat_id && chat_id.includes("@lid")) {
                        webhookMessage._chat = {
                            ...(webhookMessage._chat || {}),
                            type: "direct",
                            id: chat_id,
                            phone: cachedIdentity?.phone || normalizePhone(senderPn) || normalizePhone(participantPn) || "",
                            name: cachedIdentity?.name || message.pushName || "",
                        };
                    }

                    if (cachedIdentity) {
                        webhookMessage._contact = {
                            id: cachedIdentity.id || normalizeChatId(chat_id),
                            name: cachedIdentity.name || "",
                            phone: cachedIdentity.phone || "",
                        };
                    }

	                if (chat_id && chat_id.includes("@g.us")) {
	                    sessions[instance_id].groups ??= [];
	                    let groupInfo = sessions[instance_id].groups.find((g) => g.id === chat_id);

	                    if (!groupInfo || !groupInfo.name) {
	                        try {
	                            const group = await WA.groupMetadata(chat_id);
	                            groupInfo = {
	                                id: group.id,
	                                name: (group.subject || "").toString(),
	                                size: group.size || (Array.isArray(group.participants) ? group.participants.length : 0),
	                                desc: group.desc?.toString() || "",
	                                participants: Array.isArray(group.participants)
	                                    ? group.participants
	                                        .map((participant) => {
	                                            const jid = (participant.jid || "").toString();
	                                            const lid = (participant.lid || participant.id || "").toString();

	                                            return {
	                                                id: jid || lid,
	                                                jid,
	                                                lid,
	                                                admin: participant.admin || null,
	                                            };
	                                        })
	                                        .filter((participant) => participant.id)
	                                    : [],
	                            };

	                            const existingIndex = sessions[instance_id].groups.findIndex((g) => g.id === chat_id);
	                            if (existingIndex >= 0) {
	                                sessions[instance_id].groups[existingIndex] = groupInfo;
	                            } else {
	                                sessions[instance_id].groups.push(groupInfo);
	                            }

	                            logger.info(`Cached group: ${groupInfo.name || chat_id}`, instance_id);
	                        } catch (err) {
	                            logger.warn(`Failed to fetch group metadata: ${err.message}`, instance_id);
	                        }
	                    }

	                    if (groupInfo) {
	                        webhookMessage._chat = {
	                            type: "group",
	                            id: groupInfo.id,
	                            name: (groupInfo.name || "").toString(),
	                            size: groupInfo.size || 0,
	                            desc: (groupInfo.desc || "").toString(),
	                            participant_phone: Common.get_phone(message.key?.participantPn || "") || "",
                                participant_name: getCachedContactIdentity(instance_id, participant, participantPn)?.name || message.pushName || "",
	                            participants: Array.isArray(groupInfo.participants) ? groupInfo.participants : [],
	                        };
	                    }
	                }

	                enrichedMessages.push(webhookMessage);

	                if (message.key.fromMe || chat_id === "status@broadcast") continue;

	                if (!message.message) {
	                    logger.warn(`[INCOMING_MSG] Undecrypted message (E2EE sync pending / 'Waiting for this message'): chat_id=${chat_id}, pushName=${message.pushName || "unknown"}`, instance_id);
	                    continue;
	                }

	                const incomingText = extractMessageText(message);
	                logger.ok(`[INCOMING_MSG] Received from ${chat_id} | Name: ${message.pushName || "User"} | Text: "${incomingText}"`, instance_id);

	                const user_type = chat_id.includes("g.us") ? "group" : "user";

	                let handled = false;

	                try {
	                    handled = await WAZIPER.chatbot(instance_id, user_type, message);
	                } catch (err) {
	                    logger.warn(`chatbot() error: ${err.message}`, instance_id);
	                }

	                if (handled) continue;

	                try {
	                    handled = await WAZIPER.ai_smart_reply(instance_id, user_type, message);
	                } catch (err) {
	                    logger.warn(`ai_smart_reply() error: ${err.message}`, instance_id);
	                }

	                if (handled) continue;

	                try {
	                    await WAZIPER.autoresponder(instance_id, user_type, message);
	                } catch (err) {
	                    logger.warn(`autoresponder() error: ${err.message}`, instance_id);
	                }
	            }

	            await WAZIPER.webhook(instance_id, {
	                event: "messages.upsert",
	                data: {
	                    ...messages,
	                    messages: enrichedMessages
	                }
	            });
	        } catch (err) {
	            logger.err(`messages.upsert failed: ${err.message}`, instance_id);
	        }
	    });

	    /* ----------------------------- WEBHOOK SYNC ---------------------------- */
	    const forwardWebhook = async (event, data) => {
	        try {
	            await WAZIPER.webhook(instance_id, { event, data });
	        } catch (err) {
	            logger.warn(`webhook(${event}) failed: ${err.message}`, instance_id);
	        }
	    };

	    WA.ev.on("contacts.update", (data) => {
            if (Array.isArray(data)) {
                data.forEach((item) => cacheContactIdentity(instance_id, item));
            }
            forwardWebhook("contacts.update", data);
        });
	    WA.ev.on("contacts.upsert", (data) => {
            if (Array.isArray(data)) {
                data.forEach((item) => cacheContactIdentity(instance_id, item));
            }
            forwardWebhook("contacts.upsert", data);
        });
	    WA.ev.on("messages.update", (data) => forwardWebhook("messages.update", data));
	    WA.ev.on("groups.update", (data) => forwardWebhook("groups.update", data));
	    WA.ev.on("creds.update", async () => {
            try {
                if (!fs.existsSync(sessionPath)) {
                    fs.mkdirSync(sessionPath, { recursive: true });
                }
                await saveCreds();
            } catch (err) {
                logger.warn(`saveCreds error: ${err.message}`, instance_id);
            }
        });

	    return WA;
	},

    /* --------------------------- Core Wrappers --------------------------- */
    session: async function (instance_id, reset = false) {
	    try {
	        // Internal note
	        if (sessions[instance_id] && !reset) {
	            const wa = sessions[instance_id];
	            if (wa.status && wa.status !== "closed") {
	                logger.info("Session already active or connecting", instance_id);
	                return wa;
	            }
	        }

	        // Internal note
	        if (sessions[instance_id]?._initializing) {
	            logger.info("Session creation already in progress, waiting...", instance_id);
	            for (let i = 0; i < 10; i++) {
	                await Common.sleep(500);
	                if (sessions[instance_id] && !sessions[instance_id]._initializing) {
	                    return sessions[instance_id];
	                }
	            }
	            logger.warn("Timeout waiting existing session init", instance_id);
	        }

	        // Internal note
	        sessions[instance_id] = { _initializing: true };
	        logger.evt("Creating new session", instance_id);

	        const wa = await WAZIPER.makeWASocket(instance_id);
	        wa._initializing = false;
	        sessions[instance_id] = wa;

	        return wa;
	    } catch (err) {
	        logger.err(`session() failed: ${err.message}`, instance_id);
	        delete sessions[instance_id];
	        return null;
	    }
	},

    instance: async function (access_token, instance_id, login, res, callback, dbName, teamId) {
	        if (instance_id && dbName) Common.registerInstanceDb(instance_id, dbName);
	        let targetDb = dbName || Common.getDbForInstance(instance_id);
	    try {
	        const time_now = Math.floor(Date.now() / 1000);

	        if (!instance_id) {
	            if (res)
	                return res.json({
	                    status: "error",
	                    message: "The Instance ID must be provided to continue.",
	                });
	            return callback?.(false);
	        }

	        let team = null;

	        // 1. Try finding team by id_secure in targetDb
	        if (access_token && String(access_token).trim() !== "") {
	            team = await Common.db_get("teams", [{ id_secure: String(access_token).trim() }], targetDb);
	            if (!team) {
	                // Search across all discovered databases
	                for (const altDb of Common.getAllDatabases()) {
	                    if (altDb === targetDb) continue;
	                    const altTeam = await Common.db_get("teams", [{ id_secure: String(access_token).trim() }], altDb);
	                    if (altTeam) {
	                        team = altTeam;
	                        targetDb = altDb;
	                        Common.registerInstanceDb(instance_id, altDb);
	                        logger.info(`Found team in altDb: ${altDb}`, instance_id);
	                        break;
	                    }
	                }
	            }
	        }

	        // 2. Fallback: Try finding team by numeric team ID
	        if (!team) {
	            const tid = teamId ? parseInt(teamId) : (!isNaN(access_token) && access_token !== "" ? parseInt(access_token) : null);
	            if (tid) {
	                team = await Common.db_get("teams", [{ id: tid }], targetDb);
	                if (!team) {
	                    for (const altDb of Common.getAllDatabases()) {
	                        if (altDb === targetDb) continue;
	                        const altTeam = await Common.db_get("teams", [{ id: tid }], altDb);
	                        if (altTeam) {
	                            team = altTeam;
	                            targetDb = altDb;
	                            Common.registerInstanceDb(instance_id, altDb);
	                            logger.info(`Found team by numeric ID (${tid}) in altDb: ${altDb}`, instance_id);
	                            break;
	                        }
	                    }
	                }
	            }
	        }

	        // 3. Fallback: Check existing session in whatsapp_sessions to find team
	        if (!team && instance_id) {
	            let sess = await Common.db_get("whatsapp_sessions", [{ instance_id }], targetDb);
	            if (!sess) {
	                for (const altDb of Common.getAllDatabases()) {
	                    sess = await Common.db_get("whatsapp_sessions", [{ instance_id }], altDb);
	                    if (sess) {
	                        targetDb = altDb;
	                        Common.registerInstanceDb(instance_id, altDb);
	                        break;
	                    }
	                }
	            }
	            if (sess && sess.team_id) {
	                team = await Common.db_get("teams", [{ id: sess.team_id }], targetDb);
	            }
	        }

	        // 4. Last resort: Get first active team in database
	        if (!team) {
	            team = await Common.db_get("teams", [], targetDb);
	        }

	        if (!team) {
	            logger.err(`Authentication failed: no team found for token=${access_token}, instance=${instance_id}`, instance_id);
	            if (res)
	                return res.json({
	                    status: "error",
	                    message: "Authentication failed. Invalid access token.",
	                });
	            return callback?.(false);
	        }

	        const actualDb = Common.getDbForInstance(instance_id) || targetDb;
	        let session = await Common.db_get("whatsapp_sessions", [
	            { instance_id },
	            { team_id: team.id },
	        ], actualDb);

	        if (!session) {
	            session = await Common.db_get("whatsapp_sessions", [{ instance_id }], actualDb);
	            if (session && session.team_id !== team.id) {
	                await Common.db_update("whatsapp_sessions", [{ team_id: team.id }, { instance_id }], actualDb);
	            }
	        }

	        if (!session) {
	            try {
	                const randSec = Date.now().toString(36) + Math.random().toString(36).substring(2, 10);
	                await Common.db_insert("whatsapp_sessions", {
	                    id_secure: randSec,
	                    team_id: team.id,
	                    instance_id: instance_id,
	                    data: null,
	                    status: 0,
	                }, actualDb);
	                session = await Common.db_get("whatsapp_sessions", [{ instance_id }], actualDb);
	                logger.info(`Auto-created session for instance ${instance_id} in ${actualDb}`, instance_id);
	            } catch (err) {
	                logger.warn(`Could not auto-insert session: ${err.message}`, instance_id);
	            }
	        }

	        // Internal note
	        if (login === true) {
	            const existing = sessions[instance_id];
	            if (!existing || existing.status !== "open") {
	                const SESSION_PATH = path.join(session_dir, instance_id);
	                if (fs.existsSync(SESSION_PATH)) {
	                    try {
	                        await rimraf(SESSION_PATH, { maxRetries: 3, retryDelay: 400 });
	                        logger.warn(`Old unlinked session folder removed`, instance_id);
	                    } catch (rErr) {}
	                }
	                delete sessions[instance_id];
	                delete chatbots[instance_id];
	                delete bulks[instance_id];
	                logger.warn(`Force fresh login requested`, instance_id);
	            }
	        }

	        // Internal note
	        const shouldReset = login === true && (!sessions[instance_id] || sessions[instance_id].status !== "open");
	        const client = await WAZIPER.session(instance_id, shouldReset);
	        sessions[instance_id] = client;

	        logger.ok("Instance initialized successfully", instance_id);
	        return callback?.(client);
	    } catch (err) {
	        logger.err(`instance() failed: ${err.message}`);
	        if (res)
	            return res.json({
	                status: "error",
	                message: `Instance creation failed: ${err.message}`,
	            });
	        return callback?.(false);
	    }
	},

    webhook: async function (instance_id, data) {
	    try {
	        // Internal note
	        const tb_webhook = await Common.db_query("SHOW TABLES LIKE 'whatsapp_webhook'");
	        if (!tb_webhook) return;

	        // Internal note
	        const webhook = await Common.db_get("whatsapp_webhook", [
	            { instance_id: instance_id },
	            { status: 1 },
	        ]);

	        if (!webhook || !webhook.webhook_url) {
	            logger.info("No active webhook found", instance_id);
	            return;
	        }

	        // Internal note
	        const payload = {
	            instance_id,
	            event: data?.event || "unknown",
	            data: data?.data || data,
	            timestamp: Date.now(),
	        };

	        // Internal note
	        const webhookConfig = { timeout: 5000 };
        if (String(webhook.webhook_url || "").startsWith("https://")) {
            webhookConfig.httpsAgent = new https.Agent({ rejectUnauthorized: false });
        }

        console.log(webhook.webhook_url);

        await axios.post(webhook.webhook_url, payload, webhookConfig)
	            .then(() => {
	                logger.ok(`Webhook sent successfully ${webhook.webhook_url}`, instance_id);
	            })
	            .catch((err) => {
	                logger.warn(`Webhook delivery failed (${err.code || err.message})`,
	                    instance_id
	                );
	            });
	    } catch (err) {
	        logger.err(`webhook() failed: ${err.message}`, instance_id);
	    }
	},

    get_qrcode: async function (instance_id, res) {
	  try {
	    const client = sessions[instance_id];
	    const sessionPath = path.join(session_dir, instance_id);

	    if (!client) {
	      logger.warn("No client in memory creating session", instance_id);
	      // Internal note
	      // await WAZIPER.session(instance_id, false);
	      return res.json({ status: "error", message: "Session not found. Try again in a moment." });
	    }

	    // Internal note
	    if (client.status === "open") {
	      logger.ok("Instance already logged in", instance_id);
	      return res.json({ status: "error", message: "It seems that you have already logged in successfully." });
	    }

	    // Internal note
	    if (client.status === "qr" && client.qrcode) {
	      const qrBuffer = qrimg.imageSync(client.qrcode, { type: "png" });
	      const qrBase64 = "data:image/png;base64," + qrBuffer.toString("base64");
	      logger.info("QR served", instance_id);
	      return res.json({
	        status: "success",
	        message: "QR code generated successfully.",
	        instance_id,
	        base64: qrBase64,
	        expires_in: 300,
	      });
	    }

	    // Internal note
	    let attempts = 0;
	    while (attempts < 12) {
	      await Common.sleep(1000);
	      if (client.status === "open") {
	        return res.json({ status: "error", message: "It seems that you have already logged in successfully." });
	      }
	      if (client.status === "qr" && client.qrcode) {
	        const qrBuffer = qrimg.imageSync(client.qrcode, { type: "png" });
	        const qrBase64 = "data:image/png;base64," + qrBuffer.toString("base64");
	        logger.info("QR served after wait", instance_id);
	        return res.json({
	          status: "success",
	          message: "QR code generated successfully.",
	          instance_id,
	          base64: qrBase64,
	          expires_in: 300,
	        });
	      }
	      attempts++;
	    }

	    // Wait for the socket to become ready before continuing
	    const hasAuthFiles = fs.existsSync(sessionPath) && fs.readdirSync(sessionPath).length > 0;
	    if (hasAuthFiles && client.status !== "open" && !client.qrcode) {
	      logger.warn("Stale auth without QR force relogin", instance_id);
	      await WAZIPER.relogin(instance_id);
	      return res.json({
	        status: "error",
	        message: "Refreshing session. Please request QR again in a few seconds.",
	      });
	    }

	    logger.warn("QR not ready yet", instance_id);
	    return res.json({
	      status: "error",
	      message: "The system is initializing a new QR code. Please retry in 2 3 seconds.",
	    });
	  } catch (err) {
	    logger.err(`get_qrcode() failed: ${err.message}`, instance_id);
	    return res.json({ status: "error", message: `Failed to generate QR code: ${err.message}` });
	  }
	},

    get_info: async function (instance_id, res) {
	    try {
	        const client = sessions[instance_id];

	        if (!client) {
	            logger.warn("No active session found for instance info lookup", instance_id);
	            return res.json({
	                status: "success",
	                message: "No active session",
	                data: {
	                    connected: false,
	                    state: "offline",
	                    ts: Date.now(),
	                },
	            });
	        }

	        if (new_sessions[instance_id] || client._initializing || client.status === "qr" || client.status === "booting" || !client.user) {
	            const pendingState = client.status || (client._initializing ? "initializing" : "pending");
	            logger.info(`Session info pending (${pendingState})`, instance_id);
	            return res.json({
	                status: "success",
	                message: "Session pending",
	                data: {
	                    connected: false,
	                    state: pendingState,
	                    ts: Date.now(),
	                },
	            });
	        }

	        const user = client.user;

	        // Internal note
	        if (!user.avatar || user.avatar === "default") {
	            try {
	                user.avatar = await WAZIPER.get_avatar(client);
	            } catch (err) {
	                logger.warn(`Failed to fetch avatar: ${err.message}`, instance_id);
	                user.avatar = Common.get_avatar("default");
	            }
	        }

	        // Internal note
	        const info = {
	            id: user.id || "unknown",
	            name: user.name || Common.get_phone(user.id),
	            platform: client.version ? `Baileys ${client.version.join(".")}` : "Baileys v7",
	            avatar: user.avatar,
	            pushname: user.pushname || user.name || "",
	            phone: Common.get_phone(user.id || ""),
	            connected: true,
	            state: "connected",
	            ts: Date.now(),
	        };

	        logger.ok(`Session info retrieved successfully`, instance_id);

	        return res.json({
	            status: "success",
	            message: "Success",
	            data: info,
	        });
	    } catch (err) {
	        logger.err(`get_info() failed: ${err.message}`);
	        return res.json({
	            status: "error",
	            message: `Failed to get info: ${err.message}`,
	            relogin: true,
	        });
	    }
	},

    get_avatar: async function (client) {
	    try {
	        if (!client || !client.user) {
	            logger.warn("get_avatar(): Invalid client or user");
	            return Common.get_avatar("default");
	        }

	        const jid = client.user.id || client.user.name;
	        if (!jid) {
	            logger.warn("get_avatar(): Missing JID for user");
	            return Common.get_avatar("default");
	        }

	        // Internal note
	        try {
	            const url = await client.profilePictureUrl(jid, "image");
	            if (url && typeof url === "string") {
	                logger.ok(`Avatar fetched from WhatsApp`, jid);
	                return url;
	            }
	        } catch (err) {
	            // Internal note
	            if (err?.output?.statusCode === 404) {
	                logger.info("No avatar found (404)", jid);
	            } else {
	                logger.warn(`Failed to fetch avatar: ${err.message}`, jid);
	            }
	        }

	        // Internal note
	        return Common.get_avatar(client.user.name || "unknown");
	    } catch (err) {
	        logger.err(`get_avatar() failed: ${err.message}`);
	        return Common.get_avatar("error");
	    }
	},

    relogin: async function (instance_id) {
	    try {
	        const client = sessions[instance_id];

        if (new_sessions[instance_id]) {
            logger.info("relogin skipped: QR session still pending", instance_id);
            return client || false;
        }

        if (client && (client._initializing || client.status === "qr" || client.status === "booting")) {
            logger.info("relogin skipped: session pending", instance_id);
            return client;
        }

	        if (client) {
	            logger.warn("Relogin initiated", instance_id);

	            try {
	                const ready = await WAZIPER.waitForOpenConnection(client.ws, instance_id);

	                if (ready === 1 && typeof client.end === "function") {
	                    await new Promise((resolve) => {
	                        try {
	                            client.end(undefined, () => {
	                                logger.info("Socket closed before relogin", instance_id);
	                                resolve();
	                            });
	                            setTimeout(resolve, 800);
	                        } catch {
	                            resolve();
	                        }
	                    });
	                }
	            } catch (err) {
	                logger.warn(`Socket close error during relogin: ${err.message}`, instance_id);
	            }

	            // Internal note
	            delete sessions[instance_id];
	            delete chatbots[instance_id];
	            delete bulks[instance_id];

	            // Internal note
	            const SESSION_PATH = path.join(session_dir, instance_id);
	            try {
	                if (fs.existsSync(SESSION_PATH)) {
	                    await rimraf(SESSION_PATH, { maxRetries: 5, retryDelay: 500 });
	                    logger.info(`Old session folder removed: ${SESSION_PATH}`, instance_id);
	                }
	            } catch (err) {
	                logger.warn(`Failed to remove old session folder: ${err.message}`, instance_id);
	            }
	        } else {
	            logger.info("No existing session found, creating new one", instance_id);
	        }

	        // Internal note
	        const newClient = await WAZIPER.session(instance_id, true);
	        if (newClient) {
	            logger.ok("Relogin successful", instance_id);
	        } else {
	            logger.err("Relogin failed could not create new session", instance_id);
	        }

	        return newClient;
	    } catch (err) {
	        logger.err(`relogin() failed: ${err.message}`, instance_id);
	        return false;
	    }
	},

    logout: async function (instance_id, res) {
	    try {
	        Common.db_delete("whatsapp_sessions", [{ instance_id }]);
	        Common.db_update("accounts", [{ status: 0 }, { token: instance_id }]);

	        const SESSION_PATH = path.join(session_dir, instance_id);

	        // Internal note
	        if (sessions[instance_id]) {
	            try {
	                const readyState = await WAZIPER.waitForOpenConnection(sessions[instance_id].ws);
	                if (readyState === 1) {
	                    await new Promise((resolve) => {
	                        try {
	                            sessions[instance_id].end();
	                            setTimeout(resolve, 300);
	                        } catch {
	                            resolve();
	                        }
	                    });
	                }
	            } catch {}

	            delete sessions[instance_id];
	        }

	        // Internal note
	        delete chatbots[instance_id];
	        delete bulks[instance_id];
	        delete new_sessions[instance_id];

	        if (fs.existsSync(SESSION_PATH)) {
	            await new Promise((r) => setTimeout(r, 500));
	            await rimraf(SESSION_PATH, { maxRetries: 5, retryDelay: 300 });
	            logger.warn(`Session folder deleted: ${SESSION_PATH}`, instance_id);
	        }

	        logger.ok("Logged out & session cleared", instance_id);
	        if (res)
	            return res.json({ status: "success", message: "Logged out successfully." });
	    } catch (err) {
	        logger.err(`logout() failed: ${err.message}`, instance_id);
	        if (res)
	            return res.json({
	                status: "error",
	                message: `Logout failed: ${err.message}`,
	            });
	    }
	},

    waitForOpenConnection: async function (socket, instance_id) {
	    return new Promise((resolve) => {
	        if (!socket) {
	            logger.warn("No socket object provided", instance_id);
	            return resolve(0);
	        }

	        // Internal note
	        const intervalTime = 200;
	        let attempt = 0;

	        const interval = setInterval(() => {
	            attempt++;

	            try {
	                // Let Baileys release file handles before removing the session folder
	                const state =
	                    socket.readyState !== undefined
	                        ? socket.readyState
	                        : socket.ws?.readyState;

	                const OPEN =
	                    socket.OPEN !== undefined
	                        ? socket.OPEN
	                        : socket.ws?.OPEN ?? 1;

	                if (state === OPEN) {
	                    clearInterval(interval);
	                    logger.ok(`Socket is open after ${attempt} attempt(s)`, instance_id);
	                    return resolve(1);
	                }

	                if (attempt >= maxAttempts) {
	                    clearInterval(interval);
	                    logger.warn("Timeout waiting for open connection", instance_id);
	                    return resolve(0);
	                }
	            } catch (err) {
	                clearInterval(interval);
	                logger.err(`waitForOpenConnection() error: ${err.message}`, instance_id);
	                return resolve(0);
	            }
	        }, intervalTime);
	    });
	},

    get_groups: async function (instance_id, res) {
	    try {
	        const client = sessions[instance_id];

	        if (!client) {
	            logger.err(`Session not found for instance ${instance_id}`, instance_id);
	            return res.json({
	                status: "error",
	                message: "Session not found or disconnected.",
	                data: [],
	            });
	        }

	        if (!client.groups) {
	            client.groups = [];
	        }

	        if (client.groups.length === 0) {
	            try {
	                const groupMetadataList = await client.groupFetchAllParticipating();

	                debugDump("groupFetchAllParticipating() raw", groupMetadataList, instance_id, 3);

	                client.groups = Object.values(groupMetadataList).map((group) => ({
	                    id: group.id,
	                    name: (group.subject || "").toString(),
	                    size: group.size || (Array.isArray(group.participants) ? group.participants.length : 0),
	                    desc: group.desc?.toString() || "",
	                    participants: Array.isArray(group.participants)
	                        ? group.participants
	                            .map((participant) => {
	                                const jid = (participant.jid || "").toString();
	                                const lid = (participant.lid || participant.id || "").toString();

	                                return {
	                                    id: jid || lid,
	                                    jid,
	                                    lid,
	                                    admin: participant.admin || null,
	                                };
	                            })
	                            .filter((participant) => participant.id)
	                        : [],
	                }));

	                logger.ok(`Fetched ${client.groups.length} groups from WhatsApp`, instance_id);
	            } catch (err) {
	                logger.warn(`Could not fetch groups: ${err.message}`, instance_id);
	            }
	        }

	        debugDump("normalized groups with participants", client.groups, instance_id, 5);

	        const groups = [...client.groups]
	            .map((group) => ({
	                ...group,
	                name: (group.name || "").toString(),
	                participants: Array.isArray(group.participants) ? group.participants : [],
	                size: group.size || (Array.isArray(group.participants) ? group.participants.length : 0),
	            }))
	            .sort((a, b) => a.name.localeCompare(b.name, "en", { sensitivity: "base" }));

	        return res.json({
	            status: "success",
	            message: "Group list retrieved successfully",
	            total: groups.length,
	            data: groups,
	        });
	    } catch (err) {
	        logger.err(`get_groups() failed: ${err.message}`, instance_id);
	        return res.json({
	            status: "error",
	            message: `Failed to retrieve groups: ${err.message}`,
	            data: [],
	        });
	    }
	},

    bulk_messaging: async function () {
        const dbs = Common.getAllDatabases();
        for (const targetDb of dbs) {
            try {
                await Common.withDatabase(targetDb, async () => {
                    await WAZIPER.bulk_messaging_for_db(targetDb);
                });
            } catch (err) {
                logger.err(`bulk_messaging error for ${targetDb}: ${err.message}`);
            }
        }
    },

    bulk_messaging_for_db: async function (targetDb) {
        const d = new Date();
        const time_now = Math.floor(d.getTime() / 1000);
    
        const items = await Common.db_query(`
            SELECT * FROM whatsapp_schedules
            WHERE status = 1
              AND run <= ${time_now}
              AND accounts != ''
              AND time_post <= ${time_now}
            ORDER BY time_post ASC
            LIMIT 5
        `, false);
    
        if (!items) return;

        /* ================= LOCK ALL ================= */
        for (const item of items) {
            await Common.db_update(
                "whatsapp_schedules",
                [{ run: time_now + 30 }, { id: item.id }]
            );
        }
    
        /* ================= PROCESS ================= */
        for (const item of items) {
    
            /* ---------- TIMEZONE ---------- */
            let current_hour = -1;
            let user_diff = 0;
    
            if (item.timezone) {
                user_diff = Common.getTZDiff(item.timezone);
                current_hour = d.getHours() + (user_diff * -1);
                if (current_hour > 23) current_hour -= 23;
            }
    
            /* ---------- SCHEDULE TIME ---------- */
            if (item.schedule_time && current_hour !== -1) {
                const schedule_time = JSON.parse(item.schedule_time);
    
                if (!schedule_time.includes(current_hour.toString())) {
                    let next_time = -1;
                    let date = new Date(
                        (time_now + ((user_diff * -1) * 3600)) * 1000
                    );
    
                    for (let i = 1; i <= 24; i++) {
                        date = Common.roundMinutes(date);
                        const hour = date.getHours();
    
                        if (schedule_time.includes(hour.toString())) {
                            const minutes = d.getMinutes();
                            const max_rand = minutes > 10 ? 10 : minutes;
                            const rand = Common.randomIntFromInterval(0, max_rand);
    
                            next_time = time_now + (i * 3600) - ((minutes - rand) * 60);
                            break;
                        }
                    }
    
                    if (next_time === -1) {
                        await Common.db_update(
                            "whatsapp_schedules",
                            [{ status: 2 }, { id: item.id }]
                        );
                    } else {
                        await Common.db_update(
                            "whatsapp_schedules",
                            [{ time_post: next_time }, { id: item.id }]
                        );
                    }
                    continue;
                }
            }
    
            /* ---------- USED PHONES ---------- */
            let used_numbers = [];
            if (item.result) {
                try {
                    const result = JSON.parse(item.result);
                    used_numbers = result.map(r => r.phone_number.toString());
                } catch {}
            }
    
            /* ---------- GET PHONE ---------- */
            const phone_item = await Common.get_phone_number(
                item.contact_id,
                used_numbers
            );
    
            if (!phone_item) {
                await Common.db_update(
                    "whatsapp_schedules",
                    [{ status: 2, run: 0 }, { id: item.id }]
                );
                continue;
            }
    
            /* ---------- ACCOUNT LOOP (LEGACY STYLE) ---------- */
            const accounts = JSON.parse(item.accounts);
            let next_account = item.next_account || 0;
            if (next_account >= accounts.length) next_account = 0;
    
            let instance_id = false;
            const phone = phone_item.phone;
            const params = phone_item.params;
    
            for (const [index, account] of accounts.entries()) {
    
                if (instance_id || index !== next_account) continue;
    
                const account_item = await Common.db_get(
                    "accounts",
                    [{ id: account }, { status: 1 }]
                );
    
                if (!account_item || account_item.team_id !== phone_item.team_id) {
                    await Common.db_update(
                        "whatsapp_schedules",
                        [{ next_account: next_account + 1, run: 0, status: 0 }, { id: item.id }]
                    );
                    return;
                }
    
                instance_id = account_item.token;
    
                /* ---------- SESSION CHECK ---------- */
                const waSession = await Common.db_get("whatsapp_sessions", [{ instance_id }]);
                const liveClient = sessions[instance_id];
                const isLiveOpen = !!(liveClient && liveClient.status === "open" && liveClient.user);
                if (isLiveOpen) {
                    if (waSession && waSession.status != 1) {
                        await Common.db_update("whatsapp_sessions", [{ status: 1 }, { instance_id }]);
                        waSession.status = 1;
                    } else if (!waSession) {
                        await Common.db_insert("whatsapp_sessions", { id_secure: Common.makeid(10), team_id: phone_item.team_id, instance_id, data: JSON.stringify(liveClient.user), status: 1 });
                    }
                }
                const waSessionCheck = isLiveOpen ? { status: 1 } : waSession;
                if (!waSessionCheck || waSessionCheck.status != 1) {
                
                    // total phone numbers of this contact
                    const totalRes = await Common.db_query(
                        `SELECT COUNT(*) AS total 
                         FROM whatsapp_phone_numbers 
                         WHERE pid = ${item.contact_id}`,
                        true
                    );
                
                    const totalPhones = totalRes ? totalRes.total : 0;
                
                    const sent = item.sent || 0;
                    const failed = item.failed || 0;
                    const processed = sent + failed;
                
                    const remaining = Math.max(totalPhones - processed, 0);
                
                    await Common.db_update(
                        "whatsapp_schedules",
                        [{
                            failed: failed + remaining,
                            status: 0,
                            run: 0
                        }, {
                            id: item.id
                        }]
                    );
                
                    return;
                }
                
                /* live_back not initialized yet*/
                if (!sessions[instance_id]) {
                    await Common.db_update(
                        "whatsapp_schedules",
                        [{ run: 0 }, { id: item.id }]
                    );
                    return;
                }
                    
                const chat_id = phone.includes("g.us")
                    ? phone
                    : `${String(phone).replace(/\D/g, "")}@s.whatsapp.net`;

                /* ---------- SEND ---------- */
                await WAZIPER.auto_send(
                    instance_id,
                    chat_id,
                    phone,
                    "bulk",
                    item,
                    params,
                    async (result) => {
    
                        if (!result) return;
    
                        const status = result.status ? 1 : 0;
    
                        let result_list = [];
                        try {
                            result_list = item.result ? JSON.parse(item.result) : [];
                        } catch {}
    
                        result_list.push({
                            phone_number: phone,
                            status: status
                        });
    
                        if (!bulks[item.id]) {
                            bulks[item.id] = {
                                bulk_sent: item.sent,
                                bulk_failed: item.failed
                            };
                        }
    
                        bulks[item.id].bulk_sent += status ? 1 : 0;
                        bulks[item.id].bulk_failed += status ? 0 : 1;
    
                        const random_time =
                            Math.floor(Math.random() * item.max_delay) + item.min_delay;
    
                        let next_time = item.time_post + random_time;
                        if (next_time < time_now) next_time = time_now + random_time;
    
                        await Common.db_update(
                            "whatsapp_schedules",
                            [{
                                result: JSON.stringify(result_list),
                                sent: bulks[item.id].bulk_sent,
                                failed: bulks[item.id].bulk_failed,
                                time_post: next_time,
                                next_account: next_account + 1,
                                run: 0
                            }, { id: item.id }]
                        );
                    }
                );
            }
        }
    },

   	autoresponder: async function (instance_id, user_type, message) {
	    try {
	        const chat_id = message.key?.remoteJid;
	        if (!chat_id) return false;

	        const now = Math.floor(Date.now() / 1000);
	        const session = sessions[instance_id];
	        if (!session) {
	            logger.err(`No active session`, instance_id);
	            return false;
	        }

	        /* ================= LOAD RULE ================= */
	        const rule = await Common.db_get("whatsapp_autoresponder", [
	            { instance_id },
	            { status: 1 }
	        ]);
	        if (!rule) return false;

	        /* ================= TEAM ID  ================= */
	        if (!rule.team_id) {
	            const waSession = await Common.db_get("whatsapp_sessions", [
	                { instance_id }
	            ]);
	            rule.team_id = waSession?.team_id;
	        }

	        if (!rule.team_id) {
	            logger.warn("Autoresponder missing team_id", instance_id);
	            return false;
	        }

	        /* ================= SEND TO CHECK ================= */
	        if (
	            (rule.send_to === 2 && user_type === "group") ||
	            (rule.send_to === 3 && user_type === "user")
	        ) {
	            return false;
	        }

	        /* ================= DELAY / ANTI SPAM ================= */
	        session.lastMsg ??= {};
	        const last_time = session.lastMsg[chat_id] ?? 0;
	        const delay_seconds = (rule.delay || 0) * 60;

	        if (delay_seconds > 0 && now - last_time < delay_seconds) {
	            return false;
	        }
	        session.lastMsg[chat_id] = now;

	        /* ================= EXCEPT ================= */
	        const except = (rule.except || "")
	            .split(",")
	            .map(s => s.trim())
	            .filter(Boolean);

	        if (except.some(e => chat_id.includes(e))) return false;

	        /* ================= LOG INCOMING ================= */
	        let text = "";
	        if (message.message?.conversation)
	            text = message.message.conversation;
	        else if (message.message?.extendedTextMessage)
	            text = message.message.extendedTextMessage.text;
	        else if (message.message?.imageMessage?.caption)
	            text = message.message.imageMessage.caption;

	        logger.info(`Incoming: ${text}`, instance_id);

	        /* ================= SEND ================= */
	        await WAZIPER.auto_send(
	            instance_id,
	            chat_id,
	            chat_id,
	            "autoresponder",
	            rule,
	            false,
	            async (result) => {
	                if (result.status === 1) {
	                    logger.ok(`Autoresponder sent ${chat_id}`, instance_id);
	                } else {
	                    logger.err(`Autoresponder failed ${result.message}`, instance_id);
	                }
	            }
	        );

	        return true;

	    } catch (err) {
	        logger.err(`autoresponder() failed: ${err.message}`, instance_id);
	        return false;
	    }
	},

    chatbot: async function (instance_id, user_type, message) {
	    try {
	        const chat_id = message.key.remoteJid;

	        const bots = await Common.db_fetch("whatsapp_chatbot", [
	            { instance_id },
	            { status: 1 },
	            { run: 1 },
	        ]);

	        if (!bots || !bots.length) return false;

	        const client = sessions[instance_id];
	        if (!client) {
	            logger.err("No active session", instance_id);
	            return false;
	        }

	        let team_id = null;
	        const waSession = await Common.db_get("whatsapp_sessions", [{ instance_id }]);
	        team_id = waSession?.team_id;

	        if (!team_id) {
	            logger.warn("Chatbot missing team_id", instance_id);
	            return false;
	        }

	        const content = extractMessageText(message);

	        if (!content) return false;

	        const msg = content.trim().toLowerCase();
	        let triggered = false;
	        const delayMs = 1500;

	        let target_chat_id = chat_id;
	        const senderPhone = message.key?.senderPn || message.key?.participantPn || Common.get_phone(chat_id) || "";
	        if (chat_id && chat_id.includes("@lid")) {
	            const cleanPn = String(senderPhone).replace(/\D/g, "");
	            if (cleanPn) {
	                target_chat_id = `${cleanPn}@s.whatsapp.net`;
	            }
	        }

	        for (const bot of bots) {
	            if (triggered) break;

	            bot.team_id = team_id;

	            if (
	                (bot.send_to === 2 && user_type === "group") ||
	                (bot.send_to === 3 && user_type === "user")
	            ) continue;

	            const keywords = (bot.keywords || "")
	                .split(",")
	                .map((k) => k.trim().toLowerCase())
	                .filter(Boolean);

	            if (!keywords.length) continue;

	            let matched = false;
	            if (bot.type_search === 1) {
	                matched = keywords.some((k) => msg.includes(k));
	            } else {
	                matched = keywords.some((k) => msg === k);
	            }

	            if (!matched) continue;

	            triggered = true;
	            logger.ok(`Chatbot triggered "${msg}" -> rule #${bot.id} (${bot.name}) sending to ${target_chat_id}`, instance_id);

	            await Common.sleep(delayMs);

	            await WAZIPER.auto_send(
	                instance_id,
	                target_chat_id,
	                senderPhone || target_chat_id,
	                "chatbot",
	                bot,
	                false,
	                async (result) => {
	                    if (result.status === 1) {
	                        logger.ok("Chatbot sent", instance_id);
	                    } else {
	                        logger.err(`Chatbot failed -> ${result.message}`, instance_id);
	                    }
	                }
	            );
	        }

	        if (!triggered) {
	            logger.info(`No chatbot matched " ${msg}"`, instance_id);
	        }

	        return triggered;
	    } catch (err) {
	        logger.err(`chatbot() failed: ${err.message}`, instance_id);
	        return false;
	    }
	},

    ai_smart_reply: async function (instance_id, user_type, message) {
	    try {
	        const chat_id = message.key.remoteJid;
	        const rule = await Common.db_get("whatsapp_ai_smart_reply", [
	            { instance_id },
	            { status: 1 },
	        ]);

	        if (!rule) return false;

	        let team_id = rule.team_id || null;
	        if (!team_id) {
	            const waSession = await Common.db_get("whatsapp_sessions", [{ instance_id }]);
	            team_id = waSession?.team_id;
	            rule.team_id = team_id;
	        }

	        if (!team_id) {
	            logger.warn("AI smart reply missing team_id", instance_id);
	            return false;
	        }

	        if (
	            (rule.send_to === 2 && user_type === "group") ||
	            (rule.send_to === 3 && user_type === "user")
	        ) {
	            return false;
	        }

	        const session = sessions[instance_id] ?? {};
	        session.lastAiSmartReply ??= {};
	        const now = Math.floor(Date.now() / 1000);
	        const last_time = session.lastAiSmartReply[chat_id] ?? 0;
	        const delay_seconds = (rule.delay || 0) * 60;

	        if (delay_seconds > 0 && now - last_time < delay_seconds) {
	            sessions[instance_id] = session;
	            return false;
	        }

	        const except = (rule.except || "")
	            .split(",")
	            .map((s) => s.trim())
	            .filter(Boolean);
	        if (except.some((e) => chat_id.includes(e))) return false;

	        let content = "";
	        if (message.message?.templateButtonReplyMessage) {
	            content = message.message.templateButtonReplyMessage.selectedDisplayText;
	        } else if (message.message?.listResponseMessage) {
	            const list = message.message.listResponseMessage;
	            content = (list.title || "") + " " + (list.description || "");
	        } else if (message.message?.extendedTextMessage) {
	            content = message.message.extendedTextMessage.text;
	        } else if (message.message?.imageMessage?.caption) {
	            content = message.message.imageMessage.caption;
	        } else if (message.message?.videoMessage?.caption) {
	            content = message.message.videoMessage.caption;
	        } else if (message.message?.conversation) {
	            content = message.message.conversation;
	        }

	        content = String(content || "").trim();
	        if (!content) return false;

	        const aiReply = await WAZIPER.request_ai_smart_reply(instance_id, rule, chat_id, content);
	        if (!aiReply) return false;

	        session.lastAiSmartReply[chat_id] = now;
	        sessions[instance_id] = session;

	        const sendItem = {
	            ...rule,
	            caption: aiReply,
	            media: null,
	        };

	        await WAZIPER.auto_send(
	            instance_id,
	            chat_id,
	            chat_id,
	            "ai_smart_reply",
	            sendItem,
	            false,
	            async (result) => {
	                if (result.status === 1) {
	                    logger.ok(`AI smart reply sent -> ${chat_id}`, instance_id);
	                } else {
	                    logger.err(`AI smart reply failed -> ${result.message}`, instance_id);
	                }
	            }
	        );

	        return true;
	    } catch (err) {
	        logger.err(`ai_smart_reply() failed: ${err.message}`, instance_id);
	        return false;
	    }
	},

    request_ai_smart_reply: async function (instance_id, item, chat_id, message_text) {
	    try {
	        if (!item?.team_id) return false;
	        const team = await Common.db_get("teams", [{ id: item.team_id }]);
	        if (!team?.id_secure) {
	            logger.warn("AI smart reply missing team access token", instance_id);
	            return false;
	        }

	        const appUrl = String(config.app_url || "").replace(/\/+$/, "");
	        const url = `${appUrl}/app/whatsapp/ai-smart-reply/generate`;
	        const response = await axios.get(url, {
	            params: {
	                access_token: team.id_secure,
	                instance_id,
	                chat_id,
	                message: message_text,
	            },
	            timeout: 20000,
	            httpsAgent: new https.Agent({ rejectUnauthorized: false }),
	        });

	        const data = response?.data || {};
	        if (data.status !== 1 || !data.data) {
	            logger.warn(`AI smart reply not generated -> ${data.message || "empty response"}`, instance_id);
	            return false;
	        }

	        return String(data.data || "").trim();
	    } catch (err) {
	        logger.warn(`request_ai_smart_reply() failed: ${err.message}`, instance_id);
	        return false;
	    }
	},

    send_message: async function (instance_id, access_token, req, res) {
	    try {
	        let {
	            chat_id,
	            number,
	            message,
	            caption,
	            media_url,
	            filename,
	            file_path
	        } = req.body;

	        /* ======================================================
	         * 1. BACKWARD COMPATIBLE (number -> chat_id)
	         * ====================================================== */
	        if (!chat_id && number) {
	            const clean = number.toString().replace(/\D/g, "");
	            chat_id = clean.includes("@")
	                ? clean
	                : `${clean}@s.whatsapp.net`;
	        }

	        if (!chat_id) {
	            return res.json({
	                status: "error",
	                message: "Missing chat_id or number"
	            });
	        }

	        /* ======================================================
	         * 2. message -> caption fallback
	         * ====================================================== */
	        if (!caption && message) {
	            caption = message;
	        }

	        /* ======================================================
	         * 3. AUTH
	         * ====================================================== */
	        const team = await Common.db_get("teams", [{ id_secure: access_token }]);
	        if (!team) {
	            return res.json({
	                status: "error",
	                message: "Authentication failed invalid access token",
	            });
	        }

	        /* ======================================================
	         * 4. BUILD ITEM
	         * ====================================================== */
	        const item = {
	            team_id: team.id,
	            type: 1,
	            caption: caption || "",
	            media: media_url || file_path || "",
	            file_path: file_path || "",
	            filename: filename || "",
	        };

	        /* ======================================================
	         * 5. CHECK SESSION
	         * ====================================================== */
	        const client = sessions[instance_id];
	        if (!client) {
	            return res.json({
	                status: "error",
	                message: "Session not found or disconnected"
	            });
	        }

	        /* ======================================================
	         * 6. SEND
	         * ====================================================== */
	        await WAZIPER.auto_send(
	            instance_id,
	            chat_id,
	            chat_id,
	            "api",
	            item,
	            false,
	            (result) => {
	                if (result?.status === 1) {
	                    return res.json({
	                        status: "success",
	                        message: "Message sent successfully",
	                        chat_id,
	                        data: result.message
	                    });
	                }

	                return res.json({
	                    status: "error",
	                    message: result?.message || "Send failed"
	                });
	            }
	        );

	    } catch (err) {
	        logger.err(`send_message() error: ${err.message}`, instance_id);
	        /*return res.json({
	            status: "error",
	            message: err.message
	        });*/
	        return res.json({
	            status: "error",
	            message: "Authentication failed invalid access token",
	        });
	    }
	},

    auto_send: async function (instance_id, chat_id, phone_number, type, item, params, callback) {
        try {
            const client = sessions[instance_id];
            if (!client) {
                return callback({
                    status: 0,
                    type: type,
                    message: "Session not found"
                });
            }

            /* ================= NORMALIZE LID RECIPIENTS ================= */
            if (chat_id && chat_id.includes("@lid")) {
                const cleanPhone = String(phone_number || "").replace(/\D/g, "");
                if (cleanPhone) {
                    chat_id = `${cleanPhone}@s.whatsapp.net`;
                }
            }

            /* ================= CONTACT & E2EE SESSION PRIMING ================= */
            if (chat_id && chat_id.endsWith("@s.whatsapp.net")) {
                try {
                    const [contact] = await client.onWhatsApp(chat_id);
                    if (contact && contact.exists && contact.jid) {
                        chat_id = contact.jid;
                    }
                } catch (e) {}

                try {
                    await client.presenceSubscribe(chat_id);
                    await client.sendPresenceUpdate("composing", chat_id);
                    await new Promise((r) => setTimeout(r, 200));
                    await client.sendPresenceUpdate("paused", chat_id);
                } catch (e) {}
            }

            /* ================= TEST MODE ================= */
            if (type === "test") {
                try {
                    let text = item.caption || "TEST MESSAGE";
    
                    // params + spintax
                    text = Common.params(params, text);
                    text = spintax.unspin(text);
    
                    const sent = await client.sendMessage(chat_id, { text });
                    if (sent?.key?.id && sent?.message) {
                        saveCachedMessage(sent.key.id, sent.message);
                    }
    
                    return callback({
                        status: 1,
                        type: type,
                        phone_number,
                        message: sent
                    });
                } catch (err) {
                    let friendlyMsg = err.message || "Failed to transmit message";
            if (friendlyMsg.includes("Connection Closed") || friendlyMsg.includes("connection closed") || friendlyMsg.includes("Connection lost") || friendlyMsg.includes("QR refs attempts ended")) {
                friendlyMsg = "WhatsApp connection was closed or disconnected from your phone. Please verify your phone has active internet or re-link your channel in the Channels menu.";
            }
            return callback({
                status: 0,
                type: type,
                message: friendlyMsg
            });
                }
            }

            /* ================= LIMIT CHECK ================= */
            const allow = await WAZIPER.limit(item, type);
            if (!allow) {
                return callback({
                    status: 0,
                    type: type,
                    message: "WhatsApp monthly limit reached"
                });
            }

    
            /* ================= BUILD PAYLOAD ================= */
            let payload = null;
    
            // ---------- BASE CAPTION ----------
            let caption = item.caption || "";
            caption = Common.params(params, caption);
            caption = spintax.unspin(caption);
    
            // ---------- MEDIA / TEXT ----------

            if (item.media && item.media.trim() !== "") {
                let mediaSource = item.media.trim();
                let localFile = null;

                // 1. Direct local file check
                if (item.file_path && fs.existsSync(item.file_path)) {
                    localFile = item.file_path;
                    mediaSource = item.file_path;
                    logger.info(`Using direct local file_path: ${mediaSource}`, instance_id);
                } else if (fs.existsSync(mediaSource)) {
                    localFile = mediaSource;
                    logger.info(`Media source is already local disk file: ${mediaSource}`, instance_id);
                } else {
                    // Try resolving URL to local disk file
                    try {
                        let relPath = null;
                        if (mediaSource.startsWith("http://") || mediaSource.startsWith("https://")) {
                            const parsed = new URL(mediaSource);
                            relPath = decodeURIComponent(parsed.pathname.replace(/^\/+/, ""));
                        } else if (mediaSource.startsWith("/")) {
                            relPath = mediaSource.replace(/^\/+/, "");
                        }

                        if (relPath) {
                            const candidates = [
                                path.resolve(__dirname, "../../Install/public", relPath),
                                path.resolve(__dirname, "../../public", relPath),
                                path.resolve("/home/nooryak/public_html/saasreselling.com/Install/public", relPath),
                                path.resolve("/home/nooryak/public_html/saasreselling.com/public", relPath),
                                path.resolve("D:/xamp/htdocs/launchshop_dev/whatsappautomationtool/Install/public", relPath),
                            ];
                            for (const c of candidates) {
                                if (fs.existsSync(c)) {
                                    localFile = c;
                                    mediaSource = c;
                                    logger.info(`Resolved media URL to local disk file: ${localFile}`, instance_id);
                                    break;
                                }
                            }
                        }
                    } catch (e) {}
                }

                const mime = item.mimetype || Common.ext2mime(mediaSource) || "video/mp4";
                const post_type = Common.post_type(mime, 1);
                const filename = item.filename || Common.get_file_name(mediaSource) || "media";

                // In Baileys, local files should be passed as Buffer to prevent invalid URL errors
                const mediaData = (localFile && fs.existsSync(localFile))
                    ? fs.readFileSync(localFile)
                    : { url: mediaSource };

                switch (post_type) {
                    case "videoMessage":
                        payload = {
                            video: mediaData,
                            mimetype: (typeof mime === "string" && mime.startsWith("video/")) ? mime : "video/mp4",
                            fileName: filename,
                            caption
                        };
                        break;

                    case "imageMessage":
                        payload = {
                            image: mediaData,
                            mimetype: (typeof mime === "string" && mime.startsWith("image/")) ? mime : "image/jpeg",
                            caption
                        };
                        break;

                    case "audioMessage":
                        payload = {
                            audio: mediaData,
                            mimetype: (typeof mime === "string" && mime.startsWith("audio/")) ? mime : "audio/mpeg",
                            ptt: false
                        };
                        break;

                    default:
                        payload = {
                            document: mediaData,
                            mimetype: mime,
                            fileName: filename,
                            caption
                        };
                        break;
                }
            } else {
                payload = { text: caption };
            }

            /* ================= SEND MESSAGE WITH BULLETPROOF FALLBACK ================= */
            let sent;
            try {
                sent = await client.sendMessage(chat_id, payload);
            } catch (sendErr) {
                // If sending as videoMessage failed (e.g. ffmpeg encoding failure in Baileys), fallback to documentMessage
                if (payload && payload.video) {
                    logger.warn(`videoMessage send failed (${sendErr.message}), falling back to documentMessage for delivery`, instance_id);
                    const fallbackDocPayload = {
                        document: payload.video,
                        mimetype: payload.mimetype || "video/mp4",
                        fileName: payload.fileName || "video.mp4",
                        caption: payload.caption || ""
                    };
                    sent = await client.sendMessage(chat_id, fallbackDocPayload);
                } else {
                    throw sendErr;
                }
            }
            if (sent?.key?.id && sent?.message) {
                saveCachedMessage(sent.key.id, sent.message);
            }
    
            /* ================= STATS (SUCCESS) ================= */
            await WAZIPER.stats(instance_id, type, item, true);
    
            return callback({
                status: 1,
                type: type,
                phone_number,
                message: sent
            });
    
        } catch (err) {
            logger.err(`auto_send failed: ${err.message}`, instance_id);
    
            /* ================= STATS (FAILED) ================= */
            try {
                if (type !== "test") {
                    await WAZIPER.stats(instance_id, type, item, false);
                }
            } catch {}
    
            return callback({
                status: 0,
                type: type,
                message: err.message
            });
        }
    },

    limit: async function (item, type) {
	    try {
	        const team_id = item.team_id;
	        const time_now = Math.floor(Date.now() / 1000);

	        // Internal note
	        const team = await Common.db_get('teams', [{ id: team_id }]);
	        if (!team) return false;

	        const user = await Common.db_get('users', [{ id: team.owner }]);
	        if (!user) return false;

	        const expirationDate = parseInt(user.expiration_date ?? 0, 10);

	        // 0 or -1 means lifetime / unlimited subscription.
	        if (!Number.isNaN(expirationDate) && expirationDate > 0 && expirationDate < time_now) {
	            logger.warn(`Team ${team_id} subscription expired`);
	            return false;
	        }

	        // Initialize monthly WhatsApp stats cache if missing.
	        if (!stats_history[team_id]) {
	            let current_stats = await Common.db_get('whatsapp_stats', [{ team_id }]);

	            if (!current_stats) {
	                const seed_stats = {
	                    id_secure: Common.makeid(10),
	                    team_id: team_id,
	                    wa_total_sent_by_month: 0,
	                    wa_total_sent: 0,
	                    wa_chatbot_count: 0,
	                    wa_autoresponder_count: 0,
	                    wa_api_count: 0,
	                    wa_bulk_total_count: 0,
	                    wa_bulk_sent_count: 0,
	                    wa_bulk_failed_count: 0,
	                    wa_time_reset: 0,
	                    next_update: 0,
	                };

	                await Common.db_insert('whatsapp_stats', seed_stats);
	                current_stats = seed_stats;
	                logger.info(`Created whatsapp_stats row for team ${team_id}`);
	            }

	            stats_history[team_id] = current_stats;
	        }

	        const stats = stats_history[team_id];

	        // 30-day reset window
	        if (!stats.wa_time_reset || stats.wa_time_reset < time_now) {
	            stats.wa_total_sent_by_month = 0;
	            stats.wa_time_reset = time_now + 30 * 24 * 60 * 60; // 30 days
	        }

	        // Internal note
	        if (!stats.next_sync || stats.next_sync < time_now) {
	            const db_stats = await Common.db_get('whatsapp_stats', [{ team_id }]);
	            if (db_stats) {
	                stats.wa_total_sent_by_month = db_stats.wa_total_sent_by_month;
	                stats.wa_time_reset = db_stats.wa_time_reset || stats.wa_time_reset;
	            }
	            stats.next_sync = time_now + 30;
	        }

	        // Internal note
	        if (!limit_messages[team_id]) {
	            limit_messages[team_id] = { next_update: 0, whatsapp_message_per_month: 0 };
	        }

	        // Load the monthly WhatsApp quota for this team.
	        if (limit_messages[team_id].next_update < time_now) {
	            try {
	                const permissions = JSON.parse(team.permissions || '{}');
	                const rawLimit = parseInt(permissions.whatsapp_message_per_month ?? '0', 10);
	                limit_messages[team_id].whatsapp_message_per_month = Number.isNaN(rawLimit) ? 0 : rawLimit;
	                limit_messages[team_id].next_update = time_now + 30;
	            } catch {
	                limit_messages[team_id].whatsapp_message_per_month = 0;
	            }
	        }

	        const max_messages = limit_messages[team_id].whatsapp_message_per_month;
	        const sent_month = stats.wa_total_sent_by_month;

	        // `-1` means unlimited monthly WhatsApp messages.
	        if (max_messages === -1) {
	            return true;
	        }

	        


	        // Stop the automation once the monthly quota is exhausted.
	        if (max_messages > 0 && sent_month >= max_messages) {
	            logger.warn(`Monthly WhatsApp limit reached (${sent_month}/ ${max_messages})`, `team:${team_id}`);

	            if (type === 'bulk') {
	                await Common.db_update('whatsapp_schedules', [{ run: 0, status: 0 }, { id: item.id }]);
	            }

	            return false;
	        }

	        return true;
	    } catch (err) {
	        logger.err(`Limit check failed: ${err.message}`);
	        return false;
	    }
	},

    stats: async function (instance_id, type, item, status) {
	    try {
	        /* ============================================================
	         * 1. RESOLVE TEAM_ID (AUTO)
	         * ============================================================ */
	        let team_id = item?.team_id;

	        if (!team_id) {
	            const waSession = await Common.db_get("whatsapp_sessions", [
	                { instance_id }
	            ]);
	            team_id = waSession?.team_id;
	        }

	        if (!team_id) {
	            logger.err(`stats(): missing team_id | type= ${type}`, instance_id);
	            return;
	        }

	        item.team_id = team_id;

	        const time_now = Math.floor(Date.now() / 1000);
	        const sent = status ? 1 : 0;
	        const failed = status ? 0 : 1;

	        logger.info(`STATS HIT type=${type}, team=${team_id}, sent=${sent}`,
	            instance_id
	        );

	        /* ============================================================
	         * 2. LOAD / INIT CACHE
	         * ============================================================ */
	        if (!stats_history[team_id]) {
	            stats_history[team_id] =
	                (await Common.db_get("whatsapp_stats", [{ team_id }])) || {
	                    wa_total_sent: 0,
	                    wa_total_sent_by_month: 0,
	                    wa_chatbot_count: 0,
	                    wa_autoresponder_count: 0,
	                    wa_bulk_total_count: 0,
	                    wa_bulk_sent_count: 0,
	                    wa_bulk_failed_count: 0,
	                    wa_api_count: 0,
	                    wa_time_reset: 0,
	                    next_update: 0,
	                };

	            logger.info(`STATS INIT team= ${team_id}`, instance_id);
	        }

	        const stats = stats_history[team_id];

	        /* ============================================================
	         * 3. RESET THEO THÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂNG
	         * ============================================================ */
	        if (!stats.wa_time_reset || stats.wa_time_reset < time_now) {
	            stats.wa_total_sent_by_month = 0;
	            // 30-day reset window
	        }

	        /* ============================================================
	         * 4. GLOBAL COUNTERS
	         * ============================================================ */
	        stats.wa_total_sent += sent;
	        stats.wa_total_sent_by_month += sent;

	        /* ============================================================
	         * 5. TYPE-SPECIFIC
	         * ============================================================ */
	        switch (type) {

	            /* ---------------- CHATBOT ---------------- */
	            case "chatbot": {
	                if (!item.id) {
	                    logger.err("chatbot missing item.id", instance_id);
	                    return;
	                }

	                stats.wa_chatbot_count += sent;

	                const cb = (chatbots[item.id] ??= {
	                    sent: item.sent || 0,
	                    failed: item.failed || 0,
	                });

	                cb.sent += sent;
	                cb.failed += failed;

	                // update rule
	                await Common.db_update("whatsapp_chatbot", [
	                    { sent: cb.sent, failed: cb.failed },
	                    { id: item.id },
	                ]);

	                // save stats realtime
	                await Common.db_update("whatsapp_stats", [
	                    {
	                        wa_chatbot_count: stats.wa_chatbot_count,
	                        wa_total_sent: stats.wa_total_sent,
	                        wa_total_sent_by_month: stats.wa_total_sent_by_month,
	                        wa_time_reset: stats.wa_time_reset,
	                    },
	                    { team_id },
	                ]);

	                logger.info(`CHATBOT SAVE total=${stats.wa_chatbot_count}`,
	                    instance_id
	                );
	                break;
	            }

	            /* ---------------- AUTORESPONDER ---------------- */
	            case "autoresponder": {
	                if (!item.id) {
	                    logger.err("autoresponder missing item.id", instance_id);
	                    return;
	                }

	                stats.wa_autoresponder_count += sent;

	                const session = sessions[instance_id] ?? {};
	                session.autoresponder_sent =
	                    (session.autoresponder_sent ?? item.sent ?? 0) + sent;
	                session.autoresponder_failed =
	                    (session.autoresponder_failed ?? item.failed ?? 0) + failed;
	                sessions[instance_id] = session;

	                await Common.db_update("whatsapp_autoresponder", [
	                    {
	                        sent: session.autoresponder_sent,
	                        failed: session.autoresponder_failed,
	                    },
	                    { id: item.id },
	                ]);

	                // save stats realtime
	                await Common.db_update("whatsapp_stats", [
	                    {
	                        wa_autoresponder_count: stats.wa_autoresponder_count,
	                        wa_total_sent: stats.wa_total_sent,
	                        wa_total_sent_by_month: stats.wa_total_sent_by_month,
	                        wa_time_reset: stats.wa_time_reset,
	                    },
	                    { team_id },
	                ]);

	                logger.info(`AUTORESPONDER SAVE total=${stats.wa_autoresponder_count}`,
	                    instance_id
	                );
	                break;
	            }

	            /* ---------------- AI SMART REPLY ---------------- */
	            case "ai_smart_reply": {
	                if (!item.id) {
	                    logger.err("ai_smart_reply missing item.id", instance_id);
	                    return;
	                }

	                stats.wa_autoresponder_count += sent;

	                const smartReplySession = sessions[instance_id] ?? {};
	                smartReplySession.ai_smart_reply_sent =
	                    (smartReplySession.ai_smart_reply_sent ?? item.sent ?? 0) + sent;
	                smartReplySession.ai_smart_reply_failed =
	                    (smartReplySession.ai_smart_reply_failed ?? item.failed ?? 0) + failed;
	                sessions[instance_id] = smartReplySession;

	                await Common.db_update("whatsapp_ai_smart_reply", [
	                    {
	                        sent: smartReplySession.ai_smart_reply_sent,
	                        failed: smartReplySession.ai_smart_reply_failed,
	                    },
	                    { id: item.id },
	                ]);

	                await Common.db_update("whatsapp_stats", [
	                    {
	                        wa_autoresponder_count: stats.wa_autoresponder_count,
	                        wa_total_sent: stats.wa_total_sent,
	                        wa_total_sent_by_month: stats.wa_total_sent_by_month,
	                        wa_time_reset: stats.wa_time_reset,
	                    },
	                    { team_id },
	                ]);

	                logger.info(`AI SMART REPLY SAVE -> total=${stats.wa_autoresponder_count}`,
	                    instance_id
	                );
	                break;
	            }

	            /* ---------------- BULK ---------------- */
	            case "bulk": {
	                stats.wa_bulk_total_count += 1;
	                stats.wa_bulk_sent_count += sent;
	                stats.wa_bulk_failed_count += failed;

	                // Internal note
	                await Common.db_update("whatsapp_stats", [
	                    {
	                        wa_bulk_total_count: stats.wa_bulk_total_count,
	                        wa_bulk_sent_count: stats.wa_bulk_sent_count,
	                        wa_bulk_failed_count: stats.wa_bulk_failed_count,
	                        wa_total_sent: stats.wa_total_sent,
	                        wa_total_sent_by_month: stats.wa_total_sent_by_month,
	                        wa_time_reset: stats.wa_time_reset,
	                    },
	                    { team_id },
	                ]);

	                logger.info(`BULK SAVE total=${stats.wa_bulk_total_count}, sent=${stats.wa_bulk_sent_count}, failed=${stats.wa_bulk_failed_count}`,
	                    instance_id
	                );
	                break;
	            }

	            /* ---------------- API ---------------- */
	            case "api": {
	                stats.wa_api_count += sent;

	                // throttle API (30s)
	                if (!stats.next_update || stats.next_update < time_now) {
	                    stats.next_update = time_now + 30;

	                    await Common.db_update("whatsapp_stats", [
	                        {
	                            wa_api_count: stats.wa_api_count,
	                            wa_total_sent: stats.wa_total_sent,
	                            wa_total_sent_by_month: stats.wa_total_sent_by_month,
	                            wa_time_reset: stats.wa_time_reset,
	                            next_update: stats.next_update,
	                        },
	                        { team_id },
	                    ]);

	                    logger.info(`API SAVE (BATCH)`, instance_id);
	                }
	                break;
	            }

	            default:
	                break;
	        }

	        /* ============================================================
	         * 6. SAVE CACHE
	         * ============================================================ */
	        stats_history[team_id] = stats;

	    } catch (err) {
	        logger.err(`stats() failed: ${err.message}`, instance_id);
	    }
	},

    live_back: async function () {
	    try {
	        const now = Math.floor(Date.now() / 1000);

	        /* ======================================================
	         * 1. SKIP IF ALL SESSIONS ARE HEALTHY
	         * ====================================================== */
	        let needCheck = false;

	        for (const instance_id of Object.keys(sessions)) {
	            const s = sessions[instance_id];

	            // Skip heartbeat scan when a session is still connecting, waiting for QR, or has no active user yet
	            if (!s || s.status !== "open" || !s.user) {
	                needCheck = true;
	                break;
	            }
	        }

	        // If all in-memory sessions are healthy, skip the database recovery scan
	        if (Object.keys(sessions).length > 0 && !needCheck) {
	            return;
	        }

	        /* ======================================================
	         * 2. PICK ONE ACCOUNT NEED CHECK
	         * ====================================================== */
	        const account = await Common.db_query(`
	            SELECT 
	                a.changed,
	                a.token as instance_id,
	                a.id,
	                b.id_secure as access_token
	            FROM accounts as a
	            INNER JOIN teams as b ON a.team_id = b.id
	            WHERE a.social_network = 'whatsapp_unofficial'
	              AND a.login_type = '2'
	              AND a.status = 1
	            ORDER BY a.changed ASC
	            LIMIT 1
	        `);

	        if (!account) return;

	        await Common.db_update(
	            "accounts",
	            [{ changed: now }],
	            { id: account.id }
	        );

	        /* ======================================================
	         * 3. CHECK SESSION STATE
	         * ====================================================== */
	        const client = sessions[account.instance_id];

	        // Skip recovery when the session is already open and has a valid user
	        if (client && client.status === "open" && client.user) {
	            return;
	        }

	        /* ======================================================
	         * 4. INIT / RECOVER SESSION
	         * ====================================================== */
	        await WAZIPER.instance(
	            account.access_token,
	            account.instance_id,
	            false,
	            false,
	            async (client) => {

                if (!client) {
                    logger.warn("live_back: client unhealthy, recovery required", account.instance_id);
                    return;
                }

                if (new_sessions[account.instance_id] || client._initializing || client.status === "qr" || client.status === "booting" || !client.user) {
                    logger.info(`live_back skip: session pending (${client.status || "initializing"})`, account.instance_id);
                    return;
                }

	                logger.ok(`live_back OK ${client.user.name || client.user.id}`,
	                    account.instance_id
	                );
	            }
	        );

	        /* ======================================================
	         * 5. AUTO CLOSE QR TIMEOUT
	         * ====================================================== */
	        for (const instance_id of Object.keys(new_sessions)) {
	            if (
	                new_sessions[instance_id] < now &&
	                sessions[instance_id] &&
	                sessions[instance_id].status === "qr"
	            ) {
	                delete new_sessions[instance_id];
	                await WAZIPER.logout(instance_id);
	                logger.warn("QR timeout logged out",
	                    instance_id
	                );
	            }
	        }

	    } catch (err) {
	        logger.err(`live_back() failed: ${err.message}`);
	    }
	},

    add_account: async function (instance_id, team_id, wa_info, account) {
        if (!account) {
            await Common.db_insert_account(instance_id, team_id, wa_info);
        } else {
            const old_instance_id = account.token;

            await Common.db_update_account(instance_id, team_id, wa_info, account.id);

            // Move legacy WhatsApp data to the new instance and remove the stale account
            if (instance_id !== old_instance_id) {
                await Common.db_delete('whatsapp_sessions', [{ instance_id: old_instance_id }]);
                await Common.db_update('whatsapp_autoresponder', [{ instance_id: instance_id }, { instance_id: old_instance_id }]);
                await Common.db_update('whatsapp_chatbot', [{ instance_id: instance_id }, { instance_id: old_instance_id }]);
                await Common.db_update('whatsapp_webhook', [{ instance_id: instance_id }, { instance_id: old_instance_id }]);
                WAZIPER.logout(old_instance_id);
            }

            const pid = Common.get_phone(wa_info.id, 'wid');
            const account_other = await Common.db_query(
                `SELECT id FROM accounts
                 WHERE pid = '${pid}'
                   AND team_id = '${team_id}'
                   AND category = 'profile'
                   AND login_type = 2
                   AND social_network IN ('whatsapp_unofficial')
                   AND id != '${account.id}'
                 ORDER BY (social_network = 'whatsapp_unofficial') ASC, id ASC
                 LIMIT 1`,
            );
            if (account_other) await Common.db_delete('accounts', [{ id: account_other.id }]);
        }

        // ensure WA stats exists
        const wa_stats = await Common.db_get('whatsapp_stats', [{ team_id: team_id }]);
        if (!wa_stats) await Common.db_insert_stats(team_id);
    },

    auto_test_send: async function () {
	    try {
	        const TEST_NUMBER = "+84944313929";
	        const chat_id = TEST_NUMBER.replace("+", "") + "@s.whatsapp.net";

	        logger.info("Auto Test Send starting...", "TEST");

	        const instance_id_secure = Object.keys(sessions);
	        if (instance_id_secure.length === 0) {
	            logger.warn("No active sessions test skipped", "TEST");
	            return;
	        }

	        const instance_id = instance_id_secure[0];
	        const client = sessions[instance_id];

	        if (!client || client.status !== "open") {
	            logger.warn("Instance not ready for test", "TEST");
	            return;
	        }

	        const acc = await Common.db_get("accounts", [
	            { token: instance_id },
	            { status: 1 }
	        ]);

	        if (!acc) {
	            logger.warn(`No account found for instance ${instance_id}`, "TEST");
	            return;
	        }

	        const item = {
	            team_id: acc.team_id,
	            type: 1,
	            caption: "TEST MESSAGE FROM WAZIPER SERVER",
	            media: null,
	            filename: ""
	        };

	        await WAZIPER.auto_send(
	            instance_id,
	            chat_id,
	            TEST_NUMBER,
	            "test",
	            item,
	            {},
	            (result) => {
	                if (result.status === 1) {
	                    logger.ok(`Test sent ${TEST_NUMBER}`, "TEST");
	                } else {
	                    logger.err(`Test failed ${result.message}`, "TEST");
	                }
	            }
	        );

	    } catch (err) {
	        logger.err(`auto_test_send() failed: ${err.message}`, "TEST");
	    }
	},

    email_campaigns_worker: async function () {
        if (!config.email_cron_enabled || EMAIL_CRON_WORKING) {
            return;
        }

        EMAIL_CRON_WORKING = true;
        try {
            let dueDb = null;
            for (const targetDb of Common.getAllDatabases()) {
                try {
                    const row = await Common.withDatabase(targetDb, async () => {
                        return await Common.db_query(
                            `SELECT 1 AS has_due FROM email_campaigns
                             WHERE status = 1
                               AND (is_processing = 0 OR processing_started_at < UNIX_TIMESTAMP() - 300)
                               AND time_post <= UNIX_TIMESTAMP()
                             LIMIT 1`,
                            true,
                            targetDb
                        );
                    });
                    if (row && row.has_due) {
                        dueDb = targetDb;
                        break;
                    }
                } catch (e) {}
            }

            if (!dueDb) {
                return;
            }

            let cronKey = "";
            try {
                const keyRow = await Common.db_query(
                    "SELECT value FROM options WHERE name = 'cron_key' LIMIT 1",
                    true,
                    dueDb
                );
                if (keyRow && keyRow.value) {
                    cronKey = String(keyRow.value).trim();
                }
            } catch (e) {}

            let handled = false;
            const customUrl = String(config.email_cron_url || "").trim().replace(/\/+$/, "");
            const baseUrl = String(config.app_url || "").trim().replace(/\/+$/, "");
            const cronUrl = customUrl
                ? (customUrl.includes("/cron/email-campaigns") ? customUrl : `${customUrl}/cron/email-campaigns`)
                : (baseUrl ? `${baseUrl}/cron/email-campaigns` : "");

            if (/^https?:\/\//i.test(cronUrl)) {
                try {
                    const resp = await axios.get(cronUrl, {
                        params: cronKey ? { key: cronKey } : {},
                        timeout: 245000,
                        validateStatus: (status) => status >= 200 && status < 500,
                    });

                    if (resp.status === 200 && resp.data && Number(resp.data.status) === 1) {
                        const s = resp.data.summary || {};
                        if ((s.sent || 0) > 0 || (s.failed || 0) > 0 || (s.queued || 0) > 0) {
                            logger.ok(
                                `Email batch processed via HTTP: sent=${s.sent || 0}, queued=${s.queued || 0}, failed=${s.failed || 0}`,
                                "EMAIL"
                            );
                        }
                        handled = true;
                    } else {
                        logger.warn(
                            `Email cron HTTP returned ${resp.status}: ${resp.data?.message || "unexpected response"}`,
                            "EMAIL"
                        );
                    }
                } catch (httpErr) {
                    logger.warn(`Email cron HTTP trigger failed (${httpErr.message}), trying artisan CLI fallback`, "EMAIL");
                }
            }

            if (!handled) {
                const installDir = path.resolve(__dirname, "../../Install");
                const artisanPath = path.join(installDir, "artisan");
                if (fs.existsSync(artisanPath)) {
                    const phpBin = process.env.PHP_BIN || "php";
                    const { stdout } = await execFileAsync(
                        phpBin,
                        [artisanPath, "email:process-campaigns"],
                        { cwd: installDir, timeout: 245000 }
                    );
                    const summaryLine = String(stdout || "").trim().split(/\r?\n/)[0] || "completed";
                    if (summaryLine && !summaryLine.includes("Sent: 0 | Queued: 0 | Failed: 0")) {
                        logger.ok(`Email batch via artisan: ${summaryLine}`, "EMAIL");
                    }
                }
            }
        } catch (err) {
            logger.err(`email_campaigns_worker failed: ${err.message}`, "EMAIL");
        } finally {
            EMAIL_CRON_WORKING = false;
        }
    },
};

/* -------------------------------------------------------------------------- */
/* Export & Cron                                                              */
/* -------------------------------------------------------------------------- */
export default WAZIPER;

// Auto-recovery heartbeat
cron.schedule("*/5 * * * * *", () => {
    WAZIPER.live_back();
    logger.info(`Auto heartbeat active: ${Object.keys(sessions).length}`);
});

// Bulk messaging worker
cron.schedule("*/1 * * * * *", async () => {
    if (WAZIPER.bulk_messaging) {
        await WAZIPER.bulk_messaging();
    }
});

// Bulk email worker (triggers Laravel email campaigns without requiring cPanel cron)
cron.schedule("*/30 * * * * *", async () => {
    if (WAZIPER.email_campaigns_worker) {
        await WAZIPER.email_campaigns_worker();
    }
});

setTimeout(() => {
    //WAZIPER.auto_test_send();
}, 15000);









