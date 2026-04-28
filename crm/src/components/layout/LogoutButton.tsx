"use client";

import { LogOut } from "lucide-react";
import { useRouter } from "next/navigation";
import { useState } from "react";

import { useAuthStore } from "@/stores/auth-store";

export function LogoutButton() {
  const router = useRouter();
  const clearUser = useAuthStore((state) => state.clearUser);
  const [isPending, setIsPending] = useState(false);

  async function handleLogout() {
    setIsPending(true);

    try {
      await fetch("/api/auth/logout", {
        method: "POST",
      });
    } finally {
      clearUser();
      router.replace("/login");
      router.refresh();
    }
  }

  return (
    <button
      className="hype-focus inline-flex h-9 items-center gap-2 rounded-[8px] border border-slate-200 px-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
      disabled={isPending}
      onClick={handleLogout}
      type="button"
    >
      <LogOut className="h-4 w-4" aria-hidden />
      Sair
    </button>
  );
}
