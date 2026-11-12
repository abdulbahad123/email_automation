process.removeAllListeners('warning');
process.on('uncaughtException', (err) => {
  console.error('[UNCAUGHT_EXCEPTION]', err);
});
process.on('unhandledRejection', (reason) => {
  console.error('[UNHANDLED_REJECTION]', reason);
});

import WAZIPER from "./waziper/waziper.js";
import Common from "./waziper/common.js";

// Run auto-discovery on startup to register all existing agency databases
Common.autoDiscoverDatabases().catch(() => {});

// Multi-Tenant Context Middleware
WAZIPER.app.use((req, res, next) => {
  const instance_id = req.query.instance_id || req.body?.instance_id;
  const db = req.query.db || req.body?.db || (instance_id ? Common.getDbForInstance(instance_id) : null);

  if (instance_id && (req.query.db || req.body?.db)) {
    Common.registerInstanceDb(instance_id, req.query.db || req.body?.db);
  }

  Common.withDatabase(db, () => {
    next();
  });
});

WAZIPER.app.get("/instance", async (req, res) => {
  const access_token = req.query.access_token;
  const instance_id = req.query.instance_id;
  const db = req.query.db || req.body?.db;
  const team_id = req.query.team_id || req.body?.team_id;

  await WAZIPER.instance(access_token, instance_id, false, res, async (client) => {
    await WAZIPER.get_info(instance_id, res);
  }, db, team_id);
});

WAZIPER.app.get("/get_qrcode", async (req, res) => {
  const access_token = req.query.access_token;
  const instance_id = req.query.instance_id;
  const db = req.query.db || req.body?.db;
  const team_id = req.query.team_id || req.body?.team_id;
  
  if (!access_token || !instance_id) {
    return res.json({
      status: "error",
      message: "Authentication failed. Access token and Instance ID are required."
    });
  }
  
  try {
    await WAZIPER.instance(access_token, instance_id, true, res, async () => {
      await WAZIPER.get_qrcode(instance_id, res);
    }, db, team_id);
  } catch (error) {
    console.error(`[GET_QRCODE ERROR] ${instance_id}:`, error);
    return res.json({
      status: "error",
      message: `Failed to generate QR code: ${error.message}`
    });
  }
});

WAZIPER.app.get("/get_groups", async (req, res) => {
  const access_token = req.query.access_token;
  const instance_id = req.query.instance_id;
  const db = req.query.db || req.body?.db;
  const team_id = req.query.team_id || req.body?.team_id;

  await WAZIPER.instance(access_token, instance_id, false, res, async (client) => {
    await WAZIPER.get_groups(instance_id, res);
  }, db, team_id);
});

WAZIPER.app.get("/logout", async (req, res) => {
  const access_token = req.query.access_token;
  const instance_id = req.query.instance_id;
  const db = req.query.db || req.body?.db;

  WAZIPER.logout(instance_id, res, db);
});

WAZIPER.app.get("/active_instance", async (req, res) => {
  const reqDb = req.query.db || req.body?.db;
  const databasesToScan = reqDb ? [reqDb] : Common.getAllDatabases();

  for (const dbName of databasesToScan) {
    try {
      const account = await Common.db_get("accounts", [
        { status: 1 },
        { social_network: "whatsapp_unofficial" },
        { category: "profile" }
      ], dbName);

      if (account && account.token) {
        const team = await Common.db_get("teams", [{ id: account.team_id }], dbName);
        return res.json({
          status: "success",
          instance_id: account.token,
          access_token: team?.id_secure || "",
          account_name: account.name || account.username || "",
          db: dbName
        });
      }
    } catch (e) {}
  }

  return res.json({
    status: "error",
    message: "No connected WhatsApp profile found."
  });
});

WAZIPER.app.post("/send_message", async (req, res) => {
  const access_token = req.query.access_token || req.body?.access_token;
  const instance_id = req.query.instance_id || req.body?.instance_id;
  const db = req.query.db || req.body?.db;
  const team_id = req.query.team_id || req.body?.team_id;

  await WAZIPER.instance(access_token, instance_id, false, res, async (client) => {
    WAZIPER.send_message(instance_id, access_token, req, res);
  }, db, team_id);
});

WAZIPER.app.get("/", async (req, res) => {
  // Freshly discover any newly imported agency databases immediately on request
  await Common.autoDiscoverDatabases().catch(() => {});
  return res.json({ 
    status: "success", 
    message: "Welcome to WAZIPER Universal 1-Node Server",
    active_databases: Common.getAllDatabases()
  });
});

const PORT = process.env.PORT || 8000;
WAZIPER.server.listen(PORT, () => {
  console.log(`✅ WAZIPER UNIVERSAL SERVER IS LIVE ON PORT ${PORT}`);
});
