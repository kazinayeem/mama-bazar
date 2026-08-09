import { z } from "zod";

// Phone validation regex for Bangladesh numbers: 01XXXXXXXXX or +880XXXXXXXXX
const phoneRegex = /^(\+880|0)[1-9]\d{9}$/;

export const registerSchema = z.object({
  body: z.object({
    name: z.string().min(1, "Name is required"),
    phone: z.string().regex(phoneRegex, "Invalid phone number. Use format: 01XXXXXXXXX or +880XXXXXXXXX"),
    password: z.string().min(6, "Password must be at least 6 characters"),
  }),
});

export const loginSchema = z.object({
  body: z.object({
    phone: z.string().regex(phoneRegex, "Invalid phone number. Use format: 01XXXXXXXXX or +880XXXXXXXXX"),
    password: z.string().min(1, "Password is required"),
  }),
});

export const passwordResetRequestSchema = z.object({
  body: z.object({
    phone: z.string().regex(phoneRegex, "Invalid phone number. Use format: 01XXXXXXXXX or +880XXXXXXXXX"),
  }),
});

export const passwordResetSchema = z.object({
  body: z.object({
    token: z.string().min(1, "Token is required"),
    newPassword: z.string().min(6, "Password must be at least 6 characters"),
  }),
});

export const changePasswordSchema = z.object({
  body: z.object({
    oldPassword: z.string().min(1, "Old password is required"),
    newPassword: z.string().min(6, "New password must be at least 6 characters"),
  }),
});

export const updateProfileSchema = z.object({
  body: z.object({
    name: z.string().min(1, "Name is required").optional(),
    phone: z.string().regex(phoneRegex, "Invalid phone number. Use format: 01XXXXXXXXX or +880XXXXXXXXX").optional(),
    shippingArea: z.string().min(1, "Shipping area is required").optional(),
    shippingAddress: z.string().min(1, "Shipping address is required").optional(),
  }),
});

export const createAddressSchema = z.object({
  body: z.object({
    recipientName: z.string().min(1, "Recipient name is required"),
    phone: z.string().regex(phoneRegex, "Invalid phone number. Use format: 01XXXXXXXXX or +880XXXXXXXXX"),
    shippingArea: z.string().min(1, "Shipping area is required"),
    address: z.string().min(1, "Address is required"),
    isDefault: z.boolean().optional(),
  }),
});

export const updateAddressSchema = z.object({
  params: z.object({ id: z.string() }),
  body: z.object({
    recipientName: z.string().min(1, "Recipient name is required").optional(),
    phone: z.string().regex(phoneRegex, "Invalid phone number. Use format: 01XXXXXXXXX or +880XXXXXXXXX").optional(),
    shippingArea: z.string().min(1, "Shipping area is required").optional(),
    address: z.string().min(1, "Address is required").optional(),
    isDefault: z.boolean().optional(),
  }),
});

export const addressIdSchema = z.object({
  params: z.object({ id: z.string() }),
});
