import { requireCurrentUser } from "@/lib/current-user";
import { apiError, apiSuccess, HttpError } from "@/lib/http";
import { getLeadWhatsAppUrl } from "@/lib/lead-queries";

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
    const result = await getLeadWhatsAppUrl(currentUser, leadId);

    return apiSuccess(result);
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
