import "server-only";

import bcrypt from "bcryptjs";

import { signSessionToken, verifySessionToken } from "@/lib/session-token";
import { UserRole, type PublicUser, type User } from "@/types";

export { signSessionToken, verifySessionToken };

const roleHierarchy: Record<UserRole, number> = {
  [UserRole.Viewer]: 1,
  [UserRole.Seller]: 2,
  [UserRole.Manager]: 3,
  [UserRole.Admin]: 4,
};

export function hasPermission(userRole: UserRole, requiredRole: UserRole): boolean {
  return roleHierarchy[userRole] >= roleHierarchy[requiredRole];
}

export async function hashPassword(password: string): Promise<string> {
  return bcrypt.hash(password, 10);
}

export async function verifyPassword(
  password: string,
  passwordHash: string,
): Promise<boolean> {
  return bcrypt.compare(password, normalizePhpBcryptHash(passwordHash));
}

export function toPublicUser(user: User): PublicUser {
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

export function createSessionPayload(user: Pick<User, "id" | "username" | "role">) {
  return {
    userId: user.id,
    username: user.username,
    role: user.role,
  };
}

function normalizePhpBcryptHash(hash: string): string {
  if (hash.startsWith("$2y$")) {
    return `$2b$${hash.slice(4)}`;
  }

  return hash;
}
