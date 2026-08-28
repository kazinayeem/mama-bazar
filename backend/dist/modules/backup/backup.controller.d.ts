import { Request, Response } from "express";
export declare const listBackups: (_req: Request, res: Response) => Promise<void>;
export declare const createBackup: (req: Request, res: Response) => Promise<void>;
/**
 * Download endpoint — always proxies through the backend.
 *
 * WHY: The `download` attribute on <a> tags only works for same-origin URLs.
 * Cross-origin Cloudinary signed URLs are silently ignored by browsers, causing
 * the browser to navigate to the URL instead of downloading, returning a ~225-byte
 * Cloudinary error/redirect HTML page instead of the actual ZIP archive.
 *
 * Proxy approach ensures:
 * - Correct Content-Disposition: attachment header (forces download in every browser)
 * - Correct Content-Type: application/zip
 * - Auth is enforced server-side (JWT checked before any bytes are served)
 * - No cross-origin limitations
 */
export declare const downloadBackup: (req: Request, res: Response) => Promise<Response<any, Record<string, any>>>;
export declare const restoreBackup: (req: Request, res: Response) => Promise<void>;
export declare const deleteBackup: (req: Request, res: Response) => Promise<void>;
export declare const verifyPin: (req: Request, res: Response) => Promise<Response<any, Record<string, any>>>;
//# sourceMappingURL=backup.controller.d.ts.map