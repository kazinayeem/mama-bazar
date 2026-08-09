export interface IUser {
    id: number;
    name: string;
    password: string;
    phone: string;
    shippingArea?: string | null;
    shippingAddress?: string | null;
    role: "admin" | "manager" | "user";
    resetTokenHash?: string | null;
    resetTokenExpiresAt?: Date | null;
    createdAt: Date;
}
export interface CreateUserInput {
    name: string;
    phone: string;
    password: string;
    role?: "admin" | "manager" | "user";
}
export interface LoginInput {
    phone: string;
    password: string;
}
export interface PasswordResetRequestInput {
    phone: string;
}
export interface PasswordResetInput {
    token: string;
    newPassword: string;
}
export interface ChangePasswordInput {
    oldPassword: string;
    newPassword: string;
}
export interface UpdateProfileInput {
    name?: string;
    phone?: string;
    shippingArea?: string;
    shippingAddress?: string;
}
export interface UserAddressInput {
    recipientName: string;
    phone: string;
    shippingArea: string;
    address: string;
    isDefault?: boolean;
}
export interface UpdateUserAddressInput {
    recipientName?: string;
    phone?: string;
    shippingArea?: string;
    address?: string;
    isDefault?: boolean;
}
//# sourceMappingURL=user.interface.d.ts.map