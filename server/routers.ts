import { COOKIE_NAME } from "@shared/const";
import { getSessionCookieOptions } from "./_core/cookies";
import { systemRouter } from "./_core/systemRouter";
import { publicProcedure, protectedProcedure, router } from "./_core/trpc";
import { z } from "zod";
import * as db from "./db";
import { fetchBookInfo } from "./bookApi";

export const appRouter = router({
  system: systemRouter,
  auth: router({
    me: publicProcedure.query(opts => opts.ctx.user),
    logout: publicProcedure.mutation(({ ctx }) => {
      const cookieOptions = getSessionCookieOptions(ctx.req);
      ctx.res.clearCookie(COOKIE_NAME, { ...cookieOptions, maxAge: -1 });
      return {
        success: true,
      } as const;
    }),
  }),

  comics: router({
    // 蔵書一覧取得
    list: protectedProcedure
      .input(z.object({
        search: z.string().optional(),
        status: z.enum(["unread", "read"]).optional(),
        sortBy: z.enum(["createdAt", "title", "author"]).optional(),
        limit: z.number().optional(),
        offset: z.number().optional(),
      }).optional())
      .query(async ({ ctx, input }) => {
        return await db.getComicsByUserId(ctx.user.id, input);
      }),

    // 蔵書統計取得
    stats: protectedProcedure.query(async ({ ctx }) => {
      return await db.getComicsStats(ctx.user.id);
    }),

    // 最近登録した蔵書取得
    recent: protectedProcedure
      .input(z.object({ limit: z.number().optional() }).optional())
      .query(async ({ ctx, input }) => {
        return await db.getRecentComics(ctx.user.id, input?.limit);
      }),

    // 蔵書登録
    create: protectedProcedure
      .input(z.object({
        isbn: z.string(),
        title: z.string(),
        author: z.string().optional(),
        publisher: z.string().optional(),
        series: z.string().optional(),
        imageUrl: z.string().optional(),
        status: z.enum(["unread", "read"]).optional(),
      }))
      .mutation(async ({ ctx, input }) => {
        return await db.createComic({
          ...input,
          userId: ctx.user.id,
        });
      }),

    // 蔵書ステータス更新
    updateStatus: protectedProcedure
      .input(z.object({
        id: z.number(),
        status: z.enum(["unread", "read"]),
      }))
      .mutation(async ({ ctx, input }) => {
        await db.updateComicStatus(input.id, ctx.user.id, input.status);
        return { success: true };
      }),

    // 蔵書削除
    delete: protectedProcedure
      .input(z.object({ id: z.number() }))
      .mutation(async ({ ctx, input }) => {
        await db.deleteComic(input.id, ctx.user.id);
        return { success: true };
      }),

    // ISBNから蔵書情報取得
    getByIsbn: protectedProcedure
      .input(z.object({ isbn: z.string() }))
      .query(async ({ ctx, input }) => {
        return await db.getComicByIsbn(input.isbn, ctx.user.id);
      }),

    // ISBNから書誌情報を外部APIで取得
    fetchBookInfo: protectedProcedure
      .input(z.object({ isbn: z.string() }))
      .query(async ({ input }) => {
        return await fetchBookInfo(input.isbn);
      }),
  }),

  newReleases: router({
    // 新刊一覧取得
    list: protectedProcedure.query(async ({ ctx }) => {
      return await db.getNewReleasesByUserId(ctx.user.id);
    }),

    // 未購入新刊件数取得
    unpurchasedCount: protectedProcedure.query(async ({ ctx }) => {
      return await db.getUnpurchasedCount(ctx.user.id);
    }),

    // 新刊を購入済みにマーク
    markAsPurchased: protectedProcedure
      .input(z.object({ id: z.number() }))
      .mutation(async ({ ctx, input }) => {
        await db.markNewReleaseAsPurchased(input.id, ctx.user.id);
        return { success: true };
      }),

    // 新刊登録
    create: protectedProcedure
      .input(z.object({
        isbn: z.string(),
        title: z.string(),
        author: z.string().optional(),
        publisher: z.string().optional(),
        series: z.string().optional(),
        imageUrl: z.string().optional(),
        releaseDate: z.date().optional(),
      }))
      .mutation(async ({ ctx, input }) => {
        return await db.createNewRelease({
          ...input,
          userId: ctx.user.id,
        });
      }),
  }),
});

export type AppRouter = typeof appRouter;
