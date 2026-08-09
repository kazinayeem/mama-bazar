import { eq } from "drizzle-orm";
import { db } from "../src/config/db";
import { users } from "../src/config/schema";
import { seedAll } from "./seed-engine";
import { DEV_ADMIN_PHONE } from "./seed-data";

async function main() {
  if (process.env.NODE_ENV === "production") {
    throw new Error("Seeding is disabled in production");
  }
  const existing = await db.select({ id: users.id }).from(users).where(eq(users.phone, DEV_ADMIN_PHONE)).limit(1);
  if (existing[0]) {
    console.log("Database is already seeded. Run `npm run db:reset` to wipe and rebuild the development database.");
    process.exit(0);
  }
  await seedAll();
  process.exit(0);
}

main().catch((err) => {
  console.error("SEED:", err instanceof Error ? err.message : err);
  process.exit(1);
});