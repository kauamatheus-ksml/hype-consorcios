import { SalesClient } from "@/components/sales/SalesClient";
import { requireCurrentUser } from "@/lib/current-user";

export const dynamic = "force-dynamic";

export default async function SalesPage() {
  const currentUser = await requireCurrentUser();

  return <SalesClient userId={currentUser.id} userRole={currentUser.role} />;
}
