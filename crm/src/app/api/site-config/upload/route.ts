import { mkdir, unlink, writeFile } from "fs/promises";
import path from "path";

import { requireCurrentUser } from "@/lib/current-user";
import { getAuditMetadata, logAuditEvent } from "@/lib/audit-log";
import { apiError, apiSuccess, HttpError } from "@/lib/http";
import {
  assertUploadableSiteConfig,
  updateSiteConfigFileValue,
} from "@/lib/site-config-queries";
import {
  getOptionalString,
  removePreviousSupabaseUpload,
  shouldUseSupabaseStorage,
  uploadSiteFileToSupabase,
  validateSiteUploadMetadata,
} from "@/lib/site-config-upload";

export const runtime = "nodejs";

export async function POST(request: Request) {
  try {
    const currentUser = await requireCurrentUser();
    const formData = await readUploadFormData(request);
    const configKey = getOptionalString(formData.get("config_key"));
    const currentValue = getOptionalString(formData.get("current_value"));
    const file = formData.get("file");

    if (!configKey) {
      throw new HttpError("Chave de configuracao e obrigatoria", 400);
    }

    if (!(file instanceof File)) {
      throw new HttpError("Arquivo e obrigatorio", 400);
    }

    await assertUploadableSiteConfig(currentUser, configKey);
    const metadata = validateSiteUploadMetadata({
      configKey,
      fileName: file.name,
      fileSize: file.size,
      fileType: file.type,
    });
    const uploadedPath = shouldUseSupabaseStorage()
      ? await uploadSiteFileToSupabase(file, metadata.objectPath)
      : await uploadToLocalFilesystem(file, metadata.fileName, metadata.isVideo);

    const config = await updateSiteConfigFileValue(currentUser, configKey, uploadedPath);
    await removePreviousUpload(currentValue);
    await logAuditEvent({
      ...getAuditMetadata(request),
      action: "SITE_CONFIG_UPLOAD",
      description: `Arquivo enviado para configuracao: ${configKey}`,
      newValues: {
        config_key: configKey,
        file_name: file.name,
        path: uploadedPath,
        type: file.type,
      },
      oldValues: currentValue ? { path: currentValue } : null,
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

async function readUploadFormData(request: Request): Promise<FormData> {
  try {
    return await request.formData();
  } catch {
    throw new HttpError("Formulario de upload invalido", 400);
  }
}

async function uploadToLocalFilesystem(
  file: File,
  fileName: string,
  isVideo: boolean,
): Promise<string> {
  const relativePath = isVideo
    ? `assets/videos/admin/${fileName}`
    : `assets/images/admin/${fileName}`;
  const absolutePath = path.join(process.cwd(), "..", relativePath);

  await mkdir(path.dirname(absolutePath), {
    recursive: true,
  });
  await writeFile(absolutePath, Buffer.from(await file.arrayBuffer()));

  return relativePath;
}

async function removePreviousUpload(relativePath: string | null): Promise<void> {
  if (shouldUseSupabaseStorage()) {
    await removePreviousSupabaseUpload(relativePath);
    return;
  }

  if (!relativePath?.startsWith("assets/")) {
    return;
  }

  if (!relativePath.includes("/admin/")) {
    return;
  }

  const absolutePath = path.resolve(process.cwd(), "..", relativePath);
  const assetsRoot = path.resolve(process.cwd(), "..", "assets");

  if (!absolutePath.startsWith(assetsRoot)) {
    return;
  }

  try {
    await unlink(absolutePath);
  } catch {
    // Manter o upload novo mesmo se o arquivo antigo nao existir.
  }
}
