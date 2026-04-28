import { apiError, apiSuccess, HttpError, readJsonBody } from "@/lib/http";
import { getAuditMetadata, logAuditEvent } from "@/lib/audit-log";
import { createSessionCookie } from "@/lib/session";
import { verifyPassword } from "@/lib/auth";
import {
  findActiveUserByIdentifier,
  toPublicUserFromUser,
  updateLastLogin,
} from "@/lib/user-queries";

export const runtime = "nodejs";

interface LoginBody {
  username: string;
  email: string;
  identifier: string;
  password: string;
  remember: boolean;
}

export async function POST(request: Request) {
  try {
    const body = await readJsonBody<LoginBody>(request);
    const identifier = String(body.username ?? body.email ?? body.identifier ?? "").trim();
    const password = String(body.password ?? "");

    if (!identifier || !password) {
      throw new HttpError("Username e senha sao obrigatorios", 400);
    }

    const user = await findActiveUserByIdentifier(identifier);

    if (!user || !(await verifyPassword(password, user.password_hash))) {
      await logAuditEvent({
        ...getAuditMetadata(request),
        action: "LOGIN_FAILED",
        description: `Tentativa de login falhada para ${identifier}`,
        userId: user?.id ?? null,
      });
      throw new HttpError("Usuario ou senha incorretos", 401);
    }

    await updateLastLogin(user.id);

    await createSessionCookie(user, Boolean(body.remember));
    await logAuditEvent({
      ...getAuditMetadata(request),
      action: "LOGIN_SUCCESS",
      description: `Login bem-sucedido para ${user.username}`,
      userId: user.id,
    });

    return apiSuccess({
      message: "Login realizado com sucesso",
      user: toPublicUserFromUser(user),
    });
  } catch (error) {
    return apiError(error);
  }
}
