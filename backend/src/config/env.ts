import dotenv from "dotenv";
dotenv.config();

const required = (key: string): string => {
  const value = process.env[key];
  if (!value) {
    throw new Error(`Missing required env variable: ${key}`);
  }
  return value;
};

export const env = {
  DATABASE_URL: required("DATABASE_URL"),
  JWT_SECRET: required("JWT_SECRET"),
  PORT: Number(process.env.PORT) || 5000,
  NODE_ENV: process.env.NODE_ENV || "development",
  FRONTEND_URL: (process.env.FRONTEND_URL || "http://localhost:5173").trim(),
  UPLOAD_DIR: process.env.UPLOAD_DIR || "uploads",
};
