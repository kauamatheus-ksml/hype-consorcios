import "server-only";

import type { PoolClient } from "pg";

import { dbQuery, dbTransaction } from "@/lib/db-direct";
import { HttpError } from "@/lib/http";
import {
  InteractionResult,
  InteractionType,
  LeadPriority,
  LeadStatus,
  UserRole,
  type DownPaymentFlag,
  type PublicUser,
} from "@/types";
import type {
  LeadAdditionalStats,
  LeadDetailsItem,
  LeadDetailsPayload,
  LeadInteractionMutationInput,
  LeadInteractionItem,
  LeadListItem,
  LeadMutationInput,
  LeadPagination,
  LeadWhatsAppUrlResult,
  PublicLeadCaptureInput,
  PublicLeadCaptureResult,
  LeadRelatedSaleItem,
  LeadSellerOption,
  LeadStats,
} from "@/types/leads";

export interface LeadListFilters {
  assigned_to?: number;
  date_from?: string;
  date_to?: string;
  limit: number;
  page: number;
  priority?: LeadPriority;
  search?: string;
  source?: string;
  status?: LeadStatus;
}

interface LeadRow {
  id: number;
  name: string;
  email: string | null;
  phone: string;
  vehicle_interest: string | null;
  has_down_payment: string | null;
  down_payment_value: string | number | null;
  source_page: string | null;
  status: string;
  priority: string;
  notes: string | null;
  assigned_to: number | null;
  assigned_to_name: string | null;
  interactions_count: string | number;
  last_interaction: string | null;
  sale_id: number | null;
  created_at: string;
  updated_at: string;
  contacted_at: string | null;
}

interface LeadDetailsRow extends LeadRow {
  assigned_to_email: string | null;
}

interface LeadInteractionRow {
  id: number;
  lead_id: number;
  user_id: number | null;
  user_name: string | null;
  interaction_type: string | null;
  description: string | null;
  result: string | null;
  next_contact_date: string | null;
  created_at: string;
}

interface LeadRelatedSaleRow {
  id: number;
  lead_id: number;
  seller_id: number | null;
  seller_name: string | null;
  sale_value: string | number | null;
  commission_value: string | number | null;
  vehicle_sold: string | null;
  status: string | null;
  sale_date: string | null;
  created_at: string | null;
}

interface LeadMutationRow {
  id: number;
  name: string;
  email: string | null;
  phone: string;
  vehicle_interest: string | null;
  has_down_payment: string | null;
  down_payment_value: string | number | null;
  source_page: string | null;
  status: string | null;
  priority: string | null;
  notes: string | null;
  assigned_to: number | null;
  contacted_at: string | null;
}

interface PublicLeadCaptureData {
  down_payment_value: number | null;
  email: string | null;
  has_down_payment: DownPaymentFlag;
  ip_address: string | null;
  name: string;
  phone: string;
  priority: LeadPriority;
  source_page: string;
  user_agent: string | null;
  vehicle_interest: string;
}

export interface LeadRequestMetadata {
  ipAddress?: string | null;
  userAgent?: string | null;
}

export async function getLeadList(
  currentUser: PublicUser,
  filters: LeadListFilters,
): Promise<{
  leads: LeadListItem[];
  pagination: LeadPagination;
}> {
  const limit = clamp(filters.limit, 1, 50);
  const page = Math.max(filters.page, 1);
  const offset = (page - 1) * limit;
  const where = buildLeadWhere(filters, currentUser);

  const countResult = await dbQuery<{ total: string | number }>(
    `SELECT COUNT(*) AS total ${where.fromAndWhere}`,
    where.params,
  );
  const totalRecords = toNumber(countResult.rows[0]?.total);
  const totalPages = Math.max(Math.ceil(totalRecords / limit), 1);

  const listResult = await dbQuery<LeadRow>(
    `
    SELECT
      l.id,
      l.name,
      l.email,
      l.phone,
      l.vehicle_interest,
      l.has_down_payment,
      l.down_payment_value,
      l.source_page,
      l.status,
      l.priority,
      l.notes,
      l.assigned_to,
      l.created_at,
      l.updated_at,
      l.contacted_at,
      u.full_name AS assigned_to_name,
      (SELECT COUNT(*) FROM lead_interactions li WHERE li.lead_id = l.id) AS interactions_count,
      (SELECT MAX(li.created_at) FROM lead_interactions li WHERE li.lead_id = l.id) AS last_interaction,
      (SELECT s.id FROM sales s WHERE s.lead_id = l.id LIMIT 1) AS sale_id
    ${where.fromAndWhere}
    ORDER BY l.created_at DESC
    LIMIT $${where.params.length + 1} OFFSET $${where.params.length + 2}
    `,
    [...where.params, limit, offset],
  );

  return {
    leads: listResult.rows.map(mapLeadRow),
    pagination: {
      current_page: page,
      per_page: limit,
      total_records: totalRecords,
      total_pages: totalPages,
      has_next: page < totalPages,
      has_prev: page > 1,
    },
  };
}

