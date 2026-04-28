import { getAuditMetadata, logAuditEvent } from "@/lib/audit-log";
import { getCurrentUser } from "@/lib/current-user";
import { apiError, apiSuccess } from "@/lib/http";
import { destroySessionCookie } from "@/lib/session";

export const runtime = "nodejs";

export async function POST(request: Request) {
  try {
    const currentUser = await getCurrentUser();
    await destroySessionCookie();

    if (currentUser) {
      await logAuditEvent({
        ...getAuditMetadata(request),
        action: "LOGOUT",
        description: `Logout realizado para ${currentUser.username}`,
        userId: currentUser.id,
      });
    }

    return apiSuccess({
      message: "Logout realizado com sucesso",
    });
  } catch (error) {
    return apiError(error);
  }
}
