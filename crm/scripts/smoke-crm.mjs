import fs from "fs";
import path from "path";

import { SignJWT } from "jose";
import pg from "pg";

const { Pool } = pg;
const baseUrl = process.env.CRM_BASE_URL ?? "http://127.0.0.1:3000";

loadEnv(path.join(process.cwd(), ".env.local"));

const pool = new Pool({
  database: process.env.DB_NAME,
  host: process.env.DB_HOST,
  password: process.env.DB_PASS,
  port: Number(process.env.DB_PORT || "5432"),
  ssl: {
    rejectUnauthorized: false,
  },
  user: process.env.DB_USER,
});

try {
  const admin = await findUser("role IN ('admin', 'manager')");
  const seller = await findUser("role = 'seller'");
  const adminCookie = `crm_session=${await signUser(admin)}`;
  const sellerCookie = seller ? `crm_session=${await signUser(seller)}` : adminCookie;
  const currentYear = new Intl.DateTimeFormat("en-CA", {
    timeZone: "America/Sao_Paulo",
    year: "numeric",
  }).format(new Date());

  await expectJson("/api/health", {
    label: "GET health",
  });
  await expectRedirect("/commission-reports", "/login", {
    label: "GET protected commission reports without session",
  });
  await expectRedirect("/system-settings", "/login", {
    label: "GET protected system settings without session",
  });
  const leads = await expectJson("/api/leads?limit=1", {
    cookie: adminCookie,
    label: "GET leads",
  });
  const firstLead = leads.leads?.[0];

  await expectJson("/api/auth/validate", {
    cookie: adminCookie,
    label: "GET auth validate",
  });
  await expectStatus("/api/leads", 400, {
    body: {},
    cookie: adminCookie,
    label: "POST empty lead",
    method: "POST",
  });
  await expectStatus("/api/capture-lead", 400, {
    body: {},
    label: "POST empty public lead capture",
    method: "POST",
  });
  await expectStatus("/api/leads/0/claim", 400, {
    cookie: sellerCookie,
    label: "POST invalid lead claim",
    method: "POST",
  });
  await expectStatus("/api/leads/0/assign", 400, {
    body: {
      assigned_to: null,
    },
    cookie: adminCookie,
    label: "POST invalid lead assign",
    method: "POST",
  });
  await expectStatus("/api/sales/convert", 400, {
    body: {},
    cookie: adminCookie,
    label: "POST empty sale convert",
    method: "POST",
  });
  await expectStatus("/api/users/0/toggle-status", 400, {
    body: {},
    cookie: adminCookie,
    label: "POST invalid user toggle status",
    method: "POST",
  });

  if (firstLead) {
    await expectJson(`/api/leads/${firstLead.id}`, {
      cookie: adminCookie,
      label: "GET lead detail",
    });
    await expectJson(`/api/leads/${firstLead.id}/whatsapp`, {
      cookie: adminCookie,
      label: "GET lead WhatsApp URL",
    });
    await expectStatus(`/api/leads/${firstLead.id}/interactions`, 400, {
      body: {},
      cookie: adminCookie,
      label: "POST empty lead interaction",
      method: "POST",
    });
  }

  const sales = await expectJson("/api/sales?limit=1", {
    cookie: adminCookie,
    label: "GET sales",
  });
  const firstSale = sales.sales?.[0];

  if (firstSale) {
    await expectStatus(`/api/sales/${firstSale.id}/cancel`, 400, {
      body: {},
      cookie: adminCookie,
      label: "POST empty sale cancel",
      method: "POST",
    });
  }

  await expectJson(`/api/commission-reports?year=${currentYear}`, {
    cookie: adminCookie,
    label: "GET commission report",
  });
  await expectJson(`/api/sales/report?year=${currentYear}`, {
    cookie: adminCookie,
    label: "GET sales report alias",
  });
  await expectJson("/api/commission/settings", {
    cookie: adminCookie,
    label: "GET commission settings alias",
  });
  await expectStatus("/api/commission/settings/0", 400, {
    body: {},
    cookie: adminCookie,
    label: "PUT invalid commission seller alias",
    method: "PUT",
  });
  await expectJson("/api/site-config?section=hero", {
    cookie: adminCookie,
    label: "GET site config",
  });
  await expectJson("/api/system-settings?setting=default_commission_rate", {
    cookie: adminCookie,
    label: "GET default commission setting",
  });
  await expectStatus("/api/system-settings", 400, {
    body: {},
    cookie: adminCookie,
    label: "PUT empty system setting",
    method: "PUT",
  });
  await expectJson("/api/faqs", {
    cookie: adminCookie,
    label: "GET FAQs",
  });
  await expectStatus("/api/faqs", 400, {
    body: {},
    cookie: adminCookie,
    label: "POST empty FAQ",
    method: "POST",
  });
  await expectStatus("/api/faqs/reorder", 400, {
    body: {},
    cookie: adminCookie,
    label: "PUT empty FAQ reorder",
    method: "PUT",
  });

  const uploadForm = new FormData();
  uploadForm.set("config_key", "invalid");
  await expectStatus("/api/site-config/upload", 400, {
    body: uploadForm,
    cookie: adminCookie,
    label: "POST upload without file",
    method: "POST",
    rawBody: true,
  });
  await expectStatus("/api/site-config/upload-url", 400, {
    body: {},
    cookie: adminCookie,
    label: "POST empty upload URL",
    method: "POST",
  });
  await expectStatus("/api/site-config/upload-complete", 400, {
    body: {},
    cookie: adminCookie,
    label: "POST empty upload complete",
    method: "POST",
  });

  for (const page of ["/leads", "/sales", "/site-config", "/system-settings", "/commission-reports"]) {
    await expectStatus(page, 200, {
      cookie: adminCookie,
      label: `GET page ${page}`,
    });
  }

  console.log("Smoke CRM: OK");
} finally {
  await pool.end();
}

