import { requireCurrentUser } from "@/lib/current-user";
import { getAuditMetadata, logAuditEvent } from "@/lib/audit-log";
import { apiError, apiSuccess, readJsonBody } from "@/lib/http";
import {
  getSiteConfigList,
  updateSiteConfigSection,
} from "@/lib/site-config-queries";
import type { SiteConfigUpdateInput } from "@/types/site-config";

export const runtime = "nodejs";

export async function GET(request: Request) {
  try {
    const currentUser = await requireCurrentUser();
    const url = new URL(request.url);
    const section = url.searchParams.get("section") ?? undefined;
    const result = await getSiteConfigList(currentUser, section);

    return apiSuccess(result);
  } catch (error) {
    return apiError(error);
  }
}

export async function PUT(request: Request) {
  try {
    const currentUser = await requireCurrentUser();
    const input = await readJsonBody<SiteConfigUpdateInput>(request);
    const result = await updateSiteConfigSection(currentUser, input);
    await logAuditEvent({
      ...getAuditMetadata(request),
      action: "SITE_CONFIG_UPDATE",
      description: `Configuracoes do site atualizadas: ${input.section ?? "secao"}`,
      newValues: {
        section: input.section,
        values: input.values,
      },
      tableName: "site_config",
      userId: currentUser.id,
    });

    return apiSuccess({
      message: "Configuracoes salvas com sucesso",
      ...result,
    });
  } catch (error) {
    return apiError(error);
  }
}
