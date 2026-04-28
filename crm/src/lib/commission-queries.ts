import "server-only";

import { dbQuery } from "@/lib/db-direct";
import { HttpError } from "@/lib/http";
import { UserRole, UserStatus, type PublicUser } from "@/types";
import type {
  CommissionSellerItem,
  CommissionSettingsInput,
} from "@/types/commission";

interface CommissionSellerRow {
  id: number;
  full_name: string;
  username: string;
  role: string | null;
  status: string | null;
  commission_percentage: string | number | null;
  commission_installments: string | number | null;
  min_sale_value: string | number | null;
  max_sale_value: string | number | null;
  bonus_percentage: string | number | null;
  bonus_threshold: string | number | null;
  is_active: string | number | boolean | null;
  notes: string | null;
  commission_updated_at: string | null;
  updated_by_name: string | null;
  has_config: boolean;
}

interface NormalizedCommissionInput {
  bonus_percentage: number;
  bonus_threshold: number | null;
  commission_installments: number;
  commission_percentage: number;
  is_active: boolean;
  max_sale_value: number | null;
  min_sale_value: number;
  notes: string | null;
  seller_id: number | null;
}

export async function getCommissionSellers(
  currentUser: PublicUser,
): Promise<{ sellers: CommissionSellerItem[] }> {
  assertCanReadCommissions(currentUser);

  const result = await dbQuery<CommissionSellerRow>(
    `
    SELECT
      u.id,
      u.full_name,
      u.username,
      u.role,
      u.status,
      scs.commission_percentage,
      scs.commission_installments,
      scs.min_sale_value,
      scs.max_sale_value,
      scs.bonus_percentage,
      scs.bonus_threshold,
      scs.is_active,
      scs.notes,
      scs.updated_at AS commission_updated_at,
      updater.full_name AS updated_by_name,
      scs.id IS NOT NULL AS has_config
    FROM users u
    LEFT JOIN seller_commission_settings scs ON u.id = scs.seller_id
    LEFT JOIN users updater ON scs.updated_by = updater.id
    WHERE u.role IN ('seller', 'manager', 'admin')
    ORDER BY u.full_name ASC
    `,
  );

  return {
    sellers: result.rows.map(mapCommissionSellerRow),
  };
}

export async function updateCommissionSettings(
  currentUser: PublicUser,
  rawInput: Partial<CommissionSettingsInput>,
): Promise<{ seller: CommissionSellerItem }> {
  assertCanWriteCommissions(currentUser);

  const input = normalizeCommissionInput(rawInput);
  validateCommissionInput(input);

  const seller = await dbQuery<{ full_name: string; id: number }>(
    `
    SELECT id, full_name
    FROM users
    WHERE id = $1
      AND role IN ('seller', 'manager', 'admin')
    LIMIT 1
    `,
    [input.seller_id],
  );

  if (!seller.rows[0]) {
    throw new HttpError("Vendedor nao encontrado", 404);
  }

  await dbQuery(
    `
    INSERT INTO seller_commission_settings (
      seller_id,
      commission_percentage,
      commission_installments,
      min_sale_value,
      max_sale_value,
      bonus_percentage,
      bonus_threshold,
      is_active,
      notes,
      created_by,
      updated_by,
      created_at,
      updated_at
    ) VALUES (
      $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $10, NOW(), NOW()
    )
    ON CONFLICT (seller_id) DO UPDATE SET
      commission_percentage = EXCLUDED.commission_percentage,
      commission_installments = EXCLUDED.commission_installments,
      min_sale_value = EXCLUDED.min_sale_value,
      max_sale_value = EXCLUDED.max_sale_value,
      bonus_percentage = EXCLUDED.bonus_percentage,
      bonus_threshold = EXCLUDED.bonus_threshold,
      is_active = EXCLUDED.is_active,
      notes = EXCLUDED.notes,
      updated_by = EXCLUDED.updated_by,
      updated_at = NOW()
    `,
    [
      input.seller_id,
      input.commission_percentage,
      input.commission_installments,
      input.min_sale_value,
      input.max_sale_value,
      input.bonus_percentage,
      input.bonus_threshold,
      input.is_active ? 1 : 0,
      input.notes,
      currentUser.id,
    ],
  );

  const updated = await dbQuery<CommissionSellerRow>(
    `
    SELECT
      u.id,
      u.full_name,
      u.username,
      u.role,
      u.status,
      scs.commission_percentage,
      scs.commission_installments,
      scs.min_sale_value,
      scs.max_sale_value,
      scs.bonus_percentage,
      scs.bonus_threshold,
      scs.is_active,
      scs.notes,
      scs.updated_at AS commission_updated_at,
      updater.full_name AS updated_by_name,
      scs.id IS NOT NULL AS has_config
    FROM users u
    LEFT JOIN seller_commission_settings scs ON u.id = scs.seller_id
    LEFT JOIN users updater ON scs.updated_by = updater.id
    WHERE u.id = $1
    LIMIT 1
    `,
    [input.seller_id],
  );

  return {
    seller: mapCommissionSellerRow(updated.rows[0]),
  };
}