export async function getLeadDetails(
  currentUser: PublicUser,
  leadId: number,
): Promise<LeadDetailsPayload> {
  const leadResult = await dbQuery<LeadDetailsRow>(
    `
    SELECT
      l.id,
      l.name,
      l.email,
      l.phone,
      l.vehicle_interest,
      l.has_down_payment,
      l.down_payment_value,
      l.source_page,
      l.status,
      l.priority,
      l.notes,
      l.assigned_to,
      l.created_at,
      l.updated_at,
      l.contacted_at,
      u.full_name AS assigned_to_name,
      u.email AS assigned_to_email,
      (SELECT COUNT(*) FROM lead_interactions li WHERE li.lead_id = l.id) AS interactions_count,
      (SELECT MAX(li.created_at) FROM lead_interactions li WHERE li.lead_id = l.id) AS last_interaction,
      (SELECT s.id FROM sales s WHERE s.lead_id = l.id LIMIT 1) AS sale_id
    FROM leads l
    LEFT JOIN users u ON l.assigned_to = u.id
    WHERE l.id = $1
    LIMIT 1
    `,
    [leadId],
  );

  const lead = leadResult.rows[0];

  if (!lead) {
    throw new HttpError("Lead nao encontrado", 404);
  }

  if (!canReadLead(currentUser, lead.assigned_to)) {
    throw new HttpError("Sem permissao para ver este lead", 403);
  }

  const [interactionsResult, salesResult] = await Promise.all([
    dbQuery<LeadInteractionRow>(
      `
      SELECT
        i.id,
        i.lead_id,
        i.user_id,
        u.full_name AS user_name,
        i.interaction_type,
        i.description,
        i.result,
        i.next_contact_date,
        i.created_at
      FROM lead_interactions i
      LEFT JOIN users u ON i.user_id = u.id
      WHERE i.lead_id = $1
      ORDER BY i.created_at DESC
      LIMIT 50
      `,
      [leadId],
    ),
    dbQuery<LeadRelatedSaleRow>(
      `
      SELECT
        s.id,
        s.lead_id,
        s.seller_id,
        u.full_name AS seller_name,
        s.sale_value,
        s.commission_value,
        s.vehicle_sold,
        s.status,
        s.sale_date,
        s.created_at
      FROM sales s
      LEFT JOIN users u ON s.seller_id = u.id
      WHERE s.lead_id = $1
      ORDER BY s.created_at DESC
      LIMIT 20
      `,
      [leadId],
    ),
  ]);

  return {
    interactions: interactionsResult.rows.map(mapLeadInteractionRow),
    lead: mapLeadDetailsRow(lead),
    sales: salesResult.rows.map(mapLeadRelatedSaleRow),
  };
}

export async function getLeadWhatsAppUrl(
  currentUser: PublicUser,
  leadId: number,
): Promise<LeadWhatsAppUrlResult> {
  const { lead } = await getLeadDetails(currentUser, leadId);
  const phone = formatBrazilWhatsAppRecipient(lead.phone);
  const message = `Ola ${lead.name}, aqui e da Hype Consorcios. Estamos entrando em contato sobre seu interesse em consorcio de veiculos.`;

  return {
    message,
    phone,
    whatsapp_url: `https://api.whatsapp.com/send/?phone=${phone}&text=${encodeURIComponent(
      message,
    )}`,
  };
}

export async function createLead(
  currentUser: PublicUser,
  rawInput: Partial<LeadMutationInput>,
  metadata: LeadRequestMetadata = {},
): Promise<LeadDetailsItem> {
  if (!hasLeadWriteAccess(currentUser)) {
    throw new HttpError("Sem permissao para criar leads", 403);
  }

  const input = normalizeLeadMutationInput(rawInput);
  validateRequiredString(input.name, "Nome");
  validateRequiredString(input.phone, "Telefone");

  const leadName = input.name;
  const leadPhone = input.phone;
  const targetAssignedTo = canManageLeads(currentUser) ? input.assigned_to : currentUser.id;

  const leadId = await dbTransaction(async (client) => {
    await assertUniqueLeadPhone(client, leadPhone);

    if (targetAssignedTo !== null) {
      await assertActiveAssignee(client, targetAssignedTo);
    }

    const insertResult = await client.query<{ id: number }>(
      `
      INSERT INTO leads (
        name,
        email,
        phone,
        vehicle_interest,
        has_down_payment,
        down_payment_value,
        source_page,
        status,
        priority,
        notes,
        assigned_to,
        ip_address,
        user_agent,
        created_at,
        updated_at
      ) VALUES (
        $1, $2, $3, $4, $5, $6, $7, $8,
        $9, $10, $11, $12, $13, NOW(), NOW()
      )
      RETURNING id
      `,
      [
        leadName,
        input.email,
        leadPhone,
        input.vehicle_interest,
        input.has_down_payment ?? "no",
        input.down_payment_value,
        input.source_page ?? "manual",
        input.status ?? LeadStatus.New,
        input.priority ?? LeadPriority.Medium,
        input.notes,
        targetAssignedTo,
        metadata.ipAddress ?? null,
        metadata.userAgent ?? null,
      ],
    );

    const createdLeadId = insertResult.rows[0].id;
    await insertLeadInteraction(
      client,
      createdLeadId,
      currentUser.id,
      "Lead criado manualmente no sistema",
    );

    return createdLeadId;
  });

  return (await getLeadDetails(currentUser, leadId)).lead;
}

