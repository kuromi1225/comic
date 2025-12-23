import { Comic } from "../drizzle/schema";

export interface VolumeInfo {
  volume: number | null;
  originalTitle: string;
}

export interface SeriesGroup {
  seriesName: string;
  comics: Array<Comic & { volume: number | null }>;
  totalVolumes: number;
  missingVolumes: number[];
  hasAllVolumes: boolean;
}

/**
 * タイトルから巻数を抽出
 * 例: "ワンピース 1巻" -> 1
 *     "進撃の巨人（10）" -> 10
 *     "鬼滅の刃 第5巻" -> 5
 */
export function extractVolumeNumber(title: string): VolumeInfo {
  // パターン1: "1巻", "第1巻", "1"
  const pattern1 = /第?(\d+)巻/;
  const match1 = title.match(pattern1);
  if (match1) {
    return {
      volume: parseInt(match1[1], 10),
      originalTitle: title,
    };
  }

  // パターン2: "（1）", "(1)"
  const pattern2 = /[（(](\d+)[）)]/;
  const match2 = title.match(pattern2);
  if (match2) {
    return {
      volume: parseInt(match2[1], 10),
      originalTitle: title,
    };
  }

  // パターン3: タイトル末尾の数字 "タイトル 1", "タイトル1"
  const pattern3 = /\s*(\d+)\s*$/;
  const match3 = title.match(pattern3);
  if (match3) {
    return {
      volume: parseInt(match3[1], 10),
      originalTitle: title,
    };
  }

  // パターン4: "vol.1", "Vol.1", "VOL.1"
  const pattern4 = /vol\.?\s*(\d+)/i;
  const match4 = title.match(pattern4);
  if (match4) {
    return {
      volume: parseInt(match4[1], 10),
      originalTitle: title,
    };
  }

  return {
    volume: null,
    originalTitle: title,
  };
}

/**
 * タイトルからシリーズ名を抽出（巻数部分を除去）
 */
export function extractSeriesName(title: string): string {
  // 巻数パターンを除去
  let seriesName = title
    .replace(/第?\d+巻/g, "")
    .replace(/[（(]\d+[）)]/g, "")
    .replace(/vol\.?\s*\d+/gi, "")
    .replace(/\s+\d+\s*$/, "")
    .trim();

  // 括弧内の補足情報を除去（例: "タイトル（新装版）" -> "タイトル"）
  // ただし、巻数以外の括弧は残す場合もあるので慎重に
  return seriesName;
}

/**
 * 漫画をシリーズごとにグループ化
 */
export function groupBySeries(comics: Comic[]): SeriesGroup[] {
  const seriesMap = new Map<string, Array<Comic & { volume: number | null }>>();

  // シリーズごとにグループ化
  for (const comic of comics) {
    if (!comic.series) continue;

    const volumeInfo = extractVolumeNumber(comic.title);
    const comicWithVolume = { ...comic, volume: volumeInfo.volume };

    if (!seriesMap.has(comic.series)) {
      seriesMap.set(comic.series, []);
    }
    seriesMap.get(comic.series)!.push(comicWithVolume);
  }

  // 各シリーズの抜け巻を検出
  const result: SeriesGroup[] = [];

  for (const [seriesName, seriesComics] of Array.from(seriesMap.entries())) {
    // 巻数でソート
    const sortedComics = seriesComics.sort((a: typeof seriesComics[0], b: typeof seriesComics[0]) => {
      if (a.volume === null) return 1;
      if (b.volume === null) return -1;
      return a.volume - b.volume;
    });

    // 最大巻数を取得
    const volumes = sortedComics
      .map((c: typeof sortedComics[0]) => c.volume)
      .filter((v: number | null): v is number => v !== null);

    const maxVolume = volumes.length > 0 ? Math.max(...volumes) : 0;

    // 抜け巻を検出
    const existingVolumes = new Set(volumes);
    const missingVolumes: number[] = [];

    for (let i = 1; i <= maxVolume; i++) {
      if (!existingVolumes.has(i)) {
        missingVolumes.push(i);
      }
    }

    result.push({
      seriesName,
      comics: sortedComics,
      totalVolumes: maxVolume,
      missingVolumes,
      hasAllVolumes: missingVolumes.length === 0,
    });
  }

  // シリーズ名でソート
  return result.sort((a, b) => a.seriesName.localeCompare(b.seriesName, "ja"));
}

/**
 * 抜け巻の情報を取得
 */
export function getMissingVolumesInfo(series: SeriesGroup): string {
  if (series.missingVolumes.length === 0) {
    return "全巻揃っています";
  }

  if (series.missingVolumes.length <= 5) {
    return `抜け巻: ${series.missingVolumes.join(", ")}巻`;
  }

  return `抜け巻: ${series.missingVolumes.length}巻`;
}
