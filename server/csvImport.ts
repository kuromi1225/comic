import { fetchBookInfo } from "./bookApi";
import * as db from "./db";

export interface ImportProgress {
  total: number;
  processed: number;
  success: number;
  failed: number;
  errors: Array<{ isbn: string; error: string }>;
}

export interface ImportJob {
  id: string;
  userId: number;
  status: "processing" | "completed" | "failed";
  progress: ImportProgress;
  startedAt: Date;
  completedAt?: Date;
}

// インメモリでジョブを管理（本番環境ではRedisなどを使用）
const jobs = new Map<string, ImportJob>();

/**
 * CSVファイルからISBNリストを解析
 */
export function parseCSV(csvContent: string): string[] {
  const lines = csvContent.split(/\r?\n/).filter(line => line.trim());
  const isbns: string[] = [];

  for (const line of lines) {
    // カンマ区切りの場合は最初の列をISBNとして扱う
    const columns = line.split(",").map(col => col.trim());
    const isbn = columns[0];

    // ISBNの簡易バリデーション（数字とハイフンのみ）
    if (isbn && /^[\d-]+$/.test(isbn)) {
      // ハイフンを除去して正規化
      const normalizedIsbn = isbn.replace(/-/g, "");
      if (normalizedIsbn.length === 10 || normalizedIsbn.length === 13) {
        isbns.push(normalizedIsbn);
      }
    }
  }

  return isbns;
}

/**
 * CSV一括登録ジョブを開始
 */
export async function startImportJob(
  jobId: string,
  userId: number,
  isbns: string[]
): Promise<void> {
  const job: ImportJob = {
    id: jobId,
    userId,
    status: "processing",
    progress: {
      total: isbns.length,
      processed: 0,
      success: 0,
      failed: 0,
      errors: [],
    },
    startedAt: new Date(),
  };

  jobs.set(jobId, job);

  // バックグラウンドで処理を実行
  processImportJob(jobId, userId, isbns).catch((error) => {
    console.error("Import job failed:", error);
    const currentJob = jobs.get(jobId);
    if (currentJob) {
      currentJob.status = "failed";
      currentJob.completedAt = new Date();
    }
  });
}

/**
 * インポートジョブの処理
 */
async function processImportJob(
  jobId: string,
  userId: number,
  isbns: string[]
): Promise<void> {
  const job = jobs.get(jobId);
  if (!job) return;

  for (const isbn of isbns) {
    try {
      // 既に登録済みかチェック
      const existing = await db.getComicByIsbn(isbn, userId);
      if (existing) {
        job.progress.processed++;
        job.progress.failed++;
        job.progress.errors.push({
          isbn,
          error: "既に登録済みです",
        });
        continue;
      }

      // 外部APIから書誌情報を取得
      const bookInfo = await fetchBookInfo(isbn);

      if (bookInfo) {
        // データベースに登録
        await db.createComic({
          isbn: bookInfo.isbn,
          title: bookInfo.title,
          author: bookInfo.author,
          publisher: bookInfo.publisher,
          series: bookInfo.series,
          imageUrl: bookInfo.imageUrl,
          userId,
        });

        job.progress.success++;
      } else {
        job.progress.failed++;
        job.progress.errors.push({
          isbn,
          error: "書誌情報が見つかりませんでした",
        });
      }

      job.progress.processed++;

      // API制限を考慮して少し待機（openBDは制限が緩いが念のため）
      await new Promise((resolve) => setTimeout(resolve, 200));
    } catch (error) {
      job.progress.processed++;
      job.progress.failed++;
      job.progress.errors.push({
        isbn,
        error: error instanceof Error ? error.message : "不明なエラー",
      });
    }
  }

  job.status = "completed";
  job.completedAt = new Date();
}

/**
 * ジョブの進捗状況を取得
 */
export function getJobProgress(jobId: string): ImportJob | undefined {
  return jobs.get(jobId);
}

/**
 * 完了したジョブを削除
 */
export function cleanupJob(jobId: string): void {
  jobs.delete(jobId);
}
