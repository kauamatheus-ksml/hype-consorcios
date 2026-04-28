export const APP_TIME_ZONE = "America/Sao_Paulo";

const dateOnlyPattern = /^\d{4}-\d{2}-\d{2}$/;

export function currentBrazilDateString(): string {
  const parts = new Intl.DateTimeFormat("en-CA", {
    day: "2-digit",
    month: "2-digit",
    timeZone: APP_TIME_ZONE,
    year: "numeric",
  }).formatToParts(new Date());
  const values = Object.fromEntries(parts.map((part) => [part.type, part.value]));

  return `${values.year}-${values.month}-${values.day}`;
}

export function currentBrazilYear(): number {
  return Number(currentBrazilDateString().slice(0, 4));
}

export function parseDateForDisplay(value: string): Date {
  if (dateOnlyPattern.test(value)) {
    return new Date(`${value}T12:00:00-03:00`);
  }

  return new Date(value);
}

export function formatBrazilDate(value: string): string {
  return new Intl.DateTimeFormat("pt-BR", {
    dateStyle: "short",
    timeZone: APP_TIME_ZONE,
  }).format(parseDateForDisplay(value));
}

export function formatBrazilDateTime(
  value: string,
  timeStyle: "short" | "medium" = "short",
): string {
  return new Intl.DateTimeFormat("pt-BR", {
    dateStyle: "short",
    timeStyle,
    timeZone: APP_TIME_ZONE,
  }).format(parseDateForDisplay(value));
}

export function formatBrazilTime(value: string): string {
  return new Intl.DateTimeFormat("pt-BR", {
    hour: "2-digit",
    minute: "2-digit",
    timeZone: APP_TIME_ZONE,
  }).format(parseDateForDisplay(value));
}
