"use client";

import {
  Eye,
  Loader2,
  Pencil,
  Plus,
  RefreshCw,
  Save,
  Search,
  UserRound,
  UserRoundX,
  X,
} from "lucide-react";
import { useEffect, useMemo, useState, type FormEvent, type ReactNode } from "react";

import { formatBrazilDateTime } from "@/lib/date-format";
import { UserRole, UserStatus } from "@/types";
import type { UserListItem, UserMutationInput } from "@/types/users";

interface UsersClientProps {
  currentUserId: number;
  userRole: UserRole;
}

interface UsersResponse {
  message?: string;
  success: boolean;
  users?: UserListItem[];
}

interface UserResponse {
  message?: string;
  success: boolean;
  user?: UserListItem;
}

interface UserFormState {
  email: string;
  full_name: string;
  password: string;
  role: UserRole;
  status: UserStatus;
  username: string;
}

const roleLabels: Record<UserRole, string> = {
  [UserRole.Admin]: "Administrador",
  [UserRole.Manager]: "Gerente",
  [UserRole.Seller]: "Vendedor",
  [UserRole.Viewer]: "Visualizador",
};

const statusLabels: Record<UserStatus, string> = {
  [UserStatus.Active]: "Ativo",
  [UserStatus.Inactive]: "Inativo",
};

