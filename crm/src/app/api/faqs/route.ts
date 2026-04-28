import { requireCurrentUser } from "@/lib/current-user";
import { getAuditMetadata, logAuditEvent } from "@/lib/audit-log";
import { apiError, apiSuccess, readJsonBody } from "@/lib/http";
import { createFaq, getFaqList } from "@/lib/faq-queries";
import type { FAQMutationInput } from "@/types/site-config";

export const runtime = "nodejs";

export async function GET() {
  try {
    const currentUser = await requireCurrentUser();
    const faqs = await getFaqList(currentUser);

    return apiSuccess({ faqs });
  } catch (error) {
    return apiError(error);
  }
}

export async function POST(request: Request) {
  try {
    const currentUser = await requireCurrentUser();
    const input = await readJsonBody<FAQMutationInput>(request);
    const faq = await createFaq(currentUser, input);
    await logAuditEvent({
      ...getAuditMetadata(request),
      action: "FAQ_CREATE",
      description: `FAQ criada: ${faq.question}`,
      newValues: faq,
      recordId: faq.id,
      tableName: "faqs",
      userId: currentUser.id,
    });

    return apiSuccess(
      {
        faq,
        message: "FAQ criada com sucesso",
      },
      201,
    );
  } catch (error) {
    return apiError(error);
  }
}
