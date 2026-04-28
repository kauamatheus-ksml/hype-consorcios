import { requireCurrentUser } from "@/lib/current-user";
import { apiError, apiSuccess, readJsonBody } from "@/lib/http";
import {
  getCommissionSellers,
  updateCommissionSettings,
} from "@/lib/commission-queries";
import type { CommissionSettingsInput } from "@/types/commission";

export const runtime = "nodejs";

export async function GET() {
  try {
    const currentUser = await requireCurrentUser();
    const result = await getCommissionSellers(currentUser);

    return apiSuccess({
      ...result,
      user_role: currentUser.role,
    });
  } catch (error) {
    return apiError(error);
  }
}

export async function POST(request: Request) {
  try {
    const currentUser = await requireCurrentUser();
    const input = await readJsonBody<CommissionSettingsInput>(request);
    const result = await updateCommissionSettings(currentUser, input);

    return apiSuccess({
      message: `Configuracao de comissao para ${result.seller.full_name} atualizada com sucesso`,
      ...result,
    });
  } catch (error) {
    return apiError(error);
  }
}
