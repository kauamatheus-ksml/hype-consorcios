import { NextResponse } from "next/server";

import { testDbConnection } from "@/lib/db-direct";

export const runtime = "nodejs";

const requiredEnvNames = [
  "DB_HOST",
  "DB_NAME",
  "DB_USER",
  "DB_PASS",
  "JWT_SECRET",
  "NEXT_PUBLIC_SUPABASE_URL",
  "NEXT_PUBLIC_SUPABASE_ANON_KEY",
  "SUPABASE_SERVICE_ROLE_KEY",
  ...(process.env.VERCEL ? ["SITE_ASSETS_BUCKET"] : []),
];

export async function GET() {
  const startedAt = Date.now();
  const missingEnv = requiredEnvNames.filter((name) => !process.env[name]);
  let database = false;
  let databaseError: string | null = null;

  if (missingEnv.some((name) => name.startsWith("DB_"))) {
    databaseError = "Variaveis DB_* ausentes";
  } else {
    try {
      database = await testDbConnection();
    } catch (error) {
      databaseError = error instanceof Error ? error.message : "Falha ao conectar no banco";
    }
  }

  const ok = missingEnv.length === 0 && database;

  return NextResponse.json(
    {
      success: ok,
      checks: {
        database,
        env: missingEnv.length === 0,
        storage: Boolean(process.env.SITE_ASSETS_BUCKET),
      },
      duration_ms: Date.now() - startedAt,
      missing_env: missingEnv,
      node_env: process.env.NODE_ENV ?? null,
      node_version: process.version,
      timestamp: new Date().toISOString(),
      ...(databaseError ? { database_error: databaseError } : {}),
    },
    {
      status: ok ? 200 : 503,
    },
  );
}
