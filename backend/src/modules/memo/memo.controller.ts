import { Request, Response } from "express";
import * as memoService from "./memo.service";

export const list = async (req: Request, res: Response) => {
  const result = await memoService.listMemos({
    page: Number(req.query.page),
    limit: Number(req.query.limit),
    search: req.query.search as string | undefined,
    entityType: req.query.entityType as string | undefined,
    folder: req.query.folder as string | undefined,
  });
  res.json({ success: true, ...result });
};

export const getById = async (req: Request, res: Response) => {
  const data = await memoService.getMemo(Number(req.params.id));
  if (!data) return res.status(404).json({ success: false, message: "Memo not found" });
  res.json({ success: true, data });
};

export const create = async (req: Request, res: Response) => {
  const data = await memoService.createMemo(req.body);
  res.status(201).json({ success: true, data });
};

export const remove = async (req: Request, res: Response) => {
  await memoService.deleteMemo(Number(req.params.id));
  res.json({ success: true, message: "Memo deleted" });
};

export const removeMany = async (req: Request, res: Response) => {
  const { ids } = req.body as { ids: number[] };
  const result = await memoService.deleteManyMemos(ids);
  res.json({ success: true, deleted: result.deleted });
};
