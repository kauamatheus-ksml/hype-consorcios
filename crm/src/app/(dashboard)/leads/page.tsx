import { LeadsClient } from "@/components/leads/LeadsClient";
import { requireCurrentUser } from "@/lib/current-user";

export const dynamic = "force-dynamic";

export default async function LeadsPage() {
  const currentUser = await requireCurrentUser();

  return <LeadsClient currentUser={currentUser} />;
}
