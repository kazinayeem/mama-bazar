import { ensureSuperAdmin } from "./db-seed";

const guard = () => {
  if (process.env.NODE_ENV === "production") {
    throw new Error("DATABASE RESET IS DISABLED IN PRODUCTION");
  }
  if (!process.env.NODE_ENV && process.env.SEED_ALLOW_RESET !== "true") {
    throw new Error("NODE_ENV is not set. Set NODE_ENV=development or run with SEED_ALLOW_RESET=true to reset the database.");
  }
};

async function main() {
  guard();
  console.log("========================================");
  console.log("DATABASE RESET");
  console.log("========================================");
  console.log("Destructive wiping has been removed. Ensuring Super Admin exists...");
  const result = await ensureSuperAdmin();
  console.log(result === "created" ? "Super Admin created" : "Super Admin already exists");
  process.exit(0);
}

main().catch((err) => {
  console.error("\nRESET FAILED:");
  console.error(err instanceof Error ? err.message : err);
  process.exit(1);
});
