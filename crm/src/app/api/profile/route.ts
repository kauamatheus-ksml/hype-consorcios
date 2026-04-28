import { requireCurrentUser } from "@/lib/current-user";
import { getAuditMetadata, logAuditEvent } from "@/lib/audit-log";
import { apiError, apiSuccess, readJsonBody } from "@/lib/http";
import { getProfilePayload, updateProfile } from "@/lib/profile-queries";
import type { ProfileUpdateInput } from "@/types/profile";

export const runtime = "nodejs";

export async function GET() {
  try {
    const currentUser = await requireCurrentUser();
    const payload = await getProfilePayload(currentUser);

    return apiSuccess(payload);
  } catch (error) {
    return apiError(error);
  }
}

export async function PUT(request: Request) {
  try {
    const currentUser = await requireCurrentUser();
    const input = await readJsonBody<ProfileUpdateInput>(request);
    const user = await updateProfile(currentUser, input);
    const payload = await getProfilePayload(user);
    await logAuditEvent({
      ...getAuditMetadata(request),
      action: "PROFILE_UPDATE",
      description: `Perfil atualizado para ${currentUser.username}`,
      newValues: {
        email: user.email,
        full_name: user.full_name,
      },
      oldValues: {
        email: currentUser.email,
        full_name: currentUser.full_name,
      },
      recordId: currentUser.id,
      tableName: "users",
      userId: currentUser.id,
    });

    return apiSuccess({
      message: "Perfil atualizado com sucesso",
      ...payload,
    });
  } catch (error) {
    return apiError(error);
  }
}
