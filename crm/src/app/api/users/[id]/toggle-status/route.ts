import { requireCurrentUser } from "@/lib/current-user";
import { getAuditMetadata, logAuditEvent } from "@/lib/audit-log";
import { toggleUserStatus } from "@/lib/admin-user-queries";
import { apiError, apiSuccess, HttpError, readJsonBody } from "@/lib/http";

export const runtime = "nodejs";

interface ToggleStatusInput {
  status?: string | null;
}

interface RouteContext {
  params: Promise<{
    id: string;
  }>;
}

export async function POST(request: Request, context: RouteContext) {
  try {
    const currentUser = await requireCurrentUser();
    const userId = await parseUserId(context);
    const input = await readJsonBody<ToggleStatusInput>(request);
    const user = await toggleUserStatus(currentUser, userId, input.status);
    await logAuditEvent({
      ...getAuditMetadata(request),
      action: "USER_TOGGLE_STATUS",
      description: `Status do usuario atualizado: ${user.username}`,
      newValues: user,
      recordId: user.id,
      tableName: "users",
      userId: currentUser.id,
    });

    return apiSuccess({
      message: "Status do usuario atualizado com sucesso",
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
