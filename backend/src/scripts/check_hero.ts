import { db } from "../config/db";
import * as homepageService from "../modules/homepage/homepage.service";
import * as bannerService from "../modules/banner/banner.service";
import * as settingsService from "../modules/settings/settings.service";

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
