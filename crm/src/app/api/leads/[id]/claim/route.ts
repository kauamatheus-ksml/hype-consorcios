import { requireCurrentUser } from "@/lib/current-user";
import { getAuditMetadata, logAuditEvent } from "@/lib/audit-log";
import { apiError, apiSuccess, HttpError } from "@/lib/http";
import { claimLead } from "@/lib/lead-queries";

export const runtime = "nodejs";

interface RouteContext {
  params: Promise<{
    id: string;
  }>;
}

export async function POST(request: Request, context: RouteContext) {
  try {
    const currentUser = await requireCurrentUser();
    const leadId = await parseLeadId(context);
    const lead = await claimLead(currentUser, leadId);
    await logAuditEvent({
      ...getAuditMetadata(request),
      action: "LEAD_CLAIM",
      description: `Lead assumido: ${lead.name}`,
      newValues: lead,
      recordId: lead.id,
      tableName: "leads",
      userId: currentUser.id,
    });

    return apiSuccess({
      lead,
      message: "Lead assumido com sucesso",
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