export async function capturePublicLead(
  rawInput: Partial<PublicLeadCaptureInput>,
  metadata: LeadRequestMetadata = {},
): Promise<PublicLeadCaptureResult> {
  const leadData = normalizePublicLeadCaptureInput(rawInput, metadata);

  const result = await dbTransaction(async (client) => {
    const existingResult = await client.query<{ id: number }>(
      `
      SELECT id
      FROM leads
      WHERE phone = $1
        AND created_at > CURRENT_TIMESTAMP - INTERVAL '24 hours'
      ORDER BY created_at DESC
      LIMIT 1
      `,
      [leadData.phone],
    );
    const existingLeadId = existingResult.rows[0]?.id;

    if (existingLeadId) {
      await client.query(
        `
        UPDATE leads
        SET
          name = $1,
          email = $2,
          phone = $3,
          vehicle_interest = $4,
          has_down_payment = $5,
          down_payment_value = $6,
          priority = $7,
          updated_at = NOW()
        WHERE id = $8
        `,
        [
          leadData.name,
          leadData.email,
          leadData.phone,
          leadData.vehicle_interest,
          leadData.has_down_payment,
          leadData.down_payment_value,
          leadData.priority,
          existingLeadId,
        ],
      );

      return {
        leadId: existingLeadId,
        updatedExisting: true,
      };
    }

    const insertResult = await client.query<{ id: number }>(
      `
      INSERT INTO leads (
        name,
        email,
        phone,
        vehicle_interest,
        has_down_payment,
        down_payment_value,
        source_page,
        ip_address,
        user_agent,
        status,
        priority,
        created_at,
        updated_at
      ) VALUES (
        $1, $2, $3, $4, $5, $6, $7, $8,
        $9, $10, $11, NOW(), NOW()
      )
      RETURNING id
      `,
      [
        leadData.name,
        leadData.email,
        leadData.phone,
        leadData.vehicle_interest,
        leadData.has_down_payment,
        leadData.down_payment_value,
        leadData.source_page,
        leadData.ip_address,
        leadData.user_agent,
        LeadStatus.New,
        leadData.priority,
      ],
    );

    const createdLeadId = insertResult.rows[0].id;
    await insertLeadInteraction(
      client,
      createdLeadId,
      null,
      "Lead capturado via formulario do site",
    );

    return {
      leadId: createdLeadId,
      updatedExisting: false,
    };
  });

  return {
    lead_id: result.leadId,
    message: "Lead capturado com sucesso!",
    redirect_whatsapp: buildLeadWhatsAppUrl(leadData),
    updated_existing: result.updatedExisting,
  };
}

export async function updateLead(
  currentUser: PublicUser,
  leadId: number,
  rawInput: Partial<LeadMutationInput>,
): Promise<LeadDetailsItem> {
  if (!hasLeadWriteAccess(currentUser)) {
    throw new HttpError("Sem permissao para editar leads", 403);
  }

  const input = normalizeLeadMutationInput(rawInput);

  const updatedLeadId = await dbTransaction(async (client) => {
    const existing = await getLeadForMutation(client, leadId);

    if (!existing) {
      throw new HttpError("Lead nao encontrado", 404);
    }

    if (!canEditLead(currentUser, existing.assigned_to)) {
      throw new HttpError("Sem permissao para editar este lead", 403);
    }

    const updates: Array<{ column: string; label: string; value: unknown }> = [];

    if (hasOwn(rawInput, "name")) {
      validateRequiredString(input.name, "Nome");
      updates.push({ column: "name", label: "name", value: input.name });
    }

    if (hasOwn(rawInput, "email")) {
      updates.push({ column: "email", label: "email", value: input.email });
    }

    if (hasOwn(rawInput, "phone")) {
      validateRequiredString(input.phone, "Telefone");
      updates.push({ column: "phone", label: "phone", value: input.phone });
    }

    if (hasOwn(rawInput, "vehicle_interest")) {
      updates.push({
        column: "vehicle_interest",
        label: "vehicle_interest",
        value: input.vehicle_interest,
      });
    }

    if (hasOwn(rawInput, "has_down_payment")) {
      updates.push({
        column: "has_down_payment",
        label: "has_down_payment",
        value: input.has_down_payment ?? "no",
      });
    }

    if (hasOwn(rawInput, "down_payment_value")) {
      updates.push({
        column: "down_payment_value",
        label: "down_payment_value",
        value: input.down_payment_value,
      });
    }

    if (hasOwn(rawInput, "status")) {
      updates.push({ column: "status", label: "status", value: input.status ?? LeadStatus.New });
    }

    if (hasOwn(rawInput, "priority")) {
      updates.push({
        column: "priority",
        label: "priority",
        value: input.priority ?? LeadPriority.Medium,
      });
    }

    if (hasOwn(rawInput, "notes")) {
      updates.push({ column: "notes", label: "notes", value: input.notes });
    }

    if (hasOwn(rawInput, "assigned_to") && canManageLeads(currentUser)) {
      if (input.assigned_to !== null) {
        await assertActiveAssignee(client, input.assigned_to);
      }

      updates.push({
        column: "assigned_to",
        label: "assigned_to",
        value: input.assigned_to,
      });
    }

    if (updates.length === 0) {
      return leadId;
    }

    const changedUpdates = updates.filter((update) => {
      const currentValue = existing[update.column as keyof LeadMutationRow];
      return normalizeComparableValue(currentValue) !== normalizeComparableValue(update.value);
    });

    if (changedUpdates.length === 0) {
      return leadId;
    }

    const params = changedUpdates.map((update) => update.value);
    const assignments = changedUpdates
      .map((update, index) => `${update.column} = $${index + 1}`)
      .join(", ");

    await client.query(
      `
      UPDATE leads
      SET ${assignments}, updated_at = NOW()
      WHERE id = $${params.length + 1}
      `,
      [...params, leadId],
    );

    const changeDescription = changedUpdates
      .map((update) => {
        const oldValue = existing[update.column as keyof LeadMutationRow];
        return `${update.label}: ${formatValueForLog(oldValue)} -> ${formatValueForLog(
          update.value,
        )}`;
      })
      .join("; ");

    await insertLeadInteraction(
      client,
      leadId,
      currentUser.id,
      `Lead atualizado: ${changeDescription}`,
    );

    return leadId;
  });

  return (await getLeadDetails(currentUser, updatedLeadId)).lead;
}

