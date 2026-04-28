import { redirect } from "next/navigation";

import { AuditLogsClient } from "@/components/audit/AuditLogsClient";
import { hasPermission } from "@/lib/auth";
import { requireCurrentUser } from "@/lib/current-user";
import { UserRole } from "@/types";

export const dynamic = "force-dynamic";

export default async function AuditLogsPage() {
  const currentUser = await requireCurrentUser();

  if (!hasPermission(currentUser.role, UserRole.Manager)) {
    redirect("/dashboard");
  }

  return <AuditLogsClient />;
}
