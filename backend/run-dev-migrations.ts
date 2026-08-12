import dotenv from "dotenv";
dotenv.config();

const url = new URL(process.env.DATABASE_URL!);
url.port = "4000";
process.env.DATABASE_URL = url.toString();

const main = async () => {
  await import("./src/migrate-catalog");
  if (process.exitCode) {
    console.error("✗ migrate-catalog failed; aborting before masterdata");
    return;
  }
  await import("./src/migrate-masterdata");
};

main();
