"use client";

import {
  ExternalLink,
  Image as ImageIcon,
  Loader2,
  Pencil,
  Plus,
  RefreshCw,
  Save,
  Trash2,
  Upload,
} from "lucide-react";
import { useEffect, useMemo, useState, type FormEvent } from "react";

import { getSupabaseBrowserClient } from "@/lib/supabase-browser";
import type { FAQItem, FAQMutationInput, SiteConfigItem } from "@/types/site-config";

interface SiteConfigResponse {
  configs?: SiteConfigItem[];
  message?: string;
  sections?: string[];
  success: boolean;
}

interface FAQResponse {
  faq?: FAQItem;
  faqs?: FAQItem[];
  message?: string;
  success: boolean;
}

interface SiteConfigUploadResponse {
  config?: SiteConfigItem;
  message?: string;
  success: boolean;
}

interface SiteConfigUploadUrlResponse {
  message?: string;
  success: boolean;
  upload?: {
    bucket: string;
    config_key: string;
    content_type: string;
    file_name: string;
    max_size: number;
    path: string;
    public_url: string;
    signed_url: string;
    token: string;
  };
}

interface FAQFormState {
  answer: string;
  display_order: string;
  is_active: boolean;
  question: string;
}

const sectionLabels: Record<string, string> = {
  about: "Sobre",
  career: "Carreira",
  cars: "Veiculos",
  clients: "Clientes",
  company: "Empresa",
  faq: "FAQ",
  hero: "Hero",
  location: "Localizacao",
  meta: "Meta",
};

const blankFaqForm: FAQFormState = {
  answer: "",
  display_order: "",
  is_active: true,
  question: "",
};

