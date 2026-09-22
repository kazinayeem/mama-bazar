"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.env = void 0;
const dotenv_1 = __importDefault(require("dotenv"));
const path_1 = __importDefault(require("path"));
const fs_1 = __importDefault(require("fs"));
// Load environment variables from candidate env files in working directory and parent directories
const candidateFiles = [".env", ".env.local", ".env.production"];
const candidateDirs = [
    process.cwd(),
    path_1.default.resolve(__dirname, "../../"),
    path_1.default.resolve(__dirname, "../"),
    path_1.default.resolve(__dirname, "./"),
];
for (const dir of candidateDirs) {
    for (const file of candidateFiles) {
        const fullPath = path_1.default.resolve(dir, file);
        if (fs_1.default.existsSync(fullPath)) {
            dotenv_1.default.config({ path: fullPath });
        }
    }
}
dotenv_1.default.config();
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