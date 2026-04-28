import { requireCurrentUser } from "@/lib/current-user";
import { getAuditMetadata, logAuditEvent } from "@/lib/audit-log";
import { hashPassword, verifyPassword } from "@/lib/auth";
import { apiError, apiSuccess, HttpError, readJsonBody } from "@/lib/http";
import { destroySessionCookie } from "@/lib/session";
import { findActiveUserById, updatePasswordHash } from "@/lib/user-queries";

export const runtime = "nodejs";

interface ChangePasswordBody {
  current_password: string;
  new_password: string;
}

export async function POST(request: Request) {
  try {
    const currentUser = await requireCurrentUser();
    const body = await readJsonBody<ChangePasswordBody>(request);
    const currentPassword = String(body.current_password ?? "");
    const newPassword = String(body.new_password ?? "");

    if (!currentPassword || !newPassword) {
      throw new HttpError("Senha atual e nova senha sao obrigatorias", 400);
    }

    if (newPassword.length < 8) {
      throw new HttpError("A nova senha deve ter pelo menos 8 caracteres", 400);
    }

    const user = await findActiveUserById(currentUser.id);

    if (!user) {
      throw new HttpError("Usuario nao encontrado", 404);
    }

    if (!(await verifyPassword(currentPassword, user.password_hash))) {
      throw new HttpError("Senha atual incorreta", 401);
    }

    const passwordHash = await hashPassword(newPassword);
    await updatePasswordHash(currentUser.id, passwordHash);
    await logAuditEvent({
      ...getAuditMetadata(request),
      action: "PASSWORD_CHANGE",
      description: `Senha alterada para ${currentUser.username}`,
      recordId: currentUser.id,
      tableName: "users",
      userId: currentUser.id,
    });

    await destroySessionCookie();

    return apiSuccess({
      message: "Senha alterada com sucesso",
      session_invalidated: true,
    });
  } catch (error) {
    return apiError(error);
  }
}
