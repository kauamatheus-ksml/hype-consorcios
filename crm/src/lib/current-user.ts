import "server-only";

import { HttpError } from "@/lib/http";
import { getSessionFromCookies } from "@/lib/session";
import { findActiveUserById, toPublicUserFromUser } from "@/lib/user-queries";
import type { PublicUser } from "@/types";

export async function getCurrentUser(): Promise<PublicUser | null> {
  const session = await getSessionFromCookies();

  if (!session) {
    return null;
  }

  const user = await findActiveUserById(session.userId);

  if (!user) {
    return null;
  }

  return toPublicUserFromUser(user);
}

export async function requireCurrentUser(): Promise<PublicUser> {
  const user = await getCurrentUser();

  if (!user) {
    throw new HttpError("Nao autenticado", 401);
  }

  return user;
}