export async function createLeadInteraction(
  currentUser: PublicUser,
  leadId: number,
  rawInput: Partial<LeadInteractionMutationInput>,
): Promise<LeadInteractionItem> {
  if (!hasLeadWriteAccess(currentUser)) {
    throw new HttpError("Sem permissao para registrar interacoes", 403);
  }

  const input = normalizeLeadInteractionInput(rawInput);
  validateRequiredString(input.description, "Descricao");

  const description = input.description;
  const interactionId = await dbTransaction(async (client) => {
    const existing = await getLeadForMutation(client, leadId);

    if (!existing) {
      throw new HttpError("Lead nao encontrado", 404);
    }

    if (!canEditLead(currentUser, existing.assigned_to)) {
      throw new HttpError("Sem permissao para registrar interacao neste lead", 403);
    }

    const result = await client.query<{ id: number }>(
      `
      INSERT INTO lead_interactions (
        lead_id,
        user_id,
        interaction_type,
        description,
        result,
        next_contact_date,
        created_at
      ) VALUES ($1, $2, $3, $4, $5, $6, NOW())
      RETURNING id
      `,
      [
        leadId,
        currentUser.id,
        input.interaction_type,
        description,
        input.result,
        input.next_contact_date,
      ],
    );

    await client.query("UPDATE leads SET updated_at = NOW() WHERE id = $1", [leadId]);

    return result.rows[0].id;
  });

  return getLeadInteractionById(interactionId);
}

export async function claimLead(
  currentUser: PublicUser,
  leadId: number,
): Promise<LeadDetailsItem> {
  if (!hasLeadWriteAccess(currentUser)) {
    throw new HttpError("Sem permissao para assumir leads", 403);
  }

  const claimedLeadId = await dbTransaction(async (client) => {
    const existing = await getLeadForMutation(client, leadId);

    if (!existing) {
      throw new HttpError("Lead nao encontrado", 404);
    }

    if (existing.assigned_to && existing.assigned_to !== currentUser.id) {
      throw new HttpError("Lead ja possui responsavel", 409);
    }

    if (existing.assigned_to === currentUser.id) {
      return leadId;
    }

    await client.query(
      "UPDATE leads SET assigned_to = $1, updated_at = NOW() WHERE id = $2",
      [currentUser.id, leadId],
    );

    await insertLeadInteraction(
      client,
      leadId,
      currentUser.id,
      `Lead assumido por ${currentUser.full_name}`,
    );

    return leadId;
  });

  return (await getLeadDetails(currentUser, claimedLeadId)).lead;
}

