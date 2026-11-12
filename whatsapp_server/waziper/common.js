/**
 * Common.js (ESM version for Waziper Baileys v7)
 * Universal Multi-Tenant 1-Node Architecture
 * Supports automatic dynamic database pooling per White-Label Agency with AsyncLocalStorage
 * Compatible with Node 18/20/22/24
 */

import mysql from "mysql";
import config from "./../config.js";
import moment from "moment-timezone";
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";
import { AsyncLocalStorage } from "async_hooks";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const dbStorage = new AsyncLocalStorage();

const MAP_FILE = path.join(__dirname, "../instance_db_map.json");
let instanceDbMap = {};
const discoveredDatabases = new Set();
const failedDatabases = new Set();

try {
  if (fs.existsSync(MAP_FILE)) {
    instanceDbMap = JSON.parse(fs.readFileSync(MAP_FILE, "utf8"));
  }
} catch (e) {
  instanceDbMap = {};
}

function saveMap() {
  try {
    fs.writeFileSync(MAP_FILE, JSON.stringify(instanceDbMap, null, 2), "utf8");
  } catch (e) {}
}

const pools = {};
function getPool(dbName) {
  const targetDb = dbName || config.database.database;
  if (!pools[targetDb]) {
    pools[targetDb] = mysql.createPool({
      ...config.database,
      database: targetDb,
      connectionLimit: 30,
      waitForConnections: true,
      queueLimit: 0,
    });
  }
  return pools[targetDb];
}

