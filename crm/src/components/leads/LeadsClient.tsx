"use client";

import {
  AlertCircle,
  CalendarDays,
  Car,
  ChevronLeft,
  ChevronRight,
  Clock,
  Eye,
  Loader2,
  Mail,
  MessageCircle,
  MessageSquare,
  Pencil,
  Phone,
  Plus,
  Save,
  Search,
  ShoppingCart,
  UserCheck,
  UserPlus,
  UsersRound,
  X,
  type LucideIcon,
} from "lucide-react";
import { type FormEvent, type ReactNode, useEffect, useMemo, useState } from "react";

import { formatBrazilDate, formatBrazilDateTime } from "@/lib/date-format";
import {
  InteractionResult,
  InteractionType,
  LeadPriority,
  LeadStatus,
  UserRole,
  type PublicUser,
} from "@/types";
import type {
  LeadAdditionalStats,
  LeadDetailsPayload,
  LeadInteractionMutationInput,
  LeadListItem,
  LeadMutationInput,
  LeadPagination,
  LeadStats,
} from "@/types/leads";

interface LeadsClientProps {
  currentUser: PublicUser;
}

interface LeadsResponse {
  success: boolean;
  message?: string;
  leads?: LeadListItem[];
  pagination?: LeadPagination;
}

interface LeadStatsResponse {
  success: boolean;
  message?: string;
  stats?: LeadStats;
  additional?: LeadAdditionalStats;
}

interface LeadDetailsResponse extends Partial<LeadDetailsPayload> {
  success: boolean;
  message?: string;
}

interface LeadMutationResponse {
  success: boolean;
  message?: string;
  lead?: LeadListItem;
}

interface LeadWhatsAppResponse {
  success: boolean;
  message?: string;
  whatsapp_url?: string;
}

interface LeadInteractionResponse {
  success: boolean;
  message?: string;
}

interface LeadFormState {
  assigned_to: string;
  down_payment_value: string;
  email: string;
  has_down_payment: "yes" | "no";
  name: string;
  notes: string;
  phone: string;
  priority: LeadPriority;
  source_page: string;
  status: LeadStatus;
  vehicle_interest: string;
}

interface InteractionFormState {
  description: string;
  interaction_type: InteractionType;
  next_contact_date: string;
  result: string;
}

const statusLabels: Record<string, string> = {
  [LeadStatus.New]: "Novo",
  [LeadStatus.Contacted]: "Contatado",
  [LeadStatus.Negotiating]: "Negociando",
  [LeadStatus.Converted]: "Convertido",
  [LeadStatus.Lost]: "Perdido",
};

const priorityLabels: Record<string, string> = {
  [LeadPriority.Low]: "Baixa",
  [LeadPriority.Medium]: "Media",
  [LeadPriority.High]: "Alta",
  [LeadPriority.Urgent]: "Urgente",
};

const interactionTypeLabels: Record<string, string> = {
  [InteractionType.Call]: "Ligacao",
  [InteractionType.Whatsapp]: "WhatsApp",
  [InteractionType.Email]: "Email",
  [InteractionType.Meeting]: "Reuniao",
  [InteractionType.Note]: "Nota",
  [InteractionType.StatusChange]: "Mudanca de status",
};

const interactionResultLabels: Record<string, string> = {
  [InteractionResult.Positive]: "Positivo",
  [InteractionResult.Neutral]: "Neutro",
  [InteractionResult.Negative]: "Negativo",
  [InteractionResult.NoAnswer]: "Sem resposta",
};

const blankForm: LeadFormState = {
  assigned_to: "",
  down_payment_value: "",
  email: "",
  has_down_payment: "no",
  name: "",
  notes: "",
  phone: "",
  priority: LeadPriority.Medium,
  source_page: "manual",
  status: LeadStatus.New,
  vehicle_interest: "",
};

const blankInteractionForm: InteractionFormState = {
  description: "",
  interaction_type: InteractionType.Note,
  next_contact_date: "",
  result: "",
};

