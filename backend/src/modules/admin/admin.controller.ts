import { Request, Response } from "express";
import * as adminService from "./admin.service";

export const getDashboard = async (req: Request, res: Response) => {
  const data = await adminService.getDashboard({
    range: req.query.range as string | undefined,
  });
  res.json({ success: true, data });
};
