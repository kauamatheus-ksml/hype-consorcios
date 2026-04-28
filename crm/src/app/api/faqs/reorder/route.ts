import { requireCurrentUser } from "@/lib/current-user";
import { getAuditMetadata, logAuditEvent } from "@/lib/audit-log";
import { reorderFaqs } from "@/lib/faq-queries";
import { apiError, apiSuccess, readJsonBody } from "@/lib/http";

export const runtime = "nodejs";

interface FAQReorderInput {
  ids?: unknown[];
}

export async function PUT(request: Request) {
  try {
    const currentUser = await requireCurrentUser();
    const input = await readJsonBody<FAQReorderInput>(request);
    const faqs = await reorderFaqs(currentUser, input.ids);
    await logAuditEvent({
      ...getAuditMetadata(request),
      action: "FAQ_REORDER",
      description: "FAQs reordenadas",
      newValues: {
        ids: faqs.map((faq) => faq.id),
      },
      tableName: "faqs",
      userId: currentUser.id,
    });

    return apiSuccess({
      faqs,
      message: "FAQs reordenadas com sucesso",
    });
  } catch (error) {
    return apiError(error);
  }
}
