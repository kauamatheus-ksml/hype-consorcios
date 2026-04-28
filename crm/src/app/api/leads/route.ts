import { requireCurrentUser } from "@/lib/current-user";
import { getAuditMetadata, logAuditEvent } from "@/lib/audit-log";
import { apiError, apiSuccess, readJsonBody } from "@/lib/http";
import { createLead, getLeadList, parseLeadListFilters } from "@/lib/lead-queries";
import type { LeadMutationInput } from "@/types/leads";

export const runtime = "nodejs";

export async function GET(request: Request) {
  try {
    const currentUser = await requireCurrentUser();
    const url = new URL(request.url);
    const filters = parseLeadListFilters(url.searchParams);
    const result = await getLeadList(currentUser, filters);

    return apiSuccess(result);
  } catch (error) {
    return apiError(error);
  }
}

export async function POST(request: Request) {
  try {
    const currentUser = await requireCurrentUser();
    const input = await readJsonBody<LeadMutationInput>(request);
    const lead = await createLead(currentUser, input, {
      ipAddress: getClientIp(request),
      userAgent: request.headers.get("user-agent"),
    });
    await logAuditEvent({
      ...getAuditMetadata(request),
      action: "LEAD_CREATE",
      description: `Lead criado: ${lead.name}`,
      newValues: lead,
      recordId: lead.id,
      tableName: "leads",
      userId: currentUser.id,
    });

    return apiSuccess(
      {
        lead,
        message: "Lead criado com sucesso",
      },
      201,
    );
  } catch (error) {
    return apiError(error);
  }
}

function getClientIp(request: Request): string | null {
  const forwardedFor = request.headers.get("x-forwarded-for");

  if (forwardedFor) {
    return forwardedFor.split(",")[0].trim() || null;
  }

  return request.headers.get("x-real-ip");
}
