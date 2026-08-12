import bcrypt from "bcryptjs";
import { eq } from "drizzle-orm";
import { db } from "../src/config/db";
import { users } from "../src/config/schema";
import { DEV_ADMIN_PHONE, DEV_ADMIN_PASSWORD } from "../src/config/dev-credentials";

const SALT_ROUNDS = 12;

export const ensureSuperAdmin = async (): Promise<"created" | "exists"> => {
  const existing = await db
    .select({ id: users.id })
    .from(users)
    .where(eq(users.phone, DEV_ADMIN_PHONE))
    .limit(1);

  if (existing[0]) return "exists";

  const hashedPassword = await bcrypt.hash(DEV_ADMIN_PASSWORD, SALT_ROUNDS);
  await db.insert(users).values({
    name: "Super Admin",
    phone: DEV_ADMIN_PHONE,
    password: hashedPassword,
    role: "admin",
    status: "active",
  });

  return "created";
};

async function main() {
  if (process.env.NODE_ENV === "production") {
    throw new Error("Seeding is disabled in production");
  }

  console.log("SEED: Starting...");
  const result = await ensureSuperAdmin();
  console.log(result === "created" ? "SEED: Super Admin created successfully" : "SEED: Super Admin already exists");
  console.log(`SEED: Phone: ${DEV_ADMIN_PHONE}`);
  console.log("SEED: Completed successfully");
  process.exit(0);
}

main().catch((err) => {
  console.error("SEED:", err instanceof Error ? err.message : err);
  process.exit(1);
});
