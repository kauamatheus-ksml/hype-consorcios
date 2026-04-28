import { getCurrentUser } from "@/lib/current-user";
import { apiError, apiSuccess, HttpError } from "@/lib/http";

export const runtime = "nodejs";

export async function GET() {
  try {
    const user = await getCurrentUser();

    if (!user) {
      throw new HttpError("Sessao invalida ou expirada", 401);
    }

    return apiSuccess({
      user,
    });
  } catch (error) {
    return apiError(error);
  }
}
