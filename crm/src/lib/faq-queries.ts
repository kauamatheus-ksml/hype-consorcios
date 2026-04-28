import "server-only";

import type { PoolClient } from "pg";

import { dbQuery, dbTransaction } from "@/lib/db-direct";
import { HttpError } from "@/lib/http";
import { UserRole, type PublicUser } from "@/types";
import type { FAQItem, FAQMutationInput } from "@/types/site-config";

interface FAQRow {
  answer: string;
  created_at: string | null;
  display_order: string | number | null;
  id: number;
  is_active: boolean | number | string | null;
  question: string;
  updated_at: string | null;
}

interface NormalizedFAQInput {
  answer: string | null;
  display_order: number | null;
  is_active: boolean;
  question: string | null;
}

export async function getFaqList(currentUser: PublicUser): Promise<FAQItem[]> {
  assertCanManageFaqs(currentUser);

  const result = await dbQuery<FAQRow>(
    `
    SELECT id, question, answer, display_order, is_active, created_at, updated_at
    FROM faqs
    ORDER BY display_order ASC, id ASC
    `,
  );

  return result.rows.map(mapFaqRow);
}

export async function createFaq(
  currentUser: PublicUser,
  rawInput: Partial<FAQMutationInput>,
): Promise<FAQItem> {
  assertCanManageFaqs(currentUser);
  const input = normalizeFaqInput(rawInput);
  validateRequiredString(input.question, "Pergunta");
  validateRequiredString(input.answer, "Resposta");

  const faqId = await dbTransaction(async (client) => {
    const displayOrder = await resolveDisplayOrderForCreate(client, input.display_order);

    if (input.display_order && input.display_order > 0) {
      await client.query(
        "UPDATE faqs SET display_order = display_order + 1 WHERE display_order >= $1",
        [displayOrder],
      );
    }

    const result = await client.query<{ id: number }>(
      `
      INSERT INTO faqs (
        question, answer, display_order, is_active, created_at, updated_at
      ) VALUES ($1, $2, $3, $4, NOW(), NOW())
      RETURNING id
      `,
      [input.question, input.answer, displayOrder, input.is_active],
    );

    await normalizeFaqOrder(client);
    return result.rows[0].id;
  });

  return getFaqById(faqId);
}

export async function updateFaq(
  currentUser: PublicUser,
  faqId: number,
  rawInput: Partial<FAQMutationInput>,
): Promise<FAQItem> {
  assertCanManageFaqs(currentUser);
  const input = normalizeFaqInput(rawInput);
  validateRequiredString(input.question, "Pergunta");
  validateRequiredString(input.answer, "Resposta");

  await dbTransaction(async (client) => {
    const existing = await client.query<{ display_order: string | number | null; id: number }>(
      "SELECT id, display_order FROM faqs WHERE id = $1 LIMIT 1",
      [faqId],
    );

    if (!existing.rows[0]) {
      throw new HttpError("FAQ nao encontrada", 404);
    }

    const currentOrder = toNumber(existing.rows[0].display_order);
    const displayOrder = input.display_order ?? (currentOrder || 1);

    if (displayOrder !== currentOrder) {
      if (displayOrder > currentOrder) {
        await client.query(
          `
          UPDATE faqs
          SET display_order = display_order - 1
          WHERE display_order > $1
            AND display_order <= $2
            AND id != $3
          `,
          [currentOrder, displayOrder, faqId],
        );
      } else {
        await client.query(
          `
          UPDATE faqs
          SET display_order = display_order + 1
          WHERE display_order >= $1
            AND display_order < $2
            AND id != $3
          `,
          [displayOrder, currentOrder, faqId],
        );
      }
    }

    await client.query(
      `
      UPDATE faqs
      SET question = $1, answer = $2, display_order = $3, is_active = $4, updated_at = NOW()
      WHERE id = $5
      `,
      [input.question, input.answer, displayOrder, input.is_active, faqId],
    );

    await normalizeFaqOrder(client);
  });

  return getFaqById(faqId);
}

export async function deleteFaq(currentUser: PublicUser, faqId: number): Promise<void> {
  assertCanManageFaqs(currentUser);

  await dbTransaction(async (client) => {
    const existing = await client.query<{ display_order: string | number | null; id: number }>(
      "SELECT id, display_order FROM faqs WHERE id = $1 LIMIT 1",
      [faqId],
    );

    if (!existing.rows[0]) {
      throw new HttpError("FAQ nao encontrada", 404);
    }

    const deletedOrder = toNumber(existing.rows[0].display_order);
    await client.query("DELETE FROM faqs WHERE id = $1", [faqId]);
    await client.query("UPDATE faqs SET display_order = display_order - 1 WHERE display_order > $1", [
      deletedOrder,
    ]);
    await normalizeFaqOrder(client);
  });
}

