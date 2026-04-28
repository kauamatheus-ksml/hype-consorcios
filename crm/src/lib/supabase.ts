import "server-only";

import { createClient, type SupabaseClient } from "@supabase/supabase-js";

import { getRequiredEnv } from "@/lib/env";
import type { Database } from "@/types";

let adminClient: SupabaseClient<Database> | null = null;

export function getSupabaseAdminClient(): SupabaseClient<Database> {
  if (adminClient) {
    return adminClient;
  }

  adminClient = createClient<Database>(
    getRequiredEnv("NEXT_PUBLIC_SUPABASE_URL"),
    getRequiredEnv("SUPABASE_SERVICE_ROLE_KEY"),
    {
      auth: {
        autoRefreshToken: false,
        persistSession: false,
      },
      global: {
        headers: {
          "X-Client-Info": "hype-crm-next-server",
        },
      },
    },
  );

  return adminClient;
}

export const supabaseAdmin = getSupabaseAdminClient;
