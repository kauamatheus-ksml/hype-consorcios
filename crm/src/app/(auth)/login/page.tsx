import { LoginForm } from "@/components/auth/LoginForm";

export default function LoginPage() {
  return (
    <main className="flex min-h-screen items-center justify-center bg-[#242328] px-5 py-8 text-white">
      <section className="w-full max-w-sm rounded-[8px] border border-white/10 bg-white/[0.04] p-6 shadow-[var(--shadow-soft)]">
        <p className="text-sm font-semibold uppercase tracking-[0.18em] text-[#3be1c9]">
          Hype CRM
        </p>
        <h1 className="mt-3 text-2xl font-bold">Login</h1>
        <p className="mt-2 text-sm leading-6 text-slate-300">
          Acesse o painel administrativo.
        </p>

        <LoginForm />
      </section>
    </main>
  );
}
