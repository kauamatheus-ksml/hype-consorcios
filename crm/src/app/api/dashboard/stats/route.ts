import { requireCurrentUser } from "@/lib/current-user";
import { dbQuery } from "@/lib/db-direct";
import { apiError, apiSuccess, HttpError } from "@/lib/http";
import { UserRole } from "@/types";

export const runtime = "nodejs";

interface SellerOption {
  id: number;
  full_name: string;
  username: string;
}

interface TopSeller {
  full_name: string;
  sales_count: number;
  total_commission: number;
}

interface LeadsBySource {
  source: string;
  count: number;
}

interface MonthlySales {
  month: string;
  sales_count: number;
  total_value: number;
}

interface LeadsByStatus {
  status: string;
  count: number;
}

interface RecentLead {
  lead_name: string;
  phone: string;
  source: string | null;
  status: string;
  created_at: string;
  assigned_to: string | null;
}

interface DashboardStats {
  total_sales: number;
  total_revenue: number;
  total_commissions: number;
  pending_sales: number;
  total_leads: number;
  leads_this_month: number;
  sales_this_month: number;
  conversion_rate: number;
  user_role: UserRole;
  is_admin: boolean;
  is_global_view: boolean;
  selected_seller_id: number | null;
  sellers: SellerOption[];
  top_sellers: TopSeller[];
  leads_by_source: LeadsBySource[];
  monthly_sales: MonthlySales[];
  leads_by_status: LeadsByStatus[];
  recent_leads: RecentLead[];
}

export async function GET(request: Request) {
  try {
    const currentUser = await requireCurrentUser();
    const url = new URL(request.url);
    const scope = resolveDashboardScope(currentUser.role, currentUser.id, url.searchParams);

    const sellerFilter = scope.scopedSellerId ? "AND s.seller_id = $1" : "";
    const leadFilter = scope.scopedSellerId ? "AND l.assigned_to = $1" : "";
    const sellerParams = scope.scopedSellerId ? [scope.scopedSellerId] : [];
    const leadParams = scope.scopedSellerId ? [scope.scopedSellerId] : [];

    const [
      totalSales,
      totalRevenue,
      totalCommissions,
      pendingSales,
      totalLeads,
      leadsThisMonth,
      salesThisMonth,
      sellers,
      topSellers,
      leadsBySource,
      monthlySales,
      leadsByStatus,
      recentLeads,
    ] = await Promise.all([
      numberQuery(
        `SELECT COUNT(*) AS value FROM sales s WHERE s.status != 'cancelled' ${sellerFilter}`,
        sellerParams,
      ),
      numberQuery(
        `SELECT COALESCE(SUM(s.sale_value), 0) AS value FROM sales s WHERE s.status = 'confirmed' ${sellerFilter}`,
        sellerParams,
      ),
      numberQuery(
        `SELECT COALESCE(SUM((s.sale_value * COALESCE(scs.commission_percentage, 1.50)) / 100), 0) AS value
         FROM sales s
         LEFT JOIN seller_commission_settings scs
           ON s.seller_id = scs.seller_id
          AND COALESCE(scs.is_active, 1) = 1
         WHERE s.status = 'confirmed'
           AND s.created_at >= DATE_TRUNC('month', CURRENT_DATE)
           AND s.created_at < DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month'
           ${sellerFilter}`,
        sellerParams,
      ),
      numberQuery(
        `SELECT COUNT(*) AS value FROM sales s WHERE s.status = 'pending' ${sellerFilter}`,
        sellerParams,
      ),
      numberQuery(
        `SELECT COUNT(*) AS value FROM leads l WHERE 1=1 ${leadFilter}`,
        leadParams,
      ),
      numberQuery(
        `SELECT COUNT(*) AS value
         FROM leads l
         WHERE l.created_at >= DATE_TRUNC('month', CURRENT_DATE)
           AND l.created_at < DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month'
           ${leadFilter}`,
        leadParams,
      ),
      numberQuery(
        `SELECT COUNT(*) AS value
         FROM sales s
         WHERE s.created_at >= DATE_TRUNC('month', CURRENT_DATE)
           AND s.created_at < DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month'
           AND s.status != 'cancelled'
           ${sellerFilter}`,
        sellerParams,
      ),
      scope.isAdmin ? getSellers() : Promise.resolve([]),
      getTopSellers(scope.scopedSellerId),
      getLeadsBySource(scope.scopedSellerId),
      getMonthlySales(scope.scopedSellerId),
      getLeadsByStatus(scope.scopedSellerId),
      getRecentLeads(scope.scopedSellerId),
    ]);

    const stats: DashboardStats = {
      total_sales: totalSales,
      total_revenue: totalRevenue,
      total_commissions: totalCommissions,
      pending_sales: pendingSales,
      total_leads: totalLeads,
      leads_this_month: leadsThisMonth,
      sales_this_month: salesThisMonth,
      conversion_rate: totalLeads > 0 ? roundNumber((totalSales / totalLeads) * 100) : 0,
      user_role: currentUser.role,
      is_admin: scope.isAdmin,
      is_global_view: scope.isGlobalView,
      selected_seller_id: scope.selectedSellerId,
      sellers,
      top_sellers: topSellers,
      leads_by_source: leadsBySource,
      monthly_sales: monthlySales,
      leads_by_status: leadsByStatus,
      recent_leads: recentLeads,
    };

    return apiSuccess({ stats });
  } catch (error) {
    return apiError(error);
  }
}

