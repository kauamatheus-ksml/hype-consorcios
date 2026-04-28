"use client";

import { create } from "zustand";

import type { PublicUser } from "@/types";

interface AuthState {
  user: PublicUser | null;
  setUser: (user: PublicUser | null) => void;
  clearUser: () => void;
}

export const useAuthStore = create<AuthState>((set) => ({
  user: null,
  setUser: (user) => set({ user }),
  clearUser: () => set({ user: null }),
}));
