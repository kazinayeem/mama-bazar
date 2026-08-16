import { Request, Response } from "express";
export declare const register: (req: Request, res: Response) => Promise<void>;
export declare const login: (req: Request, res: Response) => Promise<void>;
export declare const devLogin: (req: Request, res: Response) => Promise<void>;
export declare const createAdmin: (req: Request, res: Response) => Promise<void>;
export declare const requestPasswordReset: (req: Request, res: Response) => Promise<void>;
export declare const resetPassword: (req: Request, res: Response) => Promise<void>;
export declare const changePassword: (req: Request, res: Response) => Promise<void>;
export declare const getProfile: (req: Request, res: Response) => Promise<void>;
export declare const updateProfile: (req: Request, res: Response) => Promise<void>;
export declare const getOrderHistory: (req: Request, res: Response) => Promise<void>;
export declare const getAddresses: (req: Request, res: Response) => Promise<void>;
export declare const createAddress: (req: Request, res: Response) => Promise<void>;
export declare const updateAddress: (req: Request, res: Response) => Promise<void>;
export declare const deleteAddress: (req: Request, res: Response) => Promise<void>;
export declare const getAll: (req: Request, res: Response) => Promise<void>;
export declare const remove: (req: Request, res: Response) => Promise<void>;
//# sourceMappingURL=user.controller.d.ts.map