function resolveDashboardScope(
  userRole: UserRole,
  userId: number,
  searchParams: URLSearchParams,
) {
  const isAdmin = userRole === UserRole.Admin;
  const rawSellerId = searchParams.get("seller_id");
  let selectedSellerId: number | null = null;

  if (rawSellerId) {
    const parsedSellerId = Number(rawSellerId);

    if (!Number.isInteger(parsedSellerId) || parsedSellerId <= 0) {
      throw new HttpError("seller_id invalido", 400);
    }

    selectedSellerId = parsedSellerId;
  }

  return {
    isAdmin,
    selectedSellerId: isAdmin ? selectedSellerId : null,
    scopedSellerId: isAdmin ? selectedSellerId : userId,
    isGlobalView: isAdmin && selectedSellerId === null,
  };
}

async function numberQuery(sql: string, params: unknown[] = []): Promise<number> {
  const result = await dbQuery<{ value: string | number }>(sql, params);
  return toNumber(result.rows[0]?.value);
}

async function getSellers(): Promise<SellerOption[]> {
  const result = await dbQuery<SellerOption>(`
    SELECT id, full_name, username
    FROM users
    WHERE role IN ('seller', 'manager', 'admin')
      AND status = 'active'
    ORDER BY full_name
  `);

  return result.rows;
}

async function getTopSellers(scopedSellerId: number | null): Promise<TopSeller[]> {
  const filter = scopedSellerId ? "AND u.id = $1" : "";
  const params = scopedSellerId ? [scopedSellerId] : [];
  const result = await dbQuery<{
    full_name: string;
    sales_count: string | number;
    total_commission: string | number;
  }>(
    `
    SELECT
      u.full_name,
      COUNT(s.id) AS sales_count,
      COALESCE(SUM(s.commission_value), 0) AS total_commission
    FROM users u
    LEFT JOIN sales s
      ON u.id = s.seller_id
     AND s.created_at >= DATE_TRUNC('month', CURRENT_DATE)
     AND s.created_at < DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month'
     AND s.status != 'cancelled'
    WHERE u.role IN ('seller', 'manager', 'admin')
      AND u.status = 'active'
      ${filter}
    GROUP BY u.id, u.full_name
    ORDER BY sales_count DESC
    LIMIT 5
    `,
    params,
  );

  return result.rows.map((row) => ({
    full_name: row.full_name,
    sales_count: toNumber(row.sales_count),
    total_commission: toNumber(row.total_commission),
  }));
}

async function getLeadsBySource(scopedSellerId: number | null): Promise<LeadsBySource[]> {
  const filter = scopedSellerId ? "AND l.assigned_to = $1" : "";
  const params = scopedSellerId ? [scopedSellerId] : [];
  const result = await dbQuery<{
    source: string;
    count: string | number;
  }>(
    `
    SELECT
      COALESCE(l.source_page, 'Nao informado') AS source,
      COUNT(*) AS count
    FROM leads l
    WHERE l.created_at >= DATE_TRUNC('month', CURRENT_DATE)
      AND l.created_at < DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month'
      ${filter}
    GROUP BY l.source_page
    ORDER BY count DESC
    LIMIT 10
    `,
    params,
  );

  return result.rows.map((row) => ({
    source: row.source,
    count: toNumber(row.count),
  }));
}

async function getMonthlySales(scopedSellerId: number | null): Promise<MonthlySales[]> {
  const filter = scopedSellerId ? "AND s.seller_id = $1" : "";
  const params = scopedSellerId ? [scopedSellerId] : [];
  const result = await dbQuery<{
    month: string;
    sales_count: string | number;
    total_value: string | number;
  }>(
    `
    SELECT
      TO_CHAR(s.created_at, 'YYYY-MM') AS month,
      COUNT(*) AS sales_count,
      COALESCE(SUM(s.sale_value), 0) AS total_value
    FROM sales s
    WHERE s.created_at >= CURRENT_DATE - INTERVAL '6 months'
      ${filter}
    GROUP BY TO_CHAR(s.created_at, 'YYYY-MM')
    ORDER BY month ASC
    `,
    params,
  );

  return result.rows.map((row) => ({
    month: row.month,
    sales_count: toNumber(row.sales_count),
    total_value: toNumber(row.total_value),
  }));
}

async function getLeadsByStatus(scopedSellerId: number | null): Promise<LeadsByStatus[]> {
  const filter = scopedSellerId ? "AND l.assigned_to = $1" : "";
  const params = scopedSellerId ? [scopedSellerId] : [];
  const result = await dbQuery<{
    status: string;
    count: string | number;
  }>(
    `
    SELECT l.status, COUNT(*) AS count
    FROM leads l
    WHERE 1=1
      ${filter}
    GROUP BY l.status
    ORDER BY count DESC
    `,
    params,
  );

  return result.rows.map((row) => ({
    status: row.status,
    count: toNumber(row.count),
  }));
}

async function getRecentLeads(scopedSellerId: number | null): Promise<RecentLead[]> {
  const filter = scopedSellerId ? "AND l.assigned_to = $1" : "";
  const params = scopedSellerId ? [scopedSellerId] : [];
  const result = await dbQuery<RecentLead>(
    `
    SELECT
      l.name AS lead_name,
      l.phone,
      l.source_page AS source,
      l.status,
      l.created_at,
      u.full_name AS assigned_to
    FROM leads l
    LEFT JOIN users u ON l.assigned_to = u.id
    WHERE 1=1
      ${filter}
    ORDER BY l.created_at DESC
    LIMIT 10
    `,
    params,
  );

  return result.rows;
}

function toNumber(value: string | number | null | undefined): number {
  if (value === null || value === undefined) {
    return 0;
  }

  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
}

function roundNumber(value: number, precision = 2): number {
  const factor = 10 ** precision;
  return Math.round(value * factor) / factor;
}
