import "server-only";

import { dbQuery } from "@/lib/db-direct";
import { HttpError } from "@/lib/http";
import { UserRole, type PublicUser } from "@/types";
import type { AuditLogItem, AuditPagination, AuditStats } from "@/types/audit";

interface AuditFilters {
  action?: string;
  limit: number;
  page: number;
  search?: string;
}

interface AuditRow {
  id: number;
  user_id: number | null;
  user_full_name: string | null;
  username: string | null;
  action: string;
  table_name: string | null;
  record_id: number | null;
  description: string | null;
  ip_address: string | null;
  user_agent: string | null;
  created_at: string;
}

export async function getAuditLogs(
  currentUser: PublicUser,
  filters: AuditFilters,
): Promise<{
  logs: AuditLogItem[];
  pagination: AuditPagination;
  stats: AuditStats;
}> {
  assertCanReadAuditLogs(currentUser);

  const limit = clamp(filters.limit, 1, 100);
  const page = Math.max(filters.page, 1);
  const offset = (page - 1) * limit;
  const where = buildAuditWhere(filters);

  const statsResult = await dbQuery<{
    today: string | number;
    total: string | number;
    week: string | number;
  }>(
    `
    SELECT
      COUNT(*) AS total,
      COUNT(*) FILTER (WHERE DATE(created_at) = CURRENT_DATE) AS today,
      COUNT(*) FILTER (WHERE created_at >= NOW() - INTERVAL '7 days') AS week
    FROM audit_logs
    `,
  );
  const stats: AuditStats = {
    total: toNumber(statsResult.rows[0]?.total),
    today: toNumber(statsResult.rows[0]?.today),
    week: toNumber(statsResult.rows[0]?.week),
  };

  const countResult = await dbQuery<{ total: string | number }>(
    `
    SELECT COUNT(*) AS total
    FROM audit_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ${where.sql}
    `,
    where.params,
  );
  const totalRecords = toNumber(countResult.rows[0]?.total);
  const totalPages = Math.max(Math.ceil(totalRecords / limit), 1);

  const logsResult = await dbQuery<AuditRow>(
    `
    SELECT
      al.id,
      al.user_id,
      u.full_name AS user_full_name,
      u.username,
      al.action,
      al.table_name,
      al.record_id,
      al.description,
      al.ip_address,
      al.user_agent,
      al.created_at
    FROM audit_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ${where.sql}
    ORDER BY al.created_at DESC
    LIMIT $${where.params.length + 1} OFFSET $${where.params.length + 2}
    `,
    [...where.params, limit, offset],
  );

  return {
    logs: logsResult.rows.map(mapAuditRow),
    pagination: {
      current_page: page,
      per_page: limit,
      total_records: totalRecords,
      total_pages: totalPages,
      has_next: page < totalPages,
      has_prev: page > 1,
    },
    stats,
  };
}

export function parseAuditFilters(searchParams: URLSearchParams): AuditFilters {
  return {
    action: parseOptionalString(searchParams.get("action")),
    limit: clamp(parseOptionalPositiveInteger(searchParams.get("limit")) ?? 50, 1, 100),
    page: parseOptionalPositiveInteger(searchParams.get("page")) ?? 1,
    search: parseOptionalString(searchParams.get("search")),
  };
}

function assertCanReadAuditLogs(currentUser: Pick<PublicUser, "role">): void {
  if (![UserRole.Admin, UserRole.Manager].includes(currentUser.role)) {
    throw new HttpError("Sem permissao para acessar logs de auditoria", 403);
  }
}

function buildAuditWhere(filters: AuditFilters) {
  const params: unknown[] = [];
  let sql = "WHERE 1=1";

  if (filters.action) {
    params.push(filters.action);
    sql += ` AND al.action = $${params.length}`;
  }

  if (filters.search) {
    params.push(`%${filters.search}%`);
    const index = params.length;
    sql += ` AND (
      al.action ILIKE $${index}
      OR al.description ILIKE $${index}
      OR al.ip_address ILIKE $${index}
      OR u.full_name ILIKE $${index}
      OR u.username ILIKE $${index}
    )`;
  }

  return { params, sql };
}

function mapAuditRow(row: AuditRow): AuditLogItem {
  return {
    id: row.id,
    user_id: row.user_id,
    user_full_name: row.user_full_name,
    username: row.username,
    action: row.action,
    table_name: row.table_name,
    record_id: row.record_id,
    description: row.description,
    ip_address: row.ip_address,
    user_agent: row.user_agent,
    created_at: row.created_at,
  };
}

function parseOptionalPositiveInteger(value: string | null): number | undefined {
  if (!value) {
    return undefined;
  }

  const parsed = Number(value);
  return Number.isInteger(parsed) && parsed > 0 ? parsed : undefined;
}

function parseOptionalString(value: string | null): string | undefined {
  const trimmed = value?.trim();
  return trimmed ? trimmed : undefined;
}

function clamp(value: number, min: number, max: number): number {
  return Math.min(Math.max(value, min), max);
}

function toNumber(value: string | number | null | undefined): number {
  if (value === null || value === undefined) {
    return 0;
  }

  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
}
