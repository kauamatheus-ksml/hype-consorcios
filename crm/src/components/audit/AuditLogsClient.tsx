"use client";

import {
  ChevronLeft,
  ChevronRight,
  ClipboardList,
  Loader2,
  Search,
} from "lucide-react";
import { useEffect, useMemo, useState } from "react";

import { formatBrazilDateTime } from "@/lib/date-format";
import type { AuditLogItem, AuditPagination, AuditStats } from "@/types/audit";

interface AuditResponse {
  logs?: AuditLogItem[];
  message?: string;
  pagination?: AuditPagination;
  stats?: AuditStats;
  success: boolean;
}

export function AuditLogsClient() {
  const [logs, setLogs] = useState<AuditLogItem[]>([]);
  const [pagination, setPagination] = useState<AuditPagination | null>(null);
  const [stats, setStats] = useState<AuditStats | null>(null);
  const [search, setSearch] = useState("");
  const [action, setAction] = useState("");
  const [page, setPage] = useState(1);
  const [error, setError] = useState("");
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const controller = new AbortController();
    const timeout = window.setTimeout(() => {
      loadLogs(controller.signal);
    }, 250);

    async function loadLogs(signal: AbortSignal) {
      setIsLoading(true);
      setError("");

      try {
        const params = new URLSearchParams({
          limit: "50",
          page: String(page),
        });

        if (search.trim()) params.set("search", search.trim());
        if (action) params.set("action", action);

        const response = await fetch(`/api/audit-logs?${params.toString()}`, {
          cache: "no-store",
          signal,
        });
        const result = (await response.json()) as AuditResponse;

        if (
          !response.ok ||
          !result.success ||
          !result.logs ||
          !result.pagination ||
          !result.stats
        ) {
          throw new Error(result.message ?? "Nao foi possivel carregar logs");
        }

        setLogs(result.logs);
        setPagination(result.pagination);
        setStats(result.stats);
      } catch (caughtError) {
        if (signal.aborted) {
          return;
        }

        setError(
          caughtError instanceof Error ? caughtError.message : "Nao foi possivel carregar logs",
        );
      } finally {
        if (!signal.aborted) {
          setIsLoading(false);
        }
      }
    }

    return () => {
      window.clearTimeout(timeout);
      controller.abort();
    };
  }, [action, page, search]);

  const actionOptions = useMemo(() => {
    const uniqueActions = Array.from(new Set(logs.map((log) => log.action))).sort();
    return uniqueActions;
  }, [logs]);

  function updateFilter(setter: (value: string) => void, value: string) {
    setter(value);
    setPage(1);
  }

  return (
    <div className="mx-auto max-w-7xl space-y-6">
      <section>
        <p className="text-sm font-semibold uppercase tracking-[0.18em] text-[#0f8f80]">
          Seguranca
        </p>
        <h1 className="mt-2 text-3xl font-bold text-slate-950">Logs de auditoria</h1>
      </section>

      <section className="grid gap-4 md:grid-cols-3">
        <StatCard label="Total" value={stats?.total ?? 0} />
        <StatCard label="Hoje" value={stats?.today ?? 0} />
        <StatCard label="Ultimos 7 dias" value={stats?.week ?? 0} />
      </section>

      <section className="rounded-[8px] border border-slate-200 bg-white p-4 shadow-sm">
        <div className="grid gap-3 md:grid-cols-[1.4fr_1fr]">
          <label className="block">
            <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
              Buscar
            </span>
            <span className="relative block">
              <Search
                className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                aria-hidden
              />
              <input
                className="hype-focus h-10 w-full rounded-[8px] border border-slate-200 bg-white pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400"
                onChange={(event) => updateFilter(setSearch, event.target.value)}
                placeholder="Acao, descricao, usuario ou IP"
                type="search"
                value={search}
              />
            </span>
          </label>

          <label className="block">
            <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
              Acao
            </span>
            <select
              className="hype-focus h-10 w-full rounded-[8px] border border-slate-200 bg-white px-3 text-sm text-slate-900"
              onChange={(event) => updateFilter(setAction, event.target.value)}
              value={action}
            >
              <option value="">Todas</option>
              {actionOptions.map((item) => (
                <option key={item} value={item}>
                  {item}
                </option>
              ))}
            </select>
          </label>
        </div>
      </section>

      {error ? (
        <div className="rounded-[8px] border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {error}
        </div>
      ) : null}

      <section className="rounded-[8px] border border-slate-200 bg-white shadow-sm">
        <div className="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
          <h2 className="text-base font-bold text-slate-950">Historico</h2>
          {isLoading ? <Loader2 className="h-5 w-5 animate-spin text-[#0f8f80]" aria-hidden /> : null}
        </div>

        <div className="overflow-x-auto">
          <table className="w-full min-w-[920px] text-left text-sm">
            <thead className="bg-slate-50 text-xs uppercase tracking-[0.12em] text-slate-500">
              <tr>
                <th className="px-4 py-3 font-semibold">Data</th>
                <th className="px-4 py-3 font-semibold">Usuario</th>
                <th className="px-4 py-3 font-semibold">Acao</th>
                <th className="px-4 py-3 font-semibold">Descricao</th>
                <th className="px-4 py-3 font-semibold">Tabela</th>
                <th className="px-4 py-3 font-semibold">IP</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {logs.map((log) => (
                <tr className="hover:bg-slate-50" key={log.id}>
                  <td className="px-4 py-3 font-mono text-xs text-slate-600">
                    {formatDate(log.created_at)}
                  </td>
                  <td className="px-4 py-3">
                    <p className="font-semibold text-slate-950">
                      {log.user_full_name ?? "Sistema"}
                    </p>
                    <p className="text-xs text-slate-500">{log.username ?? "-"}</p>
                  </td>
                  <td className="px-4 py-3">
                    <ActionBadge action={log.action} />
                  </td>
                  <td className="max-w-md px-4 py-3 text-slate-700">
                    {log.description ?? "-"}
                  </td>
                  <td className="px-4 py-3 text-slate-700">
                    {log.table_name ?? "-"}
                    {log.record_id ? (
                      <span className="block text-xs text-slate-500">ID {log.record_id}</span>
                    ) : null}
                  </td>
                  <td className="px-4 py-3 font-mono text-xs text-slate-600">
                    {log.ip_address ?? "-"}
                  </td>
                </tr>
              ))}

              {!isLoading && logs.length === 0 ? (
                <tr>
                  <td className="px-4 py-8 text-center text-slate-500" colSpan={6}>
                    Nenhum log encontrado.
                  </td>
                </tr>
              ) : null}
            </tbody>
          </table>
        </div>

        {pagination ? (
          <div className="flex flex-col justify-between gap-3 border-t border-slate-200 px-4 py-3 md:flex-row md:items-center">
            <p className="text-sm text-slate-500">
              Pagina {pagination.current_page} de {pagination.total_pages} -{" "}
              {formatInteger(pagination.total_records)} registros
            </p>
            <div className="flex gap-2">
              <button
                className="hype-focus inline-flex h-9 items-center gap-2 rounded-[8px] border border-slate-200 px-3 text-sm font-semibold text-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
                disabled={!pagination.has_prev || isLoading}
                onClick={() => setPage((current) => Math.max(current - 1, 1))}
                type="button"
              >
                <ChevronLeft className="h-4 w-4" aria-hidden />
                Anterior
              </button>
              <button
                className="hype-focus inline-flex h-9 items-center gap-2 rounded-[8px] border border-slate-200 px-3 text-sm font-semibold text-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
                disabled={!pagination.has_next || isLoading}
                onClick={() => setPage((current) => current + 1)}
                type="button"
              >
                Proxima
                <ChevronRight className="h-4 w-4" aria-hidden />
              </button>
            </div>
          </div>
        ) : null}
      </section>
    </div>
  );
}

