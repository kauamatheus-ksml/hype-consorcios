"use client";

import {
  AlertCircle,
  CheckCircle2,
  CircleDollarSign,
  Handshake,
  Loader2,
  Target,
  TrendingUp,
  UsersRound,
} from "lucide-react";
import { useEffect, useMemo, useState } from "react";

import type { DashboardStats } from "@/types/dashboard";
import { UserRole } from "@/types";

interface DashboardClientProps {
  userRole: UserRole;
}

interface DashboardResponse {
  success: boolean;
  message?: string;
  stats?: DashboardStats;
}

export function DashboardClient({ userRole }: DashboardClientProps) {
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [sellerId, setSellerId] = useState("");
  const [error, setError] = useState("");
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let active = true;

    async function loadStats() {
      setIsLoading(true);
      setError("");

      try {
        const query = sellerId ? `?seller_id=${sellerId}` : "";
        const response = await fetch(`/api/dashboard/stats${query}`, {
          cache: "no-store",
        });
        const result = (await response.json()) as DashboardResponse;

        if (!response.ok || !result.success || !result.stats) {
          throw new Error(result.message ?? "Nao foi possivel carregar o dashboard");
        }

        if (active) {
          setStats(result.stats);
        }
      } catch (caughtError) {
        if (active) {
          setError(
            caughtError instanceof Error
              ? caughtError.message
              : "Nao foi possivel carregar o dashboard",
          );
        }
      } finally {
        if (active) {
          setIsLoading(false);
        }
      }
    }

    loadStats();

    return () => {
      active = false;
    };
  }, [sellerId]);

  const statCards = useMemo(() => {
    if (!stats) {
      return [];
    }

    return [
      {
        label: "Leads",
        value: formatInteger(stats.total_leads),
        note: `${formatInteger(stats.leads_this_month)} no mes`,
        icon: UsersRound,
      },
      {
        label: "Vendas",
        value: formatInteger(stats.total_sales),
        note: `${formatInteger(stats.sales_this_month)} no mes`,
        icon: Handshake,
      },
      {
        label: "Receita",
        value: formatCurrency(stats.total_revenue),
        note: "vendas confirmadas",
        icon: CircleDollarSign,
      },
      {
        label: "Comissoes",
        value: formatCurrency(stats.total_commissions),
        note: "mes atual",
        icon: TrendingUp,
      },
      {
        label: "Conversao",
        value: `${formatDecimal(stats.conversion_rate)}%`,
        note: "vendas sobre leads",
        icon: Target,
      },
      {
        label: "Pendentes",
        value: formatInteger(stats.pending_sales),
        note: "vendas aguardando",
        icon: AlertCircle,
      },
    ];
  }, [stats]);

  if (isLoading && !stats) {
    return (
      <div className="flex min-h-[320px] items-center justify-center rounded-[8px] border border-slate-200 bg-white">
        <Loader2 className="h-6 w-6 animate-spin text-[#0f8f80]" aria-hidden />
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-7xl space-y-6">
      <section className="flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
          <p className="text-sm font-semibold uppercase tracking-[0.18em] text-[#0f8f80]">
            Dashboard
          </p>
          <h1 className="mt-2 text-3xl font-bold text-slate-950">Visao geral</h1>
        </div>

        {userRole === UserRole.Admin && stats ? (
          <label className="block min-w-64">
            <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
              Vendedor
            </span>
            <select
              className="hype-focus h-10 w-full rounded-[8px] border border-slate-200 bg-white px-3 text-sm text-slate-900"
              onChange={(event) => setSellerId(event.target.value)}
              value={sellerId}
            >
              <option value="">Todos</option>
              {stats.sellers.map((seller) => (
                <option key={seller.id} value={seller.id}>
                  {seller.full_name}
                </option>
              ))}
            </select>
          </label>
        ) : null}
      </section>

      {error ? (
        <div className="rounded-[8px] border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {error}
        </div>
      ) : null}

      {stats ? (
        <>
          <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {statCards.map((card) => (
              <article
                className="rounded-[8px] border border-slate-200 bg-white p-5 shadow-sm"
                key={card.label}
              >
                <div className="flex items-start justify-between gap-4">
                  <div>
                    <p className="text-sm font-medium text-slate-500">{card.label}</p>
                    <p className="mt-2 text-2xl font-bold text-slate-950">{card.value}</p>
                    <p className="mt-1 text-xs text-slate-500">{card.note}</p>
                  </div>
                  <span className="flex h-10 w-10 items-center justify-center rounded-[8px] bg-[#3be1c9]/15 text-[#0f8f80]">
                    <card.icon className="h-5 w-5" aria-hidden />
                  </span>
                </div>
              </article>
            ))}
          </section>

          <section className="grid gap-4 xl:grid-cols-[1fr_1.2fr]">
            <Panel title="Top vendedores">
              <div className="space-y-3">
                {stats.top_sellers.map((seller) => (
                  <div
                    className="flex items-center justify-between gap-4 rounded-[8px] bg-slate-50 px-3 py-2"
                    key={seller.full_name}
                  >
                    <div>
                      <p className="text-sm font-semibold text-slate-900">
                        {seller.full_name}
                      </p>
                      <p className="text-xs text-slate-500">
                        {formatInteger(seller.sales_count)} vendas
                      </p>
                    </div>
                    <p className="text-sm font-bold text-[#0f8f80]">
                      {formatCurrency(seller.total_commission)}
                    </p>
                  </div>
                ))}
              </div>
            </Panel>

            <Panel title="Leads recentes">
              <div className="overflow-x-auto">
                <table className="w-full min-w-[620px] text-left text-sm">
                  <thead className="text-xs uppercase tracking-[0.12em] text-slate-500">
                    <tr>
                      <th className="pb-3 font-semibold">Lead</th>
                      <th className="pb-3 font-semibold">Telefone</th>
                      <th className="pb-3 font-semibold">Status</th>
                      <th className="pb-3 font-semibold">Vendedor</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {stats.recent_leads.map((lead) => (
                      <tr key={`${lead.lead_name}-${lead.created_at}`}>
                        <td className="py-3 font-medium text-slate-900">{lead.lead_name}</td>
                        <td className="py-3 text-slate-600">{lead.phone}</td>
                        <td className="py-3">
                          <span className="inline-flex rounded-full bg-[#3be1c9]/15 px-2 py-1 text-xs font-semibold text-[#0f8f80]">
                            {lead.status}
                          </span>
                        </td>
                        <td className="py-3 text-slate-600">
                          {lead.assigned_to ?? "Nao atribuido"}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </Panel>
          </section>

          <section className="grid gap-4 xl:grid-cols-3">
            <BarList
              items={stats.leads_by_status.map((item) => ({
                label: item.status,
                value: item.count,
              }))}
              title="Leads por status"
            />
            <BarList
              items={stats.leads_by_source.map((item) => ({
                label: item.source,
                value: item.count,
              }))}
              title="Leads por fonte"
            />
            <Panel title="Vendas por mes">
              <div className="space-y-3">
                {stats.monthly_sales.map((item) => (
                  <div className="flex items-center justify-between gap-3" key={item.month}>
                    <div className="flex items-center gap-2">
                      <CheckCircle2 className="h-4 w-4 text-[#0f8f80]" aria-hidden />
                      <span className="text-sm font-medium text-slate-700">{item.month}</span>
                    </div>
                    <span className="text-sm font-bold text-slate-950">
                      {formatCurrency(item.total_value)}
                    </span>
                  </div>
                ))}
              </div>
            </Panel>
          </section>
        </>
      ) : null}
    </div>
  );
}

function Panel({
  children,
  title,
}: Readonly<{
  children: React.ReactNode;
  title: string;
}>) {
  return (
    <article className="rounded-[8px] border border-slate-200 bg-white p-5 shadow-sm">
      <h2 className="mb-4 text-base font-bold text-slate-950">{title}</h2>
      {children}
    </article>
  );
}

function BarList({
  items,
  title,
}: Readonly<{
  items: Array<{ label: string; value: number }>;
  title: string;
}>) {
  const maxValue = Math.max(...items.map((item) => item.value), 1);

  return (
    <Panel title={title}>
      <div className="space-y-3">
        {items.map((item) => (
          <div key={item.label}>
            <div className="mb-1 flex justify-between gap-3 text-sm">
              <span className="font-medium text-slate-700">{item.label}</span>
              <span className="font-semibold text-slate-950">{formatInteger(item.value)}</span>
            </div>
            <div className="h-2 rounded-full bg-slate-100">
              <div
                className="h-2 rounded-full bg-[#3be1c9]"
                style={{ width: `${Math.max((item.value / maxValue) * 100, 4)}%` }}
              />
            </div>
          </div>
        ))}
      </div>
    </Panel>
  );
}

function formatCurrency(value: number): string {
  return new Intl.NumberFormat("pt-BR", {
    style: "currency",
    currency: "BRL",
    maximumFractionDigits: 2,
  }).format(value);
}

function formatDecimal(value: number): string {
  return new Intl.NumberFormat("pt-BR", {
    maximumFractionDigits: 2,
  }).format(value);
}

function formatInteger(value: number): string {
  return new Intl.NumberFormat("pt-BR", {
    maximumFractionDigits: 0,
  }).format(value);
}