export async function reorderFaqs(
  currentUser: PublicUser,
  rawIds: unknown,
): Promise<FAQItem[]> {
  assertCanManageFaqs(currentUser);

  if (!Array.isArray(rawIds) || rawIds.length === 0) {
    throw new HttpError("Informe a ordem das FAQs", 400);
  }

  const ids = rawIds.map(parseRequiredPositiveInteger);
  const uniqueIds = [...new Set(ids)];

  if (uniqueIds.length !== ids.length) {
    throw new HttpError("A ordem das FAQs possui IDs duplicados", 400);
  }

  await dbTransaction(async (client) => {
    const existing = await client.query<{ id: number }>(
      "SELECT id FROM faqs WHERE id = ANY($1::int[])",
      [uniqueIds],
    );

    if (existing.rows.length !== uniqueIds.length) {
      throw new HttpError("Uma ou mais FAQs nao foram encontradas", 404);
    }

    for (const [index, id] of uniqueIds.entries()) {
      await client.query("UPDATE faqs SET display_order = $1, updated_at = NOW() WHERE id = $2", [
        index + 1,
        id,
      ]);
    }

    await normalizeFaqOrder(client);
  });

  return getFaqList(currentUser);
}

async function getFaqById(faqId: number): Promise<FAQItem> {
  const result = await dbQuery<FAQRow>(
    `
    SELECT id, question, answer, display_order, is_active, created_at, updated_at
    FROM faqs
    WHERE id = $1
    LIMIT 1
    `,
    [faqId],
  );

  const faq = result.rows[0];

  if (!faq) {
    throw new HttpError("FAQ nao encontrada", 404);
  }

  return mapFaqRow(faq);
}

async function resolveDisplayOrderForCreate(
  client: PoolClient,
  requestedOrder: number | null,
): Promise<number> {
  if (requestedOrder && requestedOrder > 0) {
    return requestedOrder;
  }

  const result = await client.query<{ max_order: string | number | null }>(
    "SELECT MAX(display_order) AS max_order FROM faqs",
  );

  return toNumber(result.rows[0]?.max_order) + 1;
}

async function normalizeFaqOrder(client: PoolClient): Promise<void> {
  const result = await client.query<{ id: number }>(
    "SELECT id FROM faqs ORDER BY display_order ASC, id ASC",
  );
  let order = 1;

  for (const row of result.rows) {
    await client.query("UPDATE faqs SET display_order = $1 WHERE id = $2", [order, row.id]);
    order += 1;
  }
}

function normalizeFaqInput(input: Partial<FAQMutationInput>): NormalizedFAQInput {
  return {
    answer: normalizeNullableString(input.answer),
    display_order: parseNullablePositiveInteger(input.display_order),
    is_active: parseBoolean(input.is_active, true),
    question: normalizeNullableString(input.question),
  };
}

function assertCanManageFaqs(currentUser: Pick<PublicUser, "role">): void {
  if (currentUser.role !== UserRole.Admin) {
    throw new HttpError("Apenas administradores podem gerenciar FAQs", 403);
  }
}

function mapFaqRow(row: FAQRow): FAQItem {
  return {
    answer: row.answer,
    created_at: row.created_at,
    display_order: toNumber(row.display_order),
    id: row.id,
    is_active: parseBoolean(row.is_active, false),
    question: row.question,
    updated_at: row.updated_at,
  };
}

function validateRequiredString(value: string | null, label: string): asserts value is string {
  if (!value?.trim()) {
    throw new HttpError(`${label} e obrigatoria`, 400);
  }
}

function normalizeNullableString(value: unknown): string | null {
  if (typeof value !== "string") {
    return null;
  }

  const trimmed = value.trim();
  return trimmed ? trimmed : null;
}

function parseNullablePositiveInteger(value: unknown): number | null {
  if (value === null || value === undefined || value === "") {
    return null;
  }

  const parsed = Number(value);
  return Number.isInteger(parsed) && parsed > 0 ? parsed : null;
}

function parseRequiredPositiveInteger(value: unknown): number {
  const parsed = Number(value);

  if (!Number.isInteger(parsed) || parsed < 1) {
    throw new HttpError("ID de FAQ invalido", 400);
  }

  return parsed;
}

function parseBoolean(value: unknown, defaultValue: boolean): boolean {
  if (value === true || value === 1 || value === "1" || value === "true" || value === "on") {
    return true;
  }

  if (value === false || value === 0 || value === "0" || value === "false" || value === "off") {
    return false;
  }

  return defaultValue;
}

function toNumber(value: string | number | null | undefined): number {
  if (value === null || value === undefined) {
    return 0;
  }

  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
}
