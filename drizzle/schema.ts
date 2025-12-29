import { int, mysqlEnum, mysqlTable, text, timestamp, varchar } from "drizzle-orm/mysql-core";

/**
 * Core user table backing auth flow.
 */
export const users = mysqlTable("users", {
  id: int("id").autoincrement().primaryKey(),
  openId: varchar("openId", { length: 64 }).notNull().unique(),
  name: text("name"),
  email: varchar("email", { length: 320 }),
  loginMethod: varchar("loginMethod", { length: 64 }),
  role: mysqlEnum("role", ["user", "admin"]).default("user").notNull(),
  createdAt: timestamp("createdAt").defaultNow().notNull(),
  updatedAt: timestamp("updatedAt").defaultNow().onUpdateNow().notNull(),
  lastSignedIn: timestamp("lastSignedIn").defaultNow().notNull(),
});

export type User = typeof users.$inferSelect;
export type InsertUser = typeof users.$inferInsert;

/**
 * Comics table - 蔵書情報を管理するメインテーブル
 */
export const comics = mysqlTable("comics", {
  id: int("id").autoincrement().primaryKey(),
  userId: int("userId").notNull(),
  isbn: varchar("isbn", { length: 20 }).notNull().unique(),
  title: text("title").notNull(),
  author: text("author"),
  publisher: text("publisher"),
  series: text("series"),
  volume: int("volume"),
  imageUrl: text("imageUrl"),
  imageData: text("imageData"), // Base64エンコードされた画像データ
  isRead: int("isRead").default(0).notNull(),
  createdAt: timestamp("createdAt").defaultNow().notNull(),
  updatedAt: timestamp("updatedAt").defaultNow().onUpdateNow().notNull(),
});

export type Comic = typeof comics.$inferSelect;
export type InsertComic = typeof comics.$inferInsert;

/**
 * New Releases table - 新刊情報を管理するテーブル
 */
export const newReleases = mysqlTable("newReleases", {
  id: int("id").autoincrement().primaryKey(),
  isbn: varchar("isbn", { length: 20 }).notNull().unique(),
  title: text("title").notNull(),
  author: text("author"),
  publisher: text("publisher"),
  series: text("series"),
  imageUrl: text("imageUrl"),
  releaseDate: timestamp("releaseDate"),
  purchased: int("purchased").default(0).notNull(), // 0: 未購入, 1: 購入済み
  userId: int("userId").notNull(),
  createdAt: timestamp("createdAt").defaultNow().notNull(),
});

export type NewRelease = typeof newReleases.$inferSelect;
export type InsertNewRelease = typeof newReleases.$inferInsert;
