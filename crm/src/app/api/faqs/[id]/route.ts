import { requireCurrentUser } from "@/lib/current-user";
import { getAuditMetadata, logAuditEvent } from "@/lib/audit-log";
import { apiError, apiSuccess, HttpError, readJsonBody } from "@/lib/http";
import { deleteFaq, updateFaq } from "@/lib/faq-queries";
import type { FAQMutationInput } from "@/types/site-config";

export const runtime = "nodejs";

interface RouteContext {
  params: Promise<{
    id: string;
  }>;
}

export async function PUT(request: Request, context: RouteContext) {
  try {
    const currentUser = await requireCurrentUser();
    const faqId = await parseFaqId(context);
    const input = await readJsonBody<FAQMutationInput>(request);
    const faq = await updateFaq(currentUser, faqId, input);
    await logAuditEvent({
      ...getAuditMetadata(request),
      action: "FAQ_UPDATE",
      description: `FAQ atualizada: ${faq.question}`,
      newValues: faq,
      recordId: faq.id,
      tableName: "faqs",
      userId: currentUser.id,
    });

    return apiSuccess({
      faq,
      message: "FAQ atualizada com sucesso",
    });
  } catch (error) {
    return apiError(error);
  }
}

export async function DELETE(request: Request, context: RouteContext) {
  try {
    const currentUser = await requireCurrentUser();
    const faqId = await parseFaqId(context);
    await deleteFaq(currentUser, faqId);
    await logAuditEvent({
      ...getAuditMetadata(request),
      action: "FAQ_DELETE",
      description: `FAQ removida: #${faqId}`,
      recordId: faqId,
      tableName: "faqs",
      userId: currentUser.id,
    });

    return apiSuccess({
      message: "FAQ removida com sucesso",
    });
  } catch (error) {
    return apiError(error);
  }
}

async function parseFaqId(context: RouteContext): Promise<number> {
  const { id } = await context.params;
  const faqId = Number(id);

  if (!Number.isInteger(faqId) || faqId < 1) {
    throw new HttpError("ID da FAQ invalido", 400);
  }

  return faqId;
}
