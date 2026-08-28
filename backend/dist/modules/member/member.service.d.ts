export interface CreateMemberInput {
    name: string;
    phone: string;
    email?: string;
    password: string;
    role: string;
    status?: "active" | "inactive";
    permissions?: string[];
}
export interface UpdateMemberInput {
    name?: string;
    phone?: string;
    email?: string;
    password?: string;
    role?: string;
    status?: "active" | "inactive";
    permissions?: string[];
}
export declare const getRolesAndPermissions: () => Promise<{
    permissions: import("../../config/initRbac").PermissionDefinition[];
    roles: {
        name: string;
        displayName: string;
        description: string;
        permissions: string[];
    }[];
}>;
export declare const listMembers: () => Promise<{
    id: number;
    name: string;
    phone: string;
    email: string | null;
    role: "admin" | "manager" | "user";
    customRole: string;
    status: "active" | "inactive";
    permissions: string[];
    lastLoginAt: Date | null;
    createdAt: Date;
}[]>;
export declare const countActiveSuperAdmins: (excludeId?: number) => Promise<number>;
export declare const createMember: (input: CreateMemberInput, actor?: {
    id?: number;
    name?: string;
    email?: string;
    ip?: string;
    userAgent?: string;
}) => Promise<{
    id: number;
    name: string;
    phone: string;
    email: string | undefined;
    role: string;
    customRole: string;
    status: "active" | "inactive";
    permissions: string[];
}>;
export declare const updateMember: (id: number, input: UpdateMemberInput, actor?: {
    id?: number;
    name?: string;
    email?: string;
    ip?: string;
    userAgent?: string;
}) => Promise<{
    id: number;
    name: string;
    phone: string;
    email: string | null;
    role: "admin" | "manager" | "user";
    customRole: string;
    status: "active" | "inactive";
    permissions: string[];
    lastLoginAt: Date | null;
    createdAt: Date;
}>;
export declare const deleteMember: (id: number, actor?: {
    id?: number;
    name?: string;
    email?: string;
    ip?: string;
    userAgent?: string;
}) => Promise<{
    success: boolean;
}>;
//# sourceMappingURL=member.service.d.ts.map