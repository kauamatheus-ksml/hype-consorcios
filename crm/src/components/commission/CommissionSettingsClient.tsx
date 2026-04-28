"use client";

import {
  Loader2,
  Pencil,
  RefreshCw,
  Save,
  Settings,
  X,
} from "lucide-react";
import { useEffect, useMemo, useState, type FormEvent, type ReactNode } from "react";

import { formatBrazilDateTime } from "@/lib/date-format";
import { UserRole } from "@/types";
import type {
  CommissionSellerItem,
  CommissionSettingsInput,
} from "@/types/commission";

interface CommissionSettingsClientProps {
  userRole: UserRole;
}

interface CommissionResponse {
  message?: string;
  seller?: CommissionSellerItem;
  sellers?: CommissionSellerItem[];
  success: boolean;
}

interface CommissionFormState {
  bonus_percentage: string;
  bonus_threshold: string;
  commission_installments: string;
  commission_percentage: string;
  is_active: boolean;
  max_sale_value: string;
  min_sale_value: string;
  notes: string;
  seller_id: string;
}

const roleLabels: Record<UserRole, string> = {
  [UserRole.Admin]: "Administrador",
  [UserRole.Manager]: "Gerente",
  [UserRole.Seller]: "Vendedor",
  [UserRole.Viewer]: "Visualizador",
};

