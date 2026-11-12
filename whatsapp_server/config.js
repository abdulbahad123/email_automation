/**
 * config.js (ESM version)
 * Compatible with Node.js 18/20/22/24
 * Automatically reads database credentials from .env files with environment variable overrides
 */

import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Candidate paths to search for .env in order of priority
const candidateEnvPaths = [
  path.join(__dirname, ".env"),
  path.join(__dirname, "../Install/.env"),
  path.join(__dirname, "../.env"),
];

const envVars = {};

for (const envPath of candidateEnvPaths) {
  try {
    if (fs.existsSync(envPath)) {
      const lines = fs.readFileSync(envPath, "utf8").split(/\r?\n/);
      for (const line of lines) {
        const trimmed = line.trim();
        if (trimmed && !trimmed.startsWith("#") && trimmed.includes("=")) {
          const idx = trimmed.indexOf("=");
          const key = trimmed.slice(0, idx).trim();
          let val = trimmed.slice(idx + 1).trim();
          if ((val.startsWith('"') && val.endsWith('"')) || (val.startsWith("'") && val.endsWith("'"))) {
            val = val.slice(1, -1);
          }
          if (val !== undefined && val !== "" && (!envVars[key] || envVars[key] === "")) {
            envVars[key] = val;
          }
        }
      }
    }
  } catch (e) {}
}

const standaloneFlag = process.env.STANDALONE_MODE ?? envVars.STANDALONE_MODE ?? "true";
const emailCronFlag = process.env.EMAIL_CRON_ENABLED ?? envVars.EMAIL_CRON_ENABLED ?? "true";

const config = {
  debug: process.env.NODE_DEBUG === "true",
  standalone_mode: String(standaloneFlag).toLowerCase() !== "false",
  email_cron_enabled: String(emailCronFlag).toLowerCase() !== "false",
  email_cron_url: process.env.EMAIL_CRON_URL || envVars.EMAIL_CRON_URL || "",
  app_url: process.env.APP_URL || envVars.APP_URL || "http://127.0.0.1:8080/",
  database: {
    connectionLimit: 100,
    host: process.env.DB_HOST || envVars.DB_HOST || "127.0.0.1",
    port: Number(process.env.DB_PORT || envVars.DB_PORT || 3306),
    user: process.env.DB_USERNAME || envVars.DB_USERNAME || "root",
    password: process.env.DB_PASSWORD !== undefined
      ? process.env.DB_PASSWORD
      : (envVars.DB_PASSWORD !== undefined ? envVars.DB_PASSWORD : ""),
    database: process.env.DB_DATABASE || envVars.DB_DATABASE || "whatsapp_bulk",
    charset: "utf8mb4",
    debug: false,
    waitForConnections: true,
    multipleStatements: true,
  },
  cors: {
    origin: "*",
    optionsSuccessStatus: 200,
  },
};

export default config;