function resolveDb(dbName, data) {
  if (dbName && typeof dbName === "string" && dbName.trim() !== "") {
    return dbName.trim();
  }
  
  // Scoped database from AsyncLocalStorage (set by Express middleware or cron runner)
  const scopedDb = dbStorage.getStore();
  if (scopedDb && typeof scopedDb === "string" && scopedDb.trim() !== "") {
    return scopedDb.trim();
  }

  // Auto-detect from data payload if it contains instance_id or token
  if (Array.isArray(data)) {
    for (const item of data) {
      if (item && typeof item === "object") {
        if (item.instance_id && instanceDbMap[item.instance_id]) {
          return instanceDbMap[item.instance_id];
        }
        if (item.token && instanceDbMap[item.token]) {
          return instanceDbMap[item.token];
        }
      }
    }
  } else if (data && typeof data === "object") {
    if (data.instance_id && instanceDbMap[data.instance_id]) {
      return instanceDbMap[data.instance_id];
    }
    if (data.token && instanceDbMap[data.token]) {
      return instanceDbMap[data.token];
    }
  } else if (typeof data === "string") {
    const tokenMatch = data.match(/token\s*=\s*['"]([a-zA-Z0-9_-]+)['"]/i);
    if (tokenMatch && instanceDbMap[tokenMatch[1]]) {
      return instanceDbMap[tokenMatch[1]];
    }
    const instMatch = data.match(/instance_id\s*=\s*['"]([a-zA-Z0-9_-]+)['"]/i);
    if (instMatch && instanceDbMap[instMatch[1]]) {
      return instanceDbMap[instMatch[1]];
    }
  }

  return config.database.database;
}

const Common = {
  getPool,

  withDatabase(dbName, fn) {
    return dbStorage.run(dbName, fn);
  },
  
  registerInstanceDb(instance_id, dbName) {
    if (instance_id && dbName) {
      instanceDbMap[instance_id] = dbName;
      saveMap();
    }
  },

  getDbForInstance(instance_id) {
    if (instance_id && instanceDbMap[instance_id]) {
      return instanceDbMap[instance_id];
    }
    return config.database.database;
  },

  getAllDatabases() {
    const dbs = new Set([
      config.database.database,
      ...discoveredDatabases,
    ]);
    for (const k in instanceDbMap) {
      if (instanceDbMap[k]) dbs.add(instanceDbMap[k]);
    }
    return Array.from(dbs).filter((db) => db && !failedDatabases.has(db));
  },

  async autoDiscoverDatabases() {
    try {
      const candidateDbs = new Set();
      if (config.database.database) {
        candidateDbs.add(config.database.database);
      }

      if (!config.standalone_mode) {
        const defaultPool = getPool(config.database.database);
        const rows = await new Promise((resolve) => {
          defaultPool.query("SHOW DATABASES", (err, res) => {
            if (err && config.debug) console.error("[AUTO DISCOVER DB ERR]:", err.message);
            resolve(res);
          });
        });
        if (rows && rows.length > 0) {
          for (const r of rows) {
            const dbName = Object.values(r)[0];
            if (dbName && (dbName.includes("whatsapp") || dbName.includes("automation"))) {
              candidateDbs.add(dbName);
            }
          }
        }
      }

      for (const dbName of candidateDbs) {
        discoveredDatabases.add(dbName);
        try {
          const p = getPool(dbName);
          const sessions = await new Promise((resolve) => {
            p.query("SELECT instance_id FROM whatsapp_sessions", (err, res) => resolve(res));
          });
          if (sessions && sessions.length > 0) {
            for (const s of sessions) {
              if (s.instance_id) {
                instanceDbMap[s.instance_id] = dbName;
              }
            }
          }
        } catch (err) {}
      }
      saveMap();
    } catch (e) {}
  },

  /* ----------------------------- DB Helpers ----------------------------- */
  db_query: async function (query, row, dbName) {
    const targetDb = resolveDb(dbName, query);
    if (failedDatabases.has(targetDb)) return false;
    const pool = getPool(targetDb);
    const res = await new Promise((resolve) => {
      pool.query(query, (err, res) => {
        if (err) {
          if (err.code === 'ER_DBACCESS_DENIED_ERROR' || err.code === 'ER_BAD_DB_ERROR') {
            failedDatabases.add(targetDb);
          } else if (config.debug) {
            console.error(`[DB_QUERY ERR ${targetDb}]:`, err.message);
          }
        }
        resolve(res);
      });
    });
    return Common.response(res, row);
  },

  db_insert: async function (table, data, dbName) {
    const targetDb = resolveDb(dbName, data);
    if (failedDatabases.has(targetDb)) return false;
    const pool = getPool(targetDb);
    const res = await new Promise((resolve) => {
      pool.query("INSERT INTO " + table + " SET ?", data, (err, res) => {
        if (err && config.debug) console.error(`[DB_INSERT ERR ${targetDb}]:`, err.message);
        resolve(res);
      });
    });
    return res;
  },

  db_update: async function (table, data, dbName) {
    const targetDb = resolveDb(dbName, data);
    if (failedDatabases.has(targetDb)) return false;
    const pool = getPool(targetDb);
    const res = await new Promise((resolve) => {
      pool.query("UPDATE " + table + " SET ? WHERE ?", data, (err, res) => {
        if (err && config.debug) console.error(`[DB_UPDATE ERR ${targetDb}]:`, err.message);
        resolve(res);
      });
    });
    return res;
  },

  db_get: async function (table, data, dbName) {
    let query = `SELECT * FROM ${table}`;
    let where = "";
    if (data.length > 0) {
      for (let i = 0; i < data.length; i++) where += i === 0 ? " ?" : " AND ?";
    }
    if (where !== "") query += " WHERE " + where;
    const targetDb = resolveDb(dbName, data);
    if (failedDatabases.has(targetDb)) return false;
    let pool = getPool(targetDb);
    let res = await new Promise((resolve) => {
      pool.query(query, data, (err, res) => {
        if (err) {
          if (err.code === 'ER_DBACCESS_DENIED_ERROR' || err.code === 'ER_BAD_DB_ERROR') {
            failedDatabases.add(targetDb);
          } else if (config.debug) {
            console.error(`[DB_GET ERR ${targetDb}]: ${err.message} (Query: ${query})`);
          }
        }
        resolve(res);
      });
    });

    // Fallback across pools for teams or sessions if not found in primary
    if ((!res || res.length === 0) && (table === 'teams' || table === 'whatsapp_sessions')) {
      const allDbs = Common.getAllDatabases();
      for (const altDb of allDbs) {
        if (altDb === targetDb) continue;
        try {
          const altPool = getPool(altDb);
          const altRes = await new Promise((resolve) => {
            altPool.query(query, data, (err, res) => {
              if (err && config.debug) console.error(`[DB_GET ALT ERR ${altDb}]:`, err.message);
              resolve(res);
            });
          });
          if (altRes && altRes.length > 0) {
            res = altRes;
            console.log(`[DB_GET FALLBACK] Found ${table} in alternate database: ${altDb}`);
            break;
          }
        } catch (e) {}
      }
    }

    return Common.response(res, true);
  },

  db_fetch: async function (table, data, dbName) {
    let query = `SELECT * FROM ${table}`;
    let where = "";
    if (data.length > 0) {
      for (let i = 0; i < data.length; i++) where += i === 0 ? " ?" : " AND ?";
    }
    if (where !== "") query += " WHERE " + where;
    const targetDb = resolveDb(dbName, data);
    const pool = getPool(targetDb);
    const res = await new Promise((resolve) => {
      pool.query(query, data, (err, res) => resolve(res));
    });
    return Common.response(res, false);
  },

  db_delete: async function (table, data, dbName) {
    let query = `DELETE FROM ${table}`;
    let where = "";
    if (data.length > 0) {
      for (let i = 0; i < data.length; i++) where += i === 0 ? " ?" : " AND ?";
    }
    if (where !== "") query += " WHERE " + where;
    const targetDb = resolveDb(dbName, data);
    const pool = getPool(targetDb);
    const res = await new Promise((resolve) => {
      pool.query(query, data, (err, res) => resolve(res));
    });
    return res;
  },

  get_phone_number: async function (contact_id, phone_numbers, dbName) {
    const targetDb = dbName || resolveDb(null, null);
    const pool = getPool(targetDb);
    if (!Array.isArray(phone_numbers) || phone_numbers.length === 0) {
        phone_numbers = ["__EMPTY__"];
    }

    const placeholders = phone_numbers.map(() => "?").join(",");
    const sql = `
        SELECT * FROM whatsapp_phone_numbers 
        WHERE pid = ?
        AND phone NOT IN (${placeholders})
        ORDER BY id ASC
        LIMIT 1
    `;
    const params = [contact_id, ...phone_numbers];
    const res = await new Promise((resolve) => {
        pool.query(sql, params, (err, res) => resolve(res));
    });
    return Common.response(res, true);
  },

  get_instance: async function (instance_id, dbName) {
    const targetDb = dbName || Common.getDbForInstance(instance_id);
    const pool = getPool(targetDb);
    const res = await new Promise((resolve) => {
      pool.query("SELECT * FROM whatsapp_sessions WHERE ?", [{ instance_id }], (err, res) => resolve(res));
    });
    return Common.response(res, true);
  },

  get_accounts: async function (accounts, dbName) {
    const targetDb = dbName || resolveDb(null, null);
    const pool = getPool(targetDb);
    const res = await new Promise((resolve) => {
      pool.query(
        "SELECT count(*) as count FROM accounts WHERE id IN (" + accounts + ") AND status = 1",
        (err, res) => resolve(res)
      );
    });
    return Common.response(res, true);
  },

  db_insert_account: async function (instance_id, team_id, wa_info, dbName) {
    const targetDb = dbName || Common.getDbForInstance(instance_id);
    const pool = getPool(targetDb);
    const data = {
      ids: Common.makeid(13),
      id_secure: Common.makeid(10),
      module: "AppChannelWhatsappUnofficial",
      social_network: "whatsapp_unofficial",
      category: "profile",
      login_type: 2,
      can_post: 0,
      team_id: team_id,
      pid: Common.get_phone(wa_info.id, "wid"),
      name: wa_info.name,
      username: Common.get_phone(wa_info.id),
      token: instance_id,
      avatar: wa_info.avatar,
      url: "https://web.whatsapp.com/",
      tmp: JSON.stringify(wa_info),
      status: 1,
      changed: Common.time(),
      created: Common.time(),
    };
    const res = await new Promise((resolve) => {
      pool.query("INSERT INTO accounts SET ?", data, (err, res) => resolve(res));
    });
    return res;
  },

  db_update_account: async function (instance_id, team_id, wa_info, account_id, dbName) {
    const targetDb = dbName || Common.getDbForInstance(instance_id);
    const pool = getPool(targetDb);
    const data = [
      {
        module: "AppChannelWhatsappUnofficial",
        social_network: "whatsapp_unofficial",
        category: "profile",
        login_type: 2,
        pid: Common.get_phone(wa_info.id, "wid"),
        name: wa_info.name,
        username: Common.get_phone(wa_info.id),
        token: instance_id,
        avatar: wa_info.avatar,
        tmp: JSON.stringify(wa_info),
        status: 1,
        changed: Common.time(),
      },
      { id: account_id },
    ];
    const res = await new Promise((resolve) => {
      pool.query("UPDATE accounts SET ? WHERE ?", data, (err, res) => resolve(res));
    });
    return res;
  },

  db_insert_stats: async function (team_id, dbName) {
    const targetDb = dbName || resolveDb(null, null);
    const pool = getPool(targetDb);
    const data = {
      ids: Common.makeid(13),
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
    const res = await new Promise((resolve) => {
      pool.query("INSERT INTO whatsapp_stats SET ?", data, (err, res) => resolve(res));
    });
    return res;
  },

  update_status_instance: async function (instance_id, info, dbName) {
    const targetDb = dbName || Common.getDbForInstance(instance_id);
    const pool = getPool(targetDb);
    const data = [
      {
        status: 1,
        data: JSON.stringify(info),
      },
      { instance_id: instance_id },
    ];
    const res = await new Promise((resolve) => {
      pool.query("UPDATE whatsapp_sessions SET ? WHERE ?", data, (err, res) => resolve(res));
    });
    return res;
  },

  /* ----------------------------- Utility helpers ----------------------------- */
  makeid(length) {
    const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    let result = "";
    for (let i = 0; i < length; i++) result += chars.charAt(Math.floor(Math.random() * chars.length));
    return result.toLowerCase();
  },

  time() {
    return Math.round(new Date().getTime() / 1000);
  },

  sleep: async function (ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
  },

  randomIntFromInterval(min, max) {
    return Math.floor(Math.random() * (max - min + 1) + min);
  },

  get_phone(id, type) {
    switch (type) {
      case "wid":
        const parts = id.split(":");
        if (parts.length === 2) {
          const id1 = parts[0];
          const id2 = parts[1].split("@");
          id = id1 + "@" + id2[1];
        }
        break;
      default:
        const basic = id.split(":");
        if (basic.length === 2) id = basic[0];
        else {
          id = id.split("@")[0];
        }
    }
    return id;
  },

  get_avatar(text, color) {
    if (!text) return false;
    const colors = ["E74645", "FB7756", "FACD60", "12492F", "F7A400", "58B368"];
    color = color || colors[Math.floor(Math.random() * colors.length)];
    text = text.replace(/[&=~'" ]/g, "");
    return `https://ui-avatars.com/api/?name=${encodeURI(text)}&background=${color}&color=fff&font-size=0.5&rounded=false&format=png`;
  },

  check_especials(phone, dbName) {
    return new Promise((resolve) => {
      let current_phone = phone;
      if (!phone) return resolve(phone);

      if (phone.startsWith("55")) {
        const ddd = phone.substring(2, 4);
        if (ddd >= 31 && phone.length >= 13) {
          phone = phone.substring(0, 4) + phone.substring(5);
        }
      }

      if (phone.startsWith("52") && phone.length === 12 && phone.substring(2, 3) !== "1") {
        phone = phone.substring(0, 2) + "1" + phone.substring(2);
      }

      if (phone !== current_phone) {
        const targetDb = dbName || resolveDb(null, null);
        const pool = getPool(targetDb);
        const query = `UPDATE whatsapp_phone_numbers SET phone='${phone}' WHERE phone=${current_phone}`;
        pool.query(query, (err) => resolve(phone));
      } else {
        resolve(phone);
      }
    });
  },

  roundMinutes(date) {
    date.setHours(date.getHours() + 1);
    date.setMinutes(0, 0, 0);
    return date;
  },

  getTZDiff(timezone) {
    const now = moment();
    const localOffset = now.utcOffset();
    now.tz(timezone);
    const centralOffset = now.utcOffset();
    return (localOffset - centralOffset) / 60;
  },

  convert_timezone(date, tzString) {
    return new Date((typeof date === "string" ? new Date(date) : date).toLocaleString("en-US", { timeZone: tzString }));
  },

  params(params, content) {
    if (params) {
      let obj = null;

      if (typeof params === "string") {
        obj = Common.toLowerKeys(JSON.parse(params));
      } else if (typeof params === "object") {
        obj = Common.toLowerKeys(params);
      }

      if (obj) {
        const pattern = /\%(.*?)\%/;
        let match;
        let count = 0;
        while ((match = content.match(pattern))) {
          const find = match[0].slice(1, -1).toLowerCase();
          if (obj[find]) content = content.replace(match[0], obj[find]);
          if (++count >= 100) break;
        }
      }
    }
    return content;
  },

  toLowerKeys(obj) {
    return Object.keys(obj).reduce((acc, key) => {
      acc[key.toLowerCase()] = obj[key];
      return acc;
    }, {});
  },

  get_url_extension(url) {
    if (!url || typeof url !== "string") return "";
    return url.split(/[#?]/)[0].split(".").pop().trim().toLowerCase();
  },

  ext2mime(url) {
    const mime = Common.get_url_extension(url);
    const types = {
      jpg: "image/jpeg",
      jpeg: "image/jpeg",
      png: "image/png",
      gif: "image/gif",
      webp: "image/webp",
      mp4: "video/mp4",
      m4v: "video/mp4",
      mov: "video/quicktime",
      avi: "video/x-msvideo",
      webm: "video/webm",
      mkv: "video/x-matroska",
      "3gp": "video/3gpp",
      "3gpp": "video/3gpp",
      mp3: "audio/mpeg",
      ogg: "audio/ogg",
      wav: "audio/wav",
      m4a: "audio/mp4",
      pdf: "application/pdf",
      doc: "application/msword",
      docx: "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
      xls: "application/vnd.ms-excel",
      xlsx: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
      zip: "application/zip",
      rar: "application/x-rar-compressed",
    };
    return types[mime] || "application/octet-stream";
  },

  get_file_name(url) {
    if (!url || typeof url !== "string") return "";
    const clean = url.split(/[#?]/)[0];
    const slashIndex = Math.max(clean.lastIndexOf("/"), clean.lastIndexOf("\\"));
    const filename = slashIndex >= 0 ? clean.substring(slashIndex + 1) : clean;
    return decodeURI(filename);
  },

  post_type(mime, type) {
    let post_type = "documentMessage";
    if (type === 1) {
      if (typeof mime === "string" && (mime.startsWith("image/") || ["image/png", "image/jpeg", "image/jpg", "image/gif", "image/webp"].includes(mime))) {
        post_type = "imageMessage";
      } else if (typeof mime === "string" && (mime.startsWith("video/") || ["video/mp4", "video/3gpp", "video/3gp", "video/gif", "video/quicktime", "video/webm", "video/x-matroska", "video/x-msvideo"].includes(mime))) {
        post_type = "videoMessage";
      } else if (typeof mime === "string" && (mime.startsWith("audio/") || ["audio/mpeg", "audio/ogg", "audio/mp4", "audio/wav"].includes(mime))) {
        post_type = "audioMessage";
      }
    } else {
      if (["png", "jpeg", "jpg", "gif", "webp"].includes(mime)) post_type = "imageMessage";
      else if (["mp4", "3gpp", "3gp", "mov", "avi", "webm", "mkv", "m4v"].includes(mime)) post_type = "videoMessage";
      else if (["mp3", "ogg", "wav", "m4a"].includes(mime)) post_type = "audioMessage";
    }
    return post_type;
  },

  response(res, row) {
    if (res && res.length > 0) return row || row === undefined ? res[0] : res;
    return false;
  },
};

export default Common;
