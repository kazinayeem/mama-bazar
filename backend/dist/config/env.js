"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.env = void 0;
const dotenv_1 = __importDefault(require("dotenv"));
dotenv_1.default.config();
const required = (key) => {
    const value = process.env[key];
    if (!value) {
        throw new Error(`Missing required env variable: ${key}`);
    }
    return value;
};
exports.env = {
    DATABASE_URL: required("DATABASE_URL"),
    JWT_SECRET: required("JWT_SECRET"),
    PORT: Number(process.env.PORT) || 5000,
    NODE_ENV: process.env.NODE_ENV || "development",
    FRONTEND_URL: (process.env.FRONTEND_URL || "http://localhost:5173").trim(),
    UPLOAD_DIR: process.env.UPLOAD_DIR || "uploads",
};
//# sourceMappingURL=env.js.map