import { CommissionReportsClient } from "@/components/commission-reports/CommissionReportsClient";
import { requireCurrentUser } from "@/lib/current-user";

export const dynamic = "force-dynamic";

export default async function CommissionReportsPage() {
  await requireCurrentUser();

  return <CommissionReportsClient />;
}
