"use strict";
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    var desc = Object.getOwnPropertyDescriptor(m, k);
    if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
    }
    Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __importStar = (this && this.__importStar) || (function () {
    var ownKeys = function(o) {
        ownKeys = Object.getOwnPropertyNames || function (o) {
            var ar = [];
            for (var k in o) if (Object.prototype.hasOwnProperty.call(o, k)) ar[ar.length] = k;
            return ar;
        };
        return ownKeys(o);
    };
    return function (mod) {
        if (mod && mod.__esModule) return mod;
        var result = {};
        if (mod != null) for (var k = ownKeys(mod), i = 0; i < k.length; i++) if (k[i] !== "default") __createBinding(result, mod, k[i]);
        __setModuleDefault(result, mod);
        return result;
    };
})();
Object.defineProperty(exports, "__esModule", { value: true });
exports.getLogs = exports.remove = exports.update = exports.create = exports.getById = exports.getAll = exports.getConfig = void 0;
const trackingService = __importStar(require("./tracking.service"));
const AppError_1 = require("../../utils/AppError");
const TYPES_NEEDING_PIXEL_ID = ["google_tag_manager", "google_analytics", "facebook_pixel", "tiktok_pixel"];
const DEFAULT_LOG_LIMIT = 50;
const getConfig = async (req, res) => {
    const config = await trackingService.getTrackingConfig();
    const { facebookAccessToken, facebookTestEventCode, ...publicConfig } = config;
    res.json({ success: true, data: publicConfig });
};
exports.getConfig = getConfig;
const getAll = async (req, res) => {
    const data = await trackingService.getAll();
    res.json({ success: true, data });
};
exports.getAll = getAll;
const getById = async (req, res) => {
    const data = await trackingService.getById(Number(req.params.id));
    if (!data)
        throw new AppError_1.AppError(404, "Integration not found");
    res.json({ success: true, data });
};
exports.getById = getById;
const create = async (req, res) => {
    const { name, type, pixelId, scriptCode, accessToken, testEventCode, status } = req.body;
    if (TYPES_NEEDING_PIXEL_ID.includes(type) && !pixelId) {
        throw new AppError_1.AppError(400, "Pixel/Measurement ID is required");
    }
    if (type === "custom_script" && !scriptCode) {
        throw new AppError_1.AppError(400, "Script code is required");
    }
    if (type === "facebook_conversion_api" && !accessToken) {
        throw new AppError_1.AppError(400, "Access token is required");
    }
    const data = await trackingService.create({ name, type, pixelId, scriptCode, accessToken, testEventCode, status });
    res.status(201).json({ success: true, data });
};
exports.create = create;
const update = async (req, res) => {
    const data = await trackingService.update(Number(req.params.id), req.body);
    res.json({ success: true, data });
};
exports.update = update;
const remove = async (req, res) => {
    await trackingService.remove(Number(req.params.id));
    res.json({ success: true, message: "Integration deleted" });
};
exports.remove = remove;
const getLogs = async (req, res) => {
    const data = await trackingService.getRecentLogs(Number(req.query.limit) || DEFAULT_LOG_LIMIT);
    res.json({ success: true, data });
};
exports.getLogs = getLogs;
//# sourceMappingURL=tracking.controller.js.map