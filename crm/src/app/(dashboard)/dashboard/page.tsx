import { DashboardClient } from "@/components/dashboard/DashboardClient";
import { requireCurrentUser } from "@/lib/current-user";

export const dynamic = "force-dynamic";

export default async function DashboardPage() {
  const user = await requireCurrentUser();

  return <DashboardClient userRole={user.role} />;
}
