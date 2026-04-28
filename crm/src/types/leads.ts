import type { InteractionResult, InteractionType, LeadPriority, LeadStatus, UserRole } from "@/types";

export interface LeadListItem {
  id: number;
  name: string;
  email: string | null;
  phone: string;
  vehicle_interest: string | null;
  has_down_payment: string | null;
  down_payment_value: number | null;
  source_page: string | null;
  status: LeadStatus;
  priority: LeadPriority;
  notes: string | null;
  assigned_to: number | null;
  assigned_to_name: string | null;
  interactions_count: number;
  last_interaction: string | null;
  sale_id: number | null;
  created_at: string;
  updated_at: string;
  contacted_at: string | null;
}

export interface LeadDetailsItem extends LeadListItem {
  assigned_to_email: string | null;
}

export interface LeadInteractionItem {
  id: number;
  lead_id: number;
  user_id: number | null;
  user_name: string | null;
  interaction_type: InteractionType;
  description: string | null;
  result: InteractionResult | null;
  next_contact_date: string | null;
  created_at: string;
}

export interface LeadRelatedSaleItem {
  id: number;
  lead_id: number;
  seller_id: number | null;
  seller_name: string | null;
  sale_value: number | null;
  commission_value: number | null;
  vehicle_sold: string | null;
  status: string | null;
  sale_date: string | null;
  created_at: string | null;
}

export interface LeadDetailsPayload {
  lead: LeadDetailsItem;
  interactions: LeadInteractionItem[];
  sales: LeadRelatedSaleItem[];
}

export interface LeadPagination {
  current_page: number;
  per_page: number;
  total_records: number;
  total_pages: number;
  has_next: boolean;
  has_prev: boolean;
}

export interface LeadStats {
  total: number;
  new: number;
  contacted: number;
  negotiating: number;
  converted: number;
  lost: number;
  today?: number;
  this_week?: number;
  this_month?: number;
}

export interface LeadSourceStat {
  source: string;
  count: number;
}

export interface LeadPriorityStat {
  priority: string;
  count: number;
}

export interface LeadSellerStat {
  seller_name: string;
  count: number;
}

export interface LeadSellerOption {
  id: number;
  full_name: string;
  role: UserRole;
}

export interface LeadAdditionalStats {
  leads_by_source?: LeadSourceStat[];
  leads_by_priority?: LeadPriorityStat[];
  leads_by_seller?: LeadSellerStat[];
  sellers?: LeadSellerOption[];
}

export interface LeadMutationInput {
  assigned_to?: number | null;
  down_payment_value?: number | string | null;
  email?: string | null;
  has_down_payment?: string | null;
  name?: string | null;
  notes?: string | null;
  phone?: string | null;
  priority?: string | null;
  source_page?: string | null;
  status?: string | null;
  vehicle_interest?: string | null;
}

export interface LeadInteractionMutationInput {
  description?: string | null;
  interaction_type?: string | null;
  next_contact_date?: string | null;
  result?: string | null;
}

export interface PublicLeadCaptureInput {
  downPayment?: number | string | null;
  down_payment_value?: number | string | null;
  email?: string | null;
  hasDownPayment?: string | null;
  has_down_payment?: string | null;
  name?: string | null;
  phone?: string | null;
  source?: string | null;
  vehicle?: string | null;
  vehicle_interest?: string | null;
}

export interface PublicLeadCaptureResult {
  lead_id: number;
  message: string;
  redirect_whatsapp: string;
  updated_existing: boolean;
}

export interface LeadWhatsAppUrlResult {
  message: string;
  phone: string;
  whatsapp_url: string;
}
