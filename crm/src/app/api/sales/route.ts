import { requireCurrentUser } from "@/lib/current-user";
import { getAuditMetadata, logAuditEvent } from "@/lib/audit-log";
import { apiError, apiSuccess, readJsonBody } from "@/lib/http";
import { createSale, getSaleList, parseSaleListFilters } from "@/lib/sale-queries";
import type { SaleMutationInput } from "@/types/sales";

export const runtime = "nodejs";

export async function GET(request: Request) {
  try {
    const currentUser = await requireCurrentUser();
    const url = new URL(request.url);
    const filters = parseSaleListFilters(url.searchParams);
    const result = await getSaleList(currentUser, filters);

    return apiSuccess(result);
  } catch (error) {
    return apiError(error);
  }
}

export async function POST(request: Request) {
  try {
    const currentUser = await requireCurrentUser();
    const input = await readJsonBody<SaleMutationInput>(request);
    const sale = await createSale(currentUser, input);
    await logAuditEvent({
      ...getAuditMetadata(request),
      action: "SALE_CREATE",
      description: `Venda criada: #${sale.id}`,
      newValues: sale,
      recordId: sale.id,
      tableName: "sales",
      userId: currentUser.id,
    });

    return apiSuccess(
      {
        message: "Venda criada com sucesso",
        sale,
      },
      201,
    );
  } catch (error) {
    return apiError(error);
  }
}
