"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.validate = void 0;
const validate = (schema) => (req, _res, next) => {
    schema.parse({
        body: req.body,
        query: req.query,
        params: req.params,
    });
    next();
};
exports.validate = validate;
//# sourceMappingURL=validate.js.map