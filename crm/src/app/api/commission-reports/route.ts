import { requireCurrentUser } from "@/lib/current-user";
import { apiError, apiSuccess } from "@/lib/http";
import {
  getCommissionReport,
  parseCommissionReportFilters,
} from "@/lib/commission-report-queries";

export const runtime = "nodejs";

export async function GET(request: Request) {
  try {
    const currentUser = await requireCurrentUser();
    const url = new URL(request.url);
    const filters = parseCommissionReportFilters(url.searchParams);
    const result = await getCommissionReport(currentUser, filters);

    return apiSuccess(result);
  } catch (error) {
    return apiError(error);
  }
}
