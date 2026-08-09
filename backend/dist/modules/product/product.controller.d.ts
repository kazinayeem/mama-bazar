import { Request, Response } from "express";
export declare const getAll: (req: Request, res: Response) => Promise<void>;
export declare const getById: (req: Request, res: Response) => Promise<void>;
export declare const getBySlug: (req: Request, res: Response) => Promise<void>;
export declare const getRelated: (req: Request, res: Response) => Promise<void>;
export declare const create: (req: Request, res: Response) => Promise<void>;
export declare const update: (req: Request, res: Response) => Promise<void>;
export declare const remove: (req: Request, res: Response) => Promise<void>;
export declare const bulk: (req: Request, res: Response) => Promise<void>;
export declare const duplicate: (req: Request, res: Response) => Promise<void>;
export declare const exportCsv: (req: Request, res: Response) => Promise<void>;
export declare const importCsv: (req: Request, res: Response) => Promise<void>;
export declare const saveDraft: (req: Request, res: Response) => Promise<void>;
//# sourceMappingURL=product.controller.d.ts.map