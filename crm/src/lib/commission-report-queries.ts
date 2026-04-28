import "server-only";

import { dbQuery } from "@/lib/db-direct";
import { currentBrazilYear } from "@/lib/date-format";
import { HttpError } from "@/lib/http";
import { UserRole, type PublicUser } from "@/types";
import type {
  CommissionReportMonth,
  CommissionReportPayload,
  CommissionReportSale,
  CommissionReportSeller,
  CommissionReportSellerRow,
  CommissionReportSummary,
} from "@/types/commission-reports";

export interface CommissionReportFilters {
  month: number | null;
  seller_id: number | null;
  year: number;
}

interface CommissionReportRow {
  avg_commission_percentage: string | number | null;
  month: string | number;
  month_name: string;
  sales_count: string | number;
  seller_id: number;
  seller_name: string;
  total_commission: string | number | null;
  total_monthly_commission: string | number | null;
  total_sales: string | number | null;
  year: string | number;
}

interface CommissionSaleRow {
  commission_installments: string | number | null;
  commission_percentage: string | number | null;
  commission_value: string | number | null;
  customer_name: string | null;
  id: number;
  monthly_commission: string | number | null;
  sale_date: string | null;
  sale_value: string | number | null;
  seller_id: number;
  seller_name: string;
  vehicle_sold: string | null;
}

export async function getCommissionReport(
  currentUser: PublicUser,
  filters: CommissionReportFilters,
): Promise<CommissionReportPayload> {
  const normalizedFilters = normalizeFilters(currentUser, filters);
  const where = buildCommissionWhere(currentUser, normalizedFilters);

  const monthlyResult = await dbQuery<CommissionReportRow>(
    `
    SELECT
      EXTRACT(YEAR FROM s.sale_date)::int AS year,
      EXTRACT(MONTH FROM s.sale_date)::int AS month,
      TO_CHAR(s.sale_date, 'FMMonth') AS month_name,
      u.id AS seller_id,
      u.full_name AS seller_name,
      COUNT(s.id) AS sales_count,
      COALESCE(SUM(s.sale_value), 0) AS total_sales,
      COALESCE(SUM(s.commission_value), 0) AS total_commission,
      COALESCE(SUM(s.monthly_commission), 0) AS total_monthly_commission,
      COALESCE(AVG(s.commission_percentage), 0) AS avg_commission_percentage
    FROM sales s
    JOIN users u ON s.seller_id = u.id
    JOIN leads l ON s.lead_id = l.id
    WHERE ${where.sql}
    GROUP BY
      EXTRACT(YEAR FROM s.sale_date),
      EXTRACT(MONTH FROM s.sale_date),
      TO_CHAR(s.sale_date, 'FMMonth'),
      u.id,
      u.full_name
    ORDER BY EXTRACT(YEAR FROM s.sale_date) DESC, EXTRACT(MONTH FROM s.sale_date) DESC, u.full_name
    `,
    where.params,
  );

  const months = groupMonthlyRows(monthlyResult.rows);
  const summary = summarizeMonths(months);
  const sales = normalizedFilters.month
    ? await getCommissionSales(where.sql, where.params)
    : [];
  const sellers = await getCommissionSellers(currentUser);

  return {
    filters: normalizedFilters,
    months,
    sales,
    sellers,
    summary: normalizedFilters.month ? summarizeSales(sales) : summary,
  };
}

export function parseCommissionReportFilters(
  searchParams: URLSearchParams,
): CommissionReportFilters {
  const currentYear = currentBrazilYear();

  return {
    month: clampMonth(parseOptionalPositiveInteger(searchParams.get("month"))),
    seller_id: parseOptionalPositiveInteger(searchParams.get("seller_id")) ?? null,
    year: parseOptionalPositiveInteger(searchParams.get("year")) ?? currentYear,
  };
}

function normalizeFilters(
  currentUser: PublicUser,
  filters: CommissionReportFilters,
): CommissionReportFilters {
  if (filters.year < 2000 || filters.year > 2100) {
    throw new HttpError("Ano invalido", 400);
  }

  return {
    month: filters.month,
    seller_id: canManageReports(currentUser) ? filters.seller_id : currentUser.id,
    year: filters.year,
  };
}

function buildCommissionWhere(
  currentUser: PublicUser,
  filters: CommissionReportFilters,
): { params: unknown[]; sql: string } {
  const params: unknown[] = [filters.year];
  const conditions = ["s.status = 'completed'", "EXTRACT(YEAR FROM s.sale_date) = $1"];

  if (!canManageReports(currentUser)) {
    params.push(currentUser.id);
    conditions.push(`s.seller_id = $${params.length}`);
  } else if (filters.seller_id) {
    params.push(filters.seller_id);
    conditions.push(`s.seller_id = $${params.length}`);
  }

  if (filters.month) {
    params.push(filters.month);
    conditions.push(`EXTRACT(MONTH FROM s.sale_date) = $${params.length}`);
  }

  return {
    params,
    sql: conditions.join(" AND "),
  };
}

