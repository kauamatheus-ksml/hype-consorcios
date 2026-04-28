import { requireCurrentUser } from "@/lib/current-user";
import { apiError, apiSuccess, HttpError, readJsonBody } from "@/lib/http";
import { updateCommissionSettings } from "@/lib/commission-queries";
import type { CommissionSettingsInput } from "@/types/commission";

export const runtime = "nodejs";

interface RouteContext {
  params: Promise<{
    sellerId: string;
  }>;
}

export async function PUT(request: Request, context: RouteContext) {
  try {
    const currentUser = await requireCurrentUser();
    const sellerId = await parseSellerId(context);
    const input = await readJsonBody<CommissionSettingsInput>(request);
    const result = await updateCommissionSettings(currentUser, {
      ...input,
      seller_id: sellerId,
    });

    return apiSuccess({
      message: `Configuracao de comissao para ${result.seller.full_name} atualizada com sucesso`,
      ...result,
    });
  } catch (error) {
    return apiError(error);
  }
}

async function parseSellerId(context: RouteContext): Promise<number> {
  const { sellerId } = await context.params;
  const parsed = Number(sellerId);

  if (!Number.isInteger(parsed) || parsed < 1) {
    throw new HttpError("ID do vendedor invalido", 400);
  }

  return parsed;
}
