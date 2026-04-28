import { requireCurrentUser } from "@/lib/current-user";
import { getAuditMetadata, logAuditEvent } from "@/lib/audit-log";
import { apiError, apiSuccess, HttpError, readJsonBody } from "@/lib/http";
import {
  deactivateUser,
  getUserById,
  updateUser,
} from "@/lib/admin-user-queries";
import type { UserMutationInput } from "@/types/users";

export const runtime = "nodejs";

interface RouteContext {
  params: Promise<{
    id: string;
  }>;
}

export async function GET(_request: Request, context: RouteContext) {
  try {
    const currentUser = await requireCurrentUser();
    const userId = await parseUserId(context);
    const user = await getUserById(currentUser, userId);

    return apiSuccess({ user });
  } catch (error) {
    return apiError(error);
  }
}

export async function PUT(request: Request, context: RouteContext) {
  try {
    const currentUser = await requireCurrentUser();
    const userId = await parseUserId(context);
    const input = await readJsonBody<UserMutationInput>(request);
    const oldUser = await getUserById(currentUser, userId);
    const user = await updateUser(currentUser, userId, input);
    await logAuditEvent({
      ...getAuditMetadata(request),
      action: "USER_UPDATE",
      description: `Usuario atualizado: ${user.username}`,
      newValues: user,
      oldValues: oldUser,
      recordId: user.id,
      tableName: "users",
      userId: currentUser.id,
    });

    return apiSuccess({
      message: "Usuario atualizado com sucesso",
      user,
    });
  } catch (error) {
    return apiError(error);
  }
}

export async function DELETE(request: Request, context: RouteContext) {
  try {
    const currentUser = await requireCurrentUser();
    const userId = await parseUserId(context);
    const oldUser = await getUserById(currentUser, userId);
    const user = await deactivateUser(currentUser, userId);
    await logAuditEvent({
      ...getAuditMetadata(request),
      action: "USER_DEACTIVATE",
      description: `Usuario inativado: ${user.username}`,
      newValues: user,
      oldValues: oldUser,
      recordId: user.id,
      tableName: "users",
      userId: currentUser.id,
    });

    return apiSuccess({
      message: "Usuario inativado com sucesso",
      user,
    });
  } catch (error) {
    return apiError(error);
  }
}

async function parseUserId(context: RouteContext): Promise<number> {
  const { id } = await context.params;
  const userId = Number(id);

  if (!Number.isInteger(userId) || userId < 1) {
    throw new HttpError("ID do usuario invalido", 400);
  }

  return userId;
}
