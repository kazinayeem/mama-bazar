import { Request, Response, NextFunction } from "express";
export declare const authMiddleware: (req: Request, res: Response, next: NextFunction) => Response<any, Record<string, any>> | undefined;
export declare const adminOnly: (req: Request, res: Response, next: NextFunction) => Response<any, Record<string, any>> | undefined;
export type ManagementPermission = "expenses.view" | "expenses.create" | "expenses.update" | "expenses.delete" | "costs.view" | "costs.create" | "costs.update" | "costs.delete" | "bookings.view" | "bookings.create" | "bookings.update" | "bookings.delete" | "rentals.view" | "rentals.create" | "rentals.update" | "rentals.delete" | "memos.view" | "memos.upload" | "memos.delete" | "reports.view" | "reports.export";
export declare const hasManagementPermission: (role: string | undefined, permission: ManagementPermission) => boolean;
export declare const requirePermission: (permission: ManagementPermission) => (req: Request, res: Response, next: NextFunction) => Response<any, Record<string, any>> | undefined;
//# sourceMappingURL=auth.d.ts.map