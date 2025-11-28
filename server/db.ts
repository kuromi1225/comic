import { eq, and, like, or, desc } from "drizzle-orm";
import { drizzle } from "drizzle-orm/mysql2";
import { InsertUser, users, comics, InsertComic, Comic, newReleases, InsertNewRelease, NewRelease } from "../drizzle/schema";
import { ENV } from './_core/env';

let _db: ReturnType<typeof drizzle> | null = null;

export async function getDb() {
  if (!_db && process.env.DATABASE_URL) {
    try {
      _db = drizzle(process.env.DATABASE_URL);
    } catch (error) {
      console.warn("[Database] Failed to connect:", error);
      _db = null;
    }
  }
  return _db;
}

export async function upsertUser(user: InsertUser): Promise<void> {
  if (!user.openId) {
    throw new Error("User openId is required for upsert");
  }

  const db = await getDb();
  if (!db) {
    console.warn("[Database] Cannot upsert user: database not available");
    return;
  }

  try {
    const values: InsertUser = {
      openId: user.openId,
    };
    const updateSet: Record<string, unknown> = {};

    const textFields = ["name", "email", "loginMethod"] as const;
    type TextField = (typeof textFields)[number];

    const assignNullable = (field: TextField) => {
      const value = user[field];
      if (value === undefined) return;
      const normalized = value ?? null;
      values[field] = normalized;
      updateSet[field] = normalized;
    };

    textFields.forEach(assignNullable);

    if (user.lastSignedIn !== undefined) {
      values.lastSignedIn = user.lastSignedIn;
      updateSet.lastSignedIn = user.lastSignedIn;
    }
    if (user.role !== undefined) {
      values.role = user.role;
      updateSet.role = user.role;
    } else if (user.openId === ENV.ownerOpenId) {
      values.role = 'admin';
      updateSet.role = 'admin';
    }

    if (!values.lastSignedIn) {
      values.lastSignedIn = new Date();
    }

    if (Object.keys(updateSet).length === 0) {
      updateSet.lastSignedIn = new Date();
    }

    await db.insert(users).values(values).onDuplicateKeyUpdate({
      set: updateSet,
    });
  } catch (error) {
    console.error("[Database] Failed to upsert user:", error);
    throw error;
  }
}

export async function getUserByOpenId(openId: string) {
  const db = await getDb();
  if (!db) {
    console.warn("[Database] Cannot get user: database not available");
    return undefined;
  }

  const result = await db.select().from(users).where(eq(users.openId, openId)).limit(1);

  return result.length > 0 ? result[0] : undefined;
}

// Comics queries
export async function createComic(comic: InsertComic): Promise<Comic> {
  const db = await getDb();
  if (!db) throw new Error("Database not available");
  
  await db.insert(comics).values(comic);
  const [inserted] = await db.select().from(comics)
    .where(and(eq(comics.isbn, comic.isbn), eq(comics.userId, comic.userId)))
    .limit(1);
  if (!inserted) throw new Error("Failed to create comic");
  return inserted;
}

export async function getComicsByUserId(userId: number, filters?: {
  search?: string;
  status?: "unread" | "read";
  sortBy?: "createdAt" | "title" | "author";
  limit?: number;
  offset?: number;
}): Promise<Comic[]> {
  const db = await getDb();
  if (!db) return [];

  // Apply filters
  const conditions = [eq(comics.userId, userId)];
  
  if (filters?.search) {
    conditions.push(
      or(
        like(comics.title, `%${filters.search}%`),
        like(comics.author, `%${filters.search}%`),
        like(comics.isbn, `%${filters.search}%`)
      )!
    );
  }
  
  if (filters?.status) {
    conditions.push(eq(comics.status, filters.status));
  }

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  let baseQuery: any = db.select().from(comics).where(and(...conditions));

  // Apply sorting
  if (filters?.sortBy === "title") {
    baseQuery = baseQuery.orderBy(comics.title);
  } else if (filters?.sortBy === "author") {
    baseQuery = baseQuery.orderBy(comics.author);
  } else {
    baseQuery = baseQuery.orderBy(desc(comics.createdAt));
  }

  // Apply pagination
  if (filters?.limit !== undefined) {
    baseQuery = baseQuery.limit(filters.limit);
    if (filters?.offset !== undefined) {
      baseQuery = baseQuery.offset(filters.offset);
    }
  }

  return await baseQuery;
}

export async function getComicByIsbn(isbn: string, userId: number): Promise<Comic | undefined> {
  const db = await getDb();
  if (!db) return undefined;

  const result = await db.select().from(comics)
    .where(and(eq(comics.isbn, isbn), eq(comics.userId, userId)))
    .limit(1);
  
  return result[0];
}

export async function updateComicStatus(id: number, userId: number, status: "unread" | "read"): Promise<void> {
  const db = await getDb();
  if (!db) throw new Error("Database not available");

  await db.update(comics)
    .set({ status, updatedAt: new Date() })
    .where(and(eq(comics.id, id), eq(comics.userId, userId)));
}

export async function deleteComic(id: number, userId: number): Promise<void> {
  const db = await getDb();
  if (!db) throw new Error("Database not available");

  await db.delete(comics).where(and(eq(comics.id, id), eq(comics.userId, userId)));
}

export async function getComicsStats(userId: number): Promise<{
  total: number;
  unread: number;
  read: number;
}> {
  const db = await getDb();
  if (!db) return { total: 0, unread: 0, read: 0 };

  const allComics = await db.select().from(comics).where(eq(comics.userId, userId));
  
  return {
    total: allComics.length,
    unread: allComics.filter(c => c.status === "unread").length,
    read: allComics.filter(c => c.status === "read").length,
  };
}

export async function getRecentComics(userId: number, limit: number = 5): Promise<Comic[]> {
  const db = await getDb();
  if (!db) return [];

  return await db.select().from(comics)
    .where(eq(comics.userId, userId))
    .orderBy(desc(comics.createdAt))
    .limit(limit);
}

// New Releases queries
export async function createNewRelease(release: InsertNewRelease): Promise<NewRelease> {
  const db = await getDb();
  if (!db) throw new Error("Database not available");
  
  await db.insert(newReleases).values(release);
  const [inserted] = await db.select().from(newReleases)
    .where(and(eq(newReleases.isbn, release.isbn), eq(newReleases.userId, release.userId)))
    .limit(1);
  if (!inserted) throw new Error("Failed to create new release");
  return inserted;
}

export async function getNewReleasesByUserId(userId: number, purchasedOnly: boolean = false): Promise<NewRelease[]> {
  const db = await getDb();
  if (!db) return [];

  const conditions = [eq(newReleases.userId, userId)];
  if (!purchasedOnly) {
    conditions.push(eq(newReleases.purchased, 0));
  }

  return await db.select().from(newReleases)
    .where(and(...conditions))
    .orderBy(desc(newReleases.releaseDate));
}

export async function markNewReleaseAsPurchased(id: number, userId: number): Promise<void> {
  const db = await getDb();
  if (!db) throw new Error("Database not available");

  await db.update(newReleases)
    .set({ purchased: 1 })
    .where(and(eq(newReleases.id, id), eq(newReleases.userId, userId)));
}

export async function getUnpurchasedCount(userId: number): Promise<number> {
  const db = await getDb();
  if (!db) return 0;

  const result = await db.select().from(newReleases)
    .where(and(eq(newReleases.userId, userId), eq(newReleases.purchased, 0)));
  
  return result.length;
}