async function getCommissionSales(
  whereSql: string,
  params: unknown[],
): Promise<CommissionReportSale[]> {
  const result = await dbQuery<CommissionSaleRow>(
    `
    SELECT
      s.id,
      s.sale_value,
      s.commission_percentage,
      s.commission_value,
      s.commission_installments,
      s.monthly_commission,
      s.sale_date,
      s.vehicle_sold,
      l.name AS customer_name,
      u.full_name AS seller_name,
      u.id AS seller_id
    FROM sales s
    JOIN leads l ON s.lead_id = l.id
    JOIN users u ON s.seller_id = u.id
    WHERE ${whereSql}
    ORDER BY s.sale_date DESC NULLS LAST, s.id DESC
    `,
    params,
  );

  return result.rows.map((row) => ({
    commission_installments: toNullableNumber(row.commission_installments),
    commission_percentage: toNullableNumber(row.commission_percentage),
    commission_value: toNullableNumber(row.commission_value),
    customer_name: row.customer_name,
    id: row.id,
    monthly_commission: toNullableNumber(row.monthly_commission),
    sale_date: row.sale_date,
    sale_value: toNullableNumber(row.sale_value),
    seller_id: row.seller_id,
    seller_name: row.seller_name,
    vehicle_sold: row.vehicle_sold,
  }));
}

async function getCommissionSellers(currentUser: PublicUser): Promise<CommissionReportSeller[]> {
  if (!canManageReports(currentUser)) {
    return [
      {
        full_name: currentUser.full_name,
        id: currentUser.id,
        role: currentUser.role,
      },
    ];
  }

  const result = await dbQuery<{ full_name: string; id: number; role: string }>(
    `
    SELECT id, full_name, role
    FROM users
    WHERE status = 'active'
      AND role IN ('seller', 'manager', 'admin')
    ORDER BY full_name ASC
    `,
  );

  return result.rows.map((row) => ({
    full_name: row.full_name,
    id: row.id,
    role: row.role as UserRole,
  }));
}

function groupMonthlyRows(rows: CommissionReportRow[]): CommissionReportMonth[] {
  const months = new Map<string, CommissionReportMonth>();

  for (const row of rows) {
    const year = toNumber(row.year);
    const month = toNumber(row.month);
    const key = `${year}-${month}`;
    const sellerRow: CommissionReportSellerRow = {
      avg_commission_percentage: toNumber(row.avg_commission_percentage),
      sales_count: toNumber(row.sales_count),
      seller_id: row.seller_id,
      seller_name: row.seller_name,
      total_commission: toNumber(row.total_commission),
      total_monthly_commission: toNumber(row.total_monthly_commission),
      total_sales: toNumber(row.total_sales),
    };

    const current = months.get(key) ?? {
      month,
      month_name: row.month_name.trim(),
      sellers: [],
      totals: emptySummary(),
      year,
    };

    current.sellers.push(sellerRow);
    current.totals.sales_count += sellerRow.sales_count;
    current.totals.total_sales += sellerRow.total_sales;
    current.totals.total_commission += sellerRow.total_commission;
    current.totals.total_monthly_commission += sellerRow.total_monthly_commission;
    months.set(key, current);
  }

  return Array.from(months.values());
}

function summarizeMonths(months: CommissionReportMonth[]): CommissionReportSummary {
  return months.reduce((summary, month) => {
    summary.sales_count += month.totals.sales_count;
    summary.total_sales += month.totals.total_sales;
    summary.total_commission += month.totals.total_commission;
    summary.total_monthly_commission += month.totals.total_monthly_commission;
    return summary;
  }, emptySummary());
}

function summarizeSales(sales: CommissionReportSale[]): CommissionReportSummary {
  return sales.reduce((summary, sale) => {
    summary.sales_count += 1;
    summary.total_sales += sale.sale_value ?? 0;
    summary.total_commission += sale.commission_value ?? 0;
    summary.total_monthly_commission += sale.monthly_commission ?? 0;
    return summary;
  }, emptySummary());
}

function emptySummary(): CommissionReportSummary {
  return {
    sales_count: 0,
    total_commission: 0,
    total_monthly_commission: 0,
    total_sales: 0,
  };
}

function canManageReports(currentUser: Pick<PublicUser, "role">): boolean {
  return [UserRole.Admin, UserRole.Manager].includes(currentUser.role);
}

function parseOptionalPositiveInteger(value: string | null): number | undefined {
  if (!value) {
    return undefined;
  }

  const parsed = Number(value);
  return Number.isInteger(parsed) && parsed > 0 ? parsed : undefined;
}

function clampMonth(value: number | undefined): number | null {
  if (!value) {
    return null;
  }

  return value >= 1 && value <= 12 ? value : null;
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
