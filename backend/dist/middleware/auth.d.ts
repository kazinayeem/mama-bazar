import { Request, Response, NextFunction } from "express";
export interface AuthenticatedUser {
    id: number;
    phone: string;
    role: string;
    customRole?: string;
    name?: string;
    email?: string;
    permissions: string[];
}
export declare const invalidateUserPermissionCache: (userId: number) => void;
export declare const resolveUserPermissions: (userId: number, role: string, customRoleFromToken?: string) => Promise<{
    permissions: string[];
    customRole: string;
}>;
export declare const authMiddleware: (req: Request, res: Response, next: NextFunction) => Promise<Response<any, Record<string, any>> | undefined>;
export declare const hasPermission: (user: AuthenticatedUser | undefined, permission: string) => boolean;
export declare const requirePermission: (permission: string) => (req: Request, res: Response, next: NextFunction) => Response<any, Record<string, any>> | undefined;
export declare const requireAnyPermission: (...permissions: string[]) => (req: Request, res: Response, next: NextFunction) => Response<any, Record<string, any>> | undefined;
export declare const adminOnly: (req: Request, res: Response, next: NextFunction) => void | Response<any, Record<string, any>>;
//# sourceMappingURL=auth.d.ts.map