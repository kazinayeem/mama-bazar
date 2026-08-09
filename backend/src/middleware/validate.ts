import { Request, Response, NextFunction } from "express";
import { AnyZodObject, ZodError } from "zod";
import { AppError } from "../utils/AppError";

const formatIssues = (err: ZodError): Record<string, string> => {
  const errors: Record<string, string> = {};
  for (const issue of err.issues) {
    const path = issue.path.filter((p) => p !== "body").join(".");
    const key = path || "_";
    const existing = errors[key];
    errors[key] = existing ? `${existing} / ${issue.message}` : issue.message;
  }
  return errors;
};

export const validate =
  (schema: AnyZodObject) => (req: Request, _res: Response, next: NextFunction) => {
    try {
      schema.parse({
        body: req.body,
        query: req.query,
        params: req.params,
      });
      next();
    } catch (err) {
      if (err instanceof ZodError) {
        next(new AppError(400, "Validation failed", { errors: formatIssues(err) }));
        return;
      }
      next(err);
    }
  };