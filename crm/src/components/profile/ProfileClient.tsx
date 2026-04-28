"use client";

import {
  CheckCircle2,
  CircleDollarSign,
  KeyRound,
  Loader2,
  Save,
  UserRound,
  UsersRound,
} from "lucide-react";
import { useRouter } from "next/navigation";
import { useState, type FormEvent } from "react";

import { formatBrazilDateTime } from "@/lib/date-format";
import { UserRole, type PublicUser } from "@/types";
import type { ProfilePayload } from "@/types/profile";

interface ProfileClientProps {
  initialProfile: ProfilePayload;
}

interface ProfileResponse extends ProfilePayload {
  message?: string;
  success: boolean;
}

interface PasswordResponse {
  message?: string;
  success: boolean;
}

const roleLabels: Record<UserRole, string> = {
  [UserRole.Admin]: "Administrador",
  [UserRole.Manager]: "Gerente",
  [UserRole.Seller]: "Vendedor",
  [UserRole.Viewer]: "Visualizador",
};

export function ProfileClient({ initialProfile }: Readonly<ProfileClientProps>) {
  const router = useRouter();
  const [profile, setProfile] = useState(initialProfile);
  const [fullName, setFullName] = useState(initialProfile.user.full_name);
  const [email, setEmail] = useState(initialProfile.user.email);
  const [currentPassword, setCurrentPassword] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [isProfileSaving, setIsProfileSaving] = useState(false);
  const [isPasswordSaving, setIsPasswordSaving] = useState(false);

  async function submitProfile(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError("");
    setMessage("");
    setIsProfileSaving(true);

    try {
      const response = await fetch("/api/profile", {
        body: JSON.stringify({
          email,
          full_name: fullName,
        }),
        headers: {
          "Content-Type": "application/json",
        },
        method: "PUT",
      });
      const result = (await response.json()) as ProfileResponse;

      if (!response.ok || !result.success) {
        throw new Error(result.message ?? "Nao foi possivel atualizar o perfil");
      }

      setProfile({
        stats: result.stats,
        user: result.user,
      });
      setMessage(result.message ?? "Perfil atualizado com sucesso");
      router.refresh();
    } catch (caughtError) {
      setError(
        caughtError instanceof Error
          ? caughtError.message
          : "Nao foi possivel atualizar o perfil",
      );
    } finally {
      setIsProfileSaving(false);
    }
  }

  async function submitPassword(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError("");
    setMessage("");

    if (newPassword !== confirmPassword) {
      setError("Confirmacao de senha nao confere");
      return;
    }

    setIsPasswordSaving(true);

    try {
      const response = await fetch("/api/auth/change-password", {
        body: JSON.stringify({
          current_password: currentPassword,
          new_password: newPassword,
        }),
        headers: {
          "Content-Type": "application/json",
        },
        method: "POST",
      });
      const result = (await response.json()) as PasswordResponse;

      if (!response.ok || !result.success) {
        throw new Error(result.message ?? "Nao foi possivel alterar a senha");
      }

      setCurrentPassword("");
      setNewPassword("");
      setConfirmPassword("");
      setMessage("Senha alterada. Faca login novamente.");
      router.push("/login");
    } catch (caughtError) {
      setError(
        caughtError instanceof Error
          ? caughtError.message
          : "Nao foi possivel alterar a senha",
      );
    } finally {
      setIsPasswordSaving(false);
    }
  }

  return (
    <div className="mx-auto max-w-7xl space-y-6">
      <section>
        <p className="text-sm font-semibold uppercase tracking-[0.18em] text-[#0f8f80]">
          Conta
        </p>
        <h1 className="mt-2 text-3xl font-bold text-slate-950">Perfil</h1>
      </section>

      {message ? (
        <div className="rounded-[8px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
          {message}
        </div>
      ) : null}

      {error ? (
        <div className="rounded-[8px] border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {error}
        </div>
      ) : null}

      <section className="grid gap-4 lg:grid-cols-[0.85fr_1.4fr]">
        <ProfileCard user={profile.user} />

        <div className="grid gap-4 md:grid-cols-2">
          <StatCard
            icon={UsersRound}
            label="Leads"
            value={formatInteger(profile.stats.total_leads)}
          />
          <StatCard
            icon={CheckCircle2}
            label="Leads convertidos"
            value={formatInteger(profile.stats.converted_leads)}
          />
          {profile.user.role !== UserRole.Viewer ? (
            <>
              <StatCard
                icon={CircleDollarSign}
                label="Vendas"
                value={formatInteger(profile.stats.total_sales)}
              />
              <StatCard
                icon={CircleDollarSign}
                label="Valor vendido"
                value={formatCurrency(profile.stats.total_sales_value)}
              />
            </>
          ) : null}
        </div>
      </section>

      <section className="grid gap-4 lg:grid-cols-2">
        <Panel title="Editar informacoes">
          <form className="space-y-4" onSubmit={submitProfile}>
            <TextField
              label="Nome completo"
              onChange={setFullName}
              required
              value={fullName}
            />
            <TextField label="Email" onChange={setEmail} required type="email" value={email} />

            <button
              className="hype-focus inline-flex h-10 items-center justify-center gap-2 rounded-[8px] bg-[#3be1c9] px-4 text-sm font-bold text-[#242328] disabled:cursor-not-allowed disabled:opacity-60"
              disabled={isProfileSaving}
              type="submit"
            >
              {isProfileSaving ? (
                <Loader2 className="h-4 w-4 animate-spin" aria-hidden />
              ) : (
                <Save className="h-4 w-4" aria-hidden />
              )}
              Salvar perfil
            </button>
          </form>
        </Panel>

        <Panel title="Alterar senha">
          <form className="space-y-4" onSubmit={submitPassword}>
            <TextField
              label="Senha atual"
              onChange={setCurrentPassword}
              required
              type="password"
              value={currentPassword}
            />
            <TextField
              label="Nova senha"
              minLength={8}
              onChange={setNewPassword}
              required
              type="password"
              value={newPassword}
            />
            <TextField
              label="Confirmar nova senha"
              minLength={8}
              onChange={setConfirmPassword}
              required
              type="password"
              value={confirmPassword}
            />

            <button
              className="hype-focus inline-flex h-10 items-center justify-center gap-2 rounded-[8px] border border-slate-200 px-4 text-sm font-semibold text-slate-700 disabled:cursor-not-allowed disabled:opacity-60"
              disabled={isPasswordSaving}
              type="submit"
            >
              {isPasswordSaving ? (
                <Loader2 className="h-4 w-4 animate-spin" aria-hidden />
              ) : (
                <KeyRound className="h-4 w-4" aria-hidden />
              )}
              Alterar senha
            </button>
          </form>
        </Panel>
      </section>

      <section className="grid gap-4 md:grid-cols-2">
        <StatCard
          icon={CircleDollarSign}
          label="Vendas no mes"
          value={formatInteger(profile.stats.monthly_sales)}
        />
        <StatCard
          icon={CircleDollarSign}
          label="Valor no mes"
          value={formatCurrency(profile.stats.monthly_sales_value)}
        />
      </section>
    </div>
  );
}

