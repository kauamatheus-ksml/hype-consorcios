import { redirect } from "next/navigation";

import { DashboardLayout } from "@/components/layout/DashboardLayout";
import { getCurrentUser } from "@/lib/current-user";

export const dynamic = "force-dynamic";

export default async function AuthenticatedLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  const user = await getCurrentUser();

  if (!user) {
    redirect("/login");
  }

  return <DashboardLayout user={user}>{children}</DashboardLayout>;
}
