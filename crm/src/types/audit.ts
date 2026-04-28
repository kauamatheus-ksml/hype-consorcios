export interface AuditLogItem {
  id: number;
  user_id: number | null;
  user_full_name: string | null;
  username: string | null;
  action: string;
  table_name: string | null;
  record_id: number | null;
  description: string | null;
  ip_address: string | null;
  user_agent: string | null;
  created_at: string;
}

export interface AuditPagination {
  current_page: number;
  per_page: number;
  total_records: number;
  total_pages: number;
  has_next: boolean;
  has_prev: boolean;
}

export interface AuditStats {
  total: number;
  today: number;
  week: number;
}
