import { ArrowRight, Database, LockKeyhole, ShieldCheck } from "lucide-react";
import Link from "next/link";

export default function Home() {
  return (
    <main className="min-h-screen bg-[#242328] px-5 py-8 text-white">
      <div className="mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-5xl flex-col justify-center gap-8">
        <section className="max-w-3xl">
          <p className="mb-3 text-sm font-semibold uppercase tracking-[0.18em] text-[#3be1c9]">
            Fase 0 e Fase 1
          </p>
          <h1 className="text-4xl font-bold leading-tight text-white sm:text-5xl">
            Hype Consorcios CRM
          </h1>
          <p className="mt-4 max-w-2xl text-base leading-7 text-slate-300">
            Base Next.js pronta para substituir o CRM legado em PHP, mantendo o
            banco PostgreSQL existente no Supabase e a hierarquia de permissoes
            original.
          </p>
        </section>

        <section className="grid gap-4 md:grid-cols-3">
          {[
            {
              icon: Database,
              title: "Supabase tipado",
              copy: "Cliente server-side com service role e cliente browser separado com anon key.",
            },
            {
              icon: LockKeyhole,
              title: "Sessao segura",
              copy: "JWT HS256 em cookie HttpOnly, com validade curta ou lembrar-me.",
            },
            {
              icon: ShieldCheck,
              title: "Roles preservadas",
              copy: "viewer < seller < manager < admin, compativel com a regra do Auth.php.",
            },
          ].map((item) => (
            <article
              className="rounded-[8px] border border-white/10 bg-white/[0.04] p-5 shadow-[var(--shadow-soft)]"
              key={item.title}
            >
              <item.icon className="mb-4 h-6 w-6 text-[#3be1c9]" aria-hidden />
              <h2 className="text-base font-semibold text-white">{item.title}</h2>
              <p className="mt-2 text-sm leading-6 text-slate-300">{item.copy}</p>
            </article>
          ))}
        </section>

        <div>
          <Link
            className="hype-focus inline-flex h-11 items-center gap-2 rounded-[8px] bg-[#3be1c9] px-5 text-sm font-bold text-[#242328] transition hover:bg-[#35cab5]"
            href="/dashboard"
          >
            Abrir dashboard
            <ArrowRight className="h-4 w-4" aria-hidden />
          </Link>
        </div>
      </div>
    </main>
  );
}
