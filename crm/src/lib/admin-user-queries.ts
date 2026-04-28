import "server-only";

import { hashPassword } from "@/lib/auth";
import { dbQuery } from "@/lib/db-direct";
import { HttpError } from "@/lib/http";
import { UserRole, UserStatus, type PublicUser } from "@/types";
import type { UserListFilters, UserListItem, UserMutationInput } from "@/types/users";

interface UserListRow {
  id: number;
  username: string;
  email: string;
  full_name: string;
  role: string | null;
  status: string | null;
  created_at: string | null;
  updated_at: string | null;
  last_login: string | null;
}

interface NormalizedUserInput {
  email: string;
  full_name: string | null;
  password: string | null;
  role: UserRole | null;
  status: UserStatus | null;
  username: string | null;
}

export async function getUserList(
  currentUser: PublicUser,
  filters: UserListFilters,
): Promise<{ users: UserListItem[] }> {
  assertCanReadUsers(currentUser);

  const params: unknown[] = [];
  let sql = `
    SELECT id, username, email, full_name, role, status, created_at, updated_at, last_login
    FROM users
    WHERE 1=1
  `;

  if (filters.status) {
    params.push(filters.status);
    sql += ` AND status = $${params.length}`;
  }

  if (filters.role?.length) {
    params.push(filters.role);
    sql += ` AND role = ANY($${params.length}::text[])`;
  }

  if (filters.search) {
    params.push(`%${filters.search}%`);
    const index = params.length;
    sql += ` AND (full_name ILIKE $${index} OR username ILIKE $${index} OR email ILIKE $${index})`;
  }

  sql += " ORDER BY full_name ASC, username ASC";

  const result = await dbQuery<UserListRow>(sql, params);

  return {
    users: result.rows.map(mapUserListRow),
  };
}

export async function getUserById(
  currentUser: PublicUser,
  userId: number,
): Promise<UserListItem> {
  assertCanReadUsers(currentUser);

  const result = await dbQuery<UserListRow>(
    `
    SELECT id, username, email, full_name, role, status, created_at, updated_at, last_login
    FROM users
    WHERE id = $1
    LIMIT 1
    `,
    [userId],
  );
  const user = result.rows[0];

  if (!user) {
    throw new HttpError("Usuario nao encontrado", 404);
  }

  return mapUserListRow(user);
}

export async function createUser(
  currentUser: PublicUser,
  rawInput: Partial<UserMutationInput>,
): Promise<UserListItem> {
  assertCanWriteUsers(currentUser);

  const input = normalizeUserInput(rawInput);
  validateRequiredUserFields(input, true);
  await assertUniqueUserIdentity(input.username, input.email);

  const passwordHash = await hashPassword(input.password ?? "");

  const result = await dbQuery<{ id: number }>(
    `
    INSERT INTO users (
      full_name, username, email, role, status, password_hash, created_by, created_at, updated_at
    ) VALUES ($1, $2, $3, $4, $5, $6, $7, NOW(), NOW())
    RETURNING id
    `,
    [
      input.full_name,
      input.username,
      input.email,
      input.role,
      input.status ?? UserStatus.Active,
      passwordHash,
      currentUser.id,
    ],
  );

  return getUserById(currentUser, result.rows[0].id);
}

export async function updateUser(
  currentUser: PublicUser,
  userId: number,
  rawInput: Partial<UserMutationInput>,
): Promise<UserListItem> {
  assertCanWriteUsers(currentUser);

  const existing = await getUserById(currentUser, userId);
  const input = normalizeUserInput(rawInput);
  validateRequiredUserFields(input, false);

  if (input.status === UserStatus.Inactive && userId === currentUser.id) {
    throw new HttpError("Voce nao pode inativar o proprio usuario", 400);
  }

  await assertUniqueUserIdentity(input.username, input.email, userId);

  const setClauses = [
    "full_name = $1",
    "username = $2",
    "email = $3",
    "role = $4",
    "status = $5",
    "updated_at = NOW()",
  ];
  const params: unknown[] = [
    input.full_name,
    input.username,
    input.email,
    input.role,
    input.status ?? existing.status,
  ];

  if (input.password) {
    params.push(await hashPassword(input.password));
    setClauses.push(`password_hash = $${params.length}`);
  }

  params.push(userId);

  await dbQuery(
    `
    UPDATE users
    SET ${setClauses.join(", ")}
    WHERE id = $${params.length}
    `,
    params,
  );

  return getUserById(currentUser, userId);
}

export async function deactivateUser(
  currentUser: PublicUser,
  userId: number,
): Promise<UserListItem> {
  assertCanWriteUsers(currentUser);

  if (userId === currentUser.id) {
    throw new HttpError("Voce nao pode inativar o proprio usuario", 400);
  }

  await getUserById(currentUser, userId);
  await dbQuery(
    `
    UPDATE users
    SET status = 'inactive', updated_at = NOW()
    WHERE id = $1
    `,
    [userId],
  );

  return getUserById(currentUser, userId);
}

