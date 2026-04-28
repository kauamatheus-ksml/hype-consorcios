import type { SaleStatus, UserRole } from "@/types";

export type SalePeriod = "today" | "week" | "month" | "quarter";

export interface SaleListItem {
  id: number;
  lead_id: number;
  seller_id: number;
  seller_name: string | null;
  customer_name: string | null;
  customer_phone: string | null;
  customer_email: string | null;
  lead_name: string | null;
  sale_value: number | null;
  commission_percentage: number | null;
  commission_value: number | null;
  commission_installments: number | null;
  monthly_commission: number | null;
  vehicle_sold: string | null;
  payment_type: string | null;
  down_payment: number | null;
  financing_months: number | null;
  monthly_payment: number | null;
  contract_number: string | null;
  notes: string | null;
  status: SaleStatus;
  sale_date: string | null;
  created_at: string | null;
  updated_at: string | null;
  sale_year: number | null;
  sale_month: number | null;
  sale_month_name: string | null;
}

export interface SalePagination {
  current_page: number;
  per_page: number;
  total_records: number;
  total_pages: number;
  has_next: boolean;
  has_prev: boolean;
}

export interface SaleStats {
  total: number;
  revenue: number;
  commission: number;
  pending: number;
  confirmed: number;
  cancelled: number;
  completed: number;
  today: number;
  today_revenue: number;
  this_week: number;
  this_week_revenue: number;
  this_month: number;
  this_month_revenue: number;
  average_ticket: number;
}

export interface SaleDailyStat {
  date: string;
  count: number;
  revenue: number;
}

export interface SaleTopSellerStat {
  seller_name: string;
  total_sales: number;
  total_revenue: number;
  total_commission: number;
}

export interface SaleTopVehicleStat {
  vehicle_sold: string;
  count: number;
  total_value: number;
}

export interface SaleSellerOption {
  id: number;
  full_name: string;
  role: UserRole;
}

export interface SaleAdditionalStats {
  daily_sales?: SaleDailyStat[];
  sellers?: SaleSellerOption[];
  top_sellers?: SaleTopSellerStat[];
  top_vehicles?: SaleTopVehicleStat[];
}

export interface SaleMutationInput {
  commission_installments?: number | null;
  commission_percentage?: number | null;
  contract_number?: string | null;
  customer_name?: string | null;
  down_payment?: number | null;
  email?: string | null;
  financing_months?: number | null;
  lead_id?: number | null;
  monthly_payment?: number | null;
  notes?: string | null;
  payment_type?: string | null;
  phone?: string | null;
  sale_date?: string | null;
  sale_value?: number | null;
  seller_id?: number | null;
  status?: string | null;
  vehicle_sold?: string | null;
}

export interface SaleCancelInput {
  reason?: string | null;
}
