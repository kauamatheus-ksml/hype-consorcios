import { redirect } from "next/navigation";

import { UsersClient } from "@/components/users/UsersClient";
import { hasPermission } from "@/lib/auth";
import { requireCurrentUser } from "@/lib/current-user";
import { UserRole } from "@/types";

export const dynamic = "force-dynamic";

export default async function UsersPage() {
  const currentUser = await requireCurrentUser();

  if (!hasPermission(currentUser.role, UserRole.Manager)) {
    redirect("/dashboard");
  }

  return <UsersClient currentUserId={currentUser.id} userRole={currentUser.role} />;
}
