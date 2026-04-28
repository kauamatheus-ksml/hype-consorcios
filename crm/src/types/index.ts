export type DateTimeString = string;
export type DateString = string;

export enum UserRole {
  Admin = "admin",
  Manager = "manager",
  Seller = "seller",
  Viewer = "viewer",
}

export enum UserStatus {
  Active = "active",
  Inactive = "inactive",
}

export enum LeadStatus {
  New = "new",
  Contacted = "contacted",
  Negotiating = "negotiating",
  Converted = "converted",
  Lost = "lost",
}

export enum LeadPriority {
  Low = "low",
  Medium = "medium",
  High = "high",
  Urgent = "urgent",
}

export enum SaleStatus {
  Pending = "pending",
  Confirmed = "confirmed",
  Cancelled = "cancelled",
  Completed = "completed",
}

export enum InteractionType {
  Call = "call",
  Whatsapp = "whatsapp",
  Email = "email",
  Meeting = "meeting",
  Note = "note",
  StatusChange = "status_change",
}

export enum InteractionResult {
  Positive = "positive",
  Neutral = "neutral",
  Negative = "negative",
  NoAnswer = "no_answer",
}

export enum SiteConfigType {
  Text = "text",
  Textarea = "textarea",
  Image = "image",
  Number = "number",
  Boolean = "boolean",
}

export type DownPaymentFlag = "yes" | "no";

export interface User {
  id: number;
  username: string;
  email: string;
  password_hash: string;
  full_name: string;
  role: UserRole;
  status: UserStatus;
  created_at: DateTimeString;
  updated_at: DateTimeString;
  last_login: DateTimeString | null;
  created_by: number | null;
}

export type PublicUser = Omit<User, "password_hash">;

export interface Lead {
  id: number;
  name: string;
  email: string | null;
  phone: string;
  vehicle_interest: string | null;
  has_down_payment: DownPaymentFlag | null;
  down_payment_value: number | null;
  source_page: string | null;
  ip_address: string | null;
  user_agent: string | null;
  status: LeadStatus;
  priority: LeadPriority;
  notes: string | null;
  assigned_to: number | null;
  created_at: DateTimeString;
  updated_at: DateTimeString;
  contacted_at: DateTimeString | null;
}

export interface LeadDetailed extends Lead {
  assigned_to_name: string | null;
  assigned_to_username: string | null;
  interactions_count: number;
  last_interaction: DateTimeString | null;
  sale_id: number | null;
}

export interface Sale {
  id: number;
  lead_id: number;
  seller_id: number;
  sale_value: number | null;
  commission_percentage: number;
  commission_value: number | null;
  commission_installments: number;
  monthly_commission: number | null;
  vehicle_sold: string | null;
  payment_type: string | null;
  down_payment: number | null;
  financing_months: number | null;
  monthly_payment: number | null;
  contract_number: string | null;
  notes: string | null;
  status: SaleStatus;
  sale_date: DateTimeString;
  created_at: DateTimeString;
  updated_at: DateTimeString;
  created_by: number | null;
}

export interface LeadInteraction {
  id: number;
  lead_id: number;
  user_id: number | null;
  interaction_type: InteractionType;
  description: string | null;
  result: InteractionResult | null;
  next_contact_date: DateString | null;
  created_at: DateTimeString;
}

export interface SiteConfig {
  id: number;
  config_key: string;
  config_value: string | null;
  config_type: SiteConfigType;
  section: string;
  display_name: string;
  description: string | null;
  created_at: DateTimeString;
  updated_at: DateTimeString;
}

export interface FAQ {
  id: number;
  question: string;
  answer: string;
  display_order: number;
  is_active: number;
  created_at: DateTimeString;
  updated_at: DateTimeString;
}

export interface SellerCommissionSettings {
  id: number;
  seller_id: number;
  commission_percentage: number;
  commission_installments: number;
  min_sale_value: number;
  max_sale_value: number | null;
  bonus_percentage: number;
  bonus_threshold: number | null;
  is_active: number;
  notes: string | null;
  created_at: DateTimeString;
  updated_at: DateTimeString;
  created_by: number | null;
  updated_by: number | null;
}

export interface UserSession {
  id: string;
  user_id: number;
  ip_address: string | null;
  user_agent: string | null;
  created_at: DateTimeString;
  expires_at: DateTimeString;
  last_activity: DateTimeString;
}

export interface AuditLog {
  id: number;
  user_id: number | null;
  action: string;
  table_name: string | null;
  record_id: number | null;
  old_values: Record<string, unknown> | null;
  new_values: Record<string, unknown> | null;
  description: string | null;
  ip_address: string | null;
  user_agent: string | null;
  created_at: DateTimeString;
}

export interface SystemSetting {
  id: number;
  setting_key: string;
  setting_value: string | null;
  description: string | null;
  updated_at: DateTimeString;
  updated_by: number | null;
}

type DbRow<T> = T & Record<string, unknown>;
type InsertRow<T extends { id: number }> = Omit<T, "id"> & { id?: number } & Record<string, unknown>;
type UpdateRow<T> = Partial<T> & Record<string, unknown>;

export interface Database {
  public: {
    Tables: {
      users: {
        Row: DbRow<User>;
        Insert: InsertRow<User>;
        Update: UpdateRow<User>;
        Relationships: [];
      };
      leads: {
        Row: DbRow<Lead>;
        Insert: InsertRow<Lead>;
        Update: UpdateRow<Lead>;
        Relationships: [];
      };
      sales: {
        Row: DbRow<Sale>;
        Insert: InsertRow<Sale>;
        Update: UpdateRow<Sale>;
        Relationships: [];
      };
      lead_interactions: {
        Row: DbRow<LeadInteraction>;
        Insert: InsertRow<LeadInteraction>;
        Update: UpdateRow<LeadInteraction>;
        Relationships: [];
      };
      site_config: {
        Row: DbRow<SiteConfig>;
        Insert: InsertRow<SiteConfig>;
        Update: UpdateRow<SiteConfig>;
        Relationships: [];
      };
      faqs: {
        Row: DbRow<FAQ>;
        Insert: InsertRow<FAQ>;
        Update: UpdateRow<FAQ>;
        Relationships: [];
      };
      seller_commission_settings: {
        Row: DbRow<SellerCommissionSettings>;
        Insert: InsertRow<SellerCommissionSettings>;
        Update: UpdateRow<SellerCommissionSettings>;
        Relationships: [];
      };
      user_sessions: {
        Row: DbRow<UserSession>;
        Insert: DbRow<UserSession>;
        Update: UpdateRow<UserSession>;
        Relationships: [];
      };
      audit_logs: {
        Row: DbRow<AuditLog>;
        Insert: InsertRow<AuditLog>;
        Update: UpdateRow<AuditLog>;
        Relationships: [];
      };
      system_settings: {
        Row: DbRow<SystemSetting>;
        Insert: InsertRow<SystemSetting>;
        Update: UpdateRow<SystemSetting>;
        Relationships: [];
      };
    };
    Views: {
      leads_detailed: {
        Row: DbRow<LeadDetailed>;
        Relationships: [];
      };
      leads_summary: {
        Row: DbRow<{
          status: LeadStatus;
          total: number;
          last_30_days: number;
          last_7_days: number;
        }>;
        Relationships: [];
      };
      sales_by_seller: {
        Row: DbRow<{
          seller_id: number;
          seller_name: string;
          total_sales: number;
          total_value: number | null;
          avg_sale_value: number | null;
          total_commission: number | null;
          sales_last_30_days: number;
        }>;
        Relationships: [];
      };
    };
    Functions: Record<string, never>;
  };
}
