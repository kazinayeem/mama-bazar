import { Request, Response } from "express";
import { AppError } from "../../utils/AppError";
import { uploadBuffer } from "../../utils/cloud";

const MAX_SIZE = 5 * 1024 * 1024;
const ALLOWED_MIME = ["image/jpeg", "image/png", "image/webp", "image/heic", "image/heif"];

export const uploadPaymentProof = async (req: Request, res: Response) => {
  const file = req.file;
  if (!file) throw new AppError(400, "No file uploaded. Attach a screenshot of your payment.");
  if (!ALLOWED_MIME.includes(file.mimetype)) {
    throw new AppError(400, "Only JPG, PNG, WebP or HEIC images are allowed");
  }
  if (file.size > MAX_SIZE) {
    throw new AppError(400, "File too large. Maximum size is 5MB");
  }

  const result = await uploadBuffer(file.buffer, {
    folder: "payment-proofs",
    filename: file.originalname,
    mimeType: file.mimetype,
  });

  res.status(201).json({ success: true, data: result });
};