export function CommissionSettingsClient({
  userRole,
}: Readonly<CommissionSettingsClientProps>) {
  const [sellers, setSellers] = useState<CommissionSellerItem[]>([]);
  const [editingSeller, setEditingSeller] = useState<CommissionSellerItem | null>(null);
  const [form, setForm] = useState<CommissionFormState>(() => createEmptyForm());
  const [error, setError] = useState("");
  const [formError, setFormError] = useState("");
  const [message, setMessage] = useState("");
  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [refreshKey, setRefreshKey] = useState(0);

  const canWrite = userRole === UserRole.Admin;

  useEffect(() => {
    let active = true;

    async function loadSellers() {
      setIsLoading(true);
      setError("");

      try {
        const response = await fetch("/api/commission-settings", {
          cache: "no-store",
        });
        const result = (await response.json()) as CommissionResponse;

        if (!response.ok || !result.success || !result.sellers) {
          throw new Error(result.message ?? "Nao foi possivel carregar comissoes");
        }

        if (active) {
          setSellers(result.sellers);
        }
      } catch (caughtError) {
        if (active) {
          setError(
            caughtError instanceof Error
              ? caughtError.message
              : "Nao foi possivel carregar comissoes",
          );
        }
      } finally {
        if (active) {
          setIsLoading(false);
        }
      }
    }

    loadSellers();

    return () => {
      active = false;
    };
  }, [refreshKey]);

  const stats = useMemo(() => {
    return {
      activeConfigs: sellers.filter((seller) => seller.is_active).length,
      avgCommission:
        sellers.length > 0
          ? sellers.reduce((sum, seller) => sum + seller.commission_percentage, 0) / sellers.length
          : 0,
      configured: sellers.filter((seller) => seller.has_config).length,
      total: sellers.length,
    };
  }, [sellers]);

  const preview = useMemo(() => {
    const saleValue = 100000;
    const percentage = Number(form.commission_percentage) || 0;
    const bonusPercentage = Number(form.bonus_percentage) || 0;
    const bonusThreshold = Number(form.bonus_threshold) || 0;
    const installments = Math.max(Number(form.commission_installments) || 1, 1);
    const finalPercentage =
      bonusThreshold > 0 && saleValue >= bonusThreshold
        ? percentage + bonusPercentage
        : percentage;
    const commission = (saleValue * finalPercentage) / 100;

    return {
      commission,
      finalPercentage,
      installments,
      monthly: commission / installments,
    };
  }, [
    form.bonus_percentage,
    form.bonus_threshold,
    form.commission_installments,
    form.commission_percentage,
  ]);

  function openEditForm(seller: CommissionSellerItem) {
    if (!canWrite) {
      return;
    }

    setEditingSeller(seller);
    setForm(createFormFromSeller(seller));
    setFormError("");
    setMessage("");
  }

  function closeForm() {
    if (isSubmitting) {
      return;
    }

    setEditingSeller(null);
    setFormError("");
  }

  function updateFormField<K extends keyof CommissionFormState>(
    key: K,
    value: CommissionFormState[K],
  ) {
    setForm((current) => ({
      ...current,
      [key]: value,
    }));
  }

  async function submitForm(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setFormError("");
    setMessage("");
    setIsSubmitting(true);

    try {
      const response = await fetch("/api/commission-settings", {
        body: JSON.stringify(buildPayload(form)),
        headers: {
          "Content-Type": "application/json",
        },
        method: "POST",
      });
      const result = (await response.json()) as CommissionResponse;

      if (!response.ok || !result.success) {
        throw new Error(result.message ?? "Nao foi possivel salvar comissao");
      }

      setMessage(result.message ?? "Configuracao atualizada com sucesso");
      setEditingSeller(null);
      setRefreshKey((current) => current + 1);
    } catch (caughtError) {
      setFormError(
        caughtError instanceof Error ? caughtError.message : "Nao foi possivel salvar comissao",
      );
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <div className="mx-auto max-w-7xl space-y-6">
      <section className="flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
          <p className="text-sm font-semibold uppercase tracking-[0.18em] text-[#0f8f80]">
            Financeiro
          </p>
          <h1 className="mt-2 text-3xl font-bold text-slate-950">Configuracoes de comissao</h1>
        </div>

        <button
          className="hype-focus inline-flex h-10 items-center justify-center gap-2 rounded-[8px] border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700"
          onClick={() => setRefreshKey((current) => current + 1)}
          type="button"
        >
          <RefreshCw className="h-4 w-4" aria-hidden />
          Atualizar
        </button>
      </section>

      <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <StatCard label="Vendedores" value={formatInteger(stats.total)} />
        <StatCard label="Configurados" value={formatInteger(stats.configured)} />
        <StatCard label="Ativos" value={formatInteger(stats.activeConfigs)} />
        <StatCard label="Media" value={`${formatDecimal(stats.avgCommission)}%`} />
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

      <section className="grid gap-4 xl:grid-cols-2">
        {isLoading ? (
          <div className="col-span-full flex min-h-48 items-center justify-center rounded-[8px] border border-slate-200 bg-white">
            <Loader2 className="h-6 w-6 animate-spin text-[#0f8f80]" aria-hidden />
          </div>
        ) : null}

        {!isLoading && sellers.length === 0 ? (
          <div className="col-span-full rounded-[8px] border border-slate-200 bg-white p-8 text-center text-slate-500">
            Nenhum vendedor encontrado.
          </div>
        ) : null}

        {sellers.map((seller) => (
          <article
            className="rounded-[8px] border border-slate-200 bg-white shadow-sm"
            key={seller.id}
          >
            <div className="flex items-start justify-between gap-4 border-b border-slate-200 p-5">
              <div>
                <h2 className="text-lg font-bold text-slate-950">{seller.full_name}</h2>
                <p className="text-sm text-slate-500">
                  @{seller.username} - {roleLabels[seller.role]}
                </p>
              </div>
              <span
                className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${
                  seller.is_active
                    ? "bg-emerald-50 text-emerald-700"
                    : "bg-red-50 text-red-700"
                }`}
              >
                {seller.is_active ? "Ativa" : "Inativa"}
              </span>
            </div>

            <div className="grid gap-3 p-5 md:grid-cols-2">
              <InfoItem label="Taxa" value={`${formatDecimal(seller.commission_percentage)}%`} />
              <InfoItem label="Parcelas" value={`${seller.commission_installments}x`} />
              <InfoItem label="Minimo" value={formatCurrency(seller.min_sale_value)} />
              <InfoItem
                label="Maximo"
                value={seller.max_sale_value ? formatCurrency(seller.max_sale_value) : "-"}
              />
              <InfoItem label="Bonus" value={`${formatDecimal(seller.bonus_percentage)}%`} />
              <InfoItem
                label="Limite bonus"
                value={seller.bonus_threshold ? formatCurrency(seller.bonus_threshold) : "-"}
              />
            </div>

            {seller.notes ? (
              <div className="mx-5 mb-5 rounded-[8px] bg-slate-50 p-3 text-sm text-slate-600">
                {seller.notes}
              </div>
            ) : null}

            <div className="flex justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-3">
              <p className="text-xs text-slate-500">
                {seller.has_config
                  ? `Atualizado: ${formatDate(seller.commission_updated_at)}`
                  : "Usando padrao do sistema"}
              </p>
              {canWrite ? (
                <button
                  className="hype-focus inline-flex h-9 items-center gap-2 rounded-[8px] bg-[#3be1c9] px-3 text-sm font-bold text-[#242328]"
                  onClick={() => openEditForm(seller)}
                  type="button"
                >
                  <Pencil className="h-4 w-4" aria-hidden />
                  {seller.has_config ? "Editar" : "Configurar"}
                </button>
              ) : null}
            </div>
          </article>
        ))}
      </section>

      {editingSeller ? (
        <ModalShell onClose={closeForm} title={`Comissao - ${editingSeller.full_name}`}>
          <form className="space-y-5" onSubmit={submitForm}>
            <div className="grid gap-3 md:grid-cols-2">
              <TextField
                label="Taxa de comissao (%)"
                max="100"
                min="0"
                onChange={(value) => updateFormField("commission_percentage", value)}
                required
                step="0.01"
                type="number"
                value={form.commission_percentage}
              />
              <SelectField
                label="Parcelas"
                onChange={(value) => updateFormField("commission_installments", value)}
                options={["1", "2", "3", "4", "5", "6", "10", "12"].map((value) => [
                  value,
                  `${value}x`,
                ])}
                value={form.commission_installments}
              />
              <TextField
                label="Valor minimo"
                min="0"
                onChange={(value) => updateFormField("min_sale_value", value)}
                step="0.01"
                type="number"
                value={form.min_sale_value}
              />
              <TextField
                label="Valor maximo"
                min="0"
                onChange={(value) => updateFormField("max_sale_value", value)}
                step="0.01"
                type="number"
                value={form.max_sale_value}
              />
              <TextField
                label="Bonus adicional (%)"
                max="100"
                min="0"
                onChange={(value) => updateFormField("bonus_percentage", value)}
                step="0.01"
                type="number"
                value={form.bonus_percentage}
              />
              <TextField
                label="Limite para bonus"
                min="0"
                onChange={(value) => updateFormField("bonus_threshold", value)}
                step="0.01"
                type="number"
                value={form.bonus_threshold}
              />
            </div>

            <label className="flex items-center gap-2 text-sm font-semibold text-slate-700">
              <input
                checked={form.is_active}
                className="h-4 w-4 accent-[#3be1c9]"
                onChange={(event) => updateFormField("is_active", event.target.checked)}
                type="checkbox"
              />
              Configuracao ativa
            </label>

            <label className="block">
              <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                Observacoes
              </span>
              <textarea
                className="hype-focus min-h-24 w-full rounded-[8px] border border-slate-200 px-3 py-2 text-sm text-slate-900"
                onChange={(event) => updateFormField("notes", event.target.value)}
                value={form.notes}
              />
            </label>

            <div className="grid gap-3 rounded-[8px] bg-[#3be1c9]/10 p-4 md:grid-cols-3">
              <InfoItem label="Exemplo" value="R$ 100.000,00" />
              <InfoItem label="Comissao" value={formatCurrency(preview.commission)} />
              <InfoItem label="Parcela" value={formatCurrency(preview.monthly)} />
            </div>

            {formError ? (
              <div className="rounded-[8px] border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {formError}
              </div>
            ) : null}

            <div className="flex justify-end gap-2 border-t border-slate-200 pt-4">
              <button
                className="hype-focus inline-flex h-10 items-center justify-center gap-2 rounded-[8px] border border-slate-200 px-4 text-sm font-semibold text-slate-700"
                disabled={isSubmitting}
                onClick={closeForm}
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
      ) : null}
    </div>
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
      <div className="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-[8px] bg-white shadow-2xl">
        <div className="sticky top-0 z-10 flex items-center justify-between gap-4 border-b border-slate-200 bg-white px-5 py-4">
          <h2 className="text-lg font-bold text-slate-950">{title}</h2>
          <button
            aria-label="Fechar"
            className="hype-focus inline-flex h-9 w-9 items-center justify-center rounded-[8px] border border-slate-200 bg-white text-slate-600"
            onClick={onClose}
            title="Fechar"
            type="button"
          >
            <X className="h-4 w-4" aria-hidden />
          </button>
        </div>
        <div className="p-5">{children}</div>
      </div>
    </div>
  );
}

function StatCard({ label, value }: Readonly<{ label: string; value: string }>) {
  return (
    <article className="rounded-[8px] border border-slate-200 bg-white p-5 shadow-sm">
      <div className="flex items-start justify-between gap-4">
        <div>
          <p className="text-sm font-medium text-slate-500">{label}</p>
          <p className="mt-2 text-2xl font-bold text-slate-950">{value}</p>
        </div>
        <span className="flex h-10 w-10 items-center justify-center rounded-[8px] bg-[#3be1c9]/15 text-[#0f8f80]">
          <Settings className="h-5 w-5" aria-hidden />
        </span>
      </div>
    </article>
  );
}

function InfoItem({ label, value }: Readonly<{ label: string; value: string }>) {
  return (
    <div>
      <p className="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
        {label}
      </p>
      <p className="mt-1 break-words text-sm font-semibold text-slate-950">{value}</p>
    </div>
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

function TextField({
  label,
  max,
  min,
  onChange,
  required = false,
  step,
  type = "text",
  value,
}: Readonly<{
  label: string;
  max?: string;
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
        className="hype-focus h-10 w-full rounded-[8px] border border-slate-200 bg-white px-3 text-sm text-slate-900"
        max={max}
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

function createEmptyForm(): CommissionFormState {
  return {
    bonus_percentage: "0",
    bonus_threshold: "",
    commission_installments: "5",
    commission_percentage: "1.5",
    is_active: true,
    max_sale_value: "",
    min_sale_value: "0",
    notes: "",
    seller_id: "",
  };
}

function createFormFromSeller(seller: CommissionSellerItem): CommissionFormState {
  return {
    bonus_percentage: String(seller.bonus_percentage),
    bonus_threshold: seller.bonus_threshold ? String(seller.bonus_threshold) : "",
    commission_installments: String(seller.commission_installments),
    commission_percentage: String(seller.commission_percentage),
    is_active: seller.is_active,
    max_sale_value: seller.max_sale_value ? String(seller.max_sale_value) : "",
    min_sale_value: String(seller.min_sale_value),
    notes: seller.notes ?? "",
    seller_id: String(seller.id),
  };
}

function buildPayload(form: CommissionFormState): CommissionSettingsInput {
  return {
    bonus_percentage: stringToNumberOrNull(form.bonus_percentage),
    bonus_threshold: stringToNumberOrNull(form.bonus_threshold),
    commission_installments: stringToNumberOrNull(form.commission_installments),
    commission_percentage: stringToNumberOrNull(form.commission_percentage),
    is_active: form.is_active,
    max_sale_value: stringToNumberOrNull(form.max_sale_value),
    min_sale_value: stringToNumberOrNull(form.min_sale_value),
    notes: emptyToNull(form.notes),
    seller_id: stringToNumberOrNull(form.seller_id),
  };
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
