export class AppError extends Error {
  public readonly statusCode: number;
  public readonly data?: unknown;

  constructor(statusCode: number, message: string, data?: unknown) {
    super(message);
    this.statusCode = statusCode;
    this.data = data;
    Object.setPrototypeOf(this, AppError.prototype);
  }
}
