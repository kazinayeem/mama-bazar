"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.env = void 0;
const dotenv_1 = __importDefault(require("dotenv"));
const path_1 = __importDefault(require("path"));
// Load environment variables from current directory and parent paths
dotenv_1.default.config();
dotenv_1.default.config({ path: path_1.default.resolve(process.cwd(), ".env") });
dotenv_1.default.config({ path: path_1.default.resolve(__dirname, "../../.env") });
dotenv_1.default.config({ path: path_1.default.resolve(__dirname, "../.env") });
// Construct DATABASE_URL if separate DB credentials are provided (standard in cPanel)
let databaseUrl = process.env.DATABASE_URL;
if (!databaseUrl && process.env.DB_USER && process.env.DB_NAME) {
    const host = process.env.DB_HOST || "localhost";
    const port = process.env.DB_PORT || "3306";
    const user = encodeURIComponent(process.env.DB_USER);
    const password = process.env.DB_PASSWORD ? encodeURIComponent(process.env.DB_PASSWORD) : "";
    const auth = password ? `${user}:${password}` : user;
    databaseUrl = `mysql://${auth}@${host}:${port}/${process.env.DB_NAME}`;
}
const getPort = () => {
    const rawPort = process.env.PORT;
    if (!rawPort)
        return 5000;
    const num = Number(rawPort);
    if (!isNaN(num) && num > 0)
        return num;
    // If Passenger provides a named pipe or Unix socket path
    return rawPort;
};
exports.env = {
    DATABASE_URL: databaseUrl || "",
    JWT_SECRET: process.env.JWT_SECRET || "mama_bazar_jwt_secret_key_change_in_production",
    PORT: getPort(),
    NODE_ENV: process.env.NODE_ENV || "development",
    FRONTEND_URL: (process.env.FRONTEND_URL || "http://localhost:5173").trim(),
    UPLOAD_DIR: process.env.UPLOAD_DIR || "uploads",
};
//# sourceMappingURL=env.js.map