export async function getLeadStats(
  currentUser: PublicUser,
): Promise<{
  additional: LeadAdditionalStats;
  stats: LeadStats;
}> {
  const scopeWhere = buildLeadScopeWhere(currentUser, "l", []);
  const stats: LeadStats = {
    total: 0,
    new: 0,
    contacted: 0,
    negotiating: 0,
    converted: 0,
    lost: 0,
  };

  const totalResult = await dbQuery<{ total: string | number }>(
    `SELECT COUNT(*) AS total FROM leads l WHERE 1=1 ${scopeWhere.sql}`,
    scopeWhere.params,
  );
  stats.total = toNumber(totalResult.rows[0]?.total);

  const statusResult = await dbQuery<{ status: string; count: string | number }>(
    `
    SELECT l.status, COUNT(*) AS count
    FROM leads l
    WHERE 1=1 ${scopeWhere.sql}
    GROUP BY l.status
    `,
    scopeWhere.params,
  );

  for (const row of statusResult.rows) {
    if (row.status in stats) {
      stats[row.status as keyof LeadStats] = toNumber(row.count);
    }
  }

  const timeResult = await dbQuery<{
    today: string | number;
    this_week: string | number;
    this_month: string | number;
  }>(
    `
    SELECT
      COUNT(*) FILTER (WHERE DATE(l.created_at) = CURRENT_DATE) AS today,
      COUNT(*) FILTER (WHERE l.created_at >= CURRENT_DATE - INTERVAL '7 days') AS this_week,
      COUNT(*) FILTER (
        WHERE l.created_at >= DATE_TRUNC('month', CURRENT_DATE)
          AND l.created_at < DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month'
      ) AS this_month
    FROM leads l
    WHERE 1=1 ${scopeWhere.sql}
    `,
    scopeWhere.params,
  );
  stats.today = toNumber(timeResult.rows[0]?.today);
  stats.this_week = toNumber(timeResult.rows[0]?.this_week);
  stats.this_month = toNumber(timeResult.rows[0]?.this_month);

  const additional: LeadAdditionalStats = {};

  const sourceResult = await dbQuery<{ source: string; count: string | number }>(
    `
    SELECT COALESCE(l.source_page, 'Nao informado') AS source, COUNT(*) AS count
    FROM leads l
    WHERE l.created_at >= CURRENT_DATE - INTERVAL '30 days' ${scopeWhere.sql}
    GROUP BY l.source_page
    ORDER BY count DESC
    LIMIT 10
    `,
    scopeWhere.params,
  );
  additional.leads_by_source = sourceResult.rows.map((row) => ({
    source: row.source,
    count: toNumber(row.count),
  }));

  const priorityResult = await dbQuery<{ priority: string; count: string | number }>(
    `
    SELECT l.priority, COUNT(*) AS count
    FROM leads l
    WHERE 1=1 ${scopeWhere.sql}
    GROUP BY l.priority
    ORDER BY
      CASE l.priority
        WHEN 'urgent' THEN 1
        WHEN 'high' THEN 2
        WHEN 'medium' THEN 3
        WHEN 'low' THEN 4
        ELSE 5
      END
    `,
    scopeWhere.params,
  );
  additional.leads_by_priority = priorityResult.rows.map((row) => ({
    priority: row.priority,
    count: toNumber(row.count),
  }));

  if ([UserRole.Admin, UserRole.Manager].includes(currentUser.role)) {
    const sellerResult = await dbQuery<{ seller_name: string; count: string | number }>(
      `
      SELECT COALESCE(u.full_name, 'Nao atribuido') AS seller_name, COUNT(*) AS count
      FROM leads l
      LEFT JOIN users u ON l.assigned_to = u.id
      WHERE l.created_at >= CURRENT_DATE - INTERVAL '30 days'
      GROUP BY l.assigned_to, u.full_name
      ORDER BY count DESC
      LIMIT 10
      `,
    );
    additional.leads_by_seller = sellerResult.rows.map((row) => ({
      seller_name: row.seller_name,
      count: toNumber(row.count),
    }));

    const sellersResult = await dbQuery<{ full_name: string; id: number; role: string }>(
      `
      SELECT id, full_name, role
      FROM users
      WHERE status = 'active'
        AND role IN ('seller', 'manager', 'admin')
      ORDER BY full_name ASC
      `,
    );
    additional.sellers = sellersResult.rows.map(
      (row): LeadSellerOption => ({
        full_name: row.full_name,
        id: row.id,
        role: row.role as UserRole,
      }),
    );
  }

  return { additional, stats };
}

export function parseLeadListFilters(searchParams: URLSearchParams): LeadListFilters {
  return {
    assigned_to: parseOptionalPositiveInteger(searchParams.get("assigned_to")),
    date_from: parseOptionalString(searchParams.get("date_from")),
    date_to: parseOptionalString(searchParams.get("date_to")),
    limit: clamp(parseOptionalPositiveInteger(searchParams.get("limit")) ?? 20, 1, 50),
    page: parseOptionalPositiveInteger(searchParams.get("page")) ?? 1,
    priority: parseEnum(searchParams.get("priority"), LeadPriority),
    search: parseOptionalString(searchParams.get("search")),
    source: parseOptionalString(searchParams.get("source")),
    status: parseEnum(searchParams.get("status"), LeadStatus),
  };
}

function buildLeadWhere(filters: LeadListFilters, currentUser: PublicUser) {
  const params: unknown[] = [];
  let sql = "FROM leads l LEFT JOIN users u ON l.assigned_to = u.id WHERE 1=1";

  const scope = buildLeadScopeWhere(currentUser, "l", params);
  sql += scope.sql;

  if (filters.search) {
    params.push(`%${filters.search}%`);
    const index = params.length;
    sql += ` AND (l.name ILIKE $${index} OR l.phone ILIKE $${index} OR l.email ILIKE $${index} OR l.vehicle_interest ILIKE $${index})`;
  }

  if (filters.status) {
    params.push(filters.status);
    sql += ` AND l.status = $${params.length}`;
  }

  if (filters.source) {
    if (filters.source === "Nao informado") {
      sql += " AND (l.source_page IS NULL OR l.source_page = '')";
    } else {
      params.push(filters.source);
      sql += ` AND l.source_page = $${params.length}`;
    }
  }

  if (filters.priority) {
    params.push(filters.priority);
    sql += ` AND l.priority = $${params.length}`;
  }

  if (filters.assigned_to) {
    params.push(filters.assigned_to);
    sql += ` AND l.assigned_to = $${params.length}`;
  }

  if (filters.date_from) {
    params.push(filters.date_from);
    sql += ` AND l.created_at >= $${params.length}`;
  }

  if (filters.date_to) {
    params.push(`${filters.date_to} 23:59:59`);
    sql += ` AND l.created_at <= $${params.length}`;
  }

  return {
    fromAndWhere: sql,
    params,
  };
}

