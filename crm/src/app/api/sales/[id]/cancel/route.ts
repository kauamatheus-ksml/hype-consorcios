import { requireCurrentUser } from "@/lib/current-user";
import { getAuditMetadata, logAuditEvent } from "@/lib/audit-log";
import { apiError, apiSuccess, HttpError, readJsonBody } from "@/lib/http";
import { cancelSale } from "@/lib/sale-queries";
import type { SaleCancelInput } from "@/types/sales";

export const runtime = "nodejs";

interface RouteContext {
  params: Promise<{
    id: string;
  }>;
}

export async function POST(request: Request, context: RouteContext) {
  try {
    const currentUser = await requireCurrentUser();
    const saleId = await parseSaleId(context);
    const input = await readJsonBody<SaleCancelInput>(request);
    const sale = await cancelSale(currentUser, saleId, input.reason);
    await logAuditEvent({
      ...getAuditMetadata(request),
      action: "SALE_CANCEL",
      description: `Venda cancelada: #${sale.id}`,
      newValues: sale,
      recordId: sale.id,
      tableName: "sales",
      userId: currentUser.id,
    });

    return apiSuccess({
      message: "Venda cancelada com sucesso",
      sale,
    });
  } catch (error) {
    return apiError(error);
  }
}

async function parseSaleId(context: RouteContext): Promise<number> {
  const { id } = await context.params;
  const saleId = Number(id);

  if (!Number.isInteger(saleId) || saleId < 1) {
    throw new HttpError("ID da venda invalido", 400);
  }

  return saleId;
}
