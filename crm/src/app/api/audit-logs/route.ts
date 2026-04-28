import { requireCurrentUser } from "@/lib/current-user";
import { getAuditLogs, parseAuditFilters } from "@/lib/audit-queries";
import { apiError, apiSuccess } from "@/lib/http";

export const runtime = "nodejs";

export async function GET(request: Request) {
  try {
    const currentUser = await requireCurrentUser();
    const url = new URL(request.url);
    const filters = parseAuditFilters(url.searchParams);
    const result = await getAuditLogs(currentUser, filters);

    return apiSuccess(result);
  } catch (error) {
    return apiError(error);
  }
}
