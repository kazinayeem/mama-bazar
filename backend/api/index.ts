import type { VercelRequest, VercelResponse } from "@vercel/node";
import app from "../src/app";
import { initializeRbac } from "../src/config/initRbac";

let rbacInitialized = false;

export default async function handler(req: VercelRequest, res: VercelResponse) {
  if (!rbacInitialized) {
    try {
      await initializeRbac();
      rbacInitialized = true;
    } catch (e) {
      console.error("RBAC initialization in Vercel handler error:", e);
    }
  }
  return app(req, res);
}
