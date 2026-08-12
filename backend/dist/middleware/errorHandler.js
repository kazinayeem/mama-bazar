"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.errorHandler = void 0;
const AppError_1 = require("../utils/AppError");
const zod_1 = require("zod");
const errorHandler = (err, _req, res, _next) => {
    // Zod validation errors
    if (err instanceof zod_1.ZodError) {
        const messages = err.errors.map((e) => e.message);
        res.status(400).json({ success: false, message: messages.join(", ") });
        return;
    }
    // Known operational errors
    if (err instanceof AppError_1.AppError) {
        const body = {
            success: false,
            message: err.message,
        };
        if (err.data && typeof err.data === "object" && "errors" in err.data) {
            body.errors = err.data.errors;
        }
        else if (err.data !== undefined) {
            body.data = err.data;
        }
        res.status(err.statusCode).json(body);
        return;
    }
    // Unknown / unexpected errors — hide details in production
    console.error("[Error]", err);
    const message = process.env.NODE_ENV === "production"
        ? "Internal Server Error"
        : err.message || "Internal Server Error";
    res.status(500).json({ success: false, message });
};
exports.errorHandler = errorHandler;
//# sourceMappingURL=errorHandler.js.map