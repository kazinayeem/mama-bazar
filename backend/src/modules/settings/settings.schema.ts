import { z } from "zod";

export const setSettingSchema = z.object({
  body: z.object({
    key: z.string().min(1, "Key is required"),
    value: z.any(),
  }),
});

export const getSettingSchema = z.object({
  params: z.object({ key: z.string().min(1) }),
});