function buildLeadScopeWhere(
  currentUser: Pick<PublicUser, "id" | "role">,
  alias: string,
  params: unknown[],
) {
  if ([UserRole.Admin, UserRole.Manager].includes(currentUser.role)) {
    return {
      params,
      sql: "",
    };
  }

  params.push(currentUser.id);

  return {
    params,
    sql: ` AND (${alias}.assigned_to = $${params.length} OR ${alias}.assigned_to IS NULL)`,
  };
}

function mapLeadRow(row: LeadRow): LeadListItem {
  return {
    id: row.id,
    name: row.name,
    email: row.email,
    phone: row.phone,
    vehicle_interest: row.vehicle_interest,
    has_down_payment: row.has_down_payment,
    down_payment_value: toNullableNumber(row.down_payment_value),
    source_page: row.source_page,
    status: row.status as LeadStatus,
    priority: row.priority as LeadPriority,
    notes: row.notes,
    assigned_to: row.assigned_to,
    assigned_to_name: row.assigned_to_name,
    interactions_count: toNumber(row.interactions_count),
    last_interaction: row.last_interaction,
    sale_id: row.sale_id,
    created_at: row.created_at,
    updated_at: row.updated_at,
    contacted_at: row.contacted_at,
  };
}

function mapLeadDetailsRow(row: LeadDetailsRow): LeadDetailsItem {
  return {
    ...mapLeadRow(row),
    assigned_to_email: row.assigned_to_email,
  };
}

function mapLeadInteractionRow(row: LeadInteractionRow): LeadInteractionItem {
  return {
    id: row.id,
    lead_id: row.lead_id,
    user_id: row.user_id,
    user_name: row.user_name,
    interaction_type: parseEnum(row.interaction_type, InteractionType) ?? InteractionType.Note,
    description: row.description,
    result: parseEnum(row.result, InteractionResult) ?? null,
    next_contact_date: row.next_contact_date,
    created_at: row.created_at,
  };
}

function mapLeadRelatedSaleRow(row: LeadRelatedSaleRow): LeadRelatedSaleItem {
  return {
    id: row.id,
    lead_id: row.lead_id,
    seller_id: row.seller_id,
    seller_name: row.seller_name,
    sale_value: toNullableNumber(row.sale_value),
    commission_value: toNullableNumber(row.commission_value),
    vehicle_sold: row.vehicle_sold,
    status: row.status,
    sale_date: row.sale_date,
    created_at: row.created_at,
  };
}

interface NormalizedLeadMutationInput {
  assigned_to: number | null;
  down_payment_value: number | null;
  email: string | null;
  has_down_payment: DownPaymentFlag | null;
  name: string | null;
  notes: string | null;
  phone: string | null;
  priority: LeadPriority | null;
  source_page: string | null;
  status: LeadStatus | null;
  vehicle_interest: string | null;
}

interface NormalizedLeadInteractionInput {
  description: string | null;
  interaction_type: InteractionType;
  next_contact_date: string | null;
  result: InteractionResult | null;
}

function normalizeLeadMutationInput(
  input: Partial<LeadMutationInput>,
): NormalizedLeadMutationInput {
  return {
    assigned_to: parseNullablePositiveInteger(input.assigned_to),
    down_payment_value: parseNullableNumber(input.down_payment_value),
    email: normalizeNullableString(input.email),
    has_down_payment: parseDownPaymentFlag(input.has_down_payment),
    name: normalizeNullableString(input.name),
    notes: normalizeNullableString(input.notes),
    phone: normalizeNullableString(input.phone),
    priority: parseEnum(normalizeNullableString(input.priority), LeadPriority) ?? null,
    source_page: normalizeNullableString(input.source_page),
    status: parseEnum(normalizeNullableString(input.status), LeadStatus) ?? null,
    vehicle_interest: normalizeNullableString(input.vehicle_interest),
  };
}

function normalizeLeadInteractionInput(
  input: Partial<LeadInteractionMutationInput>,
): NormalizedLeadInteractionInput {
  return {
    description: normalizeNullableString(input.description),
    interaction_type:
      parseEnum(normalizeNullableString(input.interaction_type), InteractionType) ??
      InteractionType.Note,
    next_contact_date: normalizeDateString(input.next_contact_date),
    result: parseEnum(normalizeNullableString(input.result), InteractionResult) ?? null,
  };
}

