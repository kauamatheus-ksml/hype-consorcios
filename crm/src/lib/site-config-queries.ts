import "server-only";

import { dbQuery, dbTransaction } from "@/lib/db-direct";
import { HttpError } from "@/lib/http";
import { UserRole, type PublicUser } from "@/types";
import type { SiteConfigItem, SiteConfigUpdateInput } from "@/types/site-config";

interface SiteConfigRow {
  id: number;
  config_key: string;
  config_value: string | null;
  config_type: string;
  section: string;
  display_name: string;
  description: string | null;
  updated_at: string | null;
}

export async function getSiteConfigList(
  currentUser: PublicUser,
  section?: string,
): Promise<{ configs: SiteConfigItem[]; sections: string[] }> {
  assertCanManageSiteConfig(currentUser);

  const params: unknown[] = [];
  let sql = `
    SELECT id, config_key, config_value, config_type, section, display_name, description, updated_at
    FROM site_config
    WHERE 1=1
  `;

  if (section) {
    params.push(section);
    sql += ` AND section = $${params.length}`;
  }

  sql += " ORDER BY section ASC, display_name ASC";

  const result = await dbQuery<SiteConfigRow>(sql, params);
  const sectionsResult = await dbQuery<{ section: string }>(
    "SELECT DISTINCT section FROM site_config ORDER BY section ASC",
  );

  return {
    configs: result.rows.map(mapSiteConfigRow),
    sections: sectionsResult.rows.map((row) => row.section),
  };
}

export async function updateSiteConfigSection(
  currentUser: PublicUser,
  rawInput: Partial<SiteConfigUpdateInput>,
): Promise<{ configs: SiteConfigItem[]; sections: string[] }> {
  assertCanManageSiteConfig(currentUser);

  const section = normalizeString(rawInput.section);

  if (!section) {
    throw new HttpError("Secao e obrigatoria", 400);
  }

  const values = rawInput.values;

  if (!values || typeof values !== "object") {
    throw new HttpError("Valores de configuracao sao obrigatorios", 400);
  }

  await dbTransaction(async (client) => {
    const allowedResult = await client.query<{ config_key: string }>(
      "SELECT config_key FROM site_config WHERE section = $1",
      [section],
    );
    const allowedKeys = new Set(allowedResult.rows.map((row) => row.config_key));

    for (const [key, value] of Object.entries(values)) {
      if (!allowedKeys.has(key)) {
        continue;
      }

      await client.query(
        `
        UPDATE site_config
        SET config_value = $1, updated_at = NOW()
        WHERE section = $2
          AND config_key = $3
        `,
        [value ?? "", section, key],
      );
    }
  });

  return getSiteConfigList(currentUser, section);
}

export async function assertUploadableSiteConfig(
  currentUser: PublicUser,
  configKey: string,
): Promise<SiteConfigItem> {
  assertCanManageSiteConfig(currentUser);

  const normalizedKey = normalizeString(configKey);

  if (!normalizedKey) {
    throw new HttpError("Chave de configuracao e obrigatoria", 400);
  }

  const result = await dbQuery<SiteConfigRow>(
    `
    SELECT id, config_key, config_value, config_type, section, display_name, description, updated_at
    FROM site_config
    WHERE config_key = $1
    LIMIT 1
    `,
    [normalizedKey],
  );

  const config = result.rows[0];

  if (!config) {
    throw new HttpError("Configuracao nao encontrada", 404);
  }

  if (config.config_type !== "image") {
    throw new HttpError("Configuracao nao aceita upload de arquivo", 400);
  }

  return mapSiteConfigRow(config);
}

export async function updateSiteConfigFileValue(
  currentUser: PublicUser,
  configKey: string,
  relativePath: string,
): Promise<SiteConfigItem> {
  const config = await assertUploadableSiteConfig(currentUser, configKey);

  await dbQuery(
    `
    UPDATE site_config
    SET config_value = $1, updated_at = NOW()
    WHERE config_key = $2
    `,
    [relativePath, config.config_key],
  );

  return {
    ...config,
    config_value: relativePath,
    updated_at: new Date().toISOString(),
  };
}

function assertCanManageSiteConfig(currentUser: Pick<PublicUser, "role">): void {
  if (currentUser.role !== UserRole.Admin) {
    throw new HttpError("Apenas administradores podem acessar configuracoes do site", 403);
  }
}

function mapSiteConfigRow(row: SiteConfigRow): SiteConfigItem {
  return {
    id: row.id,
    config_key: row.config_key,
    config_value: row.config_value ?? "",
    config_type: row.config_type,
    section: row.section,
    display_name: row.display_name,
    description: row.description,
    updated_at: row.updated_at,
  };
}

function normalizeString(value: unknown): string | null {
  if (typeof value !== "string") {
    return null;
  }

  const trimmed = value.trim();
  return trimmed ? trimmed : null;
}