function ProfileCard({ user }: Readonly<{ user: PublicUser }>) {
  return (
    <article className="rounded-[8px] border border-slate-200 bg-white p-5 shadow-sm">
      <div className="flex flex-col items-center text-center">
        <span className="flex h-24 w-24 items-center justify-center rounded-full bg-[#3be1c9] text-3xl font-bold text-[#242328]">
          {getInitials(user.full_name || user.username)}
        </span>
        <h2 className="mt-4 text-xl font-bold text-slate-950">{user.full_name}</h2>
        <p className="mt-1 text-sm text-slate-500">@{user.username}</p>
        <span className="mt-3 inline-flex rounded-full bg-[#3be1c9]/15 px-3 py-1 text-xs font-bold text-[#0f8f80]">
          {roleLabels[user.role]}
        </span>
      </div>

      <div className="mt-6 divide-y divide-slate-100">
        <InfoRow label="Email" value={user.email || "-"} />
        <InfoRow label="Status" value={user.status === "active" ? "Ativo" : "Inativo"} />
        <InfoRow label="Criado em" value={formatDate(user.created_at)} />
        <InfoRow label="Ultimo login" value={formatDate(user.last_login)} />
      </div>
    </article>
  );
}

function Panel({ children, title }: Readonly<{ children: React.ReactNode; title: string }>) {
  return (
    <article className="rounded-[8px] border border-slate-200 bg-white p-5 shadow-sm">
      <h2 className="mb-4 text-base font-bold text-slate-950">{title}</h2>
      {children}
    </article>
  );
}

function StatCard({
  icon: Icon,
  label,
  value,
}: Readonly<{
  icon: typeof UserRound;
  label: string;
  value: string;
}>) {
  return (
    <article className="rounded-[8px] border border-slate-200 bg-white p-5 shadow-sm">
      <div className="flex items-start justify-between gap-4">
        <div>
          <p className="text-sm font-medium text-slate-500">{label}</p>
          <p className="mt-2 text-2xl font-bold text-slate-950">{value}</p>
        </div>
        <span className="flex h-10 w-10 items-center justify-center rounded-[8px] bg-[#3be1c9]/15 text-[#0f8f80]">
          <Icon className="h-5 w-5" aria-hidden />
        </span>
      </div>
    </article>
  );
}

function TextField({
  label,
  minLength,
  onChange,
  required = false,
  type = "text",
  value,
}: Readonly<{
  label: string;
  minLength?: number;
  onChange: (value: string) => void;
  required?: boolean;
  type?: string;
  value: string;
}>) {
  return (
    <label className="block">
      <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
        {label}
      </span>
      <input
        className="hype-focus h-10 w-full rounded-[8px] border border-slate-200 bg-white px-3 text-sm text-slate-900"
        minLength={minLength}
        onChange={(event) => onChange(event.target.value)}
        required={required}
        type={type}
        value={value}
      />
    </label>
  );
}

function InfoRow({ label, value }: Readonly<{ label: string; value: string }>) {
  return (
    <div className="flex justify-between gap-4 py-3 text-sm">
      <span className="text-slate-500">{label}</span>
      <span className="text-right font-semibold text-slate-950">{value}</span>
    </div>
  );
}

function getInitials(value: string): string {
  return value
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join("");
}

function formatCurrency(value: number): string {
  return new Intl.NumberFormat("pt-BR", {
    currency: "BRL",
    maximumFractionDigits: 2,
    style: "currency",
  }).format(value);
}

function formatDate(value: string | null): string {
  if (!value) {
    return "-";
  }

  return formatBrazilDateTime(value);
}

function formatInteger(value: number): string {
  return new Intl.NumberFormat("pt-BR", {
    maximumFractionDigits: 0,
  }).format(value);
}
