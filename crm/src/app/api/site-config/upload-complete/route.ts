import { requireCurrentUser } from "@/lib/current-user";
import { getAuditMetadata, logAuditEvent } from "@/lib/audit-log";
import { apiError, apiSuccess, HttpError, readJsonBody } from "@/lib/http";
import {
  assertUploadableSiteConfig,
  updateSiteConfigFileValue,
} from "@/lib/site-config-queries";
import {
  assertSiteAssetExists,
  assertSiteAssetObjectPath,
  getOptionalString,
  getSiteAssetPublicUrl,
  removePreviousSupabaseUpload,
} from "@/lib/site-config-upload";

export const runtime = "nodejs";

interface SiteConfigUploadCompleteInput {
  config_key?: string | null;
  current_value?: string | null;
  file_name?: string | null;
  file_type?: string | null;
  path?: string | null;
}

export async function POST(request: Request) {
  try {
    const currentUser = await requireCurrentUser();
    const input = await readJsonBody<SiteConfigUploadCompleteInput>(request);
    const configKey = getOptionalString(input.config_key);
    const currentValue = getOptionalString(input.current_value);

    if (!configKey) {
      throw new HttpError("Chave de configuracao e obrigatoria", 400);
    }

    const objectPath = assertSiteAssetObjectPath(input.path);
    const previousConfig = await assertUploadableSiteConfig(currentUser, configKey);
    await assertSiteAssetExists(objectPath);

    const bucket = process.env.SITE_ASSETS_BUCKET;

    if (!bucket) {
      throw new HttpError(
        "Configure SITE_ASSETS_BUCKET para upload em producao na Vercel",
        500,
      );
    }

    const publicUrl = getSiteAssetPublicUrl(bucket, objectPath);
    const config = await updateSiteConfigFileValue(currentUser, configKey, publicUrl);
    await removePreviousSupabaseUpload(currentValue ?? previousConfig.config_value);
    await logAuditEvent({
      ...getAuditMetadata(request),
      action: "SITE_CONFIG_UPLOAD",
      description: `Arquivo enviado para configuracao: ${configKey}`,
      newValues: {
        config_key: configKey,
        file_name: getOptionalString(input.file_name),
        path: publicUrl,
        storage_path: objectPath,
        type: getOptionalString(input.file_type),
      },
      oldValues: { path: currentValue ?? previousConfig.config_value },
      recordId: config.id,
      tableName: "site_config",
      userId: currentUser.id,
    });

    return apiSuccess({
      config,
      message: "Arquivo enviado com sucesso",
    });
  } catch (error) {
    return apiError(error);
  }
}
