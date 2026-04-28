import { requireCurrentUser } from "@/lib/current-user";
import { getAuditMetadata, logAuditEvent } from "@/lib/audit-log";
import { apiError, apiSuccess, readJsonBody } from "@/lib/http";
import {
  createUser,
  getUserList,
  parseUserListFilters,
} from "@/lib/admin-user-queries";
import type { UserMutationInput } from "@/types/users";

export const runtime = "nodejs";

export async function GET(request: Request) {
  try {
    const currentUser = await requireCurrentUser();
    const url = new URL(request.url);
    const filters = parseUserListFilters(url.searchParams);
    const result = await getUserList(currentUser, filters);

    return apiSuccess(result);
  } catch (error) {
    return apiError(error);
  }
}

export async function POST(request: Request) {
  try {
    const currentUser = await requireCurrentUser();
    const input = await readJsonBody<UserMutationInput>(request);
    const user = await createUser(currentUser, input);
    await logAuditEvent({
      ...getAuditMetadata(request),
      action: "USER_CREATE",
      description: `Usuario criado: ${user.username}`,
      newValues: user,
      recordId: user.id,
      tableName: "users",
      userId: currentUser.id,
    });

    return apiSuccess(
      {
        message: "Usuario criado com sucesso",
        user,
      },
      201,
    );
  } catch (error) {
    return apiError(error);
  }
}
