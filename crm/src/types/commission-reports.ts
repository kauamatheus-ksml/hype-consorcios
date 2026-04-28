import type { UserRole } from "@/types";

export interface CommissionReportSeller {
  id: number;
  full_name: string;
  role: UserRole;
}

export interface CommissionReportSellerRow {
  avg_commission_percentage: number;
  sales_count: number;
  seller_id: number;
  seller_name: string;
  total_commission: number;
  total_monthly_commission: number;
  total_sales: number;
}

export interface CommissionReportMonth {
  month: number;
  month_name: string;
  sellers: CommissionReportSellerRow[];
  totals: CommissionReportSummary;
  year: number;
}

export interface CommissionReportSale {
  commission_installments: number | null;
  commission_percentage: number | null;
  commission_value: number | null;
  customer_name: string | null;
  id: number;
  monthly_commission: number | null;
  sale_date: string | null;
  sale_value: number | null;
  seller_id: number;
  seller_name: string;
  vehicle_sold: string | null;
}

export interface CommissionReportSummary {
  sales_count: number;
  total_commission: number;
  total_monthly_commission: number;
  total_sales: number;
}

export interface CommissionReportPayload {
  filters: {
    month: number | null;
    seller_id: number | null;
    year: number;
  };
  months: CommissionReportMonth[];
  sales: CommissionReportSale[];
  sellers: CommissionReportSeller[];
  summary: CommissionReportSummary;
}
