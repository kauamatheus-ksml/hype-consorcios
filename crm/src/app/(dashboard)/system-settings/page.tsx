import { SystemSettingsClient } from "@/components/system-settings/SystemSettingsClient";
import { requireCurrentUser } from "@/lib/current-user";

export const dynamic = "force-dynamic";

export default async function SystemSettingsPage() {
  await requireCurrentUser();

  return <SystemSettingsClient />;
}
