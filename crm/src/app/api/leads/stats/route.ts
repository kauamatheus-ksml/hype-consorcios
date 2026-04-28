import { requireCurrentUser } from "@/lib/current-user";
import { apiError, apiSuccess } from "@/lib/http";
import { getLeadStats } from "@/lib/lead-queries";

export const runtime = "nodejs";

export async function GET() {
  try {
    const currentUser = await requireCurrentUser();
    const result = await getLeadStats(currentUser);

    return apiSuccess({
      ...result,
      user_role: currentUser.role,
    });
  } catch (error) {
    return apiError(error);
  }
}
