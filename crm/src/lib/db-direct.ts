import "server-only";

import { Pool, type PoolClient, type QueryResult, type QueryResultRow } from "pg";

import { getOptionalEnv, getRequiredEnv } from "@/lib/env";

let pool: Pool | null = null;

const TRANSIENT_DB_RETRY_DELAYS_MS = [150, 400];

export function getPgPool(): Pool {
  if (pool) {
    return pool;
  }

  pool = new Pool({
    allowExitOnIdle: true,
    connectionTimeoutMillis: Number(getOptionalEnv("DB_CONNECTION_TIMEOUT_MS", "10000")),
    host: getRequiredEnv("DB_HOST"),
    idleTimeoutMillis: Number(getOptionalEnv("DB_IDLE_TIMEOUT_MS", "10000")),
    max: Number(getOptionalEnv("DB_POOL_MAX", "1")),
    database: getRequiredEnv("DB_NAME"),
    user: getRequiredEnv("DB_USER"),
    password: getRequiredEnv("DB_PASS"),
    port: Number(getOptionalEnv("DB_PORT", "5432")),
    ssl: {
      rejectUnauthorized: false,
    },
  });

  return pool;
}

export async function dbQuery<T extends QueryResultRow>(
  text: string,
  params: unknown[] = [],
): Promise<QueryResult<T>> {
  return withTransientDbRetry(() => getPgPool().query<T>(text, params));
}

export async function dbTransaction<T>(
  callback: (client: PoolClient) => Promise<T>,
): Promise<T> {
  const client = await getPgPool().connect();

  try {
    await client.query("BEGIN");
    const result = await callback(client);
    await client.query("COMMIT");
    return result;
  } catch (error) {
    await client.query("ROLLBACK");
    throw error;
  } finally {
    client.release();
  }
}

export async function testDbConnection(): Promise<boolean> {
  const result = await dbQuery<{ ok: number }>("SELECT 1 as ok");
  return result.rows[0]?.ok === 1;
}

async function withTransientDbRetry<T>(operation: () => Promise<T>): Promise<T> {
  let lastError: unknown;

  for (let attempt = 0; attempt <= TRANSIENT_DB_RETRY_DELAYS_MS.length; attempt += 1) {
    try {
      return await operation();
    } catch (error) {
      lastError = error;

      if (
        attempt === TRANSIENT_DB_RETRY_DELAYS_MS.length ||
        !isTransientDbError(error)
      ) {
        throw error;
      }

      await delay(TRANSIENT_DB_RETRY_DELAYS_MS[attempt]);
    }
  }

  throw lastError;
}

function isTransientDbError(error: unknown): boolean {
  const maybeError = error as { code?: string; message?: string } | null;
  const code = maybeError?.code ?? "";
  const message = maybeError?.message ?? "";

  return (
    ["ECONNRESET", "ETIMEDOUT", "EPIPE", "57P01", "53300"].includes(code) ||
    message.includes("EMAXCONNSESSION") ||
    message.includes("Connection terminated") ||
    message.includes("timeout")
  );
}

function delay(milliseconds: number): Promise<void> {
  return new Promise((resolve) => {
    setTimeout(resolve, milliseconds);
  });
}
