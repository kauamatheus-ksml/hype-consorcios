import "server-only";

import { Pool, type PoolClient, type QueryResult, type QueryResultRow } from "pg";

import { getOptionalEnv, getRequiredEnv } from "@/lib/env";

let pool: Pool | null = null;

export function getPgPool(): Pool {
  if (pool) {
    return pool;
  }

  pool = new Pool({
    host: getRequiredEnv("DB_HOST"),
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
  return getPgPool().query<T>(text, params);
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
