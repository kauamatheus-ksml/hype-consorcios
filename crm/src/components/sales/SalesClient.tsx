"use client";

import {
  AlertCircle,
  Ban,
  ChevronLeft,
  ChevronRight,
  CircleDollarSign,
  Eye,
  Handshake,
  Loader2,
  Pencil,
  Plus,
  Save,
  Search,
  TrendingUp,
  X,
} from "lucide-react";
import { useEffect, useMemo, useState, type FormEvent, type ReactNode } from "react";

import {
  currentBrazilDateString,
  formatBrazilDate,
  formatBrazilTime,
} from "@/lib/date-format";
import { SaleStatus, UserRole } from "@/types";
import type { LeadListItem } from "@/types/leads";
import type {
  SaleAdditionalStats,
  SaleCancelInput,
  SaleListItem,
  SaleMutationInput,
  SalePagination,
  SaleStats,
} from "@/types/sales";

interface SalesClientProps {
  userId: number;
  userRole: UserRole;
}

interface SalesResponse {
  message?: string;
  pagination?: SalePagination;
  sales?: SaleListItem[];
  success: boolean;
}

interface SaleResponse {
  message?: string;
  sale?: SaleListItem;
  success: boolean;
}

interface SaleStatsResponse {
  additional?: SaleAdditionalStats;
  message?: string;
  stats?: SaleStats;
  success: boolean;
}

interface LeadsResponse {
  leads?: LeadListItem[];
  message?: string;
  success: boolean;
}

interface SaleFormState {
  commission_installments: string;
  commission_percentage: string;
  contract_number: string;
  customer_name: string;
  down_payment: string;
  email: string;
  financing_months: string;
  lead_id: string;
  monthly_payment: string;
  notes: string;
  payment_type: string;
  phone: string;
  sale_date: string;
  sale_value: string;
  seller_id: string;
  status: SaleStatus;
  vehicle_sold: string;
}

const statusLabels: Record<string, string> = {
  [SaleStatus.Pending]: "Pendente",
  [SaleStatus.Confirmed]: "Confirmado",
  [SaleStatus.Cancelled]: "Cancelado",
  [SaleStatus.Completed]: "Concluida",
};

