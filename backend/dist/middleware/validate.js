"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.validate = void 0;
const zod_1 = require("zod");
const AppError_1 = require("../utils/AppError");
const formatIssues = (err) => {
    const errors = {};
    for (const issue of err.issues) {
        const path = issue.path.filter((p) => p !== "body").join(".");
        const key = path || "_";
        const existing = errors[key];
        errors[key] = existing ? `${existing} / ${issue.message}` : issue.message;
    }
    return errors;
};
const validate = (schema) => (req, _res, next) => {
    try {
        schema.parse({
            body: req.body,
            query: req.query,
            params: req.params,
        });
        next();
    }
    catch (err) {
        if (err instanceof zod_1.ZodError) {
            next(new AppError_1.AppError(400, "Validation failed", { errors: formatIssues(err) }));
            return;
        }
        next(err);
    }
};
exports.validate = validate;
//# sourceMappingURL=validate.js.map