export function UsersClient({
  currentUserId,
  userRole,
}: Readonly<UsersClientProps>) {
  const [users, setUsers] = useState<UserListItem[]>([]);
  const [roleFilter, setRoleFilter] = useState("");
  const [statusFilter, setStatusFilter] = useState<UserStatus | "">(
    UserStatus.Active,
  );
  const [search, setSearch] = useState("");
  const [error, setError] = useState("");
  const [formError, setFormError] = useState("");
  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [refreshKey, setRefreshKey] = useState(0);
  const [detailsUser, setDetailsUser] = useState<UserListItem | null>(null);
  const [editingUser, setEditingUser] = useState<UserListItem | null>(null);
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [form, setForm] = useState<UserFormState>(() => createEmptyForm());

  const canWriteUsers = userRole === UserRole.Admin;

  useEffect(() => {
    const controller = new AbortController();
    const timeout = window.setTimeout(() => {
      loadUsers(controller.signal);
    }, 250);

    async function loadUsers(signal: AbortSignal) {
      setIsLoading(true);
      setError("");

      try {
        const params = new URLSearchParams();

        if (roleFilter) params.set("role", roleFilter);
        if (statusFilter) params.set("status", statusFilter);
        if (search.trim()) params.set("search", search.trim());

        const response = await fetch(`/api/users?${params.toString()}`, {
          cache: "no-store",
          signal,
        });
        const result = (await response.json()) as UsersResponse;

        if (!response.ok || !result.success || !result.users) {
          throw new Error(result.message ?? "Nao foi possivel carregar usuarios");
        }

        setUsers(result.users);
      } catch (caughtError) {
        if (signal.aborted) {
          return;
        }

        setError(
          caughtError instanceof Error
            ? caughtError.message
            : "Nao foi possivel carregar usuarios",
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
  }, [refreshKey, roleFilter, search, statusFilter]);

  const stats = useMemo(() => {
    return {
      active: users.filter((user) => user.status === UserStatus.Active).length,
      admin: users.filter((user) => user.role === UserRole.Admin).length,
      sellers: users.filter((user) => user.role === UserRole.Seller).length,
      total: users.length,
    };
  }, [users]);

  function updateFilter(setter: (value: string) => void, value: string) {
    setter(value);
  }

  function openCreateForm() {
    setEditingUser(null);
    setForm(createEmptyForm());
    setFormError("");
    setIsFormOpen(true);
  }

  function openEditForm(user: UserListItem) {
    setEditingUser(user);
    setForm(createFormFromUser(user));
    setFormError("");
    setIsFormOpen(true);
  }

  function openDetails(user: UserListItem) {
    setDetailsUser(user);
  }

  function closeForm() {
    if (isSubmitting) {
      return;
    }

    setIsFormOpen(false);
    setEditingUser(null);
    setFormError("");
  }

  function updateFormField<K extends keyof UserFormState>(
    key: K,
    value: UserFormState[K],
  ) {
    setForm((current) => ({
      ...current,
      [key]: value,
    }));
  }

  async function submitUserForm(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setFormError("");
    setIsSubmitting(true);

    try {
      const payload = buildUserPayload(form, Boolean(editingUser));
      const endpoint = editingUser ? `/api/users/${editingUser.id}` : "/api/users";
      const method = editingUser ? "PUT" : "POST";
      const response = await fetch(endpoint, {
        body: JSON.stringify(payload),
        headers: {
          "Content-Type": "application/json",
        },
        method,
      });
      const result = (await response.json()) as UserResponse;

      if (!response.ok || !result.success) {
        throw new Error(result.message ?? "Nao foi possivel salvar o usuario");
      }

      setIsFormOpen(false);
      setEditingUser(null);
      setRefreshKey((current) => current + 1);
    } catch (caughtError) {
      setFormError(
        caughtError instanceof Error
          ? caughtError.message
          : "Nao foi possivel salvar o usuario",
      );
    } finally {
      setIsSubmitting(false);
    }
  }

  async function deactivateSelectedUser(user: UserListItem) {
    if (!canWriteUsers || user.id === currentUserId) {
      return;
    }

    const confirmed = window.confirm(`Inativar o usuario "${user.username}"?`);

    if (!confirmed) {
      return;
    }

    setError("");

    try {
      const response = await fetch(`/api/users/${user.id}`, {
        method: "DELETE",
      });
      const result = (await response.json()) as UserResponse;

      if (!response.ok || !result.success) {
        throw new Error(result.message ?? "Nao foi possivel inativar o usuario");
      }

      setRefreshKey((current) => current + 1);
    } catch (caughtError) {
      setError(
        caughtError instanceof Error
          ? caughtError.message
          : "Nao foi possivel inativar o usuario",
      );
    }
  }

  return (
    <div className="mx-auto max-w-7xl space-y-6">
      <section className="flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
          <p className="text-sm font-semibold uppercase tracking-[0.18em] text-[#0f8f80]">
            Administracao
          </p>
          <h1 className="mt-2 text-3xl font-bold text-slate-950">Usuarios</h1>
        </div>

        <div className="flex gap-2">
          <button
            className="hype-focus inline-flex h-10 items-center justify-center gap-2 rounded-[8px] border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700"
            onClick={() => setRefreshKey((current) => current + 1)}
            type="button"
          >
            <RefreshCw className="h-4 w-4" aria-hidden />
            Atualizar
          </button>
          {canWriteUsers ? (
            <button
              className="hype-focus inline-flex h-10 items-center justify-center gap-2 rounded-[8px] bg-[#3be1c9] px-4 text-sm font-bold text-[#242328] transition hover:bg-[#2bd4bd]"
              onClick={openCreateForm}
              type="button"
            >
              <Plus className="h-4 w-4" aria-hidden />
              Novo usuario
            </button>
          ) : null}
        </div>
      </section>

      <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <StatCard label="Listados" value={stats.total} />
        <StatCard label="Ativos" value={stats.active} />
        <StatCard label="Vendedores" value={stats.sellers} />
        <StatCard label="Admins" value={stats.admin} />
      </section>

      <section className="rounded-[8px] border border-slate-200 bg-white p-4 shadow-sm">
        <div className="grid gap-3 md:grid-cols-[1.4fr_1fr_1fr]">
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
                placeholder="Nome, usuario ou email"
                type="search"
                value={search}
              />
            </span>
          </label>

          <SelectField
            label="Funcao"
            onChange={(value) => updateFilter(setRoleFilter, value)}
            options={[
              ["", "Todas"],
              [UserRole.Admin, roleLabels[UserRole.Admin]],
              [UserRole.Manager, roleLabels[UserRole.Manager]],
              [UserRole.Seller, roleLabels[UserRole.Seller]],
              [UserRole.Viewer, roleLabels[UserRole.Viewer]],
            ]}
            value={roleFilter}
          />

          <SelectField
            label="Status"
            onChange={(value) => setStatusFilter(value as UserStatus | "")}
            options={[
              [UserStatus.Active, "Ativos"],
              [UserStatus.Inactive, "Inativos"],
              ["", "Todos"],
            ]}
            value={statusFilter}
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
          <h2 className="text-base font-bold text-slate-950">Lista de usuarios</h2>
          {isLoading ? <Loader2 className="h-5 w-5 animate-spin text-[#0f8f80]" aria-hidden /> : null}
        </div>

        <div className="overflow-x-auto">
          <table className="w-full min-w-[820px] text-left text-sm">
            <thead className="bg-slate-50 text-xs uppercase tracking-[0.12em] text-slate-500">
              <tr>
                <th className="px-4 py-3 font-semibold">Usuario</th>
                <th className="px-4 py-3 font-semibold">Email</th>
                <th className="px-4 py-3 font-semibold">Funcao</th>
                <th className="px-4 py-3 font-semibold">Status</th>
                <th className="px-4 py-3 font-semibold">Ultimo login</th>
                <th className="px-4 py-3 font-semibold">Acoes</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {users.map((user) => (
                <tr className="hover:bg-slate-50" key={user.id}>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-3">
                      <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#3be1c9] text-sm font-bold text-[#242328]">
                        {getInitials(user.full_name || user.username)}
                      </span>
                      <div>
                        <p className="font-semibold text-slate-950">{user.full_name}</p>
                        <p className="text-xs text-slate-500">@{user.username}</p>
                      </div>
                    </div>
                  </td>
                  <td className="px-4 py-3 text-slate-700">{user.email || "-"}</td>
                  <td className="px-4 py-3">
                    <RoleBadge role={user.role} />
                  </td>
                  <td className="px-4 py-3">
                    <StatusBadge status={user.status} />
                  </td>
                  <td className="px-4 py-3 text-slate-700">{formatDate(user.last_login)}</td>
                  <td className="px-4 py-3">
                    <div className="flex gap-2">
                      <IconButton label="Ver detalhes" onClick={() => openDetails(user)}>
                        <Eye className="h-4 w-4" aria-hidden />
                      </IconButton>
                      {canWriteUsers ? (
                        <IconButton label="Editar" onClick={() => openEditForm(user)}>
                          <Pencil className="h-4 w-4" aria-hidden />
                        </IconButton>
                      ) : null}
                      {canWriteUsers && user.status === UserStatus.Active ? (
                        <IconButton
                          label="Inativar"
                          onClick={() => deactivateSelectedUser(user)}
                        >
                          <UserRoundX className="h-4 w-4" aria-hidden />
                        </IconButton>
                      ) : null}
                    </div>
                  </td>
                </tr>
              ))}

              {!isLoading && users.length === 0 ? (
                <tr>
                  <td className="px-4 py-8 text-center text-slate-500" colSpan={6}>
                    Nenhum usuario encontrado.
                  </td>
                </tr>
              ) : null}
            </tbody>
          </table>
        </div>
      </section>

      {detailsUser ? (
        <UserDetailsModal onClose={() => setDetailsUser(null)} user={detailsUser} />
      ) : null}

      {isFormOpen ? (
        <UserFormModal
          editingUser={editingUser}
          error={formError}
          form={form}
          isSubmitting={isSubmitting}
          onClose={closeForm}
          onSubmit={submitUserForm}
          updateField={updateFormField}
        />
      ) : null}
    </div>
  );
}

function UserDetailsModal({
  onClose,
  user,
}: Readonly<{
  onClose: () => void;
  user: UserListItem;
}>) {
  return (
    <ModalShell onClose={onClose} title="Detalhes do usuario">
      <div className="grid gap-4 md:grid-cols-2">
        <DetailItem label="Nome" value={user.full_name} />
        <DetailItem label="Usuario" value={`@${user.username}`} />
        <DetailItem label="Email" value={user.email || "-"} />
        <DetailItem label="Funcao" value={roleLabels[user.role]} />
        <DetailItem label="Status" value={statusLabels[user.status]} />
        <DetailItem label="Ultimo login" value={formatDate(user.last_login)} />
      </div>
    </ModalShell>
  );
}

function UserFormModal({
  editingUser,
  error,
  form,
  isSubmitting,
  onClose,
  onSubmit,
  updateField,
}: Readonly<{
  editingUser: UserListItem | null;
  error: string;
  form: UserFormState;
  isSubmitting: boolean;
  onClose: () => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
  updateField: <K extends keyof UserFormState>(key: K, value: UserFormState[K]) => void;
}>) {
  return (
    <ModalShell onClose={onClose} title={editingUser ? "Editar usuario" : "Novo usuario"}>
      <form className="space-y-4" onSubmit={onSubmit}>
        <div className="grid gap-3 md:grid-cols-2">
          <TextField
            label="Nome completo"
            onChange={(value) => updateField("full_name", value)}
            required
            value={form.full_name}
          />
          <TextField
            label="Nome de usuario"
            onChange={(value) => updateField("username", value)}
            required
            value={form.username}
          />
          <TextField
            label="Email"
            onChange={(value) => updateField("email", value)}
            type="email"
            value={form.email}
          />
          <TextField
            label={editingUser ? "Nova senha" : "Senha"}
            minLength={6}
            onChange={(value) => updateField("password", value)}
            required={!editingUser}
            type="password"
            value={form.password}
          />
          <SelectField
            label="Funcao"
            onChange={(value) => updateField("role", value as UserRole)}
            options={[
              [UserRole.Viewer, roleLabels[UserRole.Viewer]],
              [UserRole.Seller, roleLabels[UserRole.Seller]],
              [UserRole.Manager, roleLabels[UserRole.Manager]],
              [UserRole.Admin, roleLabels[UserRole.Admin]],
            ]}
            required
            value={form.role}
          />
          <SelectField
            label="Status"
            onChange={(value) => updateField("status", value as UserStatus)}
            options={[
              [UserStatus.Active, statusLabels[UserStatus.Active]],
              [UserStatus.Inactive, statusLabels[UserStatus.Inactive]],
            ]}
            required
            value={form.status}
          />
        </div>

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
      <div className="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-[8px] bg-white shadow-2xl">
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
          <UserRound className="h-5 w-5" aria-hidden />
        </span>
      </div>
    </article>
  );
}

function SelectField({
  label,
  onChange,
  options,
  required = false,
  value,
}: Readonly<{
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
        className="hype-focus h-10 w-full rounded-[8px] border border-slate-200 bg-white px-3 text-sm text-slate-900"
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
      className="hype-focus inline-flex h-9 w-9 items-center justify-center rounded-[8px] border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-950 disabled:cursor-not-allowed disabled:opacity-40"
      onClick={onClick}
      title={label}
      type="button"
    >
      {children}
    </button>
  );
}

function RoleBadge({ role }: Readonly<{ role: UserRole }>) {
  const classes: Record<UserRole, string> = {
    [UserRole.Admin]: "bg-red-50 text-red-700",
    [UserRole.Manager]: "bg-amber-50 text-amber-700",
    [UserRole.Seller]: "bg-blue-50 text-blue-700",
    [UserRole.Viewer]: "bg-slate-100 text-slate-600",
  };

  return (
    <span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${classes[role]}`}>
      {roleLabels[role]}
    </span>
  );
}

function StatusBadge({ status }: Readonly<{ status: UserStatus }>) {
  const classes: Record<UserStatus, string> = {
    [UserStatus.Active]: "bg-emerald-50 text-emerald-700",
    [UserStatus.Inactive]: "bg-red-50 text-red-700",
  };

  return (
    <span
      className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${classes[status]}`}
    >
      {statusLabels[status]}
    </span>
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

function createEmptyForm(): UserFormState {
  return {
    email: "",
    full_name: "",
    password: "",
    role: UserRole.Seller,
    status: UserStatus.Active,
    username: "",
  };
}

function createFormFromUser(user: UserListItem): UserFormState {
  return {
    email: user.email,
    full_name: user.full_name,
    password: "",
    role: user.role,
    status: user.status,
    username: user.username,
  };
}

function buildUserPayload(form: UserFormState, isEditing: boolean): UserMutationInput {
  return {
    email: emptyToNull(form.email),
    full_name: form.full_name,
    password: isEditing && !form.password.trim() ? null : form.password,
    role: form.role,
    status: form.status,
    username: form.username,
  };
}

function emptyToNull(value: string): string | null {
  const trimmed = value.trim();
  return trimmed ? trimmed : null;
}

function getInitials(value: string): string {
  return value
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join("");
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
