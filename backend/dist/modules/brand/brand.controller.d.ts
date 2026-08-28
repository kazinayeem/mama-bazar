import { Request, Response } from "express";
export declare const getAll: (_req: Request, res: Response) => Promise<Response<any, Record<string, any>> | undefined>;
export declare const listAdmin: (req: Request, res: Response) => Promise<void>;
export declare const getById: (req: Request, res: Response) => Promise<void>;
export declare const getBySlug: (req: Request, res: Response) => Promise<void>;
export declare const getUsage: (req: Request, res: Response) => Promise<void>;
export declare const create: (req: Request, res: Response) => Promise<void>;
export declare const update: (req: Request, res: Response) => Promise<void>;
export declare const remove: (req: Request, res: Response) => Promise<void>;
export declare const moveProducts: (req: Request, res: Response) => Promise<void>;
//# sourceMappingURL=brand.controller.d.ts.map