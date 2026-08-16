"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.createAdminSchema = exports.addressIdSchema = exports.updateAddressSchema = exports.createAddressSchema = exports.updateProfileSchema = exports.changePasswordSchema = exports.passwordResetSchema = exports.passwordResetRequestSchema = exports.loginSchema = exports.registerSchema = void 0;
const zod_1 = require("zod");
// Phone validation regex for Bangladesh numbers: 01XXXXXXXXX or +880XXXXXXXXX
const phoneRegex = /^(\+880|0)[1-9]\d{9}$/;
exports.registerSchema = zod_1.z.object({
    body: zod_1.z.object({
        name: zod_1.z.string().min(1, "Name is required"),
        phone: zod_1.z.string().regex(phoneRegex, "Invalid phone number. Use format: 01XXXXXXXXX or +880XXXXXXXXX"),
        password: zod_1.z.string().min(6, "Password must be at least 6 characters"),
    }),
});
exports.loginSchema = zod_1.z.object({
    body: zod_1.z.object({
        phone: zod_1.z.string().regex(phoneRegex, "Invalid phone number. Use format: 01XXXXXXXXX or +880XXXXXXXXX"),
        password: zod_1.z.string().min(1, "Password is required"),
    }),
});
exports.passwordResetRequestSchema = zod_1.z.object({
    body: zod_1.z.object({
        phone: zod_1.z.string().regex(phoneRegex, "Invalid phone number. Use format: 01XXXXXXXXX or +880XXXXXXXXX"),
    }),
});
exports.passwordResetSchema = zod_1.z.object({
    body: zod_1.z.object({
        token: zod_1.z.string().min(1, "Token is required"),
        newPassword: zod_1.z.string().min(6, "Password must be at least 6 characters"),
    }),
});
exports.changePasswordSchema = zod_1.z.object({
    body: zod_1.z.object({
        oldPassword: zod_1.z.string().min(1, "Old password is required"),
        newPassword: zod_1.z.string().min(6, "New password must be at least 6 characters"),
    }),
});
exports.updateProfileSchema = zod_1.z.object({
    body: zod_1.z.object({
        name: zod_1.z.string().min(1, "Name is required").optional(),
        phone: zod_1.z.string().regex(phoneRegex, "Invalid phone number. Use format: 01XXXXXXXXX or +880XXXXXXXXX").optional(),
        shippingArea: zod_1.z.string().min(1, "Shipping area is required").optional(),
        shippingAddress: zod_1.z.string().min(1, "Shipping address is required").optional(),
    }),
});
exports.createAddressSchema = zod_1.z.object({
    body: zod_1.z.object({
        recipientName: zod_1.z.string().min(1, "Recipient name is required"),
        phone: zod_1.z.string().regex(phoneRegex, "Invalid phone number. Use format: 01XXXXXXXXX or +880XXXXXXXXX"),
        shippingArea: zod_1.z.string().min(1, "Shipping area is required"),
        address: zod_1.z.string().min(1, "Address is required"),
        isDefault: zod_1.z.boolean().optional(),
    }),
});
exports.updateAddressSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
    body: zod_1.z.object({
        recipientName: zod_1.z.string().min(1, "Recipient name is required").optional(),
        phone: zod_1.z.string().regex(phoneRegex, "Invalid phone number. Use format: 01XXXXXXXXX or +880XXXXXXXXX").optional(),
        shippingArea: zod_1.z.string().min(1, "Shipping area is required").optional(),
        address: zod_1.z.string().min(1, "Address is required").optional(),
        isDefault: zod_1.z.boolean().optional(),
    }),
});
exports.addressIdSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
});
exports.createAdminSchema = zod_1.z.object({
    body: zod_1.z
        .object({
        name: zod_1.z.string().min(1, "Name is required"),
        email: zod_1.z.string().min(1, "Email is required").email("Invalid email address"),
        phone: zod_1.z.string().regex(phoneRegex, "Invalid phone number. Use format: 01XXXXXXXXX or +880XXXXXXXXX"),
        password: zod_1.z.string().min(6, "Password must be at least 6 characters"),
        confirmPassword: zod_1.z.string(),
        role: zod_1.z.enum(["admin", "manager"]).default("admin"),
    })
        .refine((data) => data.password === data.confirmPassword, {
        message: "Passwords do not match",
        path: ["confirmPassword"],
    }),
});
//# sourceMappingURL=user.schema.js.map