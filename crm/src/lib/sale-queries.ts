import "server-only";

import type { PoolClient } from "pg";

import { dbQuery, dbTransaction } from "@/lib/db-direct";
import { currentBrazilDateString } from "@/lib/date-format";
import { HttpError } from "@/lib/http";
import { getDefaultCommissionRate } from "@/lib/system-setting-queries";
import { SaleStatus, UserRole, type PublicUser } from "@/types";
import type {
  SaleAdditionalStats,
  SaleDailyStat,
  SaleListItem,
  SaleMutationInput,
  SalePagination,
  SalePeriod,
  SaleSellerOption,
  SaleStats,
  SaleTopSellerStat,
  SaleTopVehicleStat,
} from "@/types/sales";

export interface SaleListFilters {
  limit: number;
  page: number;
  period?: SalePeriod;
  search?: string;
  seller_id?: number;
  status?: SaleStatus;
}

interface SaleRow {
  id: number;
  lead_id: number;
  seller_id: number;
  seller_name: string | null;
  customer_name: string | null;
  customer_phone: string | null;
  customer_email: string | null;
  lead_name: string | null;
  sale_value: string | number | null;
  commission_percentage: string | number | null;
  commission_value: string | number | null;
  commission_installments: string | number | null;
  monthly_commission: string | number | null;
  vehicle_sold: string | null;
  payment_type: string | null;
  down_payment: string | number | null;
  financing_months: string | number | null;
  monthly_payment: string | number | null;
  contract_number: string | null;
  notes: string | null;
  status: string | null;
  sale_date: string | null;
  created_at: string | null;
  updated_at: string | null;
  sale_year: string | number | null;
  sale_month: string | number | null;
  sale_month_name: string | null;
}

interface LeadForSaleRow {
  assigned_to: number | null;
  email: string | null;
  id: number;
  name: string;
  phone: string;
}

interface SellerCommissionSettingsRow {
  bonus_percentage: string | number | null;
  bonus_threshold: string | number | null;
  commission_installments: string | number | null;
  commission_percentage: string | number | null;
  id: number;
}

interface SaleMutationRow {
  commission_installments: string | number | null;
  commission_percentage: string | number | null;
  contract_number: string | null;
  down_payment: string | number | null;
  financing_months: string | number | null;
  id: number;
  lead_id: number;
  monthly_payment: string | number | null;
  notes: string | null;
  payment_type: string | null;
  sale_date: string | null;
  sale_value: string | number | null;
  seller_id: number;
  status: string | null;
  vehicle_sold: string | null;
}

export async function getSaleList(
  currentUser: PublicUser,
  filters: SaleListFilters,
): Promise<{
  pagination: SalePagination;
  sales: SaleListItem[];
}> {
  const limit = clamp(filters.limit, 1, 50);
  const page = Math.max(filters.page, 1);
  const offset = (page - 1) * limit;
  const where = buildSaleWhere(filters, currentUser);

  const countResult = await dbQuery<{ total: string | number }>(
    `SELECT COUNT(*) AS total ${where.fromAndWhere}`,
    where.params,
  );
  const totalRecords = toNumber(countResult.rows[0]?.total);
  const totalPages = Math.max(Math.ceil(totalRecords / limit), 1);

  const listResult = await dbQuery<SaleRow>(
    `
    SELECT
      s.id,
      s.lead_id,
      s.seller_id,
      s.sale_value,
      s.commission_percentage,
      s.commission_value,
      s.commission_installments,
      s.monthly_commission,
      s.vehicle_sold,
      s.payment_type,
      s.down_payment,
      s.financing_months,
      s.monthly_payment,
      s.contract_number,
      s.notes,
      s.status,
      s.sale_date,
      s.created_at,
      s.updated_at,
      u.full_name AS seller_name,
      l.name AS customer_name,
      l.phone AS customer_phone,
      l.email AS customer_email,
      l.name AS lead_name,
      EXTRACT(YEAR FROM s.sale_date)::int AS sale_year,
      EXTRACT(MONTH FROM s.sale_date)::int AS sale_month,
      TO_CHAR(s.sale_date, 'FMMonth') AS sale_month_name
    ${where.fromAndWhere}
    ORDER BY s.sale_date DESC NULLS LAST, s.created_at DESC NULLS LAST
    LIMIT $${where.params.length + 1} OFFSET $${where.params.length + 2}
    `,
    [...where.params, limit, offset],
  );

  return {
    pagination: {
      current_page: page,
      per_page: limit,
      total_records: totalRecords,
      total_pages: totalPages,
      has_next: page < totalPages,
      has_prev: page > 1,
    },
    sales: listResult.rows.map(mapSaleRow),
  };
}

