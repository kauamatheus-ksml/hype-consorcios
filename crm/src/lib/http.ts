import { NextResponse } from "next/server";

export class HttpError extends Error {
  constructor(
    message: string,
    public readonly status = 400,
  ) {
    super(message);
    this.name = "HttpError";
  }
}

export function apiSuccess<T>(payload: T, status = 200): NextResponse<T & { success: true }> {
  return NextResponse.json(
    {
      success: true,
      ...payload,
    },
    { status },
  );
}

export function apiError(
  error: unknown,
  fallbackMessage = "Erro interno do servidor",
): NextResponse<{ success: false; message: string }> {
  if (error instanceof HttpError) {
    return NextResponse.json(
      {
        success: false,
        message: error.message,
      },
      { status: error.status },
    );
  }

  console.error("Unhandled API error", error);

  return NextResponse.json(
    {
      success: false,
      message: fallbackMessage,
    },
    { status: 500 },
  );
}

export async function readJsonBody<T>(request: Request): Promise<Partial<T>> {
  try {
    return (await request.json()) as Partial<T>;
  } catch {
    return {};
  }
}