export function SiteConfigClient() {
  const [configs, setConfigs] = useState<SiteConfigItem[]>([]);
  const [sections, setSections] = useState<string[]>([]);
  const [activeSection, setActiveSection] = useState("hero");
  const [values, setValues] = useState<Record<string, string>>({});
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [faqs, setFaqs] = useState<FAQItem[]>([]);
  const [faqForm, setFaqForm] = useState<FAQFormState>(blankFaqForm);
  const [editingFaqId, setEditingFaqId] = useState<number | null>(null);
  const [faqMessage, setFaqMessage] = useState("");
  const [faqError, setFaqError] = useState("");
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [isFaqLoading, setIsFaqLoading] = useState(false);
  const [isFaqSaving, setIsFaqSaving] = useState(false);
  const [uploadingKey, setUploadingKey] = useState("");
  const [refreshKey, setRefreshKey] = useState(0);

  useEffect(() => {
    let active = true;

    async function loadConfig() {
      setIsLoading(true);
      setError("");

      try {
        const response = await fetch(`/api/site-config?section=${activeSection}`, {
          cache: "no-store",
        });
        const result = (await response.json()) as SiteConfigResponse;

        if (!response.ok || !result.success || !result.configs || !result.sections) {
          throw new Error(result.message ?? "Nao foi possivel carregar configuracoes");
        }

        if (active) {
          setConfigs(result.configs);
          setSections(result.sections);
          setValues(
            Object.fromEntries(
              result.configs.map((config) => [config.config_key, config.config_value]),
            ),
          );
        }
      } catch (caughtError) {
        if (active) {
          setError(
            caughtError instanceof Error
              ? caughtError.message
              : "Nao foi possivel carregar configuracoes",
          );
        }
      } finally {
        if (active) {
          setIsLoading(false);
        }
      }
    }

    loadConfig();

    return () => {
      active = false;
    };
  }, [activeSection, refreshKey]);

  const sortedSections = useMemo(() => {
    const baseSections = sections.length ? sections : ["hero"];
    return Array.from(new Set([...baseSections, "faq"]));
  }, [sections]);

  useEffect(() => {
    if (activeSection !== "faq") {
      return;
    }

    let active = true;

    async function loadFaqs() {
      setIsFaqLoading(true);
      setFaqError("");

      try {
        const response = await fetch("/api/faqs", {
          cache: "no-store",
        });
        const result = (await response.json()) as FAQResponse;

        if (!response.ok || !result.success || !result.faqs) {
          throw new Error(result.message ?? "Nao foi possivel carregar FAQs");
        }

        if (active) {
          setFaqs(result.faqs);
        }
      } catch (caughtError) {
        if (active) {
          setFaqError(
            caughtError instanceof Error ? caughtError.message : "Nao foi possivel carregar FAQs",
          );
        }
      } finally {
        if (active) {
          setIsFaqLoading(false);
        }
      }
    }

    loadFaqs();

    return () => {
      active = false;
    };
  }, [activeSection, refreshKey]);

  function updateValue(key: string, value: string) {
    setValues((current) => ({
      ...current,
      [key]: value,
    }));
  }

  async function submitSection(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setMessage("");
    setError("");
    setIsSaving(true);

    try {
      const response = await fetch("/api/site-config", {
        body: JSON.stringify({
          section: activeSection,
          values,
        }),
        headers: {
          "Content-Type": "application/json",
        },
        method: "PUT",
      });
      const result = (await response.json()) as SiteConfigResponse;

      if (!response.ok || !result.success || !result.configs) {
        throw new Error(result.message ?? "Nao foi possivel salvar configuracoes");
      }

      setConfigs(result.configs);
      setValues(
        Object.fromEntries(
          result.configs.map((config) => [config.config_key, config.config_value]),
        ),
      );
      setMessage(result.message ?? "Configuracoes salvas com sucesso");
    } catch (caughtError) {
      setError(
        caughtError instanceof Error
          ? caughtError.message
          : "Nao foi possivel salvar configuracoes",
      );
    } finally {
      setIsSaving(false);
    }
  }

  async function uploadConfigFile(config: SiteConfigItem, file: File) {
    setMessage("");
    setError("");
    setUploadingKey(config.config_key);

    try {
      const currentValue = values[config.config_key] ?? config.config_value;
      const uploadUrlResponse = await fetch("/api/site-config/upload-url", {
        body: JSON.stringify({
          config_key: config.config_key,
          current_value: currentValue,
          file_name: file.name,
          file_size: file.size,
          file_type: file.type,
        }),
        headers: {
          "Content-Type": "application/json",
        },
        method: "POST",
      });
      const uploadUrlResult = (await uploadUrlResponse.json()) as SiteConfigUploadUrlResponse;

      if (!uploadUrlResponse.ok || !uploadUrlResult.success || !uploadUrlResult.upload) {
        throw new Error(uploadUrlResult.message ?? "Nao foi possivel preparar upload");
      }

      const upload = uploadUrlResult.upload;
      const supabase = getSupabaseBrowserClient();
      const { error: uploadError } = await supabase.storage
        .from(upload.bucket)
        .uploadToSignedUrl(upload.path, upload.token, file, {
          contentType: file.type,
          upsert: false,
        });

      if (uploadError) {
        throw new Error(`Nao foi possivel enviar arquivo: ${uploadError.message}`);
      }

      const completeResponse = await fetch("/api/site-config/upload-complete", {
        body: JSON.stringify({
          config_key: config.config_key,
          current_value: currentValue,
          file_name: file.name,
          file_type: file.type,
          path: upload.path,
        }),
        headers: {
          "Content-Type": "application/json",
        },
        method: "POST",
      });
      const result = (await completeResponse.json()) as SiteConfigUploadResponse;

      if (!completeResponse.ok || !result.success || !result.config) {
        throw new Error(result.message ?? "Nao foi possivel finalizar upload");
      }

      const uploadedConfig = result.config;
      setValues((current) => ({
        ...current,
        [uploadedConfig.config_key]: uploadedConfig.config_value,
      }));
      setConfigs((current) =>
        current.map((item) =>
          item.config_key === uploadedConfig.config_key ? uploadedConfig : item,
        ),
      );
      setMessage(result.message ?? "Arquivo enviado com sucesso");
    } catch (caughtError) {
      setError(
        caughtError instanceof Error ? caughtError.message : "Nao foi possivel enviar arquivo",
      );
    } finally {
      setUploadingKey("");
    }
  }

  function updateFaqForm<K extends keyof FAQFormState>(key: K, value: FAQFormState[K]) {
    setFaqForm((current) => ({
      ...current,
      [key]: value,
    }));
  }

  function startCreateFaq() {
    setEditingFaqId(null);
    setFaqForm(blankFaqForm);
    setFaqMessage("");
    setFaqError("");
  }

  function startEditFaq(faq: FAQItem) {
    setEditingFaqId(faq.id);
    setFaqForm({
      answer: faq.answer,
      display_order: String(faq.display_order),
      is_active: faq.is_active,
      question: faq.question,
    });
    setFaqMessage("");
    setFaqError("");
  }

  async function submitFaq(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setFaqMessage("");
    setFaqError("");
    setIsFaqSaving(true);

    try {
      const payload: FAQMutationInput = {
        answer: faqForm.answer,
        display_order: faqForm.display_order ? Number(faqForm.display_order) : null,
        is_active: faqForm.is_active,
        question: faqForm.question,
      };
      const endpoint = editingFaqId ? `/api/faqs/${editingFaqId}` : "/api/faqs";
      const response = await fetch(endpoint, {
        body: JSON.stringify(payload),
        headers: {
          "Content-Type": "application/json",
        },
        method: editingFaqId ? "PUT" : "POST",
      });
      const result = (await response.json()) as FAQResponse;

      if (!response.ok || !result.success) {
        throw new Error(result.message ?? "Nao foi possivel salvar FAQ");
      }

      setFaqMessage(result.message ?? "FAQ salva com sucesso");
      setEditingFaqId(null);
      setFaqForm(blankFaqForm);
      setRefreshKey((current) => current + 1);
    } catch (caughtError) {
      setFaqError(caughtError instanceof Error ? caughtError.message : "Nao foi possivel salvar FAQ");
    } finally {
      setIsFaqSaving(false);
    }
  }

  async function removeFaq(faq: FAQItem) {
    setFaqMessage("");
    setFaqError("");
    setIsFaqSaving(true);

    try {
      const response = await fetch(`/api/faqs/${faq.id}`, {
        method: "DELETE",
      });
      const result = (await response.json()) as FAQResponse;

      if (!response.ok || !result.success) {
        throw new Error(result.message ?? "Nao foi possivel remover FAQ");
      }

      setFaqMessage(result.message ?? "FAQ removida com sucesso");
      setRefreshKey((current) => current + 1);
    } catch (caughtError) {
      setFaqError(
        caughtError instanceof Error ? caughtError.message : "Nao foi possivel remover FAQ",
      );
    } finally {
      setIsFaqSaving(false);
    }
  }

  return (
    <div className="mx-auto max-w-7xl space-y-6">
      <section className="flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
          <p className="text-sm font-semibold uppercase tracking-[0.18em] text-[#0f8f80]">
            Site
          </p>
          <h1 className="mt-2 text-3xl font-bold text-slate-950">Configuracoes do site</h1>
        </div>

        <div className="flex gap-2">
          <a
            className="hype-focus inline-flex h-10 items-center justify-center gap-2 rounded-[8px] border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700"
            href="/"
            target="_blank"
          >
            <ExternalLink className="h-4 w-4" aria-hidden />
            Ver site
          </a>
          <button
            className="hype-focus inline-flex h-10 items-center justify-center gap-2 rounded-[8px] border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700"
            onClick={() => setRefreshKey((current) => current + 1)}
            type="button"
          >
            <RefreshCw className="h-4 w-4" aria-hidden />
            Atualizar
          </button>
        </div>
      </section>

      <section className="flex gap-2 overflow-x-auto border-b border-slate-200 pb-2">
        {sortedSections.map((section) => (
          <button
            className={`hype-focus h-9 shrink-0 rounded-[8px] px-3 text-sm font-semibold ${
              activeSection === section
                ? "bg-[#3be1c9] text-[#242328]"
                : "bg-white text-slate-700"
            }`}
            key={section}
            onClick={() => {
              setActiveSection(section);
              setMessage("");
              setError("");
            }}
            type="button"
          >
            {sectionLabels[section] ?? section}
          </button>
        ))}
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

      <section className="rounded-[8px] border border-slate-200 bg-white shadow-sm">
        <div className="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
          <h2 className="text-base font-bold text-slate-950">
            {sectionLabels[activeSection] ?? activeSection}
          </h2>
          {isLoading ? <Loader2 className="h-5 w-5 animate-spin text-[#0f8f80]" aria-hidden /> : null}
        </div>

        <form className="space-y-5 p-5" onSubmit={submitSection}>
          {!isLoading && configs.length === 0 ? (
            <div className="rounded-[8px] bg-slate-50 p-6 text-center text-sm text-slate-500">
              Nenhuma configuracao encontrada nesta secao.
            </div>
          ) : null}

          {configs.map((config) => (
            <ConfigField
              config={config}
              isUploading={uploadingKey === config.config_key}
              key={config.config_key}
              onChange={(value) => updateValue(config.config_key, value)}
              onUpload={(file) => uploadConfigFile(config, file)}
              value={values[config.config_key] ?? ""}
            />
          ))}

          <div className="flex justify-end border-t border-slate-200 pt-4">
            <button
              className="hype-focus inline-flex h-10 items-center justify-center gap-2 rounded-[8px] bg-[#3be1c9] px-4 text-sm font-bold text-[#242328] disabled:cursor-not-allowed disabled:opacity-60"
              disabled={isSaving || isLoading}
              type="submit"
            >
              {isSaving ? (
                <Loader2 className="h-4 w-4 animate-spin" aria-hidden />
              ) : (
                <Save className="h-4 w-4" aria-hidden />
              )}
              Salvar secao
            </button>
          </div>
        </form>
      </section>

      {activeSection === "faq" ? (
        <FAQManager
          error={faqError}
          faqs={faqs}
          form={faqForm}
          isLoading={isFaqLoading}
          isSaving={isFaqSaving}
          message={faqMessage}
          editingFaqId={editingFaqId}
          onDelete={removeFaq}
          onEdit={startEditFaq}
          onNew={startCreateFaq}
          onSubmit={submitFaq}
          onUpdate={updateFaqForm}
        />
      ) : null}
    </div>
  );
}

