"use client";

import { Eye, EyeOff, Loader2, LockKeyhole, UserRound } from "lucide-react";
import { useRouter } from "next/navigation";
import { FormEvent, useState } from "react";

import { useAuthStore } from "@/stores/auth-store";
import type { PublicUser } from "@/types";

interface LoginResponse {
  success: boolean;
  message?: string;
  user?: PublicUser;
}

export function LoginForm() {
  const router = useRouter();
  const setUser = useAuthStore((state) => state.setUser);
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [remember, setRemember] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError("");
    setIsSubmitting(true);

    try {
      const response = await fetch("/api/auth/login", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          username,
          password,
          remember,
        }),
      });

      const result = (await response.json()) as LoginResponse;

      if (!response.ok || !result.success || !result.user) {
        setError(result.message ?? "Nao foi possivel entrar");
        return;
      }

      setUser(result.user);
      router.replace("/dashboard");
      router.refresh();
    } catch {
      setError("Nao foi possivel conectar ao servidor");
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <form className="mt-6 space-y-4" onSubmit={handleSubmit}>
      <label className="block">
        <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">
          Usuario ou email
        </span>
        <span className="relative block">
          <UserRound
            className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
            aria-hidden
          />
          <input
            autoComplete="username"
            className="hype-focus h-11 w-full rounded-[8px] border border-white/10 bg-[#1e293b] pl-10 pr-3 text-sm text-white placeholder:text-slate-500"
            onChange={(event) => setUsername(event.target.value)}
            placeholder="usuario@hypeconsorcios.com.br"
            required
            type="text"
            value={username}
          />
        </span>
      </label>

      <label className="block">
        <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">
          Senha
        </span>
        <span className="relative block">
          <LockKeyhole
            className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
            aria-hidden
          />
          <input
            autoComplete="current-password"
            className="hype-focus h-11 w-full rounded-[8px] border border-white/10 bg-[#1e293b] pl-10 pr-11 text-sm text-white placeholder:text-slate-500"
            onChange={(event) => setPassword(event.target.value)}
            placeholder="Senha"
            required
            type={showPassword ? "text" : "password"}
            value={password}
          />
          <button
            aria-label={showPassword ? "Ocultar senha" : "Mostrar senha"}
            className="hype-focus absolute right-2 top-1/2 inline-flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-[8px] text-slate-400 hover:bg-white/10 hover:text-white"
            onClick={() => setShowPassword((current) => !current)}
            type="button"
          >
            {showPassword ? (
              <EyeOff className="h-4 w-4" aria-hidden />
            ) : (
              <Eye className="h-4 w-4" aria-hidden />
            )}
          </button>
        </span>
      </label>

      <label className="flex items-center gap-2 text-sm text-slate-300">
        <input
          checked={remember}
          className="h-4 w-4 accent-[#3be1c9]"
          onChange={(event) => setRemember(event.target.checked)}
          type="checkbox"
        />
        Lembrar-me
      </label>

      {error ? (
        <div className="rounded-[8px] border border-red-400/30 bg-red-500/10 px-3 py-2 text-sm text-red-100">
          {error}
        </div>
      ) : null}

      <button
        className="hype-focus inline-flex h-11 w-full items-center justify-center gap-2 rounded-[8px] bg-[#3be1c9] text-sm font-bold text-[#242328] transition hover:bg-[#35cab5] disabled:cursor-not-allowed disabled:opacity-70"
        disabled={isSubmitting}
        type="submit"
      >
        {isSubmitting ? <Loader2 className="h-4 w-4 animate-spin" aria-hidden /> : null}
        Entrar
      </button>
    </form>
  );
}
