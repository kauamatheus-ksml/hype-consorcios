import type { UserRole, UserStatus } from "@/types";

export interface UserListItem {
  id: number;
  username: string;
  email: string;
  full_name: string;
  role: UserRole;
  status: UserStatus;
  created_at: string | null;
  updated_at: string | null;
  last_login: string | null;
}

export interface UserListFilters {
  role?: UserRole[];
  search?: string;
  status?: UserStatus;
}

export interface UserMutationInput {
  email?: string | null;
  full_name?: string | null;
  password?: string | null;
  role?: string | null;
  status?: string | null;
  username?: string | null;
}
