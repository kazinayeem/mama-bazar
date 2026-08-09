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
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.getSubscribers = exports.subscribeNewsletter = exports.resetConfig = exports.saveConfig = exports.getConfig = exports.getHomepage = void 0;
const jsonwebtoken_1 = __importDefault(require("jsonwebtoken"));
const env_1 = require("../../config/env");
const homepageService = __importStar(require("./homepage.service"));
const getOptionalUserId = (req) => {
    const authHeader = req.headers.authorization;
    if (!authHeader || !authHeader.startsWith("Bearer "))
        return null;
    try {
        const decoded = jsonwebtoken_1.default.verify(authHeader.split(" ")[1], env_1.env.JWT_SECRET);
        return decoded?.id ? Number(decoded.id) : null;
    }
    catch {
        return null;
    }
};
const getHomepage = async (req, res) => {
    const data = await homepageService.getHomepage(getOptionalUserId(req));
    res.json({ success: true, data });
};
exports.getHomepage = getHomepage;
const getConfig = async (req, res) => {
    const data = await homepageService.getConfig();
    res.json({ success: true, data });
};
exports.getConfig = getConfig;
const saveConfig = async (req, res) => {
    const data = await homepageService.saveConfig(req.body);
    res.json({ success: true, data });
};
exports.saveConfig = saveConfig;
const resetConfig = async (req, res) => {
    const data = await homepageService.resetConfig();
    res.json({ success: true, data });
};
exports.resetConfig = resetConfig;
const subscribeNewsletter = async (req, res) => {
    const { email, source } = req.body || {};
    const data = await homepageService.subscribeNewsletter(email, source);
    res.status(data.alreadySubscribed ? 200 : 201).json({ success: true, data });
};
exports.subscribeNewsletter = subscribeNewsletter;
const getSubscribers = async (req, res) => {
    const data = await homepageService.getSubscribers();
    res.json({ success: true, data });
};
exports.getSubscribers = getSubscribers;
//# sourceMappingURL=homepage.controller.js.map