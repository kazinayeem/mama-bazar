import { Request, Response, NextFunction } from "express";
import { AppError } from "../utils/AppError";
import { ZodError } from "zod";

export const errorHandler = (
  err: Error,
  _req: Request,
  res: Response,
  _next: NextFunction
) => {
  // Zod validation errors
  if (err instanceof ZodError) {
    const messages = err.errors.map((e) => e.message);
    res.status(400).json({ success: false, message: messages.join(", ") });
    return;
  }

  // Known operational errors
  if (err instanceof AppError) {
    const body: Record<string, unknown> = {
      success: false,
      message: err.message,
    };
    if (err.data && typeof err.data === "object" && "errors" in err.data) {
      body.errors = (err.data as { errors: unknown }).errors;
    } else if (err.data !== undefined) {
      body.data = err.data;
    }
    res.status(err.statusCode).json(body);
    return;
  }

  // Unknown / unexpected errors — hide details in production
  console.error("[Error]", err);
  const message =
    process.env.NODE_ENV === "production"
      ? "Internal Server Error"
      : err.message || "Internal Server Error";

  res.status(500).json({ success: false, message });
};