function StatCard({ label, value }: Readonly<{ label: string; value: number }>) {
  return (
    <article className="rounded-[8px] border border-slate-200 bg-white p-5 shadow-sm">
      <div className="flex items-start justify-between gap-4">
        <div>
          <p className="text-sm font-medium text-slate-500">{label}</p>
          <p className="mt-2 text-2xl font-bold text-slate-950">
            {formatInteger(value)}
          </p>
        </div>
        <span className="flex h-10 w-10 items-center justify-center rounded-[8px] bg-[#3be1c9]/15 text-[#0f8f80]">
          <ClipboardList className="h-5 w-5" aria-hidden />
        </span>
      </div>
    </article>
  );
}

function ActionBadge({ action }: Readonly<{ action: string }>) {
  const classes: Record<string, string> = {
    LOGIN_FAILED: "bg-red-50 text-red-700",
    LOGIN_SUCCESS: "bg-emerald-50 text-emerald-700",
    LOGOUT: "bg-amber-50 text-amber-700",
    PASSWORD_CHANGE: "bg-pink-50 text-pink-700",
    PROFILE_UPDATE: "bg-blue-50 text-blue-700",
  };

  return (
    <span
      className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${
        classes[action] ?? "bg-slate-100 text-slate-600"
      }`}
    >
      {action}
    </span>
  );
}

function formatDate(value: string): string {
  return formatBrazilDateTime(value, "medium");
}

function formatInteger(value: number): string {
  return new Intl.NumberFormat("pt-BR", {
    maximumFractionDigits: 0,
  }).format(value);
}