export async function getSaleById(
  currentUser: PublicUser,
  saleId: number,
): Promise<SaleListItem> {
  const result = await dbQuery<SaleRow>(
    `
    SELECT
      s.id,
      s.lead_id,
      s.seller_id,
      s.sale_value,
      s.commission_percentage,
      s.commission_value,
      s.commission_installments,
      s.monthly_commission,
      s.vehicle_sold,
      s.payment_type,
      s.down_payment,
      s.financing_months,
      s.monthly_payment,
      s.contract_number,
      s.notes,
      s.status,
      s.sale_date,
      s.created_at,
      s.updated_at,
      u.full_name AS seller_name,
      l.name AS customer_name,
      l.phone AS customer_phone,
      l.email AS customer_email,
      l.name AS lead_name,
      EXTRACT(YEAR FROM s.sale_date)::int AS sale_year,
      EXTRACT(MONTH FROM s.sale_date)::int AS sale_month,
      TO_CHAR(s.sale_date, 'FMMonth') AS sale_month_name
    FROM sales s
    LEFT JOIN users u ON s.seller_id = u.id
    LEFT JOIN leads l ON s.lead_id = l.id
    WHERE s.id = $1
    LIMIT 1
    `,
    [saleId],
  );

  const sale = result.rows[0];

  if (!sale) {
    throw new HttpError("Venda nao encontrada", 404);
  }

  if (!canReadSale(currentUser, sale.seller_id)) {
    throw new HttpError("Sem permissao para ver esta venda", 403);
  }

  return mapSaleRow(sale);
}

export async function createSale(
  currentUser: PublicUser,
  rawInput: Partial<SaleMutationInput>,
): Promise<SaleListItem> {
  if (!hasSalesAccess(currentUser)) {
    throw new HttpError("Sem permissao para criar vendas", 403);
  }

  const input = normalizeSaleMutationInput(rawInput);
  validateRequiredSaleInput(input);

  const targetSellerId =
    canManageSales(currentUser) && input.seller_id ? input.seller_id : currentUser.id;
  const defaultCommissionRate = await getDefaultCommissionRate();

  const saleId = await dbTransaction(async (client) => {
    await assertActiveSeller(client, targetSellerId);

    const commissionSettings = await getOrCreateSellerCommissionSettings(
      client,
      targetSellerId,
      currentUser.id,
      defaultCommissionRate,
    );

    const leadId = input.lead_id
      ? await assertLeadForSale(client, input.lead_id, currentUser)
      : await createLeadForSale(client, input, targetSellerId);

    const commissionPercentage =
      canManageSales(currentUser) && input.commission_percentage !== null
        ? input.commission_percentage
        : toNumber(commissionSettings.commission_percentage) || defaultCommissionRate;
    const commissionInstallments =
      canManageSales(currentUser) && input.commission_installments !== null
        ? input.commission_installments
        : toNumber(commissionSettings.commission_installments) || 5;

    const commission = calculateCommission({
      applyBonus: true,
      commissionInstallments,
      commissionPercentage,
      commissionSettings,
      saleValue: input.sale_value ?? 0,
    });

    const insertResult = await client.query<{ id: number }>(
      `
      INSERT INTO sales (
        lead_id, seller_id, sale_value, commission_percentage, commission_value,
        commission_installments, monthly_commission, vehicle_sold, payment_type,
        down_payment, financing_months, monthly_payment, contract_number, notes,
        status, sale_date, created_by, created_at, updated_at
      ) VALUES (
        $1, $2, $3, $4, $5,
        $6, $7, $8, $9,
        $10, $11, $12, $13, $14,
        $15, $16, $17, NOW(), NOW()
      )
      RETURNING id
      `,
      [
        leadId,
        targetSellerId,
        input.sale_value,
        commission.finalPercentage,
        commission.commissionValue,
        commission.installments,
        commission.monthlyCommission,
        input.vehicle_sold,
        input.payment_type,
        input.down_payment,
        input.financing_months,
        input.monthly_payment,
        input.contract_number,
        input.notes,
        input.status ?? SaleStatus.Confirmed,
        input.sale_date ?? currentDateString(),
        currentUser.id,
      ],
    );

    await client.query("UPDATE leads SET status = 'converted', updated_at = NOW() WHERE id = $1", [
      leadId,
    ]);

    await client.query(
      `
      INSERT INTO lead_interactions (
        lead_id, user_id, interaction_type, description, created_at
      ) VALUES ($1, $2, 'note', $3, NOW())
      `,
      [
        leadId,
        currentUser.id,
        `Lead convertido em venda - Valor: ${formatCurrencyForLog(input.sale_value ?? 0)}`,
      ],
    );

    return insertResult.rows[0].id;
  });

  return getSaleById(currentUser, saleId);
}

