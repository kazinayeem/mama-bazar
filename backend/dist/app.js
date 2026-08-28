"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = __importDefault(require("express"));
const cors_1 = __importDefault(require("cors"));
const helmet_1 = __importDefault(require("helmet"));
const express_rate_limit_1 = __importDefault(require("express-rate-limit"));
const compression_1 = __importDefault(require("compression"));
const path_1 = __importDefault(require("path"));
const env_1 = require("./config/env");
const category_route_1 = __importDefault(require("./modules/category/category.route"));
const product_route_1 = __importDefault(require("./modules/product/product.route"));
const order_route_1 = __importDefault(require("./modules/order/order.route"));
const coupon_route_1 = __importDefault(require("./modules/coupon/coupon.route"));
const user_route_1 = __importDefault(require("./modules/user/user.route"));
const settings_route_1 = __importDefault(require("./modules/settings/settings.route"));
const tracking_route_1 = __importDefault(require("./modules/tracking/tracking.route"));
const analytics_route_1 = __importDefault(require("./modules/analytics/analytics.route"));
const brand_route_1 = __importDefault(require("./modules/brand/brand.route"));
const banner_route_1 = __importDefault(require("./modules/banner/banner.route"));
const media_route_1 = __importDefault(require("./modules/media/media.route"));
const admin_route_1 = __importDefault(require("./modules/admin/admin.route"));
const catalog_route_1 = require("./modules/catalog/catalog.route");
const shipping_route_1 = __importDefault(require("./modules/shipping/shipping.route"));
const payment_route_1 = __importDefault(require("./modules/payment/payment.route"));
const checkout_notice_route_1 = __importDefault(require("./modules/checkout-notice/checkout-notice.route"));
const upload_route_1 = __importDefault(require("./modules/upload/upload.route"));
const review_route_1 = __importDefault(require("./modules/review/review.route"));
const homepage_route_1 = __importDefault(require("./modules/homepage/homepage.route"));
const pages_route_1 = __importDefault(require("./modules/pages/pages.route"));
const expense_route_1 = __importDefault(require("./modules/expense/expense.route"));
const cost_route_1 = __importDefault(require("./modules/cost/cost.route"));
const booking_route_1 = __importDefault(require("./modules/booking/booking.route"));
const rental_route_1 = __importDefault(require("./modules/rental/rental.route"));
const memo_route_1 = __importDefault(require("./modules/memo/memo.route"));
const member_route_1 = __importDefault(require("./modules/member/member.route"));
const backup_route_1 = __importDefault(require("./modules/backup/backup.route"));
const errorHandler_1 = require("./middleware/errorHandler");
const app = (0, express_1.default)();
// Security headers
app.use((0, helmet_1.default)());
// Gzip/Brotli compression for all responses (JSON APIs shrink ~70-80%)
app.use((0, compression_1.default)());
// CORS — dynamically allow configured frontend origin, localhost, and all vercel.app preview/production domains
const allowedOrigins = [
    env_1.env.FRONTEND_URL,
    "http://localhost:5173",
    "http://localhost:3000",
    "http://localhost:4173",
    "https://mamabazar.vercel.app",
    "https://mama-bazar.vercel.app",
    "https://ghorerbazar-five.vercel.app",
];
app.use((0, cors_1.default)({
    origin: (origin, callback) => {
        // Allow requests with no origin (e.g. mobile apps, curl, server-to-server)
        if (!origin)
            return callback(null, true);
        if (allowedOrigins.includes(origin) ||
            origin.endsWith(".vercel.app") ||
            origin.includes("localhost") ||
            origin.includes("127.0.0.1")) {
            return callback(null, true);
        }
        return callback(null, true);
    },
    credentials: true,
    methods: ["GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS"],
    allowedHeaders: ["Content-Type", "Authorization", "X-Requested-With", "Accept"],
}));
// Body parsing with size limits
app.use(express_1.default.json({ limit: "1mb" }));
app.use(express_1.default.urlencoded({ extended: true, limit: "1mb" }));
// Global rate limiter — 100 requests per 15 minutes per IP
//app.use(rateLimit({ windowMs: 5000 * 60 * 1000, max: 100, standardHeaders: true, legacyHeaders: false }));
// Auth rate limiter — stricter for login only
const authLimiter = (0, express_rate_limit_1.default)({
    windowMs: 15 * 60 * 1000,
    max: 20,
    standardHeaders: true,
    legacyHeaders: false,
    message: { success: false, message: "Too many login attempts, please try again later." },
});
// Static uploads
app.use("/uploads", express_1.default.static(path_1.default.join(process.cwd(), env_1.env.UPLOAD_DIR)));
// API Routes
app.use("/api/categories", category_route_1.default);
app.use("/api/products", product_route_1.default);
app.use("/api/order", order_route_1.default);
app.use("/api/coupons", coupon_route_1.default);
app.use("/api/users/login", authLimiter);
app.use("/api/users", user_route_1.default);
app.use("/api/settings", settings_route_1.default);
app.use("/api/tracking", tracking_route_1.default);
app.use("/api/analytics", analytics_route_1.default);
app.use("/api/brands", brand_route_1.default);
app.use("/api/banners", banner_route_1.default);
app.use("/api/media", media_route_1.default);
app.use("/api/admin", admin_route_1.default);
app.use("/api/colors", catalog_route_1.colorsRouter);
app.use("/api/sizes", catalog_route_1.sizesRouter);
app.use("/api/collections", catalog_route_1.collectionsRouter);
app.use("/api/vendors", catalog_route_1.vendorsRouter);
app.use("/api/suppliers", catalog_route_1.suppliersRouter);
app.use("/api/shipping-methods", shipping_route_1.default);
app.use("/api/payment-methods", payment_route_1.default);
app.use("/api/checkout-notices", checkout_notice_route_1.default);
app.use("/api/uploads", upload_route_1.default);
app.use("/api/reviews", review_route_1.default);
app.use("/api/homepage", homepage_route_1.default);
app.use("/api/pages", pages_route_1.default);
app.use("/api/expenses", expense_route_1.default);
app.use("/api/costs", cost_route_1.default);
app.use("/api/bookings", booking_route_1.default);
app.use("/api/rentals", rental_route_1.default);
app.use("/api/memos", memo_route_1.default);
app.use("/api/members", member_route_1.default);
app.use("/api/backup", backup_route_1.default);
// home
app.get("/", (_req, res) => {
    res.json({ success: true, message: "Welcome to Mamabazar API" });
});
// Health check
app.get("/api/health", (_req, res) => {
    res.json({ success: true, message: "Mamabazar API is running" });
});
// JSON 404 for unknown routes (prevents HTML error pages in API clients)
app.use((req, res, next) => {
    if (req.path.startsWith("/api/")) {
        return res.status(404).json({ success: false, message: `Cannot ${req.method} ${req.path}` });
    }
    next();
});
// Global error handler (must be after all routes)
app.use(errorHandler_1.errorHandler);
exports.default = app;
//# sourceMappingURL=app.js.map