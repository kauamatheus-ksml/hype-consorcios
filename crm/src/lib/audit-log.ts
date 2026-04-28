import "server-only";

import { dbQuery } from "@/lib/db-direct";

interface AuditLogInput {
  action: string;
  description: string;
  ipAddress?: string | null;
  newValues?: unknown;
  oldValues?: unknown;
  recordId?: number | null;
  tableName?: string | null;
  userAgent?: string | null;
  userId?: number | null;
}

const sensitiveKeys = new Set([
  "confirm_password",
  "current_password",
  "new_password",
  "password",
  "password_hash",
]);

export async function logAuditEvent(input: AuditLogInput): Promise<void> {
  try {
    await dbQuery(
      `
      INSERT INTO audit_logs (
        user_id,
        action,
        table_name,
        record_id,
        old_values,
        new_values,
        description,
        ip_address,
        user_agent,
        created_at
      ) VALUES (
        $1, $2, $3, $4, $5::jsonb, $6::jsonb, $7, $8, $9, NOW()
      )
      `,
      [
        input.userId ?? null,
        input.action,
        input.tableName ?? null,
        input.recordId ?? null,
        serializeAuditValues(input.oldValues),
        serializeAuditValues(input.newValues),
        input.description,
        input.ipAddress ?? null,
        input.userAgent ?? null,
      ],
    );
  } catch (error) {
    console.error("Audit log failed", error);
  }
}

export function getAuditMetadata(request: Request): {
  ipAddress: string | null;
  userAgent: string | null;
} {
  const forwardedFor = request.headers.get("x-forwarded-for");
  const ipAddress = forwardedFor?.split(",")[0].trim() || request.headers.get("x-real-ip");

  return {
    ipAddress,
    userAgent: request.headers.get("user-agent"),
  };
}

function serializeAuditValues(value: unknown): string | null {
  if (!value) {
    return null;
  }

  return JSON.stringify(sanitizeAuditValue(value));
}

function sanitizeAuditValue(value: unknown): unknown {
  if (Array.isArray(value)) {
    return value.map((item) => sanitizeAuditValue(item));
  }

  if (!value || typeof value !== "object") {
    return value;
  }

  return Object.fromEntries(
    Object.entries(value as Record<string, unknown>).map(([key, nestedValue]) => [
      key,
      sensitiveKeys.has(key) ? "[REDACTED]" : sanitizeAuditValue(nestedValue),
    ]),
  );
}
