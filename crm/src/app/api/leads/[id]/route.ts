import { requireCurrentUser } from "@/lib/current-user";
import { getAuditMetadata, logAuditEvent } from "@/lib/audit-log";
import { apiError, apiSuccess, HttpError, readJsonBody } from "@/lib/http";
import { getLeadDetails, updateLead } from "@/lib/lead-queries";
import type { LeadMutationInput } from "@/types/leads";

export const runtime = "nodejs";

interface RouteContext {
  params: Promise<{
    id: string;
  }>;
}

export async function GET(_request: Request, context: RouteContext) {
  try {
    const currentUser = await requireCurrentUser();
    const leadId = await parseLeadId(context);
    const result = await getLeadDetails(currentUser, leadId);

    return apiSuccess(result);
  } catch (error) {
    return apiError(error);
  }
}

export async function PUT(request: Request, context: RouteContext) {
  try {
    const currentUser = await requireCurrentUser();
    const leadId = await parseLeadId(context);
    const input = await readJsonBody<LeadMutationInput>(request);
    const oldDetails = await getLeadDetails(currentUser, leadId);
    const lead = await updateLead(currentUser, leadId, input);
    await logAuditEvent({
      ...getAuditMetadata(request),
      action: "LEAD_UPDATE",
      description: `Lead atualizado: ${lead.name}`,
      newValues: lead,
      oldValues: oldDetails.lead,
      recordId: lead.id,
      tableName: "leads",
      userId: currentUser.id,
    });

    return apiSuccess({
      lead,
      message: "Lead atualizado com sucesso",
    });
  } catch (error) {
    return apiError(error);
  }
}

async function parseLeadId(context: RouteContext): Promise<number> {
  const { id } = await context.params;
  const leadId = Number(id);

  if (!Number.isInteger(leadId) || leadId < 1) {
    throw new HttpError("ID do lead invalido", 400);
  }

  return leadId;
}
