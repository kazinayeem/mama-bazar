import express from "express";
import cors from "cors";
import helmet from "helmet";
import rateLimit from "express-rate-limit";
import compression from "compression";
import path from "path";
import { env } from "./config/env";

import categoryRoutes from "./modules/category/category.route";
import productRoutes from "./modules/product/product.route";
import orderRoutes from "./modules/order/order.route";
import couponRoutes from "./modules/coupon/coupon.route";
import userRoutes from "./modules/user/user.route";
import settingsRoutes from "./modules/settings/settings.route";
import trackingRoutes from "./modules/tracking/tracking.route";
import analyticsRoutes from "./modules/analytics/analytics.route";
import brandRoutes from "./modules/brand/brand.route";
import bannerRoutes from "./modules/banner/banner.route";
import mediaRoutes from "./modules/media/media.route";
import adminRoutes from "./modules/admin/admin.route";
import { colorsRouter, sizesRouter, collectionsRouter, vendorsRouter, suppliersRouter } from "./modules/catalog/catalog.route";
import shippingRoutes from "./modules/shipping/shipping.route";
import paymentRoutes from "./modules/payment/payment.route";
import checkoutNoticeRoutes from "./modules/checkout-notice/checkout-notice.route";
import uploadRoutes from "./modules/upload/upload.route";
import reviewRoutes from "./modules/review/review.route";
import homepageRoutes from "./modules/homepage/homepage.route";
import pagesRoutes from "./modules/pages/pages.route";
import expenseRoutes from "./modules/expense/expense.route";
import costRoutes from "./modules/cost/cost.route";
import bookingRoutes from "./modules/booking/booking.route";
import rentalRoutes from "./modules/rental/rental.route";
import memoRoutes from "./modules/memo/memo.route";
import memberRoutes from "./modules/member/member.route";
import backupRoutes from "./modules/backup/backup.route";
import { errorHandler } from "./middleware/errorHandler";

const app = express();

// Security headers
app.use(helmet());

// Gzip/Brotli compression for all responses (JSON APIs shrink ~70-80%)
app.use(compression());

// CORS — dynamically allow configured frontend origin, localhost, and all vercel.app preview/production domains
const allowedOrigins = [
  env.FRONTEND_URL,
  "http://localhost:5173",
  "http://localhost:3000",
  "http://localhost:4173",
  "https://mamabazar.vercel.app",
  "https://mama-bazar.vercel.app",
  "https://ghorerbazar-five.vercel.app",
];

app.use(
  cors({
    origin: (origin, callback) => {
      // Allow requests with no origin (e.g. mobile apps, curl, server-to-server)
      if (!origin) return callback(null, true);
      if (
        allowedOrigins.includes(origin) ||
        origin.endsWith(".vercel.app") ||
        origin.includes("localhost") ||
        origin.includes("127.0.0.1")
      ) {
        return callback(null, true);
      }
      return callback(null, true);
    },
    credentials: true,
    methods: ["GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS"],
    allowedHeaders: ["Content-Type", "Authorization", "X-Requested-With", "Accept"],
  })
);

// Body parsing with size limits
app.use(express.json({ limit: "1mb" }));
app.use(express.urlencoded({ extended: true, limit: "1mb" }));

// Global rate limiter — 100 requests per 15 minutes per IP
//app.use(rateLimit({ windowMs: 5000 * 60 * 1000, max: 100, standardHeaders: true, legacyHeaders: false }));

// Auth rate limiter — stricter for login only
const authLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 20,
  standardHeaders: true,
  legacyHeaders: false,
  message: { success: false, message: "Too many login attempts, please try again later." },
});

// Static uploads
app.use("/uploads", express.static(path.join(process.cwd(), env.UPLOAD_DIR)));

// API Routes
app.use("/api/categories", categoryRoutes);
app.use("/api/products", productRoutes);
app.use("/api/order", orderRoutes);
app.use("/api/coupons", couponRoutes);
app.use("/api/users/login", authLimiter);
app.use("/api/users", userRoutes);
app.use("/api/settings", settingsRoutes);
app.use("/api/tracking", trackingRoutes);
app.use("/api/analytics", analyticsRoutes);
app.use("/api/brands", brandRoutes);
app.use("/api/banners", bannerRoutes);
app.use("/api/media", mediaRoutes);
app.use("/api/admin", adminRoutes);
app.use("/api/colors", colorsRouter);
app.use("/api/sizes", sizesRouter);
app.use("/api/collections", collectionsRouter);
app.use("/api/vendors", vendorsRouter);
app.use("/api/suppliers", suppliersRouter);
app.use("/api/shipping-methods", shippingRoutes);
app.use("/api/payment-methods", paymentRoutes);
app.use("/api/checkout-notices", checkoutNoticeRoutes);
app.use("/api/uploads", uploadRoutes);
app.use("/api/reviews", reviewRoutes);
app.use("/api/homepage", homepageRoutes);
app.use("/api/pages", pagesRoutes);
app.use("/api/expenses", expenseRoutes);
app.use("/api/costs", costRoutes);
app.use("/api/bookings", bookingRoutes);
app.use("/api/rentals", rentalRoutes);
app.use("/api/memos", memoRoutes);
app.use("/api/members", memberRoutes);
app.use("/api/backup", backupRoutes);
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
app.use(errorHandler);

export default app;
