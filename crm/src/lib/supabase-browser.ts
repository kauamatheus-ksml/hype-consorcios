"use client";

import { createClient, type SupabaseClient } from "@supabase/supabase-js";

import type { Database } from "@/types";

let browserClient: SupabaseClient<Database> | null = null;

export function getSupabaseBrowserClient(): SupabaseClient<Database> {
  if (browserClient) {
    return browserClient;
  }

  const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
  const supabaseAnonKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY;

  if (!supabaseUrl || !supabaseAnonKey) {
    throw new Error("Missing browser Supabase environment variables.");
  }

  browserClient = createClient<Database>(supabaseUrl, supabaseAnonKey);

  return browserClient;
}
