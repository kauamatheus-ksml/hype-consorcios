import { redirect } from "next/navigation";

import { CommissionSettingsClient } from "@/components/commission/CommissionSettingsClient";
import { hasPermission } from "@/lib/auth";
import { requireCurrentUser } from "@/lib/current-user";
import { UserRole } from "@/types";

export const dynamic = "force-dynamic";

export default async function CommissionSettingsPage() {
  const currentUser = await requireCurrentUser();

  if (!hasPermission(currentUser.role, UserRole.Manager)) {
    redirect("/dashboard");
  }

  return <CommissionSettingsClient userRole={currentUser.role} />;
}
