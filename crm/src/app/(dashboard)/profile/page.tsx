import { ProfileClient } from "@/components/profile/ProfileClient";
import { requireCurrentUser } from "@/lib/current-user";
import { getProfilePayload } from "@/lib/profile-queries";

export const dynamic = "force-dynamic";

export default async function ProfilePage() {
  const currentUser = await requireCurrentUser();
  const profile = await getProfilePayload(currentUser);

  return <ProfileClient initialProfile={profile} />;
}
