import type { Metadata } from "next";
import { Inter } from "next/font/google";
import Script from "next/script";
import "./globals.css";

const inter = Inter({
  variable: "--font-inter",
  subsets: ["latin"],
  display: "swap",
});

export const metadata: Metadata = {
  title: "Hype Consorcios CRM",
  description: "Painel administrativo Hype Consorcios em Next.js",
};

const hydrationGuardScript = `
(function () {
  function repairExternalMutations() {
    var metadataNode = document.getElementById("btc_aprume");

    if (metadataNode && metadataNode.tagName === "DIV") {
      metadataNode.hidden = true;
      metadataNode.removeAttribute("id");
    }

    if (document.body) {
      document.body.removeAttribute("cz-shortcut-listen");
    }
  }

  repairExternalMutations();

  if (typeof MutationObserver === "undefined") {
    return;
  }

  var observer = new MutationObserver(repairExternalMutations);
  observer.observe(document.documentElement, {
    attributeFilter: ["id", "hidden", "cz-shortcut-listen"],
    attributes: true,
    childList: true,
    subtree: true
  });

  window.setTimeout(function () {
    observer.disconnect();
    repairExternalMutations();
  }, 5000);
})();
`;

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html
      lang="pt-BR"
      className={`${inter.variable} h-full antialiased`}
      suppressHydrationWarning
    >
      <body className="min-h-full" suppressHydrationWarning>
        <Script
          id="hype-hydration-guard"
          strategy="beforeInteractive"
          dangerouslySetInnerHTML={{
            __html: hydrationGuardScript,
          }}
        />
        {children}
      </body>
    </html>
  );
}