export async function updateSale(
  currentUser: PublicUser,
  saleId: number,
  rawInput: Partial<SaleMutationInput>,
): Promise<SaleListItem> {
  const input = normalizeSaleMutationInput(rawInput);
  const defaultCommissionRate = await getDefaultCommissionRate();

  const updatedSaleId = await dbTransaction(async (client) => {
    const existing = await getSaleForMutation(client, saleId);

    if (!existing) {
      throw new HttpError("Venda nao encontrada", 404);
    }

    if (!canEditSale(currentUser, existing)) {
      throw new HttpError("Sem permissao para editar esta venda", 403);
    }

    const targetSellerId =
      canManageSales(currentUser) && input.seller_id ? input.seller_id : existing.seller_id;
    await assertActiveSeller(client, targetSellerId);

    const saleValue = input.sale_value ?? toNumber(existing.sale_value);
    const vehicleSold = input.vehicle_sold ?? existing.vehicle_sold;
    const paymentType = input.payment_type ?? existing.payment_type;

    validateSaleValue(saleValue);
    validateRequiredString(vehicleSold, "Veiculo vendido");
    validateRequiredString(paymentType, "Forma de pagamento");

    const commissionSettings = await getOrCreateSellerCommissionSettings(
      client,
      targetSellerId,
      currentUser.id,
      defaultCommissionRate,
    );
    const commissionPercentage =
      canManageSales(currentUser) && input.commission_percentage !== null
        ? input.commission_percentage
        : toNumber(existing.commission_percentage) ||
          toNumber(commissionSettings.commission_percentage) ||
          defaultCommissionRate;
    const commissionInstallments =
      canManageSales(currentUser) && input.commission_installments !== null
        ? input.commission_installments
        : toNumber(existing.commission_installments) ||
          toNumber(commissionSettings.commission_installments) ||
          5;

    const commission = calculateCommission({
      applyBonus: false,
      commissionInstallments,
      commissionPercentage,
      commissionSettings,
      saleValue,
    });

    await client.query(
      `
      UPDATE sales
      SET
        seller_id = $1,
        sale_value = $2,
        commission_percentage = $3,
        commission_value = $4,
        commission_installments = $5,
        monthly_commission = $6,
        vehicle_sold = $7,
        payment_type = $8,
        down_payment = $9,
        financing_months = $10,
        monthly_payment = $11,
        contract_number = $12,
        notes = $13,
        status = $14,
        sale_date = $15,
        updated_at = NOW()
      WHERE id = $16
      `,
      [
        targetSellerId,
        saleValue,
        commission.finalPercentage,
        commission.commissionValue,
        commission.installments,
        commission.monthlyCommission,
        vehicleSold,
        paymentType,
        input.down_payment ?? toNullableNumber(existing.down_payment),
        input.financing_months ?? toNullableNumber(existing.financing_months),
        input.monthly_payment ?? toNullableNumber(existing.monthly_payment),
        input.contract_number ?? existing.contract_number,
        input.notes ?? existing.notes,
        input.status ?? existing.status ?? SaleStatus.Pending,
        input.sale_date ?? existing.sale_date ?? currentDateString(),
        saleId,
      ],
    );

    return saleId;
  });

  return getSaleById(currentUser, updatedSaleId);
}

