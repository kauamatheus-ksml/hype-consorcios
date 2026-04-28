import { NextResponse } from "next/server";

import { getAuditMetadata, logAuditEvent } from "@/lib/audit-log";
import { apiError, apiSuccess, readJsonBody } from "@/lib/http";
import { capturePublicLead } from "@/lib/lead-queries";
import type { PublicLeadCaptureInput } from "@/types/leads";

export const runtime = "nodejs";

const corsHeaders = {
  "Access-Control-Allow-Headers": "Content-Type",
  "Access-Control-Allow-Methods": "POST, OPTIONS",
  "Access-Control-Allow-Origin": "*",
};

export async function OPTIONS() {
  return new Response(null, {
    headers: corsHeaders,
    status: 204,
  });
}

export async function POST(request: Request) {
  try {
    const metadata = getAuditMetadata(request);
    const input = await readCaptureLeadBody(request);
    const result = await capturePublicLead(input, metadata);
    await logAuditEvent({
      ...metadata,
      action: "PUBLIC_LEAD_CAPTURE",
      description: `Lead capturado pelo site: ${input.name ?? result.lead_id}`,
      newValues: {
        email: input.email ?? null,
        hasDownPayment: input.hasDownPayment ?? input.has_down_payment ?? null,
        lead_id: result.lead_id,
        name: input.name ?? null,
        phone: input.phone ?? null,
        source: input.source ?? null,
        updated_existing: result.updated_existing,
        vehicle: input.vehicle ?? input.vehicle_interest ?? null,
      },
      recordId: result.lead_id,
      tableName: "leads",
      userId: null,
    });

    return withCors(apiSuccess(result, 201));
  } catch (error) {
    return withCors(apiError(error));
  }
}

async function readCaptureLeadBody(
  request: Request,
): Promise<Partial<PublicLeadCaptureInput>> {
  const contentType = request.headers.get("content-type") ?? "";

  if (contentType.includes("application/json")) {
    return readJsonBody<PublicLeadCaptureInput>(request);
  }

  if (
    contentType.includes("application/x-www-form-urlencoded") ||
    contentType.includes("multipart/form-data")
  ) {
    const formData = await request.formData();
    const input: Record<string, string> = {};

    formData.forEach((value, key) => {
      if (typeof value === "string") {
        input[key] = value;
      }
    });

    return input;
  }

  return {};
}

function withCors<T>(response: NextResponse<T>): NextResponse<T> {
  for (const [key, value] of Object.entries(corsHeaders)) {
    response.headers.set(key, value);
  }

  return response;
}
