import {
  BarChart3,
  ClipboardList,
  FileClock,
  Handshake,
  LayoutDashboard,
  Settings,
  SlidersHorizontal,
  UserCircle,
  Users,
} from "lucide-react";
import Link from "next/link";

import { LogoutButton } from "@/components/layout/LogoutButton";
import { hasPermission } from "@/lib/auth";
import { UserRole, type PublicUser } from "@/types";

interface DashboardLayoutProps {
  children: React.ReactNode;
  user: PublicUser;
}

const navItems = [
  {
    href: "/dashboard",
    label: "Dashboard",
    icon: LayoutDashboard,
    minRole: UserRole.Viewer,
  },
  {
    href: "/leads",
    label: "Leads",
    icon: ClipboardList,
    minRole: UserRole.Seller,
  },
  {
    href: "/sales",
    label: "Vendas",
    icon: Handshake,
    minRole: UserRole.Seller,
  },
  {
    href: "/commission-reports",
    label: "Relatorios",
    icon: BarChart3,
    minRole: UserRole.Seller,
  },
  {
    href: "/users",
    label: "Usuarios",
    icon: Users,
    minRole: UserRole.Manager,
  },
  {
    href: "/commission-settings",
    label: "Comissoes",
    icon: SlidersHorizontal,
    minRole: UserRole.Manager,
  },
  {
    href: "/site-config",
    label: "Config Site",
    icon: Settings,
    minRole: UserRole.Admin,
  },
  {
    href: "/system-settings",
    label: "Config Sistema",
    icon: SlidersHorizontal,
    minRole: UserRole.Admin,
  },
  {
    href: "/audit-logs",
    label: "Auditoria",
    icon: FileClock,
    minRole: UserRole.Manager,
  },
  {
    href: "/profile",
    label: "Perfil",
    icon: UserCircle,
    minRole: UserRole.Viewer,
  },
];

export function DashboardLayout({ children, user }: DashboardLayoutProps) {
  const allowedItems = navItems.filter((item) => hasPermission(user.role, item.minRole));

  return (
    <div className="min-h-screen bg-slate-50 text-slate-950">
      <aside className="fixed inset-y-0 left-0 hidden w-64 border-r border-slate-200 bg-[#242328] px-4 py-5 text-white lg:block">
        <Link className="flex items-center gap-3" href="/dashboard" prefetch={false}>
          <span className="flex h-10 w-10 items-center justify-center rounded-[8px] bg-[#3be1c9] text-[#242328]">
            <BarChart3 className="h-5 w-5" aria-hidden />
          </span>
          <span>
            <span className="block text-sm font-bold">Hype Consorcios</span>
            <span className="block text-xs text-slate-400">CRM</span>
          </span>
        </Link>

        <nav className="mt-8 space-y-1">
          {allowedItems.map((item) => (
            <Link
              className="hype-focus flex h-10 items-center gap-3 rounded-[8px] px-3 text-sm font-medium text-slate-300 transition hover:bg-white/10 hover:text-white"
              href={item.href}
              key={item.href}
              prefetch={false}
            >
              <item.icon className="h-4 w-4 text-[#3be1c9]" aria-hidden />
              {item.label}
            </Link>
          ))}
        </nav>
      </aside>

      <div className="min-w-0 lg:pl-64">
        <header className="sticky top-0 z-10 border-b border-slate-200 bg-white/95 px-5 py-4 backdrop-blur">
          <div className="flex items-center justify-between gap-4">
            <div className="min-w-0">
              <p className="truncate text-sm font-semibold text-slate-950">
                {user.full_name}
              </p>
              <p className="text-xs uppercase tracking-[0.16em] text-slate-500">
                {user.role}
              </p>
            </div>
            <LogoutButton />
          </div>

          <nav className="mt-4 flex gap-2 overflow-x-auto pb-1 lg:hidden">
            {allowedItems.map((item) => (
              <Link
                className="hype-focus inline-flex h-9 shrink-0 items-center gap-2 rounded-[8px] border border-slate-200 px-3 text-xs font-semibold text-slate-700"
                href={item.href}
                key={item.href}
                prefetch={false}
              >
                <item.icon className="h-4 w-4 text-[#0f8f80]" aria-hidden />
                {item.label}
              </Link>
            ))}
          </nav>
        </header>

        <main className="min-w-0 px-4 py-6 sm:px-5">{children}</main>
      </div>
    </div>
  );
}
