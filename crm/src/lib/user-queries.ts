import "server-only";

import { dbQuery } from "@/lib/db-direct";
import { UserRole, UserStatus, type PublicUser, type User } from "@/types";

interface UserRow {
  id: number;
  username: string;
  email: string;
  password_hash: string;
  full_name: string;
  role: string;
  status: string;
  created_at: string;
  updated_at: string;
  last_login: string | null;
  created_by: number | null;
}

export async function findActiveUserByIdentifier(
  identifier: string,
): Promise<User | null> {
  const result = await dbQuery<UserRow>(
    `
    SELECT id, username, email, password_hash, full_name, role, status,
           created_at, updated_at, last_login, created_by
    FROM users
    WHERE (username = $1 OR email = $1)
      AND status = 'active'
    LIMIT 1
    `,
    [identifier],
  );

  return result.rows[0] ? mapUserRow(result.rows[0]) : null;
}

export async function findActiveUserById(userId: number): Promise<User | null> {
  const result = await dbQuery<UserRow>(
    `
    SELECT id, username, email, password_hash, full_name, role, status,
           created_at, updated_at, last_login, created_by
    FROM users
    WHERE id = $1
      AND status = 'active'
    LIMIT 1
    `,
    [userId],
  );

  return result.rows[0] ? mapUserRow(result.rows[0]) : null;
}

export async function updateLastLogin(userId: number): Promise<void> {
  await dbQuery(
    `
    UPDATE users
    SET last_login = NOW(), updated_at = NOW()
    WHERE id = $1
    `,
    [userId],
  );
}

export async function updatePasswordHash(
  userId: number,
  passwordHash: string,
): Promise<void> {
  await dbQuery(
    `
    UPDATE users
    SET password_hash = $1, updated_at = NOW()
    WHERE id = $2
    `,
    [passwordHash, userId],
  );
}

export function toPublicUserFromUser(user: User): PublicUser {
  return {
    id: user.id,
    username: user.username,
    email: user.email,
    full_name: user.full_name,
    role: user.role,
    status: user.status,
    created_at: user.created_at,
    updated_at: user.updated_at,
    last_login: user.last_login,
    created_by: user.created_by,
  };
}

function mapUserRow(row: UserRow): User {
  return {
    id: row.id,
    username: row.username,
    email: row.email,
    password_hash: row.password_hash,
    full_name: row.full_name,
    role: row.role as UserRole,
    status: row.status as UserStatus,
    created_at: row.created_at,
    updated_at: row.updated_at,
    last_login: row.last_login,
    created_by: row.created_by,
  };
}
