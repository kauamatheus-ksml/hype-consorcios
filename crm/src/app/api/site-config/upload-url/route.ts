import { requireCurrentUser } from "@/lib/current-user";
import { apiError, apiSuccess, HttpError, readJsonBody } from "@/lib/http";
import { assertUploadableSiteConfig } from "@/lib/site-config-queries";
import {
  createSignedSiteUpload,
  getOptionalNumber,
  getOptionalString,
  validateSiteUploadMetadata,
} from "@/lib/site-config-upload";

export const runtime = "nodejs";

interface SiteConfigUploadUrlInput {
  config_key?: string | null;
  file_name?: string | null;
  file_size?: number | string | null;
  file_type?: string | null;
}

export async function POST(request: Request) {
  try {
    const currentUser = await requireCurrentUser();
    const input = await readJsonBody<SiteConfigUploadUrlInput>(request);
    const configKey = getOptionalString(input.config_key);
    const fileName = getOptionalString(input.file_name);
    const fileType = getOptionalString(input.file_type);
    const fileSize = getOptionalNumber(input.file_size);

    if (!configKey) {
      throw new HttpError("Chave de configuracao e obrigatoria", 400);
    }

    if (!fileName) {
      throw new HttpError("Nome do arquivo e obrigatorio", 400);
    }

    if (!fileType) {
      throw new HttpError("Tipo de arquivo e obrigatorio", 400);
    }

    if (!fileSize) {
      throw new HttpError("Tamanho do arquivo e obrigatorio", 400);
    }

    await assertUploadableSiteConfig(currentUser, configKey);
    const metadata = validateSiteUploadMetadata({
      configKey,
      fileName,
      fileSize,
      fileType,
    });
    const upload = await createSignedSiteUpload(metadata);

    return apiSuccess({
      upload: {
        ...upload,
        config_key: configKey,
        file_name: metadata.fileName,
      },
    });
  } catch (error) {
    return apiError(error);
  }
}