export async function cancelSale(
  currentUser: PublicUser,
  saleId: number,
  rawReason: unknown,
): Promise<SaleListItem> {
  if (!canManageSales(currentUser)) {
    throw new HttpError("Sem permissao para cancelar vendas", 403);
  }

  const normalizedReason = normalizeNullableString(rawReason);
  validateRequiredString(normalizedReason, "Motivo do cancelamento");
  const reason = normalizedReason;

  const cancelledSaleId = await dbTransaction(async (client) => {
    const existing = await getSaleForMutation(client, saleId);

    if (!existing) {
      throw new HttpError("Venda nao encontrada", 404);
    }

    if (existing.status === SaleStatus.Cancelled) {
      throw new HttpError("Venda ja esta cancelada", 400);
    }

    await client.query(
      `
      UPDATE sales
      SET
        status = 'cancelled',
        notes = CONCAT(
          COALESCE(notes, ''),
          CASE WHEN COALESCE(notes, '') = '' THEN '' ELSE E'\n\n' END,
          'CANCELADA: ',
          $1::text
        ),
        updated_at = NOW()
      WHERE id = $2
      `,
      [reason, saleId],
    );

    await client.query(
      "UPDATE leads SET status = 'negotiating', updated_at = NOW() WHERE id = $1",
      [existing.lead_id],
    );

    await client.query(
      `
      INSERT INTO lead_interactions (
        lead_id, user_id, interaction_type, description, created_at
      ) VALUES ($1, $2, 'note', $3, NOW())
      `,
      [existing.lead_id, currentUser.id, `Venda cancelada. Motivo: ${reason}`],
    );

    return saleId;
  });

  return getSaleById(currentUser, cancelledSaleId);
}

