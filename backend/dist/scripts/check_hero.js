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
const homepageService = __importStar(require("../modules/homepage/homepage.service"));
const bannerService = __importStar(require("../modules/banner/banner.service"));
const settingsService = __importStar(require("../modules/settings/settings.service"));
async function main() {
    console.log("\n1. homepageService.getConfig():");
    const config = await homepageService.getConfig();
    console.log("Config heroSlides count:", config.heroSlides?.length);
    console.log("Config heroSlides:", JSON.stringify(config.heroSlides, null, 2));
    console.log("Config sections:", JSON.stringify(config.sections.map(s => ({ id: s.id, type: s.type, enabled: s.enabled, title: s.title })), null, 2));
    console.log("\n2. bannerService.getAll():");
    const banners = await bannerService.getAll();
    console.log("Banners count:", banners.length);
    console.log("Banners:", JSON.stringify(banners, null, 2));
    console.log("\n3. settingsService.getJSON('hero_slides'):");
    const legacySlides = await settingsService.getJSON("hero_slides", null);
    console.log("Legacy slides:", legacySlides);
    process.exit(0);
}
main().catch(err => { console.error(err); process.exit(1); });
//# sourceMappingURL=check_hero.js.map