function FAQManager({
  editingFaqId,
  error,
  faqs,
  form,
  isLoading,
  isSaving,
  message,
  onDelete,
  onEdit,
  onNew,
  onSubmit,
  onUpdate,
}: Readonly<{
  editingFaqId: number | null;
  error: string;
  faqs: FAQItem[];
  form: FAQFormState;
  isLoading: boolean;
  isSaving: boolean;
  message: string;
  onDelete: (faq: FAQItem) => void;
  onEdit: (faq: FAQItem) => void;
  onNew: () => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
  onUpdate: <K extends keyof FAQFormState>(key: K, value: FAQFormState[K]) => void;
}>) {
  return (
    <section className="rounded-[8px] border border-slate-200 bg-white shadow-sm">
      <div className="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
        <h2 className="text-base font-bold text-slate-950">FAQs do site</h2>
        <button
          className="hype-focus inline-flex h-9 items-center gap-2 rounded-[8px] border border-slate-200 px-3 text-sm font-semibold text-slate-700"
          onClick={onNew}
          type="button"
        >
          <Plus className="h-4 w-4" aria-hidden />
          Nova
        </button>
      </div>

      <div className="grid gap-5 p-5 lg:grid-cols-[0.9fr_1.1fr]">
        <form className="space-y-4 rounded-[8px] border border-slate-200 p-4" onSubmit={onSubmit}>
          <h3 className="text-sm font-bold text-slate-950">
            {editingFaqId ? "Editar FAQ" : "Nova FAQ"}
          </h3>

          <TextField
            label="Pergunta"
            onChange={(value) => onUpdate("question", value)}
            required
            value={form.question}
          />

          <label className="block">
            <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
              Resposta
            </span>
            <textarea
              className="hype-focus min-h-32 w-full rounded-[8px] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900"
              onChange={(event) => onUpdate("answer", event.target.value)}
              required
              value={form.answer}
            />
          </label>

          <div className="grid gap-3 md:grid-cols-2">
            <TextField
              label="Ordem"
              onChange={(value) => onUpdate("display_order", value)}
              type="number"
              value={form.display_order}
            />
            <label className="block">
              <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                Status
              </span>
              <select
                className="hype-focus h-10 w-full rounded-[8px] border border-slate-200 bg-white px-3 text-sm text-slate-900"
                onChange={(event) => onUpdate("is_active", event.target.value === "active")}
                value={form.is_active ? "active" : "inactive"}
              >
                <option value="active">Ativa</option>
                <option value="inactive">Inativa</option>
              </select>
            </label>
          </div>

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

          <button
            className="hype-focus inline-flex h-10 items-center justify-center gap-2 rounded-[8px] bg-[#3be1c9] px-4 text-sm font-bold text-[#242328] disabled:cursor-not-allowed disabled:opacity-60"
            disabled={isSaving}
            type="submit"
          >
            {isSaving ? (
              <Loader2 className="h-4 w-4 animate-spin" aria-hidden />
            ) : (
              <Save className="h-4 w-4" aria-hidden />
            )}
            Salvar FAQ
          </button>
        </form>

        <div className="overflow-x-auto rounded-[8px] border border-slate-200">
          <table className="w-full min-w-[640px] text-left text-sm">
            <thead className="bg-slate-50 text-xs uppercase tracking-[0.12em] text-slate-500">
              <tr>
                <th className="px-4 py-3 font-semibold">Ordem</th>
                <th className="px-4 py-3 font-semibold">Pergunta</th>
                <th className="px-4 py-3 font-semibold">Status</th>
                <th className="px-4 py-3 text-right font-semibold">Acoes</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {faqs.map((faq) => (
                <tr className="hover:bg-slate-50" key={faq.id}>
                  <td className="px-4 py-3 text-slate-700">{faq.display_order}</td>
                  <td className="px-4 py-3">
                    <p className="font-semibold text-slate-950">{faq.question}</p>
                    <p className="line-clamp-2 text-xs text-slate-500">{faq.answer}</p>
                  </td>
                  <td className="px-4 py-3 text-slate-700">
                    {faq.is_active ? "Ativa" : "Inativa"}
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex justify-end gap-2">
                      <button
                        className="hype-focus inline-flex h-9 w-9 items-center justify-center rounded-[8px] border border-slate-200 text-slate-600"
                        onClick={() => onEdit(faq)}
                        title="Editar"
                        type="button"
                      >
                        <Pencil className="h-4 w-4" aria-hidden />
                      </button>
                      <button
                        className="hype-focus inline-flex h-9 w-9 items-center justify-center rounded-[8px] border border-slate-200 text-red-600"
                        disabled={isSaving}
                        onClick={() => onDelete(faq)}
                        title="Remover"
                        type="button"
                      >
                        <Trash2 className="h-4 w-4" aria-hidden />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}

              {!isLoading && faqs.length === 0 ? (
                <tr>
                  <td className="px-4 py-8 text-center text-slate-500" colSpan={4}>
                    Nenhuma FAQ cadastrada.
                  </td>
                </tr>
              ) : null}
            </tbody>
          </table>
        </div>
      </div>
    </section>
  );
}

function ConfigField({
  config,
  isUploading,
  onChange,
  onUpload,
  value,
}: Readonly<{
  config: SiteConfigItem;
  isUploading: boolean;
  onChange: (value: string) => void;
  onUpload: (file: File) => void;
  value: string;
}>) {
  return (
    <label className="block">
      <span className="mb-2 block text-sm font-bold text-slate-950">
        {config.display_name}
      </span>
      {config.description ? (
        <span className="mb-2 block text-sm leading-6 text-slate-500">
          {config.description}
        </span>
      ) : null}

      {config.config_type === "textarea" ? (
        <textarea
          className="hype-focus min-h-28 w-full rounded-[8px] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900"
          onChange={(event) => onChange(event.target.value)}
          value={value}
        />
      ) : config.config_type === "image" ? (
        <div className="space-y-3">
          <input
            className="hype-focus h-10 w-full rounded-[8px] border border-slate-200 bg-white px-3 text-sm text-slate-900"
            onChange={(event) => onChange(event.target.value)}
            type="text"
            value={value}
          />
          <label className="hype-focus flex min-h-20 cursor-pointer items-center justify-center rounded-[8px] border border-dashed border-slate-300 bg-slate-50 px-4 text-center text-sm font-semibold text-slate-600">
            <input
              accept={config.config_key.includes("video") ? "video/*" : "image/*"}
              className="sr-only"
              disabled={isUploading}
              onChange={(event) => {
                const file = event.target.files?.[0];
                if (file) {
                  onUpload(file);
                  event.target.value = "";
                }
              }}
              type="file"
            />
            {isUploading ? (
              <span className="inline-flex items-center gap-2">
                <Loader2 className="h-4 w-4 animate-spin" aria-hidden />
                Enviando arquivo
              </span>
            ) : (
              <span className="inline-flex items-center gap-2">
                <Upload className="h-4 w-4" aria-hidden />
                Selecionar arquivo
              </span>
            )}
          </label>
        </div>
      ) : (
        <input
          className="hype-focus h-10 w-full rounded-[8px] border border-slate-200 bg-white px-3 text-sm text-slate-900"
          onChange={(event) => onChange(event.target.value)}
          type={config.config_type === "number" ? "number" : "text"}
          value={value}
        />
      )}

      {config.config_type === "image" && value ? (
        <div className="mt-3 flex items-center gap-3 rounded-[8px] bg-slate-50 p-3">
          <ImageIcon className="h-4 w-4 shrink-0 text-slate-400" aria-hidden />
          <span className="break-all text-xs text-slate-500">{value}</span>
        </div>
      ) : null}
    </label>
  );
}

function TextField({
  label,
  onChange,
  required = false,
  type = "text",
  value,
}: Readonly<{
  label: string;
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
        onChange={(event) => onChange(event.target.value)}
        required={required}
        type={type}
        value={value}
      />
    </label>
  );
}
