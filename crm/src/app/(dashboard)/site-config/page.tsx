import { redirect } from "next/navigation";

import { SiteConfigClient } from "@/components/site-config/SiteConfigClient";
import { requireCurrentUser } from "@/lib/current-user";
import { UserRole } from "@/types";

export const dynamic = "force-dynamic";

export default async function SiteConfigPage() {
  const currentUser = await requireCurrentUser();

  if (currentUser.role !== UserRole.Admin) {
    redirect("/dashboard");
  }

  return <SiteConfigClient />;
}
