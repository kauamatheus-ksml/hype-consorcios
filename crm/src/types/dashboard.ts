import type { UserRole } from "@/types";

export interface SellerOption {
  id: number;
  full_name: string;
  username: string;
}

export interface TopSeller {
  full_name: string;
  sales_count: number;
  total_commission: number;
}

export interface LeadsBySource {
  source: string;
  count: number;
}

export interface MonthlySales {
  month: string;
  sales_count: number;
  total_value: number;
}

export interface LeadsByStatus {
  status: string;
  count: number;
}

export interface RecentLead {
  lead_name: string;
  phone: string;
  source: string | null;
  status: string;
  created_at: string;
  assigned_to: string | null;
}

export interface DashboardStats {
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
