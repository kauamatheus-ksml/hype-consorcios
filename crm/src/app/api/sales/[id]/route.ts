import { requireCurrentUser } from "@/lib/current-user";
import { getAuditMetadata, logAuditEvent } from "@/lib/audit-log";
import { apiError, apiSuccess, HttpError, readJsonBody } from "@/lib/http";
import { getSaleById, updateSale } from "@/lib/sale-queries";
import type { SaleMutationInput } from "@/types/sales";

export const runtime = "nodejs";

interface RouteContext {
  params: Promise<{
    id: string;
  }>;
}

export async function GET(_request: Request, context: RouteContext) {
  try {
    const currentUser = await requireCurrentUser();
    const saleId = await parseSaleId(context);
    const sale = await getSaleById(currentUser, saleId);

    return apiSuccess({ sale });
  } catch (error) {
    return apiError(error);
  }
}

export async function PUT(request: Request, context: RouteContext) {
  try {
    const currentUser = await requireCurrentUser();
    const saleId = await parseSaleId(context);
    const input = await readJsonBody<SaleMutationInput>(request);
    const oldSale = await getSaleById(currentUser, saleId);
    const sale = await updateSale(currentUser, saleId, input);
    await logAuditEvent({
      ...getAuditMetadata(request),
      action: "SALE_UPDATE",
      description: `Venda atualizada: #${sale.id}`,
      newValues: sale,
      oldValues: oldSale,
      recordId: sale.id,
      tableName: "sales",
      userId: currentUser.id,
    });

    return apiSuccess({
      message: "Venda atualizada com sucesso",
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
