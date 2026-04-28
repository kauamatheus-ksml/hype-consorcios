import { requireCurrentUser } from "@/lib/current-user";
import { getAuditMetadata, logAuditEvent } from "@/lib/audit-log";
import { apiError, apiSuccess, HttpError, readJsonBody } from "@/lib/http";
import { getLeadDetails, updateLead } from "@/lib/lead-queries";
import { UserRole } from "@/types";

export const runtime = "nodejs";

interface AssignLeadInput {
  assigned_to?: number | string | null;
}

interface RouteContext {
  params: Promise<{
    id: string;
  }>;
}

export async function POST(request: Request, context: RouteContext) {
  try {
    const currentUser = await requireCurrentUser();

    if (![UserRole.Admin, UserRole.Manager].includes(currentUser.role)) {
      throw new HttpError("Sem permissao para atribuir leads", 403);
    }

    const leadId = await parseLeadId(context);
    const input = await readJsonBody<AssignLeadInput>(request);
    const assignedTo = parseAssignedTo(input.assigned_to);
    const oldDetails = await getLeadDetails(currentUser, leadId);
    const lead = await updateLead(currentUser, leadId, {
      assigned_to: assignedTo,
    });
    await logAuditEvent({
      ...getAuditMetadata(request),
      action: "LEAD_ASSIGN",
      description: `Lead atribuido: ${lead.name}`,
      newValues: lead,
      oldValues: oldDetails.lead,
      recordId: lead.id,
      tableName: "leads",
      userId: currentUser.id,
    });

    return apiSuccess({
      lead,
      message: "Lead atribuido com sucesso",
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

function parseAssignedTo(value: unknown): number | null {
  if (value === null || value === "") {
    return null;
  }

  const parsed = Number(value);

  if (!Number.isInteger(parsed) || parsed < 1) {
    throw new HttpError("Vendedor responsavel invalido", 400);
  }

  return parsed;
}