export async function toggleUserStatus(
  currentUser: PublicUser,
  userId: number,
  requestedStatus?: unknown,
): Promise<UserListItem> {
  assertCanWriteUsers(currentUser);

  const existing = await getUserById(currentUser, userId);
  const status =
    parseEnum(normalizeString(requestedStatus), UserStatus) ??
    (existing.status === UserStatus.Active ? UserStatus.Inactive : UserStatus.Active);

  if (status === UserStatus.Inactive && userId === currentUser.id) {
    throw new HttpError("Voce nao pode inativar o proprio usuario", 400);
  }

  await dbQuery(
    `
    UPDATE users
    SET status = $1, updated_at = NOW()
    WHERE id = $2
    `,
    [status, userId],
  );

  return getUserById(currentUser, userId);
}

export function parseUserListFilters(searchParams: URLSearchParams): UserListFilters {
  return {
    role: parseRoleList(searchParams.get("role")),
    search: parseOptionalString(searchParams.get("search")),
    status: parseEnum(searchParams.get("status"), UserStatus),
  };
}

function assertCanReadUsers(currentUser: Pick<PublicUser, "role">): void {
  if (![UserRole.Admin, UserRole.Manager].includes(currentUser.role)) {
    throw new HttpError("Sem permissao para acessar usuarios", 403);
  }
}

function assertCanWriteUsers(currentUser: Pick<PublicUser, "role">): void {
  if (currentUser.role !== UserRole.Admin) {
    throw new HttpError("Apenas administradores podem criar ou editar usuarios", 403);
  }
}

function normalizeUserInput(input: Partial<UserMutationInput>): NormalizedUserInput {
  return {
    email: normalizeString(input.email) ?? "",
    full_name: normalizeString(input.full_name),
    password: normalizeString(input.password),
    role: parseEnum(normalizeString(input.role), UserRole) ?? null,
    status: parseEnum(normalizeString(input.status), UserStatus) ?? null,
    username: normalizeString(input.username),
  };
}

function validateRequiredUserFields(input: NormalizedUserInput, isCreating: boolean): void {
  if (!input.full_name) {
    throw new HttpError("Nome completo e obrigatorio", 400);
  }

  if (!input.username) {
    throw new HttpError("Nome de usuario e obrigatorio", 400);
  }

  if (!input.role) {
    throw new HttpError("Funcao e obrigatoria", 400);
  }

  if (isCreating && !input.password) {
    throw new HttpError("Senha e obrigatoria para novo usuario", 400);
  }

  if (input.password && input.password.length < 6) {
    throw new HttpError("Senha deve ter pelo menos 6 caracteres", 400);
  }
}

async function assertUniqueUserIdentity(
  username: string | null,
  email: string,
  ignoringUserId?: number,
): Promise<void> {
  if (!username) {
    return;
  }

  const params: unknown[] = [username];
  let usernameSql = "SELECT id FROM users WHERE username = $1";

  if (ignoringUserId) {
    params.push(ignoringUserId);
    usernameSql += ` AND id != $${params.length}`;
  }

  const usernameResult = await dbQuery<{ id: number }>(`${usernameSql} LIMIT 1`, params);

  if (usernameResult.rows[0]) {
    throw new HttpError("Nome de usuario ja existe", 400);
  }

  if (!email) {
    return;
  }

  const emailParams: unknown[] = [email];
  let emailSql = "SELECT id FROM users WHERE email = $1";

  if (ignoringUserId) {
    emailParams.push(ignoringUserId);
    emailSql += ` AND id != $${emailParams.length}`;
  }

  const emailResult = await dbQuery<{ id: number }>(`${emailSql} LIMIT 1`, emailParams);

  if (emailResult.rows[0]) {
    throw new HttpError("Email ja existe", 400);
  }
}

function mapUserListRow(row: UserListRow): UserListItem {
  return {
    id: row.id,
    username: row.username,
    email: row.email,
    full_name: row.full_name,
    role: parseEnum(row.role, UserRole) ?? UserRole.Viewer,
    status: parseEnum(row.status, UserStatus) ?? UserStatus.Active,
    created_at: row.created_at,
    updated_at: row.updated_at,
    last_login: row.last_login,
  };
}

function parseRoleList(value: string | null): UserRole[] | undefined {
  const roles = value
    ?.split(",")
    .map((role) => parseEnum(role.trim(), UserRole))
    .filter((role): role is UserRole => Boolean(role));

  return roles?.length ? roles : undefined;
}

function parseOptionalString(value: string | null): string | undefined {
  const trimmed = value?.trim();
  return trimmed ? trimmed : undefined;
}

function normalizeString(value: unknown): string | null {
  if (typeof value !== "string") {
    return null;
  }

  const trimmed = value.trim();
  return trimmed ? trimmed : null;
}

function parseEnum<T extends Record<string, string>>(
  value: string | null,
  enumObject: T,
): T[keyof T] | undefined {
  if (!value) {
    return undefined;
  }

  const values = Object.values(enumObject);
  return values.includes(value) ? (value as T[keyof T]) : undefined;
}
