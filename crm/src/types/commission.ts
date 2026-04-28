import type { UserRole, UserStatus } from "@/types";

export interface CommissionSellerItem {
  id: number;
  full_name: string;
  username: string;
  role: UserRole;
  status: UserStatus;
  commission_percentage: number;
  commission_installments: number;
  min_sale_value: number;
  max_sale_value: number | null;
  bonus_percentage: number;
  bonus_threshold: number | null;
  is_active: boolean;
  notes: string | null;
  commission_updated_at: string | null;
  updated_by_name: string | null;
  has_config: boolean;
}

export interface CommissionSettingsInput {
  bonus_percentage?: number | null;
  bonus_threshold?: number | null;
  commission_installments?: number | null;
  commission_percentage?: number | null;
  is_active?: boolean | null;
  max_sale_value?: number | null;
  min_sale_value?: number | null;
  notes?: string | null;
  seller_id?: number | null;
}