export async function getSaleStats(
  currentUser: PublicUser,
): Promise<{
  additional: SaleAdditionalStats;
  stats: SaleStats;
}> {
  const params: unknown[] = [];
  const scope = buildSaleScopeWhere(currentUser, "s", params);
  const defaultCommissionRate = await getDefaultCommissionRate();

  const statsResult = await dbQuery<{
    cancelled: string | number;
    completed: string | number;
    confirmed: string | number;
    pending: string | number;
    revenue: string | number;
    this_month: string | number;
    this_month_revenue: string | number;
    this_week: string | number;
    this_week_revenue: string | number;
    today: string | number;
    today_revenue: string | number;
    total: string | number;
  }>(
    `
    SELECT
      COUNT(*) AS total,
      COUNT(*) FILTER (WHERE s.status = 'pending') AS pending,
      COUNT(*) FILTER (WHERE s.status = 'confirmed') AS confirmed,
      COUNT(*) FILTER (WHERE s.status = 'cancelled') AS cancelled,
      COUNT(*) FILTER (WHERE s.status = 'completed') AS completed,
      COALESCE(SUM(s.sale_value) FILTER (WHERE s.status = 'confirmed'), 0) AS revenue,
      COUNT(*) FILTER (WHERE DATE(s.sale_date) = CURRENT_DATE) AS today,
      COALESCE(SUM(s.sale_value) FILTER (WHERE DATE(s.sale_date) = CURRENT_DATE), 0) AS today_revenue,
      COUNT(*) FILTER (WHERE s.sale_date >= CURRENT_DATE - INTERVAL '7 days') AS this_week,
      COALESCE(SUM(s.sale_value) FILTER (WHERE s.sale_date >= CURRENT_DATE - INTERVAL '7 days'), 0) AS this_week_revenue,
      COUNT(*) FILTER (
        WHERE s.sale_date >= DATE_TRUNC('month', CURRENT_DATE)
          AND s.sale_date < DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month'
      ) AS this_month,
      COALESCE(SUM(s.sale_value) FILTER (
        WHERE s.sale_date >= DATE_TRUNC('month', CURRENT_DATE)
          AND s.sale_date < DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month'
      ), 0) AS this_month_revenue
    FROM sales s
    WHERE 1=1 ${scope.sql}
    `,
    scope.params,
  );

  const commissionResult = await dbQuery<{ commission: string | number }>(
    `
    SELECT COALESCE(
      SUM(
        COALESCE(s.sale_value, 0)
        * COALESCE(scs.commission_percentage, s.commission_percentage, $${scope.params.length + 1})
        / 100
      ),
      0
    ) AS commission
    FROM sales s
    LEFT JOIN seller_commission_settings scs
      ON s.seller_id = scs.seller_id
      AND COALESCE(scs.is_active::text, '1') IN ('1', 'true', 't')
    WHERE s.status = 'confirmed' ${scope.sql}
    `,
    [...scope.params, defaultCommissionRate],
  );

  const row = statsResult.rows[0];
  const total = toNumber(row?.total);
  const revenue = toNumber(row?.revenue);
  const stats: SaleStats = {
    total,
    revenue,
    commission: toNumber(commissionResult.rows[0]?.commission),
    pending: toNumber(row?.pending),
    confirmed: toNumber(row?.confirmed),
    cancelled: toNumber(row?.cancelled),
    completed: toNumber(row?.completed),
    today: toNumber(row?.today),
    today_revenue: toNumber(row?.today_revenue),
    this_week: toNumber(row?.this_week),
    this_week_revenue: toNumber(row?.this_week_revenue),
    this_month: toNumber(row?.this_month),
    this_month_revenue: toNumber(row?.this_month_revenue),
    average_ticket: total > 0 ? revenue / total : 0,
  };

  const additional: SaleAdditionalStats = {};

  const dailyResult = await dbQuery<{
    count: string | number;
    date: string;
    revenue: string | number;
  }>(
    `
    SELECT
      DATE(s.sale_date)::text AS date,
      COUNT(*) AS count,
      COALESCE(SUM(s.sale_value), 0) AS revenue
    FROM sales s
    WHERE s.sale_date >= CURRENT_DATE - INTERVAL '7 days' ${scope.sql}
    GROUP BY DATE(s.sale_date)
    ORDER BY date DESC
    `,
    scope.params,
  );
  additional.daily_sales = dailyResult.rows.map(
    (item): SaleDailyStat => ({
      date: item.date,
      count: toNumber(item.count),
      revenue: toNumber(item.revenue),
    }),
  );

  const vehicleResult = await dbQuery<{
    count: string | number;
    total_value: string | number;
    vehicle_sold: string;
  }>(
    `
    SELECT
      s.vehicle_sold,
      COUNT(*) AS count,
      COALESCE(SUM(s.sale_value), 0) AS total_value
    FROM sales s
    WHERE s.vehicle_sold IS NOT NULL
      AND s.vehicle_sold != ''
      AND s.sale_date >= CURRENT_DATE - INTERVAL '30 days'
      ${scope.sql}
    GROUP BY s.vehicle_sold
    ORDER BY count DESC
    LIMIT 10
    `,
    scope.params,
  );
  additional.top_vehicles = vehicleResult.rows.map(
    (item): SaleTopVehicleStat => ({
      vehicle_sold: item.vehicle_sold,
      count: toNumber(item.count),
      total_value: toNumber(item.total_value),
    }),
  );

  if ([UserRole.Admin, UserRole.Manager].includes(currentUser.role)) {
    const sellersResult = await dbQuery<{
      full_name: string;
      id: number;
      role: string;
    }>(
      `
      SELECT id, full_name, role
      FROM users
      WHERE status = 'active'
        AND role IN ('seller', 'manager', 'admin')
      ORDER BY full_name ASC
      `,
    );
    additional.sellers = sellersResult.rows.map(
      (item): SaleSellerOption => ({
        id: item.id,
        full_name: item.full_name,
        role: item.role as UserRole,
      }),
    );

    const topSellersResult = await dbQuery<{
      seller_name: string;
      total_commission: string | number;
      total_revenue: string | number;
      total_sales: string | number;
    }>(
      `
      SELECT
        COALESCE(u.full_name, 'Nao atribuido') AS seller_name,
        COUNT(s.id) AS total_sales,
        COALESCE(SUM(s.sale_value), 0) AS total_revenue,
        COALESCE(SUM(s.commission_value), 0) AS total_commission
      FROM sales s
      LEFT JOIN users u ON s.seller_id = u.id
      WHERE s.sale_date >= CURRENT_DATE - INTERVAL '30 days'
      GROUP BY s.seller_id, u.full_name
      ORDER BY total_revenue DESC
      LIMIT 10
      `,
    );
    additional.top_sellers = topSellersResult.rows.map(
      (item): SaleTopSellerStat => ({
        seller_name: item.seller_name,
        total_sales: toNumber(item.total_sales),
        total_revenue: toNumber(item.total_revenue),
        total_commission: toNumber(item.total_commission),
      }),
    );
  }

  return { additional, stats };
}

export function parseSaleListFilters(searchParams: URLSearchParams): SaleListFilters {
  return {
    limit: clamp(parseOptionalPositiveInteger(searchParams.get("limit")) ?? 20, 1, 50),
    page: parseOptionalPositiveInteger(searchParams.get("page")) ?? 1,
    period: parsePeriod(searchParams.get("period")),
    search: parseOptionalString(searchParams.get("search")),
    seller_id: parseOptionalPositiveInteger(searchParams.get("seller_id")),
    status: parseEnum(searchParams.get("status"), SaleStatus),
  };
}

