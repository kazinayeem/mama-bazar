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
exports.addHeroSlideByLink = exports.deleteHeroSlide = exports.addHeroSlide = exports.getHeroSlides = exports.set = exports.get = exports.getAll = void 0;
const settingsService = __importStar(require("./settings.service"));
const getAll = async (req, res) => {
    const data = await settingsService.getAll();
    res.json({ success: true, data });
};
exports.getAll = getAll;
const get = async (req, res) => {
    const data = await settingsService.get(req.params.key);
    res.json({ success: true, data });
};
exports.get = get;
const set = async (req, res) => {
    const { key, value } = req.body;
    const data = await settingsService.set(key, value);
    res.json({ success: true, data });
};
exports.set = set;
// Slider management
const SLIDER_KEY = "hero_slides";
const getSlides = () => {
    // Will be fetched async, this is a helper
    return [];
};
const getHeroSlides = async (req, res) => {
    const setting = await settingsService.get(SLIDER_KEY);
    const slides = setting?.value ? JSON.parse(setting.value) : [];
    res.json({ success: true, data: slides });
};
exports.getHeroSlides = getHeroSlides;
const addHeroSlide = async (req, res) => {
    if (!req.file) {
        res.status(400).json({ success: false, message: "Image is required" });
        return;
    }
    // Store an absolute URL so the browser can load it from the frontend origin.
    const origin = `${req.protocol}://${req.get("host") || "localhost:5000"}`;
    const imageUrl = `${origin}/uploads/${req.file.filename}`;
    const setting = await settingsService.get(SLIDER_KEY);
    const slides = setting?.value ? JSON.parse(setting.value) : [];
    slides.push(imageUrl);
    await settingsService.set(SLIDER_KEY, JSON.stringify(slides));
    res.json({ success: true, data: slides });
};
exports.addHeroSlide = addHeroSlide;
const deleteHeroSlide = async (req, res) => {
    const { index } = req.params;
    const setting = await settingsService.get(SLIDER_KEY);
    const slides = setting?.value ? JSON.parse(setting.value) : [];
    const idx = parseInt(index, 10);
    if (idx < 0 || idx >= slides.length) {
        res.status(400).json({ success: false, message: "Invalid slide index" });
        return;
    }
    slides.splice(idx, 1);
    await settingsService.set(SLIDER_KEY, JSON.stringify(slides));
    res.json({ success: true, data: slides });
};
exports.deleteHeroSlide = deleteHeroSlide;
const addHeroSlideByLink = async (req, res) => {
    const { link } = req.body;
    if (!link || typeof link !== "string") {
        res.status(400).json({ success: false, message: "Image link is required" });
        return;
    }
    const setting = await settingsService.get(SLIDER_KEY);
    const slides = setting?.value ? JSON.parse(setting.value) : [];
    slides.push(link);
    await settingsService.set(SLIDER_KEY, JSON.stringify(slides));
    res.json({ success: true, data: slides });
};
exports.addHeroSlideByLink = addHeroSlideByLink;
//# sourceMappingURL=settings.controller.js.map