function normalizePublicLeadCaptureInput(
  input: Partial<PublicLeadCaptureInput>,
  metadata: LeadRequestMetadata,
): PublicLeadCaptureData {
  const name = normalizeNullableString(input.name);
  const phoneInput = normalizeNullableString(input.phone);
  const vehicleInterest =
    normalizeNullableString(input.vehicle) ?? normalizeNullableString(input.vehicle_interest);
  const missingFields: string[] = [];

  if (!name) {
    missingFields.push("name");
  }

  if (!phoneInput) {
    missingFields.push("phone");
  }

  if (!vehicleInterest) {
    missingFields.push("vehicle");
  }

  if (!name || !phoneInput || !vehicleInterest) {
    throw new HttpError(`Campos obrigatorios faltando: ${missingFields.join(", ")}`, 400);
  }

  const phone = normalizePhone(phoneInput);

  if (phone.length < 10 || phone.length > 11) {
    throw new HttpError("Telefone invalido. Use formato: (XX) XXXXX-XXXX", 400);
  }

  const email = normalizeNullableString(input.email);

  if (email && !isValidEmail(email)) {
    throw new HttpError("Email invalido", 400);
  }

  const hasDownPayment =
    parseDownPaymentFlag(
      normalizeNullableString(input.hasDownPayment) ?? normalizeNullableString(input.has_down_payment),
    ) ?? "no";

  return {
    down_payment_value:
      hasDownPayment === "yes"
        ? parseCurrencyLike(input.downPayment ?? input.down_payment_value)
        : null,
    email,
    has_down_payment: hasDownPayment,
    ip_address: metadata.ipAddress ?? null,
    name,
    phone,
    priority: LeadPriority.Medium,
    source_page: normalizeNullableString(input.source) ?? "index",
    user_agent: metadata.userAgent ?? null,
    vehicle_interest: vehicleInterest,
  };
}

async function getLeadForMutation(
  client: PoolClient,
  leadId: number,
): Promise<LeadMutationRow | null> {
  const result = await client.query<LeadMutationRow>(
    `
    SELECT
      id,
      name,
      email,
      phone,
      vehicle_interest,
      has_down_payment,
      down_payment_value,
      source_page,
      status,
      priority,
      notes,
      assigned_to,
      contacted_at
    FROM leads
    WHERE id = $1
    LIMIT 1
    `,
    [leadId],
  );

  return result.rows[0] ?? null;
}

async function getLeadInteractionById(interactionId: number): Promise<LeadInteractionItem> {
  const result = await dbQuery<LeadInteractionRow>(
    `
    SELECT
      i.id,
      i.lead_id,
      i.user_id,
      u.full_name AS user_name,
      i.interaction_type,
      i.description,
      i.result,
      i.next_contact_date,
      i.created_at
    FROM lead_interactions i
    LEFT JOIN users u ON i.user_id = u.id
    WHERE i.id = $1
    LIMIT 1
    `,
    [interactionId],
  );

  const interaction = result.rows[0];

  if (!interaction) {
    throw new HttpError("Interacao nao encontrada apos criacao", 500);
  }

  return mapLeadInteractionRow(interaction);
}

async function assertUniqueLeadPhone(client: PoolClient, phone: string): Promise<void> {
  const result = await client.query<{ id: number }>(
    "SELECT id FROM leads WHERE phone = $1 LIMIT 1",
    [phone],
  );

  if (result.rows[0]) {
    throw new HttpError("Ja existe um lead cadastrado com este telefone", 409);
  }
}

async function assertActiveAssignee(client: PoolClient, userId: number): Promise<void> {
  const result = await client.query<{ id: number }>(
    `
    SELECT id
    FROM users
    WHERE id = $1
      AND status = 'active'
      AND role IN ('seller', 'manager', 'admin')
    LIMIT 1
    `,
    [userId],
  );

  if (!result.rows[0]) {
    throw new HttpError("Vendedor responsavel nao encontrado", 400);
  }
}

async function insertLeadInteraction(
  client: PoolClient,
  leadId: number,
  userId: number | null,
  description: string,
): Promise<void> {
  await client.query(
    `
    INSERT INTO lead_interactions (
      lead_id, user_id, interaction_type, description, created_at
    ) VALUES ($1, $2, 'note', $3, NOW())
    `,
    [leadId, userId, description],
  );
}

function hasLeadWriteAccess(currentUser: Pick<PublicUser, "role">): boolean {
  return [UserRole.Admin, UserRole.Manager, UserRole.Seller].includes(currentUser.role);
}

function canManageLeads(currentUser: Pick<PublicUser, "role">): boolean {
  return [UserRole.Admin, UserRole.Manager].includes(currentUser.role);
}

function canReadLead(currentUser: Pick<PublicUser, "id" | "role">, assignedTo: number | null) {
  return canManageLeads(currentUser) || assignedTo === null || assignedTo === currentUser.id;
}

function canEditLead(currentUser: Pick<PublicUser, "id" | "role">, assignedTo: number | null) {
  return canManageLeads(currentUser) || assignedTo === currentUser.id;
}

function validateRequiredString(value: string | null, label: string): asserts value is string {
  if (!value?.trim()) {
    throw new HttpError(`${label} e obrigatorio`, 400);
  }
}

