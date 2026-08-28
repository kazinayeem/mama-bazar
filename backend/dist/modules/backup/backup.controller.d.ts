import { Request, Response } from "express";
export declare const listBackups: (_req: Request, res: Response) => Promise<void>;
export declare const createBackup: (req: Request, res: Response) => Promise<void>;
export declare const downloadBackup: (req: Request, res: Response) => Promise<Response<any, Record<string, any>>>;
export declare const restoreBackup: (req: Request, res: Response) => Promise<void>;
export declare const deleteBackup: (req: Request, res: Response) => Promise<void>;
//# sourceMappingURL=backup.controller.d.ts.map