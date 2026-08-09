import { z } from "zod";

export const createTrackingSchema = z.object({
  body: z.object({
    name: z.string().min(1, "Name is required"),
    type: z.string().min(1, "Type is required"),
    pixelId: z.string().optional(),
    scriptCode: z.string().optional(),
    accessToken: z.string().optional(),
    testEventCode: z.string().optional(),
    status: z.enum(["active", "inactive"]).optional(),
  }),
});

export const updateTrackingSchema = z.object({
  params: z.object({ id: z.string() }),
  body: z.object({
    name: z.string().min(1).optional(),
    type: z.string().optional(),
    pixelId: z.string().optional(),
    scriptCode: z.string().optional(),
    accessToken: z.string().optional(),
    testEventCode: z.string().optional(),
    status: z.enum(["active", "inactive"]).optional(),
  }),
});

export const trackingIdSchema = z.object({
  params: z.object({ id: z.string() }),
});