function normalizeNullableString(value: unknown): string | null {
  if (typeof value !== "string") {
    return null;
  }

  const trimmed = value.trim();
  return trimmed ? trimmed : null;
}

function normalizeDateString(value: unknown): string | null {
  const normalized = normalizeNullableString(value);

  if (!normalized) {
    return null;
  }

  return /^\d{4}-\d{2}-\d{2}$/.test(normalized) ? normalized : null;
}

function parseNullableNumber(value: unknown): number | null {
  if (value === null || value === undefined || value === "") {
    return null;
  }

  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : null;
}

function parseNullablePositiveInteger(value: unknown): number | null {
  const parsed = parseNullableNumber(value);

  if (parsed === null || !Number.isInteger(parsed) || parsed < 1) {
    return null;
  }

  return parsed;
}

function parseDownPaymentFlag(value: unknown): DownPaymentFlag | null {
  return value === "yes" || value === "no" ? value : null;
}

function normalizePhone(value: string): string {
  return value.replace(/\D/g, "");
}

function isValidEmail(value: string): boolean {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
}

function parseCurrencyLike(value: unknown): number | null {
  if (value === null || value === undefined || value === "") {
    return null;
  }

  if (typeof value === "number") {
    return Number.isFinite(value) ? value : null;
  }

  if (typeof value !== "string") {
    return null;
  }

  const sanitized = value.replace(/[^\d,.]/g, "");

  if (!sanitized) {
    return null;
  }

  const normalized = sanitized.includes(",")
    ? sanitized.replace(/\./g, "").replace(",", ".")
    : sanitized;
  const parsed = Number(normalized);

  return Number.isFinite(parsed) ? parsed : null;
}

function buildLeadWhatsAppUrl(leadData: PublicLeadCaptureData): string {
  const phone = "5547996862997";
  const message = [
    "Ola! Vim do site da Hype Consorcios e tenho interesse em:",
    "",
    `Veiculo: ${leadData.vehicle_interest}`,
    `Nome: ${leadData.name}`,
    `Telefone: ${formatPhoneForDisplay(leadData.phone)}`,
  ];

  if (leadData.email) {
    message.push(`Email: ${leadData.email}`);
  }

  if (leadData.has_down_payment === "yes" && leadData.down_payment_value) {
    message.push(`Entrada disponivel: ${formatCurrencyForDisplay(leadData.down_payment_value)}`);
  } else {
    message.push("Entrada: Nao tenho entrada disponivel");
  }

  message.push("", "Poderia me ajudar com mais informacoes sobre o consorcio?");

  return `https://api.whatsapp.com/send/?phone=${phone}&text=${encodeURIComponent(
    message.join("\n"),
  )}`;
}

function formatPhoneForDisplay(value: string): string {
  const phone = normalizePhone(value);

  if (phone.length === 11) {
    return `(${phone.slice(0, 2)}) ${phone.slice(2, 7)}-${phone.slice(7)}`;
  }

  if (phone.length === 10) {
    return `(${phone.slice(0, 2)}) ${phone.slice(2, 6)}-${phone.slice(6)}`;
  }

  return phone;
}

function formatBrazilWhatsAppRecipient(value: string): string {
  const phone = normalizePhone(value);

  if (phone.length < 10) {
    throw new HttpError("Telefone do lead invalido para WhatsApp", 400);
  }

  if (phone.startsWith("55") && phone.length >= 12) {
    return phone;
  }

  return `55${phone}`;
}

function formatCurrencyForDisplay(value: number): string {
  return new Intl.NumberFormat("pt-BR", {
    currency: "BRL",
    style: "currency",
  }).format(value);
}

function hasOwn<T extends object>(input: T, key: keyof T): boolean {
  return Object.prototype.hasOwnProperty.call(input, key);
}

function normalizeComparableValue(value: unknown): string {
  if (value === null || value === undefined || value === "") {
    return "";
  }

  if (typeof value === "number" || typeof value === "bigint") {
    return String(Number(value));
  }

  return String(value);
}

function formatValueForLog(value: unknown): string {
  const comparable = normalizeComparableValue(value);
  return comparable || "vazio";
}

function parseOptionalPositiveInteger(value: string | null): number | undefined {
  if (!value) {
    return undefined;
  }

  const parsed = Number(value);
  return Number.isInteger(parsed) && parsed > 0 ? parsed : undefined;
}

function parseOptionalString(value: string | null): string | undefined {
  const trimmed = value?.trim();
  return trimmed ? trimmed : undefined;
}

function parseEnum<T extends Record<string, string>>(
  value: string | null,
  enumObject: T,
): T[keyof T] | undefined {
  if (!value) {
    return undefined;
  }

  const values = Object.values(enumObject);
  return values.includes(value) ? (value as T[keyof T]) : undefined;
}

function clamp(value: number, min: number, max: number): number {
  return Math.min(Math.max(value, min), max);
}

function toNumber(value: string | number | null | undefined): number {
  if (value === null || value === undefined) {
    return 0;
  }

  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
}

function toNullableNumber(value: string | number | null): number | null {
  if (value === null) {
    return null;
  }

  return toNumber(value);
}
