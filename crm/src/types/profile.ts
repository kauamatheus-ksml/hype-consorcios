import type { PublicUser } from "@/types";

export interface ProfileStats {
  converted_leads: number;
  monthly_sales: number;
  monthly_sales_value: number;
  total_leads: number;
  total_sales: number;
  total_sales_value: number;
}

export interface ProfilePayload {
  stats: ProfileStats;
  user: PublicUser;
}

export interface ProfileUpdateInput {
  email?: string | null;
  full_name?: string | null;
}
