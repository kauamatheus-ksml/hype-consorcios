import { jwtVerify, SignJWT, type JWTPayload } from "jose";

import { getRequiredEnv } from "@/lib/env";
import { UserRole } from "@/types";

export interface SessionTokenPayload extends JWTPayload {
  sub: string;
  userId: number;
  username: string;
  role: UserRole;
}

function getJwtSecretKey(): Uint8Array {
  const secret = getRequiredEnv("JWT_SECRET");

  if (secret.length < 32) {
    throw new Error("JWT_SECRET must contain at least 32 characters.");
  }

  return new TextEncoder().encode(secret);
}

export async function signSessionToken(
  payload: Omit<SessionTokenPayload, "sub"> & { sub?: string },
  expiresIn: string,
): Promise<string> {
  const subject = payload.sub ?? String(payload.userId);

  return new SignJWT({
    userId: payload.userId,
    username: payload.username,
    role: payload.role,
  })
    .setProtectedHeader({ alg: "HS256" })
    .setSubject(subject)
    .setIssuedAt()
    .setExpirationTime(expiresIn)
    .sign(getJwtSecretKey());
}

export async function verifySessionToken(
  token: string | undefined,
): Promise<SessionTokenPayload | null> {
  if (!token) {
    return null;
  }

  try {
    const { payload } = await jwtVerify(token, getJwtSecretKey(), {
      algorithms: ["HS256"],
    });

    if (
      typeof payload.sub !== "string" ||
      typeof payload.userId !== "number" ||
      typeof payload.username !== "string" ||
      !Object.values(UserRole).includes(payload.role as UserRole)
    ) {
      return null;
    }

    return payload as SessionTokenPayload;
  } catch {
    return null;
  }
}
