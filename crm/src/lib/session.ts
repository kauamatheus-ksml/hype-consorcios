import "server-only";

import { cookies } from "next/headers";

import {
  REMEMBER_SESSION_SECONDS,
  SESSION_COOKIE_NAME,
  SHORT_SESSION_SECONDS,
} from "@/lib/session-config";
import { signSessionToken, type SessionTokenPayload, verifySessionToken } from "@/lib/session-token";
import type { User } from "@/types";

export async function createSessionCookie(
  user: Pick<User, "id" | "username" | "role">,
  remember = false,
): Promise<string> {
  const maxAge = remember ? REMEMBER_SESSION_SECONDS : SHORT_SESSION_SECONDS;
  const token = await signSessionToken(
    {
      userId: user.id,
      username: user.username,
      role: user.role,
    },
    `${maxAge}s`,
  );

  const cookieStore = await cookies();
  cookieStore.set(SESSION_COOKIE_NAME, token, {
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "lax",
    path: "/",
    maxAge,
  });

  return token;
}

export async function getSessionFromCookies(): Promise<SessionTokenPayload | null> {
  const cookieStore = await cookies();
  const token = cookieStore.get(SESSION_COOKIE_NAME)?.value;
  return verifySessionToken(token);
}

export async function destroySessionCookie(): Promise<void> {
  const cookieStore = await cookies();
  cookieStore.delete(SESSION_COOKIE_NAME);
}

export async function requireSession(): Promise<SessionTokenPayload> {
  const session = await getSessionFromCookies();

  if (!session) {
    throw new Error("Unauthenticated request.");
  }

  return session;
}
