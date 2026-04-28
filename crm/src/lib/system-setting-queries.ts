import "server-only";

import { dbQuery } from "@/lib/db-direct";
import { HttpError } from "@/lib/http";
import { UserRole, type PublicUser } from "@/types";
import type {
  SystemSettingItem,
  SystemSettingMutationInput,
} from "@/types/system-settings";

interface SystemSettingRow {
  description: string | null;
  id: number;
  setting_key: string;
  setting_value: string | null;
  updated_at: string | null;
  updated_by: number | null;
  updated_by_name: string | null;
}

interface NormalizedSystemSettingInput {
  description: string | null;
  setting_key: string | null;
  setting_value: string | null;
}

export async function getDefaultCommissionRate(): Promise<number> {
  const result = await dbQuery<{ setting_value: string | null }>(
    `
    SELECT setting_value
    FROM system_settings
    WHERE setting_key = 'default_commission_rate'
    LIMIT 1
    `,
  );

  const parsed = Number(result.rows[0]?.setting_value);
  return Number.isFinite(parsed) ? parsed : 1.5;
}

export async function getSystemSettingValue(
  currentUser: PublicUser,
  settingKey: string,
): Promise<{ setting_key: string; value: number | string | null }> {
  const normalizedKey = normalizeNullableString(settingKey);

  if (!normalizedKey) {
    throw new HttpError("Chave da configuracao e obrigatoria", 400);
  }

  if (normalizedKey === "default_commission_rate") {
    return {
      setting_key: normalizedKey,
      value: await getDefaultCommissionRate(),
    };
  }

  assertCanManageSystemSettings(currentUser);

  const result = await dbQuery<{ setting_value: string | null }>(
    `
    SELECT setting_value
    FROM system_settings
    WHERE setting_key = $1
    LIMIT 1
    `,
    [normalizedKey],
  );

  return {
    setting_key: normalizedKey,
    value: result.rows[0]?.setting_value ?? null,
  };
}

export async function getSystemSettingList(
  currentUser: PublicUser,
): Promise<SystemSettingItem[]> {
  assertCanManageSystemSettings(currentUser);

  const result = await dbQuery<SystemSettingRow>(
    `
    SELECT
      ss.id,
      ss.setting_key,
      ss.setting_value,
      ss.description,
      ss.updated_at,
      ss.updated_by,
      u.full_name AS updated_by_name
    FROM system_settings ss
    LEFT JOIN users u ON ss.updated_by = u.id
    ORDER BY ss.setting_key ASC
    `,
  );

  return result.rows.map(mapSystemSettingRow);
}

export async function upsertSystemSetting(
  currentUser: PublicUser,
  rawInput: Partial<SystemSettingMutationInput>,
): Promise<SystemSettingItem> {
  assertCanManageSystemSettings(currentUser);

  const input = normalizeSystemSettingInput(rawInput);
  validateRequiredString(input.setting_key, "setting_key");

  if (input.setting_value === null) {
    throw new HttpError("setting_value e obrigatorio", 400);
  }

  if (input.setting_key === "default_commission_rate") {
    const parsed = Number(input.setting_value);

    if (!Number.isFinite(parsed) || parsed < 0 || parsed > 100) {
      throw new HttpError("Taxa de comissao deve estar entre 0% e 100%", 400);
    }
  }

  const result = await dbQuery<SystemSettingRow>(
    `
    INSERT INTO system_settings (
      setting_key,
      setting_value,
      description,
      updated_by,
      updated_at
    ) VALUES ($1, $2, $3, $4, NOW())
    ON CONFLICT (setting_key) DO UPDATE SET
      setting_value = EXCLUDED.setting_value,
      description = EXCLUDED.description,
      updated_by = EXCLUDED.updated_by,
      updated_at = NOW()
    RETURNING
      id,
      setting_key,
      setting_value,
      description,
      updated_at,
      updated_by,
      NULL::text AS updated_by_name
    `,
    [input.setting_key, input.setting_value, input.description, currentUser.id],
  );

  return {
    ...mapSystemSettingRow(result.rows[0]),
    updated_by_name: currentUser.full_name,
  };
}

function assertCanManageSystemSettings(currentUser: Pick<PublicUser, "role">): void {
  if (currentUser.role !== UserRole.Admin) {
    throw new HttpError("Apenas administradores podem alterar configuracoes do sistema", 403);
  }
}

function normalizeSystemSettingInput(
  input: Partial<SystemSettingMutationInput>,
): NormalizedSystemSettingInput {
  return {
    description: normalizeNullableString(input.description),
    setting_key: normalizeNullableString(input.setting_key),
    setting_value:
      input.setting_value === null || input.setting_value === undefined
        ? null
        : String(input.setting_value).trim(),
  };
}

function mapSystemSettingRow(row: SystemSettingRow): SystemSettingItem {
  return {
    description: row.description,
    id: row.id,
    setting_key: row.setting_key,
    setting_value: row.setting_value,
    updated_at: row.updated_at,
    updated_by: row.updated_by,
    updated_by_name: row.updated_by_name,
  };
}

function validateRequiredString(value: string | null, label: string): asserts value is string {
  if (!value?.trim()) {
    throw new HttpError(`${label} e obrigatorio`, 400);
  }
}

function normalizeNullableString(value: unknown): string | null {
  if (typeof value !== "string") {
    return null;
  }

  const trimmed = value.trim();
  return trimmed ? trimmed : null;
}
