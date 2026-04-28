"use client";

import { Loader2, Plus, Save, Settings } from "lucide-react";
import { useEffect, useState, type FormEvent } from "react";

import type {
  SystemSettingItem,
  SystemSettingMutationInput,
} from "@/types/system-settings";

interface SystemSettingsResponse {
  message?: string;
  setting?: SystemSettingItem;
  settings?: SystemSettingItem[];
  success: boolean;
}

interface SettingFormState {
  description: string;
  setting_key: string;
  setting_value: string;
}

const blankForm: SettingFormState = {
  description: "",
  setting_key: "",
  setting_value: "",
};

export function SystemSettingsClient() {
  const [settings, setSettings] = useState<SystemSettingItem[]>([]);
  const [form, setForm] = useState<SettingFormState>(blankForm);
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [refreshKey, setRefreshKey] = useState(0);

  useEffect(() => {
    let active = true;

    async function loadSettings() {
      setIsLoading(true);
      setError("");

      try {
        const response = await fetch("/api/system-settings", {
          cache: "no-store",
        });
        const result = (await response.json()) as SystemSettingsResponse;

        if (!response.ok || !result.success || !result.settings) {
          throw new Error(result.message ?? "Nao foi possivel carregar configuracoes");
        }

        if (active) {
          setSettings(result.settings);
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

    loadSettings();

    return () => {
      active = false;
    };
  }, [refreshKey]);

  function editSetting(setting: SystemSettingItem) {
    setForm({
      description: setting.description ?? "",
      setting_key: setting.setting_key,
      setting_value: setting.setting_value ?? "",
    });
    setMessage("");
    setError("");
  }

  function updateForm<K extends keyof SettingFormState>(key: K, value: SettingFormState[K]) {
    setForm((current) => ({
      ...current,
      [key]: value,
    }));
  }

  async function submitSetting(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setMessage("");
    setError("");
    setIsSaving(true);

    try {
      const payload: SystemSettingMutationInput = {
        description: form.description,
        setting_key: form.setting_key,
        setting_value: form.setting_value,
      };
      const response = await fetch("/api/system-settings", {
        body: JSON.stringify(payload),
        headers: {
          "Content-Type": "application/json",
        },
        method: "PUT",
      });
      const result = (await response.json()) as SystemSettingsResponse;

      if (!response.ok || !result.success) {
        throw new Error(result.message ?? "Nao foi possivel salvar configuracao");
      }

      setMessage(result.message ?? "Configuracao salva com sucesso");
      setForm(blankForm);
      setRefreshKey((current) => current + 1);
    } catch (caughtError) {
      setError(
        caughtError instanceof Error
          ? caughtError.message
          : "Nao foi possivel salvar configuracao",
      );
    } finally {
      setIsSaving(false);
    }
  }

  return (
    <div className="mx-auto max-w-7xl min-w-0 space-y-6">
      <section className="flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
          <p className="text-sm font-semibold uppercase tracking-[0.18em] text-[#0f8f80]">
            Sistema
          </p>
          <h1 className="mt-2 text-3xl font-bold text-slate-950">Configuracoes globais</h1>
        </div>
        <button
          className="hype-focus inline-flex h-10 items-center justify-center gap-2 rounded-[8px] border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700"
          onClick={() => {
            setForm(blankForm);
            setMessage("");
            setError("");
          }}
          type="button"
        >
          <Plus className="h-4 w-4" aria-hidden />
          Nova configuracao
        </button>
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

      <div className="grid min-w-0 gap-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
        <section className="min-w-0 rounded-[8px] border border-slate-200 bg-white shadow-sm">
          <div className="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
            <h2 className="text-base font-bold text-slate-950">Editar configuracao</h2>
          </div>
          <form className="space-y-4 p-5" onSubmit={submitSetting}>
            <TextField
              label="Chave"
              onChange={(value) => updateForm("setting_key", value)}
              required
              value={form.setting_key}
            />
            <TextField
              label="Valor"
              onChange={(value) => updateForm("setting_value", value)}
              required
              value={form.setting_value}
            />
            <label className="block">
              <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                Descricao
              </span>
              <textarea
                className="hype-focus min-h-24 w-full rounded-[8px] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900"
                onChange={(event) => updateForm("description", event.target.value)}
                value={form.description}
              />
            </label>
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
              Salvar
            </button>
          </form>
        </section>

        <section className="min-w-0 rounded-[8px] border border-slate-200 bg-white shadow-sm">
          <div className="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
            <h2 className="text-base font-bold text-slate-950">Configuracoes cadastradas</h2>
            {isLoading ? <Loader2 className="h-5 w-5 animate-spin text-[#0f8f80]" aria-hidden /> : null}
          </div>
          <div className="overflow-x-auto">
            <table className="w-full min-w-[720px] text-left text-sm">
              <thead className="bg-slate-50 text-xs uppercase tracking-[0.12em] text-slate-500">
                <tr>
                  <th className="px-4 py-3 font-semibold">Chave</th>
                  <th className="px-4 py-3 font-semibold">Valor</th>
                  <th className="px-4 py-3 font-semibold">Atualizado por</th>
                  <th className="px-4 py-3 text-right font-semibold">Acao</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {settings.map((setting) => (
                  <tr className="hover:bg-slate-50" key={setting.id}>
                    <td className="px-4 py-3">
                      <p className="font-semibold text-slate-950">{setting.setting_key}</p>
                      <p className="text-xs text-slate-500">{setting.description ?? "Sem descricao"}</p>
                    </td>
                    <td className="px-4 py-3 text-slate-700">{setting.setting_value ?? "-"}</td>
                    <td className="px-4 py-3 text-slate-700">
                      {setting.updated_by_name ?? "Sistema"}
                    </td>
                    <td className="px-4 py-3 text-right">
                      <button
                        className="hype-focus inline-flex h-9 items-center gap-2 rounded-[8px] border border-slate-200 px-3 text-sm font-semibold text-slate-700"
                        onClick={() => editSetting(setting)}
                        type="button"
                      >
                        <Settings className="h-4 w-4" aria-hidden />
                        Editar
                      </button>
                    </td>
                  </tr>
                ))}

                {!isLoading && settings.length === 0 ? (
                  <tr>
                    <td className="px-4 py-8 text-center text-slate-500" colSpan={4}>
                      Nenhuma configuracao cadastrada.
                    </td>
                  </tr>
                ) : null}
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </div>
  );
}

function TextField({
  label,
  onChange,
  required = false,
  value,
}: Readonly<{
  label: string;
  onChange: (value: string) => void;
  required?: boolean;
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
        value={value}
      />
    </label>
  );
}
