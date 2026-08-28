import { Request, Response } from "express";
import * as memberService from "./member.service";
import { getAuditLogs } from "../admin/audit.service";

export const getRolesAndPermissions = async (_req: Request, res: Response) => {
  const data = await memberService.getRolesAndPermissions();
  res.json({ success: true, data });
};

export const listMembers = async (_req: Request, res: Response) => {
  const data = await memberService.listMembers();
  res.json({ success: true, data });
};

export const createMember = async (req: Request, res: Response) => {
  const actor = (req as any).user;
  const data = await memberService.createMember(req.body, {
    id: actor?.id,
    name: actor?.name,
    email: actor?.email,
    ip: req.ip,
    userAgent: req.headers["user-agent"],
  });
  res.status(201).json({ success: true, message: "Member created successfully", data });
};

export const updateMember = async (req: Request, res: Response) => {
  const id = Number(req.params.id);
  const actor = (req as any).user;
  const data = await memberService.updateMember(id, req.body, {
    id: actor?.id,
    name: actor?.name,
    email: actor?.email,
    ip: req.ip,
    userAgent: req.headers["user-agent"],
  });
  res.json({ success: true, message: "Member updated successfully", data });
};

export const deleteMember = async (req: Request, res: Response) => {
  const id = Number(req.params.id);
  const actor = (req as any).user;
  const data = await memberService.deleteMember(id, {
    id: actor?.id,
    name: actor?.name,
    email: actor?.email,
    ip: req.ip,
    userAgent: req.headers["user-agent"],
  });
  res.json({ success: true, message: "Member deleted successfully", data });
};

export const listAuditLogs = async (req: Request, res: Response) => {
  const limit = Math.min(Number(req.query.limit) || 100, 500);
  const data = await getAuditLogs(limit);
  res.json({ success: true, data });
};
