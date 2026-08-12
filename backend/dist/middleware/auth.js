"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.requirePermission = exports.hasManagementPermission = exports.adminOnly = exports.authMiddleware = void 0;
const jsonwebtoken_1 = __importDefault(require("jsonwebtoken"));
const env_1 = require("../config/env");
const authMiddleware = (req, res, next) => {
    const authHeader = req.headers.authorization;
    if (!authHeader || !authHeader.startsWith("Bearer ")) {
        return res.status(401).json({ success: false, message: "Unauthorized" });
    }
    const token = authHeader.split(" ")[1];
    try {
        const decoded = jsonwebtoken_1.default.verify(token, env_1.env.JWT_SECRET);
        req.user = decoded;
        next();
    }
    catch {
        return res.status(401).json({ success: false, message: "Invalid token" });
    }
};
exports.authMiddleware = authMiddleware;
const adminOnly = (req, res, next) => {
    if (req.user?.role !== "admin") {
        return res.status(403).json({ success: false, message: "Admin access required" });
    }
    next();
};
exports.adminOnly = adminOnly;
const ROLE_PERMISSIONS = {
    admin: [
        "expenses.view",
        "expenses.create",
        "expenses.update",
        "expenses.delete",
        "costs.view",
        "costs.create",
        "costs.update",
        "costs.delete",
        "bookings.view",
        "bookings.create",
        "bookings.update",
        "bookings.delete",
        "rentals.view",
        "rentals.create",
        "rentals.update",
        "rentals.delete",
        "memos.view",
        "memos.upload",
        "memos.delete",
        "reports.view",
        "reports.export",
    ],
    manager: [
        "expenses.view",
        "expenses.create",
        "expenses.update",
        "costs.view",
        "costs.create",
        "costs.update",
        "bookings.view",
        "bookings.create",
        "bookings.update",
        "rentals.view",
        "rentals.create",
        "rentals.update",
        "memos.view",
        "memos.upload",
        "reports.view",
    ],
    user: [],
};
const hasManagementPermission = (role, permission) => {
    const perms = ROLE_PERMISSIONS[role || ""] || [];
    return perms.includes(permission);
};
exports.hasManagementPermission = hasManagementPermission;
const requirePermission = (permission) => (req, res, next) => {
    const role = req.user?.role;
    if (!(0, exports.hasManagementPermission)(role, permission)) {
        return res.status(403).json({ success: false, message: "Insufficient permissions", data: { permission } });
    }
    next();
};
exports.requirePermission = requirePermission;
//# sourceMappingURL=auth.js.map