function buildSaleWhere(filters: SaleListFilters, currentUser: PublicUser) {
  const params: unknown[] = [];
  let sql = `
    FROM sales s
    LEFT JOIN users u ON s.seller_id = u.id
    LEFT JOIN leads l ON s.lead_id = l.id
    WHERE 1=1
  `;

  const scope = buildSaleScopeWhere(currentUser, "s", params);
  sql += scope.sql;

  if (filters.search) {
    params.push(`%${filters.search}%`);
    const index = params.length;
    sql += ` AND (l.name ILIKE $${index} OR s.vehicle_sold ILIKE $${index} OR s.contract_number ILIKE $${index})`;
  }

  if (filters.status) {
    params.push(filters.status);
    sql += ` AND s.status = $${params.length}`;
  }

  if (
    filters.seller_id &&
    [UserRole.Admin, UserRole.Manager].includes(currentUser.role)
  ) {
    params.push(filters.seller_id);
    sql += ` AND s.seller_id = $${params.length}`;
  }

  if (filters.period) {
    sql += getPeriodSql(filters.period);
  }

  return {
    fromAndWhere: sql,
    params,
  };
}

function buildSaleScopeWhere(
  currentUser: Pick<PublicUser, "id" | "role">,
  alias: string,
  params: unknown[],
) {
  if ([UserRole.Admin, UserRole.Manager].includes(currentUser.role)) {
    return {
      params,
      sql: "",
    };
  }

  params.push(currentUser.id);

  return {
    params,
    sql: ` AND ${alias}.seller_id = $${params.length}`,
  };
}

function getPeriodSql(period: SalePeriod): string {
  switch (period) {
    case "today":
      return " AND DATE(s.sale_date) = CURRENT_DATE";
    case "week":
      return " AND s.sale_date >= CURRENT_DATE - INTERVAL '7 days'";
    case "month":
      return " AND s.sale_date >= DATE_TRUNC('month', CURRENT_DATE) AND s.sale_date < DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month'";
    case "quarter":
      return " AND s.sale_date >= CURRENT_DATE - INTERVAL '3 months'";
  }
}

function mapSaleRow(row: SaleRow): SaleListItem {
  return {
    id: row.id,
    lead_id: row.lead_id,
    seller_id: row.seller_id,
    seller_name: row.seller_name,
    customer_name: row.customer_name,
    customer_phone: row.customer_phone,
    customer_email: row.customer_email,
    lead_name: row.lead_name,
    sale_value: toNullableNumber(row.sale_value),
    commission_percentage: toNullableNumber(row.commission_percentage),
    commission_value: toNullableNumber(row.commission_value),
    commission_installments: toNullableNumber(row.commission_installments),
    monthly_commission: toNullableNumber(row.monthly_commission),
    vehicle_sold: row.vehicle_sold,
    payment_type: row.payment_type,
    down_payment: toNullableNumber(row.down_payment),
    financing_months: toNullableNumber(row.financing_months),
    monthly_payment: toNullableNumber(row.monthly_payment),
    contract_number: row.contract_number,
    notes: row.notes,
    status: parseSaleStatus(row.status),
    sale_date: row.sale_date,
    created_at: row.created_at,
    updated_at: row.updated_at,
    sale_year: toNullableNumber(row.sale_year),
    sale_month: toNullableNumber(row.sale_month),
    sale_month_name: row.sale_month_name?.trim() ?? null,
  };
}

function parseSaleStatus(value: string | null): SaleStatus {
  return parseEnum(value, SaleStatus) ?? SaleStatus.Pending;
}

function hasSalesAccess(currentUser: Pick<PublicUser, "role">): boolean {
  return [UserRole.Admin, UserRole.Manager, UserRole.Seller].includes(currentUser.role);
}

function canManageSales(currentUser: Pick<PublicUser, "role">): boolean {
  return [UserRole.Admin, UserRole.Manager].includes(currentUser.role);
}

function canReadSale(currentUser: Pick<PublicUser, "id" | "role">, sellerId: number): boolean {
  return canManageSales(currentUser) || currentUser.id === sellerId;
}

function canEditSale(
  currentUser: Pick<PublicUser, "id" | "role">,
  sale: Pick<SaleMutationRow, "seller_id" | "status">,
): boolean {
  if (canManageSales(currentUser)) {
    return true;
  }

  return (
    currentUser.role === UserRole.Seller &&
    currentUser.id === sale.seller_id &&
    sale.status === SaleStatus.Pending
  );
}