function loadEnv(filePath) {
  if (!fs.existsSync(filePath)) {
    return;
  }

  const text = fs.readFileSync(filePath, "utf8");

  for (const line of text.split(/\r?\n/)) {
    const trimmed = line.trim();

    if (!trimmed || trimmed.startsWith("#")) {
      continue;
    }

    const index = trimmed.indexOf("=");

    if (index === -1) {
      continue;
    }

    const key = trimmed.slice(0, index);
    const value = trimmed.slice(index + 1).replace(/^['"]|['"]$/g, "");
    process.env[key] = value;
  }
}

async function findUser(whereSql) {
  const result = await pool.query(
    `
    SELECT id, username, role
    FROM users
    WHERE status = 'active'
      AND ${whereSql}
    ORDER BY CASE role WHEN 'admin' THEN 1 WHEN 'manager' THEN 2 ELSE 3 END, id ASC
    LIMIT 1
    `,
  );

  return result.rows[0] ?? null;
}

async function signUser(user) {
  if (!user) {
    throw new Error("Nenhum usuario ativo disponivel para smoke test.");
  }

  return new SignJWT({
    role: user.role,
    userId: user.id,
    username: user.username,
  })
    .setProtectedHeader({ alg: "HS256" })
    .setSubject(String(user.id))
    .setIssuedAt()
    .setExpirationTime("1h")
    .sign(new TextEncoder().encode(process.env.JWT_SECRET));
}

async function expectJson(url, options) {
  const response = await request(url, options);
  const data = await response.json();

  if (!response.ok || data.success !== true) {
    throw new Error(`${options.label}: expected success, got ${response.status}`);
  }

  console.log(`${options.label}: ${response.status}`);
  return data;
}

async function expectStatus(url, expectedStatus, options) {
  const response = await request(url, options);

  if (response.status !== expectedStatus) {
    throw new Error(`${options.label}: expected ${expectedStatus}, got ${response.status}`);
  }

  console.log(`${options.label}: ${response.status}`);
}

async function expectRedirect(url, expectedLocation, options) {
  const response = await request(url, {
    ...options,
    redirect: "manual",
  });
  const location = response.headers.get("location") ?? "";

  if (![301, 302, 303, 307, 308].includes(response.status) || !location.includes(expectedLocation)) {
    throw new Error(
      `${options.label}: expected redirect to ${expectedLocation}, got ${response.status} ${location}`,
    );
  }

  console.log(`${options.label}: ${response.status}`);
}

async function request(url, options) {
  const headers = new Headers();

  if (options.cookie) {
    headers.set("Cookie", options.cookie);
  }

  let body = undefined;

  if (options.body !== undefined) {
    if (options.rawBody) {
      body = options.body;
    } else {
      headers.set("Content-Type", "application/json");
      body = JSON.stringify(options.body);
    }
  }

  try {
    return await fetch(`${baseUrl}${url}`, {
      body,
      headers,
      method: options.method ?? "GET",
      redirect: options.redirect ?? "follow",
    });
  } catch (error) {
    throw new Error(`${options.label}: request failed. Is ${baseUrl} running? ${error.message}`);
  }
}
