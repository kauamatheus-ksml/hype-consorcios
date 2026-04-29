import "server-only";

import { cache } from "react";

import { HttpError } from "@/lib/http";
import { getSessionFromCookies } from "@/lib/session";
import { findActiveUserById, toPublicUserFromUser } from "@/lib/user-queries";
import type { PublicUser } from "@/types";

const findCurrentPublicUserById = cache(async (userId: PublicUser["id"]) => {
  const user = await findActiveUserById(userId);

  if (!user) {
    return null;
  }

  return toPublicUserFromUser(user);
});

export async function getCurrentUser(): Promise<PublicUser | null> {
  const session = await getSessionFromCookies();

  if (!session) {
    return null;
  }

  return findCurrentPublicUserById(session.userId);
}

export async function requireCurrentUser(): Promise<PublicUser> {
  const user = await getCurrentUser();

  if (!user) {
    throw new HttpError("Nao autenticado", 401);
  }

  return user;
}
