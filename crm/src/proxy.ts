import { NextRequest, NextResponse } from "next/server";

import { SESSION_COOKIE_NAME } from "@/lib/session-config";
import { verifySessionToken } from "@/lib/session-token";

const protectedPrefixes = [
  "/dashboard",
  "/leads",
  "/sales",
  "/users",
  "/profile",
  "/site-config",
  "/commission-settings",
  "/commission-reports",
  "/system-settings",
  "/audit-logs",
];

const publicAuthRoutes = ["/login"];

export default async function proxy(request: NextRequest) {
  const pathname = request.nextUrl.pathname;
  const session = await verifySessionToken(
    request.cookies.get(SESSION_COOKIE_NAME)?.value,
  );

  const isProtectedRoute = protectedPrefixes.some((prefix) =>
    pathname.startsWith(prefix),
  );

  if (isProtectedRoute && !session) {
    return NextResponse.redirect(new URL("/login", request.url));
  }

  if (publicAuthRoutes.includes(pathname) && session) {
    return NextResponse.redirect(new URL("/dashboard", request.url));
  }

  return NextResponse.next();
}

export const config = {
  matcher: ["/((?!api|_next/static|_next/image|favicon.ico|.*\\..*).*)"],
};
