import "server-only";

import { dbQuery } from "@/lib/db-direct";
import { HttpError } from "@/lib/http";
import { UserRole, type PublicUser } from "@/types";
import type { ProfilePayload, ProfileStats, ProfileUpdateInput } from "@/types/profile";

export async function getProfilePayload(currentUser: PublicUser): Promise<ProfilePayload> {
  return {
    stats: await getProfileStats(currentUser),
    user: currentUser,
  };
}

export async function updateProfile(
  currentUser: PublicUser,
  input: Partial<ProfileUpdateInput>,
): Promise<PublicUser> {
  const fullName = normalizeRequiredString(input.full_name, "Nome completo");
  const email = normalizeRequiredString(input.email, "Email");

  if (!isValidEmail(email)) {
    throw new HttpError("Email invalido", 400);
  }

  const duplicateEmail = await dbQuery<{ id: number }>(
    `
    SELECT id
    FROM users
    WHERE email = $1
      AND id != $2
    LIMIT 1
    `,
    [email, currentUser.id],
  );

  if (duplicateEmail.rows[0]) {
    throw new HttpError("Este email ja esta sendo usado por outro usuario", 400);
  }

  const result = await dbQuery<{
    created_at: string;
    created_by: number | null;
    email: string;
    full_name: string;
    id: number;
    last_login: string | null;
    role: string;
    status: string;
    updated_at: string;
    username: string;
  }>(
    `
    UPDATE users
    SET full_name = $1, email = $2, updated_at = NOW()
    WHERE id = $3
    RETURNING id, username, email, full_name, role, status, created_at, updated_at, last_login, created_by
    `,
    [fullName, email, currentUser.id],
  );

  const row = result.rows[0];

  if (!row) {
    throw new HttpError("Usuario nao encontrado", 404);
  }

  return {
    id: row.id,
    username: row.username,
    email: row.email,
    full_name: row.full_name,
    role: row.role as PublicUser["role"],
    status: row.status as PublicUser["status"],
    created_at: row.created_at,
    updated_at: row.updated_at,
    last_login: row.last_login,
    created_by: row.created_by,
  };
}

async function getProfileStats(currentUser: Pick<PublicUser, "id" | "role">): Promise<ProfileStats> {
  const stats: ProfileStats = {
    converted_leads: 0,
    monthly_sales: 0,
    monthly_sales_value: 0,
    total_leads: 0,
    total_sales: 0,
    total_sales_value: 0,
  };

  const salesScope = [UserRole.Admin, UserRole.Manager].includes(currentUser.role)
    ? { params: [], sql: "" }
    : { params: [currentUser.id], sql: " WHERE seller_id = $1" };
  const leadsScope = [UserRole.Admin, UserRole.Manager].includes(currentUser.role)
    ? { params: [], sql: "" }
    : { params: [currentUser.id], sql: " WHERE assigned_to = $1" };

  if (currentUser.role !== UserRole.Viewer) {
    const salesResult = await dbQuery<{
      monthly_sales: string | number;
      monthly_sales_value: string | number;
      total_sales: string | number;
      total_sales_value: string | number;
    }>(
      `
      SELECT
        COUNT(*) AS total_sales,
        COALESCE(SUM(sale_value), 0) AS total_sales_value,
        COUNT(*) FILTER (
          WHERE sale_date >= DATE_TRUNC('month', CURRENT_DATE)
            AND sale_date < DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month'
        ) AS monthly_sales,
        COALESCE(SUM(sale_value) FILTER (
          WHERE sale_date >= DATE_TRUNC('month', CURRENT_DATE)
            AND sale_date < DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month'
        ), 0) AS monthly_sales_value
      FROM sales
      ${salesScope.sql}
      `,
      salesScope.params,
    );
    const row = salesResult.rows[0];

    stats.total_sales = toNumber(row?.total_sales);
    stats.total_sales_value = toNumber(row?.total_sales_value);
    stats.monthly_sales = toNumber(row?.monthly_sales);
    stats.monthly_sales_value = toNumber(row?.monthly_sales_value);
  }

  const leadsResult = await dbQuery<{
    converted_leads: string | number;
    total_leads: string | number;
  }>(
    `
    SELECT
      COUNT(*) AS total_leads,
      COUNT(*) FILTER (WHERE status = 'converted') AS converted_leads
    FROM leads
    ${leadsScope.sql}
    `,
    leadsScope.params,
  );
  stats.total_leads = toNumber(leadsResult.rows[0]?.total_leads);
  stats.converted_leads = toNumber(leadsResult.rows[0]?.converted_leads);

  return stats;
}

function normalizeRequiredString(value: unknown, label: string): string {
  if (typeof value !== "string" || !value.trim()) {
    throw new HttpError(`${label} e obrigatorio`, 400);
  }

  return value.trim();
}

function isValidEmail(value: string): boolean {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
}

function toNumber(value: string | number | null | undefined): number {
  if (value === null || value === undefined) {
    return 0;
  }

  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
}
