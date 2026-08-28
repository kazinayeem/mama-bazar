"use strict";
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    var desc = Object.getOwnPropertyDescriptor(m, k);
    if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
    }
    Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __importStar = (this && this.__importStar) || (function () {
    var ownKeys = function(o) {
        ownKeys = Object.getOwnPropertyNames || function (o) {
            var ar = [];
            for (var k in o) if (Object.prototype.hasOwnProperty.call(o, k)) ar[ar.length] = k;
            return ar;
        };
        return ownKeys(o);
    };
    return function (mod) {
        if (mod && mod.__esModule) return mod;
        var result = {};
        if (mod != null) for (var k = ownKeys(mod), i = 0; i < k.length; i++) if (k[i] !== "default") __createBinding(result, mod, k[i]);
        __setModuleDefault(result, mod);
        return result;
    };
})();
Object.defineProperty(exports, "__esModule", { value: true });
exports.listAuditLogs = exports.deleteMember = exports.updateMember = exports.createMember = exports.listMembers = exports.getRolesAndPermissions = void 0;
const memberService = __importStar(require("./member.service"));
const audit_service_1 = require("../admin/audit.service");
const getRolesAndPermissions = async (_req, res) => {
    const data = await memberService.getRolesAndPermissions();
    res.json({ success: true, data });
};
exports.getRolesAndPermissions = getRolesAndPermissions;
const listMembers = async (_req, res) => {
    const data = await memberService.listMembers();
    res.json({ success: true, data });
};
exports.listMembers = listMembers;
const createMember = async (req, res) => {
    const actor = req.user;
    const data = await memberService.createMember(req.body, {
        id: actor?.id,
        name: actor?.name,
        email: actor?.email,
        ip: req.ip,
        userAgent: req.headers["user-agent"],
    });
    res.status(201).json({ success: true, message: "Member created successfully", data });
};
exports.createMember = createMember;
const updateMember = async (req, res) => {
    const id = Number(req.params.id);
    const actor = req.user;
    const data = await memberService.updateMember(id, req.body, {
        id: actor?.id,
        name: actor?.name,
        email: actor?.email,
        ip: req.ip,
        userAgent: req.headers["user-agent"],
    });
    res.json({ success: true, message: "Member updated successfully", data });
};
exports.updateMember = updateMember;
const deleteMember = async (req, res) => {
    const id = Number(req.params.id);
    const actor = req.user;
    const data = await memberService.deleteMember(id, {
        id: actor?.id,
        name: actor?.name,
        email: actor?.email,
        ip: req.ip,
        userAgent: req.headers["user-agent"],
    });
    res.json({ success: true, message: "Member deleted successfully", data });
};
exports.deleteMember = deleteMember;
const listAuditLogs = async (req, res) => {
    const limit = Math.min(Number(req.query.limit) || 100, 500);
    const data = await (0, audit_service_1.getAuditLogs)(limit);
    res.json({ success: true, data });
};
exports.listAuditLogs = listAuditLogs;
//# sourceMappingURL=member.controller.js.map