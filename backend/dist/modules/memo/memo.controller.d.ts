import { Request, Response } from "express";
export declare const list: (req: Request, res: Response) => Promise<void>;
export declare const getById: (req: Request, res: Response) => Promise<Response<any, Record<string, any>> | undefined>;
export declare const create: (req: Request, res: Response) => Promise<void>;
export declare const remove: (req: Request, res: Response) => Promise<void>;
export declare const removeMany: (req: Request, res: Response) => Promise<void>;
//# sourceMappingURL=memo.controller.d.ts.map