async function assertActiveSeller(client: PoolClient, sellerId: number): Promise<void> {
  const result = await client.query<{ id: number }>(
    `
    SELECT id
    FROM users
    WHERE id = $1
      AND status = 'active'
      AND role IN ('seller', 'manager', 'admin')
    LIMIT 1
    `,
    [sellerId],
  );

  if (!result.rows[0]) {
    throw new HttpError("Vendedor nao encontrado", 400);
  }
}

async function assertLeadForSale(
  client: PoolClient,
  leadId: number,
  currentUser: Pick<PublicUser, "id" | "role">,
): Promise<number> {
  const result = await client.query<LeadForSaleRow>(
    `
    SELECT id, name, email, phone, assigned_to
    FROM leads
    WHERE id = $1
    LIMIT 1
    `,
    [leadId],
  );
  const lead = result.rows[0];

  if (!lead) {
    throw new HttpError("Lead nao encontrado", 404);
  }

  if (
    !canManageSales(currentUser) &&
    lead.assigned_to !== null &&
    lead.assigned_to !== currentUser.id
  ) {
    throw new HttpError("Sem permissao para converter este lead", 403);
  }

  return lead.id;
}

async function createLeadForSale(
  client: PoolClient,
  input: NormalizedSaleMutationInput,
  sellerId: number,
): Promise<number> {
  validateRequiredString(input.customer_name, "Nome do cliente");

  const result = await client.query<{ id: number }>(
    `
    INSERT INTO leads (
      name, email, phone, status, assigned_to, created_at, updated_at
    ) VALUES ($1, $2, $3, 'converted', $4, NOW(), NOW())
    RETURNING id
    `,
    [input.customer_name, input.email, input.phone ?? "", sellerId],
  );

  return result.rows[0].id;
}

async function getOrCreateSellerCommissionSettings(
  client: PoolClient,
  sellerId: number,
  currentUserId: number,
  defaultCommissionRate: number,
): Promise<SellerCommissionSettingsRow> {
  const existing = await client.query<SellerCommissionSettingsRow>(
    `
    SELECT id, commission_percentage, commission_installments, bonus_percentage, bonus_threshold
    FROM seller_commission_settings
    WHERE seller_id = $1
      AND COALESCE(is_active::text, '1') IN ('1', 'true', 't')
    LIMIT 1
    `,
    [sellerId],
  );

  if (existing.rows[0]) {
    return existing.rows[0];
  }

  const created = await client.query<SellerCommissionSettingsRow>(
    `
    INSERT INTO seller_commission_settings (
      seller_id, commission_percentage, commission_installments, created_by
    ) VALUES ($1, $2, 5, $3)
    RETURNING id, commission_percentage, commission_installments, bonus_percentage, bonus_threshold
    `,
    [sellerId, defaultCommissionRate, currentUserId],
  );

  return created.rows[0];
}

async function getSaleForMutation(
  client: PoolClient,
  saleId: number,
): Promise<SaleMutationRow | null> {
  const result = await client.query<SaleMutationRow>(
    `
    SELECT
      id,
      lead_id,
      seller_id,
      sale_value,
      commission_percentage,
      commission_installments,
      vehicle_sold,
      payment_type,
      down_payment,
      financing_months,
      monthly_payment,
      contract_number,
      notes,
      status,
      sale_date
    FROM sales
    WHERE id = $1
    LIMIT 1
    `,
    [saleId],
  );

  return result.rows[0] ?? null;
}

interface NormalizedSaleMutationInput {
  commission_installments: number | null;
  commission_percentage: number | null;
  contract_number: string | null;
  customer_name: string | null;
  down_payment: number | null;
  email: string | null;
  financing_months: number | null;
  lead_id: number | null;
  monthly_payment: number | null;
  notes: string | null;
  payment_type: string | null;
  phone: string | null;
  sale_date: string | null;
  sale_value: number | null;
  seller_id: number | null;
  status: SaleStatus | null;
  vehicle_sold: string | null;
}

