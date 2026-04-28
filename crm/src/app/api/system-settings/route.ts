import { requireCurrentUser } from "@/lib/current-user";
import { getAuditMetadata, logAuditEvent } from "@/lib/audit-log";
import { apiError, apiSuccess, readJsonBody } from "@/lib/http";
import {
  getSystemSettingList,
  getSystemSettingValue,
  upsertSystemSetting,
} from "@/lib/system-setting-queries";
import type { SystemSettingMutationInput } from "@/types/system-settings";

export const runtime = "nodejs";

export async function GET(request: Request) {
  try {
    const currentUser = await requireCurrentUser();
    const url = new URL(request.url);
    const setting = url.searchParams.get("setting");

    if (setting) {
      const result = await getSystemSettingValue(currentUser, setting);
      return apiSuccess(result);
    }

    const settings = await getSystemSettingList(currentUser);
    return apiSuccess({ settings });
  } catch (error) {
    return apiError(error);
  }
}

export async function PUT(request: Request) {
  try {
    const currentUser = await requireCurrentUser();
    const input = await readJsonBody<SystemSettingMutationInput>(request);
    const setting = await upsertSystemSetting(currentUser, input);
    await logAuditEvent({
      ...getAuditMetadata(request),
      action: "SYSTEM_SETTING_UPDATE",
      description: `Configuracao do sistema atualizada: ${setting.setting_key}`,
      newValues: setting,
      recordId: setting.id,
      tableName: "system_settings",
      userId: currentUser.id,
    });

    return apiSuccess({
      message: "Configuracao atualizada com sucesso",
      setting,
    });
  } catch (error) {
    return apiError(error);
  }
}

export async function POST(request: Request) {
  return PUT(request);
}
