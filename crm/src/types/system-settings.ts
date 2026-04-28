export interface SystemSettingItem {
  description: string | null;
  id: number;
  setting_key: string;
  setting_value: string | null;
  updated_at: string | null;
  updated_by: number | null;
  updated_by_name: string | null;
}

export interface SystemSettingMutationInput {
  description?: string | null;
  setting_key?: string | null;
  setting_value?: string | number | null;
}
