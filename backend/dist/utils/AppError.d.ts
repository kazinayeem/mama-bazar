export declare class AppError extends Error {
    readonly statusCode: number;
    readonly data?: unknown;
    constructor(statusCode: number, message: string, data?: unknown);
}
//# sourceMappingURL=AppError.d.ts.map