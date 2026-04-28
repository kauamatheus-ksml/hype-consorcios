import { requireCurrentUser } from "@/lib/current-user";
import { getAuditMetadata, logAuditEvent } from "@/lib/audit-log";
import { apiError, apiSuccess, HttpError, readJsonBody } from "@/lib/http";
import { createLeadInteraction } from "@/lib/lead-queries";
import type { LeadInteractionMutationInput } from "@/types/leads";

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
    const input = await readJsonBody<LeadInteractionMutationInput>(request);
    const interaction = await createLeadInteraction(currentUser, leadId, input);
    await logAuditEvent({
      ...getAuditMetadata(request),
      action: "LEAD_INTERACTION_CREATE",
      description: `Interacao registrada no lead #${leadId}`,
      newValues: interaction,
      recordId: leadId,
      tableName: "lead_interactions",
      userId: currentUser.id,
    });

    return apiSuccess(
      {
        interaction,
        message: "Interacao registrada com sucesso",
      },
      201,
    );
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
