interface PlaceholderPageProps {
  title: string;
  eyebrow: string;
}

export function PlaceholderPage({ eyebrow, title }: PlaceholderPageProps) {
  return (
    <section className="mx-auto max-w-5xl rounded-[8px] border border-slate-200 bg-white p-6 shadow-sm">
      <p className="text-sm font-semibold uppercase tracking-[0.18em] text-[#0f8f80]">
        {eyebrow}
      </p>
      <h1 className="mt-2 text-3xl font-bold text-slate-950">{title}</h1>
      <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
        Esta area ja esta dentro do layout autenticado e sera migrada na proxima
        etapa.
      </p>
    </section>
  );
}