function normalizeSaleMutationInput(
  input: Partial<SaleMutationInput>,
): NormalizedSaleMutationInput {
  return {
    commission_installments: parseNullablePositiveInteger(input.commission_installments),
    commission_percentage: parseNullableNumber(input.commission_percentage),
    contract_number: normalizeNullableString(input.contract_number),
    customer_name: normalizeNullableString(input.customer_name),
    down_payment: parseNullableNumber(input.down_payment),
    email: normalizeNullableString(input.email),
    financing_months: parseNullablePositiveInteger(input.financing_months),
    lead_id: parseNullablePositiveInteger(input.lead_id),
    monthly_payment: parseNullableNumber(input.monthly_payment),
    notes: normalizeNullableString(input.notes),
    payment_type: normalizeNullableString(input.payment_type),
    phone: normalizeNullableString(input.phone),
    sale_date: normalizeDateString(input.sale_date),
    sale_value: parseNullableNumber(input.sale_value),
    seller_id: parseNullablePositiveInteger(input.seller_id),
    status: parseEnum(normalizeNullableString(input.status), SaleStatus) ?? null,
    vehicle_sold: normalizeNullableString(input.vehicle_sold),
  };
}

function validateRequiredSaleInput(input: NormalizedSaleMutationInput): void {
  if (!input.lead_id) {
    validateRequiredString(input.customer_name, "Nome do cliente");
  }

  validateRequiredString(input.vehicle_sold, "Veiculo vendido");
  validateRequiredString(input.payment_type, "Forma de pagamento");
  validateSaleValue(input.sale_value);
  validateCommissionPercentage(input.commission_percentage);
  validateCommissionInstallments(input.commission_installments);
}

function validateSaleValue(value: number | null): asserts value is number {
  if (!value || value <= 0) {
    throw new HttpError("Valor da venda deve ser maior que zero", 400);
  }
}

function validateCommissionPercentage(value: number | null): void {
  if (value !== null && (value < 0 || value > 100)) {
    throw new HttpError("A comissao deve estar entre 0% e 100%", 400);
  }
}

function validateCommissionInstallments(value: number | null): void {
  if (value !== null && value < 1) {
    throw new HttpError("O numero de parcelas da comissao deve ser maior que zero", 400);
  }
}

function validateRequiredString(value: string | null, label: string): asserts value is string {
  if (!value?.trim()) {
    throw new HttpError(`${label} e obrigatorio`, 400);
  }
}

function calculateCommission({
  applyBonus,
  commissionInstallments,
  commissionPercentage,
  commissionSettings,
  saleValue,
}: {
  applyBonus: boolean;
  commissionInstallments: number;
  commissionPercentage: number;
  commissionSettings: SellerCommissionSettingsRow;
  saleValue: number;
}) {
  let finalPercentage = commissionPercentage;
  const bonusPercentage = toNumber(commissionSettings.bonus_percentage);
  const bonusThreshold = toNumber(commissionSettings.bonus_threshold);

  if (applyBonus && bonusPercentage > 0 && bonusThreshold > 0 && saleValue >= bonusThreshold) {
    finalPercentage += bonusPercentage;
  }

  const installments = Math.max(commissionInstallments, 1);
  const commissionValue = (saleValue * finalPercentage) / 100;
  const monthlyCommission = commissionValue / installments;

  return {
    commissionValue,
    finalPercentage,
    installments,
    monthlyCommission,
  };
}

function normalizeNullableString(value: unknown): string | null {
  if (typeof value !== "string") {
    return null;
  }

  const trimmed = value.trim();
  return trimmed ? trimmed : null;
}

function normalizeDateString(value: unknown): string | null {
  const normalized = normalizeNullableString(value);

  if (!normalized) {
    return null;
  }

  return /^\d{4}-\d{2}-\d{2}$/.test(normalized) ? normalized : null;
}

function parseNullableNumber(value: unknown): number | null {
  if (value === null || value === undefined || value === "") {
    return null;
  }

  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : null;
}

function parseNullablePositiveInteger(value: unknown): number | null {
  const parsed = parseNullableNumber(value);

  if (parsed === null || !Number.isInteger(parsed) || parsed < 1) {
    return null;
  }

  return parsed;
}

function currentDateString(): string {
  return currentBrazilDateString();
}

function formatCurrencyForLog(value: number): string {
  return `R$ ${value.toFixed(2).replace(".", ",")}`;
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

function parsePeriod(value: string | null): SalePeriod | undefined {
  if (value === "today" || value === "week" || value === "month" || value === "quarter") {
    return value;
  }

  return undefined;
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

function toNullableNumber(value: string | number | null): number | null {
  if (value === null) {
    return null;
  }

  return toNumber(value);
}