export function LeadsClient({ currentUser }: LeadsClientProps) {
  const [leads, setLeads] = useState<LeadListItem[]>([]);
  const [pagination, setPagination] = useState<LeadPagination | null>(null);
  const [stats, setStats] = useState<LeadStats | null>(null);
  const [additional, setAdditional] = useState<LeadAdditionalStats>({});
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("");
  const [priority, setPriority] = useState("");
  const [source, setSource] = useState("");
  const [page, setPage] = useState(1);
  const [error, setError] = useState("");
  const [isLoading, setIsLoading] = useState(true);
  const [isStatsLoading, setIsStatsLoading] = useState(true);
  const [refreshKey, setRefreshKey] = useState(0);
  const [statsRefreshKey, setStatsRefreshKey] = useState(0);
  const [details, setDetails] = useState<LeadDetailsPayload | null>(null);
  const [detailsError, setDetailsError] = useState("");
  const [isDetailsOpen, setIsDetailsOpen] = useState(false);
  const [isDetailsLoading, setIsDetailsLoading] = useState(false);
  const [form, setForm] = useState<LeadFormState>(blankForm);
  const [formError, setFormError] = useState("");
  const [formMode, setFormMode] = useState<"create" | "edit">("create");
  const [formLeadId, setFormLeadId] = useState<number | null>(null);
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [claimingLeadId, setClaimingLeadId] = useState<number | null>(null);
  const [whatsAppLeadId, setWhatsAppLeadId] = useState<number | null>(null);

  const canManageLeads = [UserRole.Admin, UserRole.Manager].includes(currentUser.role);
  const canCreateLeads = [UserRole.Admin, UserRole.Manager, UserRole.Seller].includes(
    currentUser.role,
  );

  useEffect(() => {
    let active = true;

    async function loadStats() {
      setIsStatsLoading(true);

      try {
        const response = await fetch("/api/leads/stats", {
          cache: "no-store",
        });
        const result = (await response.json()) as LeadStatsResponse;

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
    const controller = new AbortController();
    const timeout = window.setTimeout(() => {
      loadLeads(controller.signal);
    }, 250);

    async function loadLeads(signal: AbortSignal) {
      setIsLoading(true);
      setError("");

      try {
        const params = new URLSearchParams({
          limit: "20",
          page: String(page),
        });

        if (search.trim()) params.set("search", search.trim());
        if (status) params.set("status", status);
        if (priority) params.set("priority", priority);
        if (source) params.set("source", source);

        const response = await fetch(`/api/leads?${params.toString()}`, {
          cache: "no-store",
          signal,
        });
        const result = (await response.json()) as LeadsResponse;

        if (!response.ok || !result.success || !result.leads || !result.pagination) {
          throw new Error(result.message ?? "Nao foi possivel carregar leads");
        }

        setLeads(result.leads);
        setPagination(result.pagination);
      } catch (caughtError) {
        if (signal.aborted) {
          return;
        }

        setError(
          caughtError instanceof Error ? caughtError.message : "Nao foi possivel carregar leads",
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
  }, [page, priority, refreshKey, search, source, status]);

  const statCards = useMemo(() => {
    if (!stats) {
      return [];
    }

    return [
      {
        icon: UsersRound,
        label: "Total",
        value: stats.total,
        note: `${stats.this_month ?? 0} no mes`,
      },
      {
        icon: Clock,
        label: "Novos",
        value: stats.new,
        note: `${stats.today ?? 0} hoje`,
      },
      {
        icon: UserCheck,
        label: "Contatados",
        value: stats.contacted,
        note: `${stats.negotiating} negociando`,
      },
      {
        icon: AlertCircle,
        label: "Urgencia",
        value:
          additional.leads_by_priority?.find((item) => item.priority === LeadPriority.Urgent)
            ?.count ?? 0,
        note: "prioridade urgente",
      },
    ];
  }, [additional.leads_by_priority, stats]);

  const sourceOptions = useMemo(
    () => [
      ["", "Todas"] as const,
      ...(additional.leads_by_source ?? []).map((item) => [item.source, item.source] as const),
    ],
    [additional.leads_by_source],
  );

  function updateFilter(setter: (value: string) => void, value: string) {
    setter(value);
    setPage(1);
  }

  function canEditLead(lead: Pick<LeadListItem, "assigned_to">): boolean {
    return canManageLeads || lead.assigned_to === currentUser.id;
  }

  function canClaimLead(lead: Pick<LeadListItem, "assigned_to">): boolean {
    return currentUser.role === UserRole.Seller && lead.assigned_to === null;
  }

  async function openDetails(leadId: number) {
    setIsDetailsOpen(true);
    setDetails(null);
    setDetailsError("");
    setIsDetailsLoading(true);

    try {
      const response = await fetch(`/api/leads/${leadId}`, {
        cache: "no-store",
      });
      const result = (await response.json()) as LeadDetailsResponse;

      if (!response.ok || !result.success || !result.lead) {
        throw new Error(result.message ?? "Nao foi possivel carregar detalhes do lead");
      }

      setDetails({
        interactions: result.interactions ?? [],
        lead: result.lead,
        sales: result.sales ?? [],
      });
    } catch (caughtError) {
      setDetailsError(
        caughtError instanceof Error
          ? caughtError.message
          : "Nao foi possivel carregar detalhes do lead",
      );
    } finally {
      setIsDetailsLoading(false);
    }
  }

  function openCreateForm() {
    setForm(blankForm);
    setFormError("");
    setFormLeadId(null);
    setFormMode("create");
    setIsFormOpen(true);
  }

  function openEditForm(lead: LeadListItem) {
    setForm({
      assigned_to: lead.assigned_to ? String(lead.assigned_to) : "",
      down_payment_value:
        lead.down_payment_value !== null ? String(lead.down_payment_value) : "",
      email: lead.email ?? "",
      has_down_payment: lead.has_down_payment === "yes" ? "yes" : "no",
      name: lead.name,
      notes: lead.notes ?? "",
      phone: lead.phone,
      priority: lead.priority,
      source_page: lead.source_page ?? "manual",
      status: lead.status,
      vehicle_interest: lead.vehicle_interest ?? "",
    });
    setFormError("");
    setFormLeadId(lead.id);
    setFormMode("edit");
    setIsFormOpen(true);
  }

  function updateForm<K extends keyof LeadFormState>(key: K, value: LeadFormState[K]) {
    setForm((current) => ({
      ...current,
      [key]: value,
      ...(key === "has_down_payment" && value === "no" ? { down_payment_value: "" } : {}),
    }));
  }

  async function submitForm(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setIsSaving(true);
    setFormError("");

    try {
      const payload: LeadMutationInput = {
        down_payment_value:
          form.has_down_payment === "yes" && form.down_payment_value
            ? Number(form.down_payment_value)
            : null,
        email: form.email,
        has_down_payment: form.has_down_payment,
        name: form.name,
        notes: form.notes,
        phone: form.phone,
        priority: form.priority,
        status: form.status,
        vehicle_interest: form.vehicle_interest,
      };

      if (formMode === "create") {
        payload.source_page = form.source_page;
      }

      if (canManageLeads) {
        payload.assigned_to = form.assigned_to ? Number(form.assigned_to) : null;
      }

      const endpoint = formMode === "edit" && formLeadId ? `/api/leads/${formLeadId}` : "/api/leads";
      const response = await fetch(endpoint, {
        body: JSON.stringify(payload),
        headers: {
          "Content-Type": "application/json",
        },
        method: formMode === "edit" ? "PUT" : "POST",
      });
      const result = (await response.json()) as LeadMutationResponse;

      if (!response.ok || !result.success) {
        throw new Error(result.message ?? "Nao foi possivel salvar o lead");
      }

      setIsFormOpen(false);
      setRefreshKey((current) => current + 1);
      setStatsRefreshKey((current) => current + 1);

      const savedLead = result.lead;

      if (savedLead && details?.lead.id === savedLead.id) {
        await openDetails(savedLead.id);
      }
    } catch (caughtError) {
      setFormError(
        caughtError instanceof Error ? caughtError.message : "Nao foi possivel salvar o lead",
      );
    } finally {
      setIsSaving(false);
    }
  }

  async function claimLead(leadId: number) {
    setError("");
    setClaimingLeadId(leadId);

    try {
      const response = await fetch(`/api/leads/${leadId}/claim`, {
        method: "POST",
      });
      const result = (await response.json()) as LeadMutationResponse;

      if (!response.ok || !result.success) {
        throw new Error(result.message ?? "Nao foi possivel assumir o lead");
      }

      setRefreshKey((current) => current + 1);
      setStatsRefreshKey((current) => current + 1);
    } catch (caughtError) {
      setError(
        caughtError instanceof Error ? caughtError.message : "Nao foi possivel assumir o lead",
      );
    } finally {
      setClaimingLeadId(null);
    }
  }

  async function openLeadWhatsApp(leadId: number) {
    setError("");
    setWhatsAppLeadId(leadId);

    try {
      const response = await fetch(`/api/leads/${leadId}/whatsapp`, {
        cache: "no-store",
      });
      const result = (await response.json()) as LeadWhatsAppResponse;

      if (!response.ok || !result.success || !result.whatsapp_url) {
        throw new Error(result.message ?? "Nao foi possivel gerar o WhatsApp do lead");
      }

      window.open(result.whatsapp_url, "_blank", "noopener,noreferrer");
    } catch (caughtError) {
      setError(
        caughtError instanceof Error
          ? caughtError.message
          : "Nao foi possivel gerar o WhatsApp do lead",
      );
    } finally {
      setWhatsAppLeadId(null);
    }
  }

  return (
    <div className="mx-auto max-w-7xl space-y-6">
      <section className="flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
          <p className="text-sm font-semibold uppercase tracking-[0.18em] text-[#0f8f80]">
            CRM
          </p>
          <h1 className="mt-2 text-3xl font-bold text-slate-950">Leads</h1>
        </div>

        {canCreateLeads ? (
          <button
            className="hype-focus inline-flex h-10 items-center justify-center gap-2 rounded-[8px] bg-[#242328] px-4 text-sm font-semibold text-white transition hover:bg-[#111114]"
            onClick={openCreateForm}
            type="button"
          >
            <Plus className="h-4 w-4" aria-hidden />
            Novo lead
          </button>
        ) : null}
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
                  {isStatsLoading ? "-" : formatInteger(card.value)}
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
        <div className="grid gap-3 md:grid-cols-[1.4fr_1fr_1fr_1fr]">
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
                placeholder="Nome, telefone, email ou veiculo"
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
              [LeadStatus.New, statusLabels[LeadStatus.New]],
              [LeadStatus.Contacted, statusLabels[LeadStatus.Contacted]],
              [LeadStatus.Negotiating, statusLabels[LeadStatus.Negotiating]],
              [LeadStatus.Converted, statusLabels[LeadStatus.Converted]],
              [LeadStatus.Lost, statusLabels[LeadStatus.Lost]],
            ]}
            value={status}
          />

          <SelectFilter
            label="Prioridade"
            onChange={(value) => updateFilter(setPriority, value)}
            options={[
              ["", "Todas"],
              [LeadPriority.Low, priorityLabels[LeadPriority.Low]],
              [LeadPriority.Medium, priorityLabels[LeadPriority.Medium]],
              [LeadPriority.High, priorityLabels[LeadPriority.High]],
              [LeadPriority.Urgent, priorityLabels[LeadPriority.Urgent]],
            ]}
            value={priority}
          />

          <SelectFilter
            label="Origem"
            onChange={(value) => updateFilter(setSource, value)}
            options={sourceOptions}
            value={source}
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
          <h2 className="text-base font-bold text-slate-950">Lista de leads</h2>
          {isLoading ? <Loader2 className="h-5 w-5 animate-spin text-[#0f8f80]" aria-hidden /> : null}
        </div>

        <div className="overflow-x-auto">
          <table className="w-full min-w-[1040px] text-left text-sm">
            <thead className="bg-slate-50 text-xs uppercase tracking-[0.12em] text-slate-500">
              <tr>
                <th className="px-4 py-3 font-semibold">Lead</th>
                <th className="px-4 py-3 font-semibold">Telefone</th>
                <th className="px-4 py-3 font-semibold">Veiculo</th>
                <th className="px-4 py-3 font-semibold">Status</th>
                <th className="px-4 py-3 font-semibold">Prioridade</th>
                <th className="px-4 py-3 font-semibold">Origem</th>
                <th className="px-4 py-3 font-semibold">Vendedor</th>
                <th className="px-4 py-3 font-semibold">Criado em</th>
                <th className="px-4 py-3 text-right font-semibold">Acoes</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {leads.map((lead) => (
                <tr className="hover:bg-slate-50" key={lead.id}>
                  <td className="px-4 py-3">
                    <p className="font-semibold text-slate-950">{lead.name}</p>
                    <p className="text-xs text-slate-500">{lead.email ?? "Sem email"}</p>
                  </td>
                  <td className="px-4 py-3 text-slate-700">{lead.phone}</td>
                  <td className="px-4 py-3 text-slate-700">
                    {lead.vehicle_interest ?? "Nao informado"}
                  </td>
                  <td className="px-4 py-3">
                    <StatusBadge status={lead.status} />
                  </td>
                  <td className="px-4 py-3">
                    <PriorityBadge priority={lead.priority} />
                  </td>
                  <td className="px-4 py-3 text-slate-700">
                    {lead.source_page ?? "Nao informado"}
                  </td>
                  <td className="px-4 py-3 text-slate-700">
                    {lead.assigned_to_name ?? "Nao atribuido"}
                  </td>
                  <td className="px-4 py-3 text-slate-700">{formatDate(lead.created_at)}</td>
                  <td className="px-4 py-3">
                    <div className="flex justify-end gap-2">
                      <button
                        className="hype-focus inline-flex h-9 w-9 items-center justify-center rounded-[8px] border border-slate-200 text-slate-600 transition hover:bg-slate-50"
                        onClick={() => openDetails(lead.id)}
                        title="Ver detalhes"
                        type="button"
                      >
                        <Eye className="h-4 w-4" aria-hidden />
                      </button>
                      <button
                        className="hype-focus inline-flex h-9 w-9 items-center justify-center rounded-[8px] border border-slate-200 text-[#0f8f80] transition hover:bg-[#3be1c9]/10 disabled:cursor-not-allowed disabled:opacity-60"
                        disabled={whatsAppLeadId === lead.id}
                        onClick={() => openLeadWhatsApp(lead.id)}
                        title="WhatsApp"
                        type="button"
                      >
                        {whatsAppLeadId === lead.id ? (
                          <Loader2 className="h-4 w-4 animate-spin" aria-hidden />
                        ) : (
                          <MessageCircle className="h-4 w-4" aria-hidden />
                        )}
                      </button>
                      {canEditLead(lead) ? (
                        <button
                          className="hype-focus inline-flex h-9 w-9 items-center justify-center rounded-[8px] border border-slate-200 text-slate-600 transition hover:bg-slate-50"
                          onClick={() => openEditForm(lead)}
                          title="Editar lead"
                          type="button"
                        >
                          <Pencil className="h-4 w-4" aria-hidden />
                        </button>
                      ) : null}
                      {canClaimLead(lead) ? (
                        <button
                          className="hype-focus inline-flex h-9 w-9 items-center justify-center rounded-[8px] border border-slate-200 text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                          disabled={claimingLeadId === lead.id}
                          onClick={() => claimLead(lead.id)}
                          title="Assumir lead"
                          type="button"
                        >
                          {claimingLeadId === lead.id ? (
                            <Loader2 className="h-4 w-4 animate-spin" aria-hidden />
                          ) : (
                            <UserPlus className="h-4 w-4" aria-hidden />
                          )}
                        </button>
                      ) : null}
                    </div>
                  </td>
                </tr>
              ))}

              {!isLoading && leads.length === 0 ? (
                <tr>
                  <td className="px-4 py-8 text-center text-slate-500" colSpan={9}>
                    Nenhum lead encontrado.
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

      {isDetailsOpen ? (
        <LeadDetailsModal
          canEdit={details ? canEditLead(details.lead) : false}
          details={details}
          error={detailsError}
          isLoading={isDetailsLoading}
          onClose={() => setIsDetailsOpen(false)}
          onEdit={(lead) => openEditForm(lead)}
          onRefresh={(leadId) => openDetails(leadId)}
        />
      ) : null}

      {isFormOpen ? (
        <LeadFormModal
          additional={additional}
          canManageLeads={canManageLeads}
          error={formError}
          form={form}
          isSaving={isSaving}
          mode={formMode}
          onClose={() => setIsFormOpen(false)}
          onSubmit={submitForm}
          onUpdate={updateForm}
        />
      ) : null}
    </div>
  );
}

function LeadDetailsModal({
  canEdit,
  details,
  error,
  isLoading,
  onClose,
  onEdit,
  onRefresh,
}: Readonly<{
  canEdit: boolean;
  details: LeadDetailsPayload | null;
  error: string;
  isLoading: boolean;
  onClose: () => void;
  onEdit: (lead: LeadListItem) => void;
  onRefresh: (leadId: number) => Promise<void>;
}>) {
  const [interactionForm, setInteractionForm] =
    useState<InteractionFormState>(blankInteractionForm);
  const [interactionError, setInteractionError] = useState("");
  const [isSavingInteraction, setIsSavingInteraction] = useState(false);

  function updateInteractionForm<K extends keyof InteractionFormState>(
    key: K,
    value: InteractionFormState[K],
  ) {
    setInteractionForm((current) => ({
      ...current,
      [key]: value,
    }));
  }

  async function submitInteraction(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (!details) {
      return;
    }

    setInteractionError("");
    setIsSavingInteraction(true);

    try {
      const payload: LeadInteractionMutationInput = {
        description: interactionForm.description,
        interaction_type: interactionForm.interaction_type,
        next_contact_date: interactionForm.next_contact_date || null,
        result: interactionForm.result || null,
      };
      const response = await fetch(`/api/leads/${details.lead.id}/interactions`, {
        body: JSON.stringify(payload),
        headers: {
          "Content-Type": "application/json",
        },
        method: "POST",
      });
      const result = (await response.json()) as LeadInteractionResponse;

      if (!response.ok || !result.success) {
        throw new Error(result.message ?? "Nao foi possivel registrar a interacao");
      }

      setInteractionForm(blankInteractionForm);
      await onRefresh(details.lead.id);
    } catch (caughtError) {
      setInteractionError(
        caughtError instanceof Error
          ? caughtError.message
          : "Nao foi possivel registrar a interacao",
      );
    } finally {
      setIsSavingInteraction(false);
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
      <div className="max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-[8px] bg-white shadow-xl">
        <div className="sticky top-0 z-10 flex items-center justify-between gap-3 border-b border-slate-200 bg-white px-5 py-4">
          <div>
            <h2 className="text-lg font-bold text-slate-950">Detalhes do lead</h2>
            <p className="text-sm text-slate-500">
              {details?.lead.name ?? "Carregando informacoes"}
            </p>
          </div>
          <div className="flex gap-2">
            {details && canEdit ? (
              <button
                className="hype-focus inline-flex h-9 items-center gap-2 rounded-[8px] border border-slate-200 px-3 text-sm font-semibold text-slate-700"
                onClick={() => onEdit(details.lead)}
                type="button"
              >
                <Pencil className="h-4 w-4" aria-hidden />
                Editar
              </button>
            ) : null}
            <button
              className="hype-focus inline-flex h-9 w-9 items-center justify-center rounded-[8px] border border-slate-200 text-slate-600"
              onClick={onClose}
              title="Fechar"
              type="button"
            >
              <X className="h-4 w-4" aria-hidden />
            </button>
          </div>
        </div>

        <div className="space-y-5 p-5">
          {isLoading ? (
            <div className="flex items-center justify-center gap-2 py-10 text-sm text-slate-500">
              <Loader2 className="h-5 w-5 animate-spin text-[#0f8f80]" aria-hidden />
              Carregando detalhes
            </div>
          ) : null}

          {error ? (
            <div className="rounded-[8px] border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {error}
            </div>
          ) : null}

          {details && !isLoading ? (
            <>
              <div className="grid gap-4 md:grid-cols-2">
                <DetailPanel title="Contato">
                  <DetailLine icon={Phone} label="Telefone" value={details.lead.phone} />
                  <DetailLine icon={Mail} label="Email" value={details.lead.email ?? "Sem email"} />
                  <DetailLine
                    icon={Car}
                    label="Veiculo"
                    value={details.lead.vehicle_interest ?? "Nao informado"}
                  />
                </DetailPanel>

                <DetailPanel title="Qualificacao">
                  <div className="flex flex-wrap gap-2">
                    <StatusBadge status={details.lead.status} />
                    <PriorityBadge priority={details.lead.priority} />
                  </div>
                  <DetailText
                    label="Entrada"
                    value={
                      details.lead.has_down_payment === "yes"
                        ? formatCurrency(details.lead.down_payment_value ?? 0)
                        : "Sem entrada"
                    }
                  />
                  <DetailText
                    label="Responsavel"
                    value={details.lead.assigned_to_name ?? "Nao atribuido"}
                  />
                  <DetailText label="Origem" value={details.lead.source_page ?? "Nao informado"} />
                </DetailPanel>
              </div>

              <DetailPanel title="Observacoes">
                <p className="whitespace-pre-wrap text-sm text-slate-700">
                  {details.lead.notes ?? "Sem observacoes."}
                </p>
              </DetailPanel>

              {canEdit ? (
                <DetailPanel title="Nova interacao">
                  <form className="space-y-4" onSubmit={submitInteraction}>
                    {interactionError ? (
                      <div className="rounded-[8px] border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {interactionError}
                      </div>
                    ) : null}
                    <div className="grid gap-4 md:grid-cols-3">
                      <SelectField
                        label="Tipo"
                        onChange={(value) =>
                          updateInteractionForm("interaction_type", value as InteractionType)
                        }
                        options={[
                          [InteractionType.Note, interactionTypeLabels[InteractionType.Note]],
                          [InteractionType.Call, interactionTypeLabels[InteractionType.Call]],
                          [
                            InteractionType.Whatsapp,
                            interactionTypeLabels[InteractionType.Whatsapp],
                          ],
                          [InteractionType.Email, interactionTypeLabels[InteractionType.Email]],
                          [InteractionType.Meeting, interactionTypeLabels[InteractionType.Meeting]],
                        ]}
                        value={interactionForm.interaction_type}
                      />
                      <SelectField
                        label="Resultado"
                        onChange={(value) => updateInteractionForm("result", value)}
                        options={[
                          ["", "Nao informado"],
                          [
                            InteractionResult.Positive,
                            interactionResultLabels[InteractionResult.Positive],
                          ],
                          [
                            InteractionResult.Neutral,
                            interactionResultLabels[InteractionResult.Neutral],
                          ],
                          [
                            InteractionResult.Negative,
                            interactionResultLabels[InteractionResult.Negative],
                          ],
                          [
                            InteractionResult.NoAnswer,
                            interactionResultLabels[InteractionResult.NoAnswer],
                          ],
                        ]}
                        value={interactionForm.result}
                      />
                      <TextField
                        label="Proximo contato"
                        onChange={(value) => updateInteractionForm("next_contact_date", value)}
                        type="date"
                        value={interactionForm.next_contact_date}
                      />
                    </div>
                    <label className="block">
                      <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                        Descricao
                      </span>
                      <textarea
                        className="hype-focus min-h-24 w-full rounded-[8px] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900"
                        onChange={(event) =>
                          updateInteractionForm("description", event.target.value)
                        }
                        required
                        value={interactionForm.description}
                      />
                    </label>
                    <div className="flex justify-end">
                      <button
                        className="hype-focus inline-flex h-10 items-center justify-center gap-2 rounded-[8px] bg-[#242328] px-4 text-sm font-semibold text-white transition hover:bg-[#111114] disabled:cursor-not-allowed disabled:opacity-60"
                        disabled={isSavingInteraction}
                        type="submit"
                      >
                        {isSavingInteraction ? (
                          <Loader2 className="h-4 w-4 animate-spin" aria-hidden />
                        ) : (
                          <MessageSquare className="h-4 w-4" aria-hidden />
                        )}
                        Registrar
                      </button>
                    </div>
                  </form>
                </DetailPanel>
              ) : null}

              <DetailPanel title="Interacoes">
                {details.interactions.length > 0 ? (
                  <div className="space-y-3">
                    {details.interactions.map((interaction) => (
                      <div className="border-l-2 border-[#3be1c9] pl-3" key={interaction.id}>
                        <p className="text-sm font-semibold text-slate-900">
                          {interaction.description ?? "Interacao registrada"}
                        </p>
                        <p className="mt-1 text-xs text-slate-500">
                          {interaction.user_name ?? "Sistema"} - {formatDate(interaction.created_at)}
                        </p>
                        {interaction.next_contact_date ? (
                          <p className="mt-1 flex items-center gap-1 text-xs text-slate-500">
                            <CalendarDays className="h-3.5 w-3.5" aria-hidden />
                            Proximo contato: {formatDateOnly(interaction.next_contact_date)}
                          </p>
                        ) : null}
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="text-sm text-slate-500">Nenhuma interacao registrada.</p>
                )}
              </DetailPanel>

              <DetailPanel title="Vendas relacionadas">
                {details.sales.length > 0 ? (
                  <div className="overflow-x-auto">
                    <table className="w-full min-w-[620px] text-left text-sm">
                      <thead className="text-xs uppercase tracking-[0.12em] text-slate-500">
                        <tr>
                          <th className="py-2 pr-3 font-semibold">Venda</th>
                          <th className="py-2 pr-3 font-semibold">Veiculo</th>
                          <th className="py-2 pr-3 font-semibold">Valor</th>
                          <th className="py-2 pr-3 font-semibold">Status</th>
                          <th className="py-2 pr-3 font-semibold">Vendedor</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100">
                        {details.sales.map((sale) => (
                          <tr key={sale.id}>
                            <td className="py-2 pr-3 text-slate-700">#{sale.id}</td>
                            <td className="py-2 pr-3 text-slate-700">
                              {sale.vehicle_sold ?? "Nao informado"}
                            </td>
                            <td className="py-2 pr-3 font-semibold text-slate-950">
                              {formatCurrency(sale.sale_value ?? 0)}
                            </td>
                            <td className="py-2 pr-3 text-slate-700">
                              {sale.status ?? "Nao informado"}
                            </td>
                            <td className="py-2 pr-3 text-slate-700">
                              {sale.seller_name ?? "Nao atribuido"}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                ) : (
                  <p className="flex items-center gap-2 text-sm text-slate-500">
                    <ShoppingCart className="h-4 w-4" aria-hidden />
                    Nenhuma venda relacionada.
                  </p>
                )}
              </DetailPanel>
            </>
          ) : null}
        </div>
      </div>
    </div>
  );
}

function LeadFormModal({
  additional,
  canManageLeads,
  error,
  form,
  isSaving,
  mode,
  onClose,
  onSubmit,
  onUpdate,
}: Readonly<{
  additional: LeadAdditionalStats;
  canManageLeads: boolean;
  error: string;
  form: LeadFormState;
  isSaving: boolean;
  mode: "create" | "edit";
  onClose: () => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
  onUpdate: <K extends keyof LeadFormState>(key: K, value: LeadFormState[K]) => void;
}>) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
      <form
        className="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-[8px] bg-white shadow-xl"
        onSubmit={onSubmit}
      >
        <div className="sticky top-0 z-10 flex items-center justify-between gap-3 border-b border-slate-200 bg-white px-5 py-4">
          <div>
            <h2 className="text-lg font-bold text-slate-950">
              {mode === "create" ? "Novo lead" : "Editar lead"}
            </h2>
            <p className="text-sm text-slate-500">Campos principais do cadastro legado.</p>
          </div>
          <button
            className="hype-focus inline-flex h-9 w-9 items-center justify-center rounded-[8px] border border-slate-200 text-slate-600"
            onClick={onClose}
            title="Fechar"
            type="button"
          >
            <X className="h-4 w-4" aria-hidden />
          </button>
        </div>

        <div className="space-y-5 p-5">
          {error ? (
            <div className="rounded-[8px] border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {error}
            </div>
          ) : null}

          <div className="grid gap-4 md:grid-cols-2">
            <TextField
              label="Nome"
              onChange={(value) => onUpdate("name", value)}
              required
              value={form.name}
            />
            <TextField
              label="Telefone"
              onChange={(value) => onUpdate("phone", value)}
              required
              value={form.phone}
            />
            <TextField
              label="Email"
              onChange={(value) => onUpdate("email", value)}
              type="email"
              value={form.email}
            />
            <TextField
              label="Veiculo de interesse"
              onChange={(value) => onUpdate("vehicle_interest", value)}
              value={form.vehicle_interest}
            />
          </div>

          <div className="grid gap-4 md:grid-cols-3">
            <SelectField
              label="Status"
              onChange={(value) => onUpdate("status", value as LeadStatus)}
              options={[
                [LeadStatus.New, statusLabels[LeadStatus.New]],
                [LeadStatus.Contacted, statusLabels[LeadStatus.Contacted]],
                [LeadStatus.Negotiating, statusLabels[LeadStatus.Negotiating]],
                [LeadStatus.Converted, statusLabels[LeadStatus.Converted]],
                [LeadStatus.Lost, statusLabels[LeadStatus.Lost]],
              ]}
              value={form.status}
            />
            <SelectField
              label="Prioridade"
              onChange={(value) => onUpdate("priority", value as LeadPriority)}
              options={[
                [LeadPriority.Low, priorityLabels[LeadPriority.Low]],
                [LeadPriority.Medium, priorityLabels[LeadPriority.Medium]],
                [LeadPriority.High, priorityLabels[LeadPriority.High]],
                [LeadPriority.Urgent, priorityLabels[LeadPriority.Urgent]],
              ]}
              value={form.priority}
            />
            {mode === "create" ? (
              <TextField
                label="Origem"
                onChange={(value) => onUpdate("source_page", value)}
                value={form.source_page}
              />
            ) : null}
          </div>

          <div className="grid gap-4 md:grid-cols-3">
            <SelectField
              label="Tem entrada"
              onChange={(value) => onUpdate("has_down_payment", value as "yes" | "no")}
              options={[
                ["no", "Nao"],
                ["yes", "Sim"],
              ]}
              value={form.has_down_payment}
            />
            <TextField
              disabled={form.has_down_payment === "no"}
              label="Valor da entrada"
              onChange={(value) => onUpdate("down_payment_value", value)}
              type="number"
              value={form.down_payment_value}
            />
            {canManageLeads ? (
              <SelectField
                label="Responsavel"
                onChange={(value) => onUpdate("assigned_to", value)}
                options={[
                  ["", "Nao atribuido"],
                  ...(additional.sellers ?? []).map(
                    (seller) =>
                      [
                        String(seller.id),
                        `${seller.full_name} (${seller.role})`,
                      ] as const,
                  ),
                ]}
                value={form.assigned_to}
              />
            ) : null}
          </div>

          <label className="block">
            <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
              Observacoes
            </span>
            <textarea
              className="hype-focus min-h-28 w-full rounded-[8px] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900"
              onChange={(event) => onUpdate("notes", event.target.value)}
              value={form.notes}
            />
          </label>
        </div>

        <div className="sticky bottom-0 flex justify-end gap-2 border-t border-slate-200 bg-white px-5 py-4">
          <button
            className="hype-focus inline-flex h-10 items-center justify-center rounded-[8px] border border-slate-200 px-4 text-sm font-semibold text-slate-700"
            disabled={isSaving}
            onClick={onClose}
            type="button"
          >
            Cancelar
          </button>
          <button
            className="hype-focus inline-flex h-10 items-center justify-center gap-2 rounded-[8px] bg-[#242328] px-4 text-sm font-semibold text-white transition hover:bg-[#111114] disabled:cursor-not-allowed disabled:opacity-60"
            disabled={isSaving}
            type="submit"
          >
            {isSaving ? (
              <Loader2 className="h-4 w-4 animate-spin" aria-hidden />
            ) : (
              <Save className="h-4 w-4" aria-hidden />
            )}
            Salvar
          </button>
        </div>
      </form>
    </div>
  );
}

function DetailPanel({
  children,
  title,
}: Readonly<{
  children: ReactNode;
  title: string;
}>) {
  return (
    <section className="rounded-[8px] border border-slate-200 p-4">
      <h3 className="mb-3 text-sm font-bold uppercase tracking-[0.14em] text-slate-500">
        {title}
      </h3>
      <div className="space-y-3">{children}</div>
    </section>
  );
}

function DetailLine({
  icon: Icon,
  label,
  value,
}: Readonly<{
  icon: LucideIcon;
  label: string;
  value: string;
}>) {
  return (
    <div className="flex items-start gap-3">
      <span className="mt-0.5 flex h-8 w-8 items-center justify-center rounded-[8px] bg-[#3be1c9]/15 text-[#0f8f80]">
        <Icon className="h-4 w-4" aria-hidden />
      </span>
      <DetailText label={label} value={value} />
    </div>
  );
}

function DetailText({ label, value }: Readonly<{ label: string; value: string }>) {
  return (
    <div>
      <p className="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{label}</p>
      <p className="mt-1 text-sm text-slate-800">{value}</p>
    </div>
  );
}

function TextField({
  disabled = false,
  label,
  onChange,
  required = false,
  type = "text",
  value,
}: Readonly<{
  disabled?: boolean;
  label: string;
  onChange: (value: string) => void;
  required?: boolean;
  type?: "date" | "email" | "number" | "text";
  value: string;
}>) {
  return (
    <label className="block">
      <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
        {label}
      </span>
      <input
        className="hype-focus h-10 w-full rounded-[8px] border border-slate-200 bg-white px-3 text-sm text-slate-900 disabled:bg-slate-100 disabled:text-slate-400"
        disabled={disabled}
        min={type === "number" ? "0" : undefined}
        onChange={(event) => onChange(event.target.value)}
        required={required}
        step={type === "number" ? "0.01" : undefined}
        type={type}
        value={value}
      />
    </label>
  );
}

function SelectField({
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

function StatusBadge({ status }: Readonly<{ status: string }>) {
  const classes: Record<string, string> = {
    [LeadStatus.New]: "bg-blue-50 text-blue-700",
    [LeadStatus.Contacted]: "bg-amber-50 text-amber-700",
    [LeadStatus.Negotiating]: "bg-orange-50 text-orange-700",
    [LeadStatus.Converted]: "bg-emerald-50 text-emerald-700",
    [LeadStatus.Lost]: "bg-slate-100 text-slate-600",
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

function PriorityBadge({ priority }: Readonly<{ priority: string }>) {
  const classes: Record<string, string> = {
    [LeadPriority.Low]: "bg-slate-100 text-slate-600",
    [LeadPriority.Medium]: "bg-amber-50 text-amber-700",
    [LeadPriority.High]: "bg-red-50 text-red-700",
    [LeadPriority.Urgent]: "bg-red-600 text-white",
  };

  return (
    <span
      className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${
        classes[priority] ?? "bg-slate-100 text-slate-600"
      }`}
    >
      {priorityLabels[priority] ?? priority}
    </span>
  );
}

function formatDate(value: string): string {
  return formatBrazilDateTime(value);
}

function formatDateOnly(value: string): string {
  return formatBrazilDate(value);
}

function formatCurrency(value: number): string {
  return new Intl.NumberFormat("pt-BR", {
    currency: "BRL",
    style: "currency",
  }).format(value);
}

function formatInteger(value: number): string {
  return new Intl.NumberFormat("pt-BR", {
    maximumFractionDigits: 0,
  }).format(value);
}
