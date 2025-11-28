import { describe, expect, it } from "vitest";
import { appRouter } from "./routers";
import type { TrpcContext } from "./_core/context";

type AuthenticatedUser = NonNullable<TrpcContext["user"]>;

function createAuthContext(): TrpcContext {
  const user: AuthenticatedUser = {
    id: 1,
    openId: "test-user",
    email: "test@example.com",
    name: "Test User",
    loginMethod: "manus",
    role: "user",
    createdAt: new Date(),
    updatedAt: new Date(),
    lastSignedIn: new Date(),
  };

  const ctx: TrpcContext = {
    user,
    req: {
      protocol: "https",
      headers: {},
    } as TrpcContext["req"],
    res: {
      clearCookie: () => {},
    } as TrpcContext["res"],
  };

  return ctx;
}

describe("comics router", () => {
  it("should return empty stats for new user", async () => {
    const ctx = createAuthContext();
    const caller = appRouter.createCaller(ctx);

    const stats = await caller.comics.stats();

    expect(stats).toEqual({
      total: 0,
      unread: 0,
      read: 0,
    });
  });

  it("should return empty list for new user", async () => {
    const ctx = createAuthContext();
    const caller = appRouter.createCaller(ctx);

    const comics = await caller.comics.list();

    expect(comics).toEqual([]);
  });

  it("should create a comic", async () => {
    const ctx = createAuthContext();
    const caller = appRouter.createCaller(ctx);

    const comic = await caller.comics.create({
      isbn: "9784065123456",
      title: "テスト漫画",
      author: "テスト著者",
      publisher: "テスト出版社",
    });

    expect(comic).toBeDefined();
    expect(comic.isbn).toBe("9784065123456");
    expect(comic.title).toBe("テスト漫画");
    expect(comic.status).toBe("unread");
  });
});
