import "server-only";

import { HttpError } from "@/lib/http";
import { getSupabaseAdminClient } from "@/lib/supabase";

const imageTypes = new Set(["image/jpeg", "image/png", "image/gif", "image/webp"]);
const videoTypes = new Set(["video/mp4", "video/webm", "video/avi", "video/mov", "video/quicktime"]);

export interface ValidatedSiteUploadMetadata {
  allowedTypes: Set<string>;
  fileName: string;
  fileSize: number;
  fileType: string;
  isVideo: boolean;
  maxSize: number;
  objectPath: string;
}

export interface SignedSiteUpload {
  bucket: string;
  content_type: string;
  max_size: number;
  path: string;
  public_url: string;
  signed_url: string;
  token: string;
}

export function shouldUseSupabaseStorage(): boolean {
  return Boolean(process.env.SITE_ASSETS_BUCKET || process.env.VERCEL);
}

export function validateSiteUploadMetadata(input: {
  configKey: string;
  fileName: string;
  fileSize: number;
  fileType: string;
}): ValidatedSiteUploadMetadata {
  const isVideo = input.configKey.includes("video") || input.fileType.startsWith("video/");
  const allowedTypes = isVideo ? videoTypes : imageTypes;
  const maxSize = isVideo ? 50 * 1024 * 1024 : 5 * 1024 * 1024;

  if (!allowedTypes.has(input.fileType)) {
    throw new HttpError("Tipo de arquivo nao permitido", 400);
  }

  if (!Number.isFinite(input.fileSize) || input.fileSize <= 0) {
    throw new HttpError("Tamanho do arquivo e obrigatorio", 400);
  }

  if (input.fileSize > maxSize) {
    throw new HttpError(`Arquivo muito grande. Maximo: ${maxSize / 1024 / 1024}MB`, 400);
  }

  const fileName = `${Date.now()}_${sanitizeSiteUploadFileName(input.fileName)}`;

  return {
    allowedTypes,
    fileName,
    fileSize: input.fileSize,
    fileType: input.fileType,
    isVideo,
    maxSize,
    objectPath: buildSiteAssetObjectPath(fileName, isVideo),
  };
}

export async function createSignedSiteUpload(
  metadata: Pick<ValidatedSiteUploadMetadata, "fileType" | "maxSize" | "objectPath">,
): Promise<SignedSiteUpload> {
  const bucket = getRequiredSiteAssetsBucket();
  const supabase = getSupabaseAdminClient();
  const { data, error } = await supabase.storage
    .from(bucket)
    .createSignedUploadUrl(metadata.objectPath);

  if (error || !data?.signedUrl || !data.token) {
    throw new HttpError(
      `Erro ao criar URL assinada de upload: ${error?.message ?? "resposta invalida"}`,
      500,
    );
  }

  return {
    bucket,
    content_type: metadata.fileType,
    max_size: metadata.maxSize,
    path: data.path ?? metadata.objectPath,
    public_url: getSiteAssetPublicUrl(bucket, metadata.objectPath),
    signed_url: data.signedUrl,
    token: data.token,
  };
}

export async function uploadSiteFileToSupabase(
  file: File,
  objectPath: string,
): Promise<string> {
  const bucket = getRequiredSiteAssetsBucket();
  const supabase = getSupabaseAdminClient();
  const { error } = await supabase.storage.from(bucket).upload(
    objectPath,
    Buffer.from(await file.arrayBuffer()),
    {
      contentType: file.type,
      upsert: false,
    },
  );

  if (error) {
    throw new HttpError(`Erro ao enviar arquivo para Supabase Storage: ${error.message}`, 500);
  }

  return getSiteAssetPublicUrl(bucket, objectPath);
}

export async function assertSiteAssetExists(objectPath: string): Promise<void> {
  const bucket = getRequiredSiteAssetsBucket();
  const { data, error } = await getSupabaseAdminClient().storage.from(bucket).exists(objectPath);

  if (error) {
    throw new HttpError(`Erro ao validar arquivo enviado: ${error.message}`, 500);
  }

  if (!data) {
    throw new HttpError("Upload ainda nao foi concluido no Supabase Storage", 400);
  }
}

export function assertSiteAssetObjectPath(value: unknown): string {
  if (typeof value !== "string") {
    throw new HttpError("Caminho do arquivo e obrigatorio", 400);
  }

  const objectPath = value.trim().replace(/^\/|\/$/g, "").replace(/\/+/g, "/");

  if (!objectPath) {
    throw new HttpError("Caminho do arquivo e obrigatorio", 400);
  }

  if (objectPath.includes("..")) {
    throw new HttpError("Caminho do arquivo invalido", 400);
  }

  if (!objectPath.startsWith("admin/images/") && !objectPath.startsWith("admin/videos/")) {
    throw new HttpError("Caminho do arquivo invalido", 400);
  }

  return objectPath;
}

export function getSiteAssetPublicUrl(bucket: string, objectPath: string): string {
  const publicUrl = getSupabaseAdminClient().storage.from(bucket).getPublicUrl(objectPath).data
    .publicUrl;

  if (!publicUrl) {
    throw new HttpError("Nao foi possivel gerar URL publica do arquivo", 500);
  }

  return publicUrl;
}

export async function removePreviousSupabaseUpload(publicUrl: string | null): Promise<void> {
  const bucket = process.env.SITE_ASSETS_BUCKET;

  if (!bucket || !publicUrl) {
    return;
  }

  const objectPath = extractSupabaseObjectPath(publicUrl, bucket);

  if (!objectPath?.startsWith("admin/")) {
    return;
  }

  const { error } = await getSupabaseAdminClient().storage.from(bucket).remove([objectPath]);

  if (error) {
    console.error("Failed to remove previous Supabase upload", error);
  }
}

export function getOptionalString(value: unknown): string | null {
  return typeof value === "string" && value.trim() ? value.trim() : null;
}

export function getOptionalNumber(value: unknown): number | null {
  if (typeof value === "number" && Number.isFinite(value)) {
    return value;
  }

  if (typeof value === "string" && value.trim()) {
    const numberValue = Number(value);
    return Number.isFinite(numberValue) ? numberValue : null;
  }

  return null;
}

function getRequiredSiteAssetsBucket(): string {
  const bucket = process.env.SITE_ASSETS_BUCKET;

  if (!bucket) {
    throw new HttpError(
      "Configure SITE_ASSETS_BUCKET para upload em producao na Vercel",
      500,
    );
  }

  return bucket;
}

function buildSiteAssetObjectPath(fileName: string, isVideo: boolean): string {
  return `${isVideo ? "admin/videos" : "admin/images"}/${fileName}`;
}

function sanitizeSiteUploadFileName(fileName: string): string {
  return fileName.replace(/[^a-zA-Z0-9.\-_]/g, "") || "upload";
}

function extractSupabaseObjectPath(publicUrl: string, bucket: string): string | null {
  try {
    const url = new URL(publicUrl);
    const publicPathPrefix = `/storage/v1/object/public/${bucket}/`;

    if (url.pathname.startsWith(publicPathPrefix)) {
      return decodeURIComponent(url.pathname.slice(publicPathPrefix.length));
    }
  } catch {
    return null;
  }

  return null;
}
