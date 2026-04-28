export interface SiteConfigItem {
  id: number;
  config_key: string;
  config_value: string;
  config_type: string;
  section: string;
  display_name: string;
  description: string | null;
  updated_at: string | null;
}

export interface SiteConfigUpdateInput {
  section?: string | null;
  values?: Record<string, string | null | undefined> | null;
}

export interface FAQItem {
  answer: string;
  created_at: string | null;
  display_order: number;
  id: number;
  is_active: boolean;
  question: string;
  updated_at: string | null;
}

export interface FAQMutationInput {
  answer?: string | null;
  display_order?: number | string | null;
  is_active?: boolean | number | string | null;
  question?: string | null;
}