export function SalesClient({ userId, userRole }: Readonly<SalesClientProps>) {
  const [sales, setSales] = useState<SaleListItem[]>([]);
  const [pagination, setPagination] = useState<SalePagination | null>(null);
  const [stats, setStats] = useState<SaleStats | null>(null);
  const [additional, setAdditional] = useState<SaleAdditionalStats>({});
  const [leadOptions, setLeadOptions] = useState<LeadListItem[]>([]);
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("");
  const [sellerId, setSellerId] = useState("");
  const [period, setPeriod] = useState("");
  const [page, setPage] = useState(1);
  const [error, setError] = useState("");
  const [formError, setFormError] = useState("");
  const [isLoading, setIsLoading] = useState(true);
  const [isStatsLoading, setIsStatsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [refreshKey, setRefreshKey] = useState(0);
  const [statsRefreshKey, setStatsRefreshKey] = useState(0);
  const [detailsSale, setDetailsSale] = useState<SaleListItem | null>(null);
  const [editingSale, setEditingSale] = useState<SaleListItem | null>(null);
  const [cancelTarget, setCancelTarget] = useState<SaleListItem | null>(null);
  const [cancelReason, setCancelReason] = useState("");
  const [cancelError, setCancelError] = useState("");
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [isCancelling, setIsCancelling] = useState(false);
  const [form, setForm] = useState<SaleFormState>(() => createEmptyForm());

  const canManageSales = [UserRole.Admin, UserRole.Manager].includes(userRole);

  useEffect(() => {
    let active = true;

    async function loadStats() {
      setIsStatsLoading(true);

      try {
        const response = await fetch("/api/sales/stats", {
          cache: "no-store",
        });
        const result = (await response.json()) as SaleStatsResponse;

        if (!response.ok || !result.success || !result.stats) {
          throw new Error(result.message ?? "Nao foi possivel carregar estatisticas");
        }

        if (active) {
          setStats(result.stats);
          setAdditional(result.additional ?? {});
        }
      } catch (caughtError) {
        if (active) {
          setError(
            caughtError instanceof Error
              ? caughtError.message
              : "Nao foi possivel carregar estatisticas",
          );
        }
      } finally {
        if (active) {
          setIsStatsLoading(false);
        }
      }
    }

    loadStats();

    return () => {
      active = false;
    };
  }, [statsRefreshKey]);

  useEffect(() => {
    let active = true;

    async function loadLeadOptions() {
      try {
        const response = await fetch("/api/leads?limit=50", {
          cache: "no-store",
        });
        const result = (await response.json()) as LeadsResponse;

        if (active && response.ok && result.success && result.leads) {
          setLeadOptions(result.leads);
        }
      } catch {
        if (active) {
          setLeadOptions([]);
        }
      }
    }

    loadLeadOptions();

    return () => {
      active = false;
    };
  }, [refreshKey]);

  useEffect(() => {
    const controller = new AbortController();
    const timeout = window.setTimeout(() => {
      loadSales(controller.signal);
    }, 250);

    async function loadSales(signal: AbortSignal) {
      setIsLoading(true);
      setError("");

      try {
        const params = new URLSearchParams({
          limit: "20",
          page: String(page),
        });

        if (search.trim()) params.set("search", search.trim());
        if (status) params.set("status", status);
        if (sellerId && canManageSales) params.set("seller_id", sellerId);
        if (period) params.set("period", period);

        const response = await fetch(`/api/sales?${params.toString()}`, {
          cache: "no-store",
          signal,
        });
        const result = (await response.json()) as SalesResponse;

        if (!response.ok || !result.success || !result.sales || !result.pagination) {
          throw new Error(result.message ?? "Nao foi possivel carregar vendas");
        }

        setSales(result.sales);
        setPagination(result.pagination);
      } catch (caughtError) {
        if (signal.aborted) {
          return;
        }

        setError(
          caughtError instanceof Error ? caughtError.message : "Nao foi possivel carregar vendas",
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
  }, [canManageSales, page, period, refreshKey, search, sellerId, status]);

  const statCards = useMemo(() => {
    if (!stats) {
      return [];
    }

    return [
      {
        icon: Handshake,
        label: "Total",
        note: `${formatInteger(stats.this_month)} no mes`,
        value: formatInteger(stats.total),
      },
      {
        icon: CircleDollarSign,
        label: "Receita",
        note: "vendas confirmadas",
        value: formatCurrency(stats.revenue),
      },
      {
        icon: TrendingUp,
        label: "Comissoes",
        note: `${formatCurrency(stats.average_ticket)} ticket medio`,
        value: formatCurrency(stats.commission),
      },
      {
        icon: AlertCircle,
        label: "Pendentes",
        note: `${formatInteger(stats.confirmed)} confirmadas`,
        value: formatInteger(stats.pending),
      },
    ];
  }, [stats]);

  const commissionPreview = useMemo(() => {
    const saleValue = Number(form.sale_value) || 0;
    const percentage = Number(form.commission_percentage) || 1.5;
    const installments = Math.max(Number(form.commission_installments) || 5, 1);
    const commissionValue = (saleValue * percentage) / 100;

    return {
      commissionValue,
      installments,
      monthlyCommission: commissionValue / installments,
      percentage,
    };
  }, [form.commission_installments, form.commission_percentage, form.sale_value]);

  function updateFilter(setter: (value: string) => void, value: string) {
    setter(value);
    setPage(1);
  }

  function openCreateForm() {
    setEditingSale(null);
    setForm(createEmptyForm(canManageSales ? sellerId : ""));
    setFormError("");
    setIsFormOpen(true);
  }

  async function openDetails(saleId: number) {
    setError("");

    try {
      const sale = await fetchSale(saleId);
      setDetailsSale(sale);
    } catch (caughtError) {
      setError(caughtError instanceof Error ? caughtError.message : "Nao foi possivel abrir a venda");
    }
  }

  async function openEditForm(saleId: number) {
    setError("");

    try {
      const sale = await fetchSale(saleId);
      setEditingSale(sale);
      setForm(createFormFromSale(sale));
      setFormError("");
      setIsFormOpen(true);
    } catch (caughtError) {
      setError(caughtError instanceof Error ? caughtError.message : "Nao foi possivel editar a venda");
    }
  }

  function closeForm() {
    if (isSubmitting) {
      return;
    }

    setIsFormOpen(false);
    setEditingSale(null);
    setFormError("");
  }

  function openCancelModal(sale: SaleListItem) {
    setCancelTarget(sale);
    setCancelReason("");
    setCancelError("");
  }

  function closeCancelModal() {
    if (isCancelling) {
      return;
    }

    setCancelTarget(null);
    setCancelReason("");
    setCancelError("");
  }

  function handleLeadChange(value: string) {
    const selectedLead = leadOptions.find((lead) => String(lead.id) === value);

    setForm((current) => ({
      ...current,
      customer_name: selectedLead?.name ?? current.customer_name,
      email: selectedLead?.email ?? current.email,
      lead_id: value,
      phone: selectedLead?.phone ?? current.phone,
      vehicle_sold: selectedLead?.vehicle_interest ?? current.vehicle_sold,
    }));
  }

  function updateFormField<K extends keyof SaleFormState>(key: K, value: SaleFormState[K]) {
    setForm((current) => ({
      ...current,
      [key]: value,
    }));
  }

  async function submitSaleForm(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setFormError("");
    setIsSubmitting(true);

    try {
      const payload = buildSalePayload(form, canManageSales);
      const endpoint = editingSale ? `/api/sales/${editingSale.id}` : "/api/sales";
      const method = editingSale ? "PUT" : "POST";
      const response = await fetch(endpoint, {
        body: JSON.stringify(payload),
        headers: {
          "Content-Type": "application/json",
        },
        method,
      });
      const result = (await response.json()) as SaleResponse;

      if (!response.ok || !result.success) {
        throw new Error(result.message ?? "Nao foi possivel salvar a venda");
      }

      setIsFormOpen(false);
      setEditingSale(null);
      setPage(1);
      setRefreshKey((current) => current + 1);
      setStatsRefreshKey((current) => current + 1);
    } catch (caughtError) {
      setFormError(
        caughtError instanceof Error ? caughtError.message : "Nao foi possivel salvar a venda",
      );
    } finally {
      setIsSubmitting(false);
    }
  }

  async function submitCancelSale(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (!cancelTarget) {
      return;
    }

    setCancelError("");
    setIsCancelling(true);

    try {
      const payload: SaleCancelInput = {
        reason: cancelReason,
      };
      const response = await fetch(`/api/sales/${cancelTarget.id}/cancel`, {
        body: JSON.stringify(payload),
        headers: {
          "Content-Type": "application/json",
        },
        method: "POST",
      });
      const result = (await response.json()) as SaleResponse;

      if (!response.ok || !result.success) {
        throw new Error(result.message ?? "Nao foi possivel cancelar a venda");
      }

      setCancelTarget(null);
      setCancelReason("");
      setDetailsSale((current) => (current?.id === cancelTarget.id ? null : current));
      setRefreshKey((current) => current + 1);
      setStatsRefreshKey((current) => current + 1);
    } catch (caughtError) {
      setCancelError(
        caughtError instanceof Error ? caughtError.message : "Nao foi possivel cancelar a venda",
      );
    } finally {
      setIsCancelling(false);
    }
  }

  return (
    <div className="mx-auto max-w-7xl space-y-6">
      <section className="flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
          <p className="text-sm font-semibold uppercase tracking-[0.18em] text-[#0f8f80]">
            CRM
          </p>
          <h1 className="mt-2 text-3xl font-bold text-slate-950">Vendas</h1>
        </div>

        <button
          className="hype-focus inline-flex h-10 items-center justify-center gap-2 rounded-[8px] bg-[#3be1c9] px-4 text-sm font-bold text-[#242328] transition hover:bg-[#2bd4bd]"
          onClick={openCreateForm}
          type="button"
        >
          <Plus className="h-4 w-4" aria-hidden />
          Nova venda
        </button>
      </section>

      <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {statCards.map((card) => (
          <article
            className="rounded-[8px] border border-slate-200 bg-white p-5 shadow-sm"
            key={card.label}
          >
            <div className="flex items-start justify-between gap-4">
              <div>
                <p className="text-sm font-medium text-slate-500">{card.label}</p>
                <p className="mt-2 text-2xl font-bold text-slate-950">
                  {isStatsLoading ? "-" : card.value}
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
        <div
          className={
            canManageSales
              ? "grid gap-3 md:grid-cols-[1.4fr_1fr_1fr_1fr]"
              : "grid gap-3 md:grid-cols-[1.4fr_1fr_1fr]"
          }
        >
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
                placeholder="Cliente, contrato ou veiculo"
                type="search"
                value={search}
              />
            </span>
          </label>

          <SelectFilter
            label="Status"
            onChange={(value) => updateFilter(setStatus, value)}
            options={[
              ["", "Todos"],
              [SaleStatus.Pending, statusLabels[SaleStatus.Pending]],
              [SaleStatus.Confirmed, statusLabels[SaleStatus.Confirmed]],
              [SaleStatus.Cancelled, statusLabels[SaleStatus.Cancelled]],
              [SaleStatus.Completed, statusLabels[SaleStatus.Completed]],
            ]}
            value={status}
          />

          {canManageSales ? (
            <SelectFilter
              label="Vendedor"
              onChange={(value) => updateFilter(setSellerId, value)}
              options={[
                ["", "Todos"],
                ...(additional.sellers ?? []).map(
                  (seller) => [String(seller.id), seller.full_name] as const,
                ),
              ]}
              value={sellerId}
            />
          ) : null}

          <SelectFilter
            label="Periodo"
            onChange={(value) => updateFilter(setPeriod, value)}
            options={[
              ["", "Todos"],
              ["today", "Hoje"],
              ["week", "Esta semana"],
              ["month", "Este mes"],
              ["quarter", "Trimestre"],
            ]}
            value={period}
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
          <h2 className="text-base font-bold text-slate-950">Lista de vendas</h2>
          {isLoading ? <Loader2 className="h-5 w-5 animate-spin text-[#0f8f80]" aria-hidden /> : null}
        </div>

        <div className="overflow-x-auto">
          <table className="w-full min-w-[1060px] text-left text-sm">
            <thead className="bg-slate-50 text-xs uppercase tracking-[0.12em] text-slate-500">
              <tr>
                <th className="px-4 py-3 font-semibold">Cliente</th>
                <th className="px-4 py-3 font-semibold">Veiculo</th>
                <th className="px-4 py-3 font-semibold">Valor</th>
                <th className="px-4 py-3 font-semibold">Comissao</th>
                <th className="px-4 py-3 font-semibold">Vendedor</th>
                <th className="px-4 py-3 font-semibold">Status</th>
                <th className="px-4 py-3 font-semibold">Data</th>
                <th className="px-4 py-3 font-semibold">Acoes</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {sales.map((sale) => (
                <tr className="hover:bg-slate-50" key={sale.id}>
                  <td className="px-4 py-3">
                    <p className="font-semibold text-slate-950">
                      {sale.customer_name ?? sale.lead_name ?? "N/A"}
                    </p>
                    <p className="text-xs text-slate-500">
                      {sale.contract_number ? `Contrato: ${sale.contract_number}` : "Sem contrato"}
                    </p>
                  </td>
                  <td className="px-4 py-3 text-slate-700">
                    <p className="max-w-[220px] truncate" title={sale.vehicle_sold ?? undefined}>
                      {sale.vehicle_sold ?? "Nao informado"}
                    </p>
                    <p className="text-xs text-slate-500">
                      {sale.payment_type ?? "Forma nao informada"}
                    </p>
                  </td>
                  <td className="px-4 py-3">
                    <p className="font-semibold text-slate-950">
                      {formatCurrency(sale.sale_value ?? 0)}
                    </p>
                    {sale.down_payment ? (
                      <p className="text-xs text-slate-500">
                        Entrada: {formatCurrency(sale.down_payment)}
                      </p>
                    ) : null}
                  </td>
                  <td className="px-4 py-3">
                    <p className="font-semibold text-[#0f8f80]">
                      {formatCurrency(sale.commission_value ?? 0)}
                    </p>
                    <p className="text-xs text-slate-500">
                      {formatCommissionDetails(sale)}
                    </p>
                  </td>
                  <td className="px-4 py-3 text-slate-700">
                    {sale.seller_name ?? "Nao atribuido"}
                  </td>
                  <td className="px-4 py-3">
                    <StatusBadge status={sale.status} />
                  </td>
                  <td className="px-4 py-3 text-slate-700">
                    <p>{formatDate(sale.sale_date)}</p>
                    <p className="text-xs text-slate-500">{formatTime(sale.sale_date)}</p>
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex gap-2">
                      <IconButton label="Ver detalhes" onClick={() => openDetails(sale.id)}>
                        <Eye className="h-4 w-4" aria-hidden />
                      </IconButton>
                      {canEditSaleInClient(userId, userRole, sale) ? (
                        <IconButton label="Editar" onClick={() => openEditForm(sale.id)}>
                          <Pencil className="h-4 w-4" aria-hidden />
                        </IconButton>
                      ) : null}
                      {canCancelSaleInClient(userRole, sale) ? (
                        <IconButton label="Cancelar" onClick={() => openCancelModal(sale)}>
                          <Ban className="h-4 w-4" aria-hidden />
                        </IconButton>
                      ) : null}
                    </div>
                  </td>
                </tr>
              ))}

              {!isLoading && sales.length === 0 ? (
                <tr>
                  <td className="px-4 py-8 text-center text-slate-500" colSpan={8}>
                    Nenhuma venda encontrada.
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

      {additional.top_vehicles && additional.top_vehicles.length > 0 ? (
        <section className="grid gap-4 xl:grid-cols-2">
          <Panel title="Veiculos mais vendidos">
            <div className="space-y-3">
              {additional.top_vehicles.map((vehicle) => (
                <div
                  className="flex items-center justify-between gap-4 rounded-[8px] bg-slate-50 px-3 py-2"
                  key={vehicle.vehicle_sold}
                >
                  <div className="min-w-0">
                    <p className="truncate text-sm font-semibold text-slate-900">
                      {vehicle.vehicle_sold}
                    </p>
                    <p className="text-xs text-slate-500">
                      {formatInteger(vehicle.count)} vendas
                    </p>
                  </div>
                  <p className="text-sm font-bold text-[#0f8f80]">
                    {formatCurrency(vehicle.total_value)}
                  </p>
                </div>
              ))}
            </div>
          </Panel>

          {additional.top_sellers && additional.top_sellers.length > 0 ? (
            <Panel title="Top vendedores">
              <div className="space-y-3">
                {additional.top_sellers.map((seller) => (
                  <div
                    className="flex items-center justify-between gap-4 rounded-[8px] bg-slate-50 px-3 py-2"
                    key={seller.seller_name}
                  >
                    <div>
                      <p className="text-sm font-semibold text-slate-900">
                        {seller.seller_name}
                      </p>
                      <p className="text-xs text-slate-500">
                        {formatInteger(seller.total_sales)} vendas
                      </p>
                    </div>
                    <p className="text-sm font-bold text-[#0f8f80]">
                      {formatCurrency(seller.total_revenue)}
                    </p>
                  </div>
                ))}
              </div>
            </Panel>
          ) : null}
        </section>
      ) : null}

      {detailsSale ? (
        <SaleDetailsModal onClose={() => setDetailsSale(null)} sale={detailsSale} />
      ) : null}

      {isFormOpen ? (
        <SaleFormModal
          additional={additional}
          canManageSales={canManageSales}
          commissionPreview={commissionPreview}
          editingSale={editingSale}
          error={formError}
          form={form}
          isSubmitting={isSubmitting}
          leadOptions={leadOptions}
          onClose={closeForm}
          onLeadChange={handleLeadChange}
          onSubmit={submitSaleForm}
          updateField={updateFormField}
        />
      ) : null}

      {cancelTarget ? (
        <CancelSaleModal
          error={cancelError}
          isSubmitting={isCancelling}
          onClose={closeCancelModal}
          onReasonChange={setCancelReason}
          onSubmit={submitCancelSale}
          reason={cancelReason}
          sale={cancelTarget}
        />
      ) : null}
    </div>
  );
}

async function fetchSale(saleId: number): Promise<SaleListItem> {
  const response = await fetch(`/api/sales/${saleId}`, {
    cache: "no-store",
  });
  const result = (await response.json()) as SaleResponse;

  if (!response.ok || !result.success || !result.sale) {
    throw new Error(result.message ?? "Nao foi possivel carregar a venda");
  }

  return result.sale;
}

function SaleDetailsModal({
  onClose,
  sale,
}: Readonly<{
  onClose: () => void;
  sale: SaleListItem;
}>) {
  return (
    <ModalShell onClose={onClose} title="Detalhes da venda">
      <div className="grid gap-4 md:grid-cols-2">
        <DetailItem label="Cliente" value={sale.customer_name ?? sale.lead_name ?? "N/A"} />
        <DetailItem label="Vendedor" value={sale.seller_name ?? "Nao atribuido"} />
        <DetailItem label="Telefone" value={sale.customer_phone ?? "Nao informado"} />
        <DetailItem label="Email" value={sale.customer_email ?? "Nao informado"} />
        <DetailItem label="Veiculo" value={sale.vehicle_sold ?? "Nao informado"} />
        <DetailItem label="Contrato" value={sale.contract_number ?? "Sem contrato"} />
        <DetailItem label="Forma de pagamento" value={sale.payment_type ?? "Nao informado"} />
        <DetailItem label="Status" value={statusLabels[sale.status] ?? sale.status} />
        <DetailItem label="Valor da venda" value={formatCurrency(sale.sale_value ?? 0)} />
        <DetailItem label="Entrada" value={formatCurrency(sale.down_payment ?? 0)} />
        <DetailItem label="Comissao" value={formatCurrency(sale.commission_value ?? 0)} />
        <DetailItem label="Data" value={`${formatDate(sale.sale_date)} ${formatTime(sale.sale_date)}`} />
      </div>

      {sale.notes ? (
        <div className="mt-5 rounded-[8px] bg-slate-50 p-4">
          <p className="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
            Observacoes
          </p>
          <p className="mt-2 text-sm text-slate-700">{sale.notes}</p>
        </div>
      ) : null}
    </ModalShell>
  );
}

function CancelSaleModal({
  error,
  isSubmitting,
  onClose,
  onReasonChange,
  onSubmit,
  reason,
  sale,
}: Readonly<{
  error: string;
  isSubmitting: boolean;
  onClose: () => void;
  onReasonChange: (value: string) => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
  reason: string;
  sale: SaleListItem;
}>) {
  return (
    <ModalShell onClose={onClose} title="Cancelar venda">
      <form className="space-y-5" onSubmit={onSubmit}>
        <div className="rounded-[8px] border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
          A venda #{sale.id} sera marcada como cancelada, o lead voltara para negociacao e o
          motivo ficara registrado no historico.
        </div>

        <label className="block">
          <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
            Motivo do cancelamento
          </span>
          <textarea
            className="hype-focus min-h-28 w-full rounded-[8px] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900"
            onChange={(event) => onReasonChange(event.target.value)}
            required
            value={reason}
          />
        </label>

        {error ? (
          <div className="rounded-[8px] border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {error}
          </div>
        ) : null}

        <div className="flex flex-col justify-end gap-2 border-t border-slate-200 pt-4 md:flex-row">
          <button
            className="hype-focus inline-flex h-10 items-center justify-center gap-2 rounded-[8px] border border-slate-200 px-4 text-sm font-semibold text-slate-700"
            disabled={isSubmitting}
            onClick={onClose}
            type="button"
          >
            Voltar
          </button>
          <button
            className="hype-focus inline-flex h-10 items-center justify-center gap-2 rounded-[8px] bg-red-600 px-4 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-60"
            disabled={isSubmitting}
            type="submit"
          >
            {isSubmitting ? (
              <Loader2 className="h-4 w-4 animate-spin" aria-hidden />
            ) : (
              <Ban className="h-4 w-4" aria-hidden />
            )}
            Cancelar venda
          </button>
        </div>
      </form>
    </ModalShell>
  );
}

function SaleFormModal({
  additional,
  canManageSales,
  commissionPreview,
  editingSale,
  error,
  form,
  isSubmitting,
  leadOptions,
  onClose,
  onLeadChange,
  onSubmit,
  updateField,
}: Readonly<{
  additional: SaleAdditionalStats;
  canManageSales: boolean;
  commissionPreview: {
    commissionValue: number;
    installments: number;
    monthlyCommission: number;
    percentage: number;
  };
  editingSale: SaleListItem | null;
  error: string;
  form: SaleFormState;
  isSubmitting: boolean;
  leadOptions: LeadListItem[];
  onClose: () => void;
  onLeadChange: (value: string) => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
  updateField: <K extends keyof SaleFormState>(key: K, value: SaleFormState[K]) => void;
}>) {
  return (
    <ModalShell onClose={onClose} title={editingSale ? "Editar venda" : "Nova venda"}>
      <form className="space-y-5" onSubmit={onSubmit}>
        {!editingSale ? (
          <section className="rounded-[8px] border border-slate-200 bg-slate-50 p-4">
            <h3 className="mb-4 text-sm font-bold text-slate-950">Cliente</h3>
            <div className="grid gap-3 md:grid-cols-2">
              <SelectField
                label="Lead relacionado"
                onChange={onLeadChange}
                options={[
                  ["", "Selecionar lead existente"],
                  ...leadOptions.map((lead) => [String(lead.id), lead.name] as const),
                ]}
                value={form.lead_id}
              />
              <TextField
                label="Nome do cliente"
                onChange={(value) => updateField("customer_name", value)}
                required={!form.lead_id}
                value={form.customer_name}
              />
              <TextField
                label="Email"
                onChange={(value) => updateField("email", value)}
                type="email"
                value={form.email}
              />
              <TextField
                label="Telefone"
                onChange={(value) => updateField("phone", value)}
                type="tel"
                value={form.phone}
              />
            </div>
          </section>
        ) : null}

        <section className="rounded-[8px] border border-slate-200 bg-white p-4">
          <h3 className="mb-4 text-sm font-bold text-slate-950">Venda</h3>
          <div className="grid gap-3 md:grid-cols-2">
            {canManageSales ? (
              <SelectField
                label="Vendedor"
                onChange={(value) => updateField("seller_id", value)}
                options={[
                  ["", "Vendedor atual"],
                  ...(additional.sellers ?? []).map(
                    (seller) => [String(seller.id), seller.full_name] as const,
                  ),
                ]}
                value={form.seller_id}
              />
            ) : null}
            <TextField
              label="Veiculo vendido"
              onChange={(value) => updateField("vehicle_sold", value)}
              required
              value={form.vehicle_sold}
            />
            <TextField
              label="Contrato"
              onChange={(value) => updateField("contract_number", value)}
              value={form.contract_number}
            />
            <SelectField
              label="Forma de pagamento"
              onChange={(value) => updateField("payment_type", value)}
              options={[
                ["", "Selecionar"],
                ["consorcio", "Consorcio"],
                ["financiamento", "Financiamento"],
                ["vista", "A vista"],
              ]}
              required
              value={form.payment_type}
            />
            <TextField
              label="Data da venda"
              onChange={(value) => updateField("sale_date", value)}
              type="date"
              value={form.sale_date}
            />
            <SelectField
              label="Status"
              onChange={(value) => updateField("status", value as SaleStatus)}
              options={[
                [SaleStatus.Pending, statusLabels[SaleStatus.Pending]],
                [SaleStatus.Confirmed, statusLabels[SaleStatus.Confirmed]],
                [SaleStatus.Cancelled, statusLabels[SaleStatus.Cancelled]],
                [SaleStatus.Completed, statusLabels[SaleStatus.Completed]],
              ]}
              value={form.status}
            />
          </div>
        </section>

        <section className="rounded-[8px] border border-slate-200 bg-white p-4">
          <h3 className="mb-4 text-sm font-bold text-slate-950">Valores</h3>
          <div className="grid gap-3 md:grid-cols-2">
            <TextField
              label="Valor da venda"
              min="0"
              onChange={(value) => updateField("sale_value", value)}
              required
              step="0.01"
              type="number"
              value={form.sale_value}
            />
            <TextField
              disabled={!canManageSales}
              label="Comissao (%)"
              min="0"
              onChange={(value) => updateField("commission_percentage", value)}
              step="0.01"
              type="number"
              value={form.commission_percentage}
            />
            <SelectField
              disabled={!canManageSales}
              label="Parcelas da comissao"
              onChange={(value) => updateField("commission_installments", value)}
              options={["1", "2", "3", "4", "5", "6", "10", "12"].map((value) => [
                value,
                `${value}x`,
              ])}
              value={form.commission_installments}
            />
            <TextField
              label="Entrada"
              min="0"
              onChange={(value) => updateField("down_payment", value)}
              step="0.01"
              type="number"
              value={form.down_payment}
            />
            <TextField
              label="Meses financiamento"
              min="1"
              onChange={(value) => updateField("financing_months", value)}
              type="number"
              value={form.financing_months}
            />
            <TextField
              label="Parcela mensal"
              min="0"
              onChange={(value) => updateField("monthly_payment", value)}
              step="0.01"
              type="number"
              value={form.monthly_payment}
            />
          </div>

          <div className="mt-4 grid gap-3 rounded-[8px] bg-[#3be1c9]/10 p-4 md:grid-cols-3">
            <DetailItem
              label="Comissao total"
              value={formatCurrency(commissionPreview.commissionValue)}
            />
            <DetailItem
              label="Valor por parcela"
              value={formatCurrency(commissionPreview.monthlyCommission)}
            />
            <DetailItem label="Parcelas" value={`${commissionPreview.installments}x`} />
          </div>
        </section>

        <label className="block">
          <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
            Observacoes
          </span>
          <textarea
            className="hype-focus min-h-24 w-full rounded-[8px] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900"
            onChange={(event) => updateField("notes", event.target.value)}
            value={form.notes}
          />
        </label>

        {error ? (
          <div className="rounded-[8px] border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {error}
          </div>
        ) : null}

        <div className="flex flex-col justify-end gap-2 border-t border-slate-200 pt-4 md:flex-row">
          <button
            className="hype-focus inline-flex h-10 items-center justify-center gap-2 rounded-[8px] border border-slate-200 px-4 text-sm font-semibold text-slate-700"
            disabled={isSubmitting}
            onClick={onClose}
            type="button"
          >
            Cancelar
          </button>
          <button
            className="hype-focus inline-flex h-10 items-center justify-center gap-2 rounded-[8px] bg-[#3be1c9] px-4 text-sm font-bold text-[#242328] disabled:cursor-not-allowed disabled:opacity-60"
            disabled={isSubmitting}
            type="submit"
          >
            {isSubmitting ? (
              <Loader2 className="h-4 w-4 animate-spin" aria-hidden />
            ) : (
              <Save className="h-4 w-4" aria-hidden />
            )}
            Salvar
          </button>
        </div>
      </form>
    </ModalShell>
  );
}

function ModalShell({
  children,
  onClose,
  title,
}: Readonly<{
  children: ReactNode;
  onClose: () => void;
  title: string;
}>) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
      <div className="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-[8px] bg-white shadow-2xl">
        <div className="sticky top-0 z-10 flex items-center justify-between gap-4 border-b border-slate-200 bg-white px-5 py-4">
          <h2 className="text-lg font-bold text-slate-950">{title}</h2>
          <IconButton label="Fechar" onClick={onClose}>
            <X className="h-4 w-4" aria-hidden />
          </IconButton>
        </div>
        <div className="p-5">{children}</div>
      </div>
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
    <SelectField label={label} onChange={onChange} options={options} value={value} />
  );
}

function SelectField({
  disabled = false,
  label,
  onChange,
  options,
  required = false,
  value,
}: Readonly<{
  disabled?: boolean;
  label: string;
  onChange: (value: string) => void;
  options: ReadonlyArray<readonly [string, string]>;
  required?: boolean;
  value: string;
}>) {
  return (
    <label className="block">
      <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
        {label}
      </span>
      <select
        className="hype-focus h-10 w-full rounded-[8px] border border-slate-200 bg-white px-3 text-sm text-slate-900 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500"
        disabled={disabled}
        onChange={(event) => onChange(event.target.value)}
        required={required}
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
  disabled = false,
  label,
  min,
  onChange,
  required = false,
  step,
  type = "text",
  value,
}: Readonly<{
  disabled?: boolean;
  label: string;
  min?: string;
  onChange: (value: string) => void;
  required?: boolean;
  step?: string;
  type?: string;
  value: string;
}>) {
  return (
    <label className="block">
      <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
        {label}
      </span>
      <input
        className="hype-focus h-10 w-full rounded-[8px] border border-slate-200 bg-white px-3 text-sm text-slate-900 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500"
        disabled={disabled}
        min={min}
        onChange={(event) => onChange(event.target.value)}
        required={required}
        step={step}
        type={type}
        value={value}
      />
    </label>
  );
}

function IconButton({
  children,
  label,
  onClick,
}: Readonly<{
  children: ReactNode;
  label: string;
  onClick: () => void;
}>) {
  return (
    <button
      aria-label={label}
      className="hype-focus inline-flex h-9 w-9 items-center justify-center rounded-[8px] border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-950"
      onClick={onClick}
      title={label}
      type="button"
    >
      {children}
    </button>
  );
}

function StatusBadge({ status }: Readonly<{ status: string }>) {
  const classes: Record<string, string> = {
    [SaleStatus.Pending]: "bg-amber-50 text-amber-700",
    [SaleStatus.Confirmed]: "bg-emerald-50 text-emerald-700",
    [SaleStatus.Cancelled]: "bg-red-50 text-red-700",
    [SaleStatus.Completed]: "bg-blue-50 text-blue-700",
  };

  return (
    <span
      className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${
        classes[status] ?? "bg-slate-100 text-slate-600"
      }`}
    >
      {statusLabels[status] ?? status}
    </span>
  );
}

function Panel({
  children,
  title,
}: Readonly<{
  children: ReactNode;
  title: string;
}>) {
  return (
    <article className="rounded-[8px] border border-slate-200 bg-white p-5 shadow-sm">
      <h2 className="mb-4 text-base font-bold text-slate-950">{title}</h2>
      {children}
    </article>
  );
}

function DetailItem({ label, value }: Readonly<{ label: string; value: string }>) {
  return (
    <div>
      <p className="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
        {label}
      </p>
      <p className="mt-1 break-words text-sm font-semibold text-slate-950">{value}</p>
    </div>
  );
}

function canEditSaleInClient(userId: number, userRole: UserRole, sale: SaleListItem): boolean {
  if ([UserRole.Admin, UserRole.Manager].includes(userRole)) {
    return true;
  }

  return userRole === UserRole.Seller && sale.seller_id === userId && sale.status === SaleStatus.Pending;
}

function canCancelSaleInClient(userRole: UserRole, sale: SaleListItem): boolean {
  return [UserRole.Admin, UserRole.Manager].includes(userRole) && sale.status !== SaleStatus.Cancelled;
}

function createEmptyForm(sellerId = ""): SaleFormState {
  return {
    commission_installments: "5",
    commission_percentage: "1.5",
    contract_number: "",
    customer_name: "",
    down_payment: "",
    email: "",
    financing_months: "",
    lead_id: "",
    monthly_payment: "",
    notes: "",
    payment_type: "",
    phone: "",
    sale_date: currentBrazilDateString(),
    sale_value: "",
    seller_id: sellerId,
    status: SaleStatus.Confirmed,
    vehicle_sold: "",
  };
}

function createFormFromSale(sale: SaleListItem): SaleFormState {
  return {
    commission_installments: sale.commission_installments ? String(sale.commission_installments) : "5",
    commission_percentage: sale.commission_percentage ? String(sale.commission_percentage) : "1.5",
    contract_number: sale.contract_number ?? "",
    customer_name: sale.customer_name ?? sale.lead_name ?? "",
    down_payment: sale.down_payment ? String(sale.down_payment) : "",
    email: sale.customer_email ?? "",
    financing_months: sale.financing_months ? String(sale.financing_months) : "",
    lead_id: String(sale.lead_id),
    monthly_payment: sale.monthly_payment ? String(sale.monthly_payment) : "",
    notes: sale.notes ?? "",
    payment_type: sale.payment_type ?? "",
    phone: sale.customer_phone ?? "",
    sale_date: sale.sale_date ? sale.sale_date.slice(0, 10) : currentBrazilDateString(),
    sale_value: sale.sale_value ? String(sale.sale_value) : "",
    seller_id: String(sale.seller_id),
    status: sale.status,
    vehicle_sold: sale.vehicle_sold ?? "",
  };
}

function buildSalePayload(form: SaleFormState, canManageSales: boolean): SaleMutationInput {
  const payload: SaleMutationInput = {
    contract_number: emptyToNull(form.contract_number),
    customer_name: emptyToNull(form.customer_name),
    down_payment: stringToNumberOrNull(form.down_payment),
    email: emptyToNull(form.email),
    financing_months: stringToNumberOrNull(form.financing_months),
    lead_id: stringToNumberOrNull(form.lead_id),
    monthly_payment: stringToNumberOrNull(form.monthly_payment),
    notes: emptyToNull(form.notes),
    payment_type: emptyToNull(form.payment_type),
    phone: emptyToNull(form.phone),
    sale_date: emptyToNull(form.sale_date),
    sale_value: stringToNumberOrNull(form.sale_value),
    status: form.status,
    vehicle_sold: emptyToNull(form.vehicle_sold),
  };

  if (canManageSales) {
    payload.commission_installments = stringToNumberOrNull(form.commission_installments);
    payload.commission_percentage = stringToNumberOrNull(form.commission_percentage);
    payload.seller_id = stringToNumberOrNull(form.seller_id);
  }

  return payload;
}

function emptyToNull(value: string): string | null {
  const trimmed = value.trim();
  return trimmed ? trimmed : null;
}

function stringToNumberOrNull(value: string): number | null {
  if (!value.trim()) {
    return null;
  }

  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : null;
}

function formatCommissionDetails(sale: SaleListItem): string {
  const details: string[] = [];

  if (sale.commission_percentage !== null) {
    details.push(`${formatDecimal(sale.commission_percentage)}%`);
  }

  if (sale.monthly_commission !== null) {
    details.push(`${formatCurrency(sale.monthly_commission)}/mes`);
  }

  if (sale.commission_installments && sale.commission_installments > 1) {
    details.push(`${sale.commission_installments}x`);
  }

  return details.length ? details.join(" - ") : "Sem regra";
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

function formatTime(value: string | null): string {
  if (!value) {
    return "";
  }

  return formatBrazilTime(value);
}