function assertCanReadCommissions(currentUser: Pick<PublicUser, "role">): void {
  if (![UserRole.Admin, UserRole.Manager].includes(currentUser.role)) {
    throw new HttpError("Sem permissao para acessar comissoes", 403);
  }
}

function assertCanWriteCommissions(currentUser: Pick<PublicUser, "role">): void {
  if (currentUser.role !== UserRole.Admin) {
    throw new HttpError("Apenas administradores podem alterar comissoes", 403);
  }
}

function normalizeCommissionInput(
  input: Partial<CommissionSettingsInput>,
): NormalizedCommissionInput {
  return {
    bonus_percentage: parseNumber(input.bonus_percentage) ?? 0,
    bonus_threshold: parseNumber(input.bonus_threshold),
    commission_installments: parseInteger(input.commission_installments) ?? 5,
    commission_percentage: parseNumber(input.commission_percentage) ?? 1.5,
    is_active: input.is_active !== false,
    max_sale_value: parseNumber(input.max_sale_value),
    min_sale_value: parseNumber(input.min_sale_value) ?? 0,
    notes: normalizeString(input.notes),
    seller_id: parseInteger(input.seller_id),
  };
}

function validateCommissionInput(input: NormalizedCommissionInput): void {
  if (!input.seller_id) {
    throw new HttpError("ID do vendedor e obrigatorio", 400);
  }

  if (input.commission_percentage < 0 || input.commission_percentage > 100) {
    throw new HttpError("Comissao deve estar entre 0% e 100%", 400);
  }

  if (input.commission_installments < 1) {
    throw new HttpError("Numero de parcelas deve ser maior que zero", 400);
  }

  if (input.max_sale_value !== null && input.max_sale_value <= input.min_sale_value) {
    throw new HttpError("Valor maximo deve ser maior que o valor minimo", 400);
  }

  if (input.bonus_percentage < 0 || input.bonus_percentage > 100) {
    throw new HttpError("Bonus deve estar entre 0% e 100%", 400);
  }
}

function mapCommissionSellerRow(row: CommissionSellerRow): CommissionSellerItem {
  return {
    id: row.id,
    full_name: row.full_name,
    username: row.username,
    role: parseEnum(row.role, UserRole) ?? UserRole.Seller,
    status: parseEnum(row.status, UserStatus) ?? UserStatus.Active,
    commission_percentage: toNumber(row.commission_percentage) || 1.5,
    commission_installments: toNumber(row.commission_installments) || 5,
    min_sale_value: toNumber(row.min_sale_value),
    max_sale_value: toNullableNumber(row.max_sale_value),
    bonus_percentage: toNumber(row.bonus_percentage),
    bonus_threshold: toNullableNumber(row.bonus_threshold),
    is_active: parseActiveFlag(row.is_active),
    notes: row.notes,
    commission_updated_at: row.commission_updated_at,
    updated_by_name: row.updated_by_name,
    has_config: row.has_config,
  };
}

function parseActiveFlag(value: string | number | boolean | null): boolean {
  if (value === null) {
    return true;
  }

  return value === true || value === 1 || value === "1" || value === "true" || value === "t";
}

function parseNumber(value: unknown): number | null {
  if (value === null || value === undefined || value === "") {
    return null;
  }

  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : null;
}

function parseInteger(value: unknown): number | null {
  const parsed = parseNumber(value);

  if (parsed === null || !Number.isInteger(parsed)) {
    return null;
  }

  return parsed;
}

function normalizeString(value: unknown): string | null {
  if (typeof value !== "string") {
    return null;
  }

  const trimmed = value.trim();
  return trimmed ? trimmed : null;
}

function parseEnum<T extends Record<string, string>>(
  value: string | null,
  enumObject: T,
): T[keyof T] | undefined {
  if (!value) {
    return undefined;
  }

  const values = Object.values(enumObject);
  return values.includes(value) ? (value as T[keyof T]) : undefined;
}

function toNumber(value: string | number | null | undefined): number {
  if (value === null || value === undefined) {
    return 0;
  }

  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
}

function toNullableNumber(value: string | number | null): number | null {
  if (value === null) {
    return null;
  }

  return toNumber(value);
}
