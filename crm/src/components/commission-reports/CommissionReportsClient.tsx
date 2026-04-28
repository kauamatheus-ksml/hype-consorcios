"use client";

import {
  BadgePercent,
  CalendarDays,
  CircleDollarSign,
  Loader2,
  Search,
  TrendingUp,
} from "lucide-react";
import { useEffect, useMemo, useState } from "react";

import { currentBrazilYear, formatBrazilDate } from "@/lib/date-format";
import type { CommissionReportPayload } from "@/types/commission-reports";

interface CommissionReportResponse extends Partial<CommissionReportPayload> {
  message?: string;
  success: boolean;
}

const monthOptions = [
  ["", "Ano completo"],
  ["1", "Janeiro"],
  ["2", "Fevereiro"],
  ["3", "Marco"],
  ["4", "Abril"],
  ["5", "Maio"],
  ["6", "Junho"],
  ["7", "Julho"],
  ["8", "Agosto"],
  ["9", "Setembro"],
  ["10", "Outubro"],
  ["11", "Novembro"],
  ["12", "Dezembro"],
] as const;

export function CommissionReportsClient() {
  const [report, setReport] = useState<CommissionReportPayload | null>(null);
  const [year, setYear] = useState(String(currentBrazilYear()));
  const [month, setMonth] = useState("");
  const [sellerId, setSellerId] = useState("");
  const [error, setError] = useState("");
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const controller = new AbortController();

    async function loadReport() {
      setIsLoading(true);
      setError("");

      try {
        const params = new URLSearchParams({
          year,
        });

        if (month) params.set("month", month);
        if (sellerId) params.set("seller_id", sellerId);

        const response = await fetch(`/api/commission-reports?${params.toString()}`, {
          cache: "no-store",
          signal: controller.signal,
        });
        const result = (await response.json()) as CommissionReportResponse;

        if (!response.ok || !result.success || !result.summary || !result.months || !result.sellers) {
          throw new Error(result.message ?? "Nao foi possivel carregar relatorio");
        }

        setReport({
          filters: result.filters ?? {
            month: month ? Number(month) : null,
            seller_id: sellerId ? Number(sellerId) : null,
            year: Number(year),
          },
          months: result.months,
          sales: result.sales ?? [],
          sellers: result.sellers,
          summary: result.summary,
        });
      } catch (caughtError) {
        if (controller.signal.aborted) {
          return;
        }

        setError(
          caughtError instanceof Error
            ? caughtError.message
            : "Nao foi possivel carregar relatorio",
        );
      } finally {
        if (!controller.signal.aborted) {
          setIsLoading(false);
        }
      }
    }

    loadReport();

    return () => {
      controller.abort();
    };
  }, [month, sellerId, year]);

  const cards = useMemo(() => {
    const summary = report?.summary;

    return [
      {
        icon: BadgePercent,
        label: "Vendas concluidas",
        value: formatInteger(summary?.sales_count ?? 0),
        note: "base completed",
      },
      {
        icon: CircleDollarSign,
        label: "Total vendido",
        value: formatCurrency(summary?.total_sales ?? 0),
        note: "valor das vendas",
      },
      {
        icon: TrendingUp,
        label: "Comissao total",
        value: formatCurrency(summary?.total_commission ?? 0),
        note: "valor cheio",
      },
      {
        icon: CalendarDays,
        label: "Parcela mensal",
        value: formatCurrency(summary?.total_monthly_commission ?? 0),
        note: "soma mensal",
      },
    ];
  }, [report?.summary]);

  return (
    <div className="mx-auto max-w-7xl space-y-6">
      <section className="flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
          <p className="text-sm font-semibold uppercase tracking-[0.18em] text-[#0f8f80]">
            CRM
          </p>
          <h1 className="mt-2 text-3xl font-bold text-slate-950">Relatorio de comissoes</h1>
        </div>
      </section>

      <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {cards.map((card) => (
          <article
            className="rounded-[8px] border border-slate-200 bg-white p-5 shadow-sm"
            key={card.label}
          >
            <div className="flex items-start justify-between gap-4">
              <div>
                <p className="text-sm font-medium text-slate-500">{card.label}</p>
                <p className="mt-2 text-2xl font-bold text-slate-950">
                  {isLoading ? "-" : card.value}
                </p>
                <p className="mt-1 text-xs text-slate-500">{card.note}</p>
              </div>
              <span className="flex h-10 w-10 items-center justify-center rounded-[8px] bg-[#3be1c9]/15 text-[#0f8f80]">
                <card.icon className="h-5 w-5" aria-hidden />
              </span>
            </div>
          </article>
        ))}
      </section>

      <section className="rounded-[8px] border border-slate-200 bg-white p-4 shadow-sm">
        <div className="grid gap-3 md:grid-cols-[1fr_1fr_1fr]">
          <TextField label="Ano" onChange={setYear} type="number" value={year} />
          <SelectFilter label="Mes" onChange={setMonth} options={monthOptions} value={month} />
          <SelectFilter
            label="Vendedor"
            onChange={setSellerId}
            options={[
              ["", "Todos"],
              ...(report?.sellers ?? []).map(
                (seller) => [String(seller.id), seller.full_name] as const,
              ),
            ]}
            value={sellerId}
          />
        </div>
      </section>

      {error ? (
        <div className="rounded-[8px] border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {error}
        </div>
      ) : null}

      <section className="rounded-[8px] border border-slate-200 bg-white shadow-sm">
        <div className="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
          <h2 className="text-base font-bold text-slate-950">Resumo por mes e vendedor</h2>
          {isLoading ? <Loader2 className="h-5 w-5 animate-spin text-[#0f8f80]" aria-hidden /> : null}
        </div>

        <div className="overflow-x-auto">
          <table className="w-full min-w-[980px] text-left text-sm">
            <thead className="bg-slate-50 text-xs uppercase tracking-[0.12em] text-slate-500">
              <tr>
                <th className="px-4 py-3 font-semibold">Mes</th>
                <th className="px-4 py-3 font-semibold">Vendedor</th>
                <th className="px-4 py-3 font-semibold">Vendas</th>
                <th className="px-4 py-3 font-semibold">Total vendido</th>
                <th className="px-4 py-3 font-semibold">Comissao</th>
                <th className="px-4 py-3 font-semibold">Parcela mensal</th>
                <th className="px-4 py-3 font-semibold">Media %</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {(report?.months ?? []).flatMap((item) =>
                item.sellers.map((seller) => (
                  <tr className="hover:bg-slate-50" key={`${item.year}-${item.month}-${seller.seller_id}`}>
                    <td className="px-4 py-3 font-semibold text-slate-950">
                      {item.month_name}/{item.year}
                    </td>
                    <td className="px-4 py-3 text-slate-700">{seller.seller_name}</td>
                    <td className="px-4 py-3 text-slate-700">{formatInteger(seller.sales_count)}</td>
                    <td className="px-4 py-3 text-slate-700">
                      {formatCurrency(seller.total_sales)}
                    </td>
                    <td className="px-4 py-3 font-semibold text-[#0f8f80]">
                      {formatCurrency(seller.total_commission)}
                    </td>
                    <td className="px-4 py-3 text-slate-700">
                      {formatCurrency(seller.total_monthly_commission)}
                    </td>
                    <td className="px-4 py-3 text-slate-700">
                      {formatDecimal(seller.avg_commission_percentage)}%
                    </td>
                  </tr>
                )),
              )}

              {!isLoading && (report?.months.length ?? 0) === 0 ? (
                <tr>
                  <td className="px-4 py-8 text-center text-slate-500" colSpan={7}>
                    Nenhuma comissao encontrada para vendas concluidas nesse periodo.
                  </td>
                </tr>
              ) : null}
            </tbody>
          </table>
        </div>
      </section>

      {month ? (
        <section className="rounded-[8px] border border-slate-200 bg-white shadow-sm">
          <div className="flex items-center gap-2 border-b border-slate-200 px-4 py-3">
            <Search className="h-4 w-4 text-[#0f8f80]" aria-hidden />
            <h2 className="text-base font-bold text-slate-950">Vendas do mes</h2>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full min-w-[860px] text-left text-sm">
              <thead className="bg-slate-50 text-xs uppercase tracking-[0.12em] text-slate-500">
                <tr>
                  <th className="px-4 py-3 font-semibold">Venda</th>
                  <th className="px-4 py-3 font-semibold">Cliente</th>
                  <th className="px-4 py-3 font-semibold">Veiculo</th>
                  <th className="px-4 py-3 font-semibold">Valor</th>
                  <th className="px-4 py-3 font-semibold">Comissao</th>
                  <th className="px-4 py-3 font-semibold">Vendedor</th>
                  <th className="px-4 py-3 font-semibold">Data</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {(report?.sales ?? []).map((sale) => (
                  <tr className="hover:bg-slate-50" key={sale.id}>
                    <td className="px-4 py-3 text-slate-700">#{sale.id}</td>
                    <td className="px-4 py-3 font-semibold text-slate-950">
                      {sale.customer_name ?? "N/A"}
                    </td>
                    <td className="px-4 py-3 text-slate-700">
                      {sale.vehicle_sold ?? "Nao informado"}
                    </td>
                    <td className="px-4 py-3 text-slate-700">
                      {formatCurrency(sale.sale_value ?? 0)}
                    </td>
                    <td className="px-4 py-3 font-semibold text-[#0f8f80]">
                      {formatCurrency(sale.commission_value ?? 0)}
                    </td>
                    <td className="px-4 py-3 text-slate-700">{sale.seller_name}</td>
                    <td className="px-4 py-3 text-slate-700">{formatDate(sale.sale_date)}</td>
                  </tr>
                ))}

                {!isLoading && (report?.sales.length ?? 0) === 0 ? (
                  <tr>
                    <td className="px-4 py-8 text-center text-slate-500" colSpan={7}>
                      Nenhuma venda concluida neste mes.
                    </td>
                  </tr>
                ) : null}
              </tbody>
            </table>
          </div>
        </section>
      ) : null}
    </div>
  );
}

function SelectFilter({
  label,
  onChange,
  options,
  value,
}: Readonly<{
  label: string;
  onChange: (value: string) => void;
  options: ReadonlyArray<readonly [string, string]>;
  value: string;
}>) {
  return (
    <label className="block">
      <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
        {label}
      </span>
      <select
        className="hype-focus h-10 w-full rounded-[8px] border border-slate-200 bg-white px-3 text-sm text-slate-900"
        onChange={(event) => onChange(event.target.value)}
        value={value}
      >
        {options.map(([optionValue, optionLabel]) => (
          <option key={`${label}-${optionValue}`} value={optionValue}>
            {optionLabel}
          </option>
        ))}
      </select>
    </label>
  );
}

function TextField({
  label,
  onChange,
  type = "text",
  value,
}: Readonly<{
  label: string;
  onChange: (value: string) => void;
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
        onChange={(event) => onChange(event.target.value)}
        type={type}
        value={value}
      />
    </label>
  );
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

  return formatBrazilDate(value);
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
