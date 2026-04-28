import { requireCurrentUser } from "@/lib/current-user";
import { getAuditMetadata, logAuditEvent } from "@/lib/audit-log";
import { apiError, apiSuccess, HttpError, readJsonBody } from "@/lib/http";
import { createSale } from "@/lib/sale-queries";
import type { SaleMutationInput } from "@/types/sales";

export const runtime = "nodejs";

export async function POST(request: Request) {
  try {
    const currentUser = await requireCurrentUser();
    const input = await readJsonBody<SaleMutationInput>(request);
    const leadId = Number(input.lead_id);

    if (!Number.isInteger(leadId) || leadId < 1) {
      throw new HttpError("Lead e obrigatorio para conversao", 400);
    }

    const sale = await createSale(currentUser, input);
    await logAuditEvent({
      ...getAuditMetadata(request),
      action: "SALE_CONVERT",
      description: `Lead convertido em venda: #${sale.id}`,
      newValues: sale,
      recordId: sale.id,
      tableName: "sales",
      userId: currentUser.id,
    });

    return apiSuccess(
      {
        message: "Lead convertido em venda com sucesso",
        sale,
      },
      201,
    );
  } catch (error) {
    return apiError(error);
  }
}
