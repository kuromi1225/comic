import axios from "axios";
import { downloadAndEncodeImage, generateDefaultCoverImage } from "./imageHelper";

export interface BookInfo {
  isbn: string;
  title: string;
  author?: string;
  publisher?: string;
  series?: string;
  imageUrl?: string;
  imageData?: string; // Base64エンコードされた画像データ
  releaseDate?: Date;
}

/**
 * openBD APIから書誌情報を取得
 * https://openbd.jp/
 */
export async function fetchBookInfoFromOpenBD(isbn: string): Promise<BookInfo | null> {
  try {
    const response = await axios.get(`https://api.openbd.jp/v1/get?isbn=${isbn}`);
    const data = response.data;

    if (!data || !data[0]) {
      return null;
    }

    const book = data[0];
    const summary = book.summary;
    const onix = book.onix;

    return {
      isbn,
      title: summary?.title || onix?.DescriptiveDetail?.TitleDetail?.TitleElement?.TitleText?.content || "",
      author: summary?.author || onix?.DescriptiveDetail?.Contributor?.[0]?.PersonName?.content,
      publisher: summary?.publisher || onix?.PublishingDetail?.Publisher?.[0]?.PublisherName,
      series: summary?.series,
      imageUrl: summary?.cover,
      releaseDate: summary?.pubdate ? new Date(summary.pubdate) : undefined,
    };
  } catch (error) {
    console.error("openBD API error:", error);
    return null;
  }
}

/**
 * 国立国会図書館サーチAPIから書影を取得
 * https://ndlsearch.ndl.go.jp/
 */
export async function fetchCoverFromNDL(isbn: string): Promise<string | null> {
  try {
    const response = await axios.get(
      `https://ndlsearch.ndl.go.jp/api/opensearch`,
      {
        params: {
          isbn,
          mediatype: 1, // 図書
          cnt: 1,
        },
      }
    );

    // NDL APIはXMLを返すため、簡易的な処理
    const xmlData = response.data;
    const coverMatch = xmlData.match(/<link[^>]*type="image\/jpeg"[^>]*href="([^"]+)"/);
    
    if (coverMatch && coverMatch[1]) {
      return coverMatch[1];
    }

    return null;
  } catch (error) {
    console.error("NDL API error:", error);
    return null;
  }
}

/**
 * ISBNから書誌情報を取得（openBD優先、フォールバックとしてNDL）
 * @param isbn ISBN番号
 * @param downloadImage trueの場合、画像をダウンロードしてBase64エンコード
 */
export async function fetchBookInfo(isbn: string, downloadImage: boolean = false): Promise<BookInfo | null> {
  // まずopenBDから取得
  let bookInfo = await fetchBookInfoFromOpenBD(isbn);

  if (bookInfo) {
    // 書影がない場合はNDLから取得を試みる
    if (!bookInfo.imageUrl) {
      const ndlCover = await fetchCoverFromNDL(isbn);
      if (ndlCover) {
        bookInfo.imageUrl = ndlCover;
      }
    }

    // 画像をダウンロードしてBase64エンコード
    if (downloadImage) {
      if (bookInfo.imageUrl) {
        const imageData = await downloadAndEncodeImage(bookInfo.imageUrl);
        if (imageData) {
          bookInfo.imageData = imageData;
        } else {
          // ダウンロード失敗時はデフォルト画像を生成
          bookInfo.imageData = generateDefaultCoverImage(bookInfo.title);
        }
      } else {
        // 書影URLがない場合はデフォルト画像を生成
        bookInfo.imageData = generateDefaultCoverImage(bookInfo.title);
      }
    }

    return bookInfo;
  }

  // openBDで見つからない場合、NDLから書影のみ取得
  const ndlCover = await fetchCoverFromNDL(isbn);
  if (ndlCover) {
    const result: BookInfo = {
      isbn,
      title: `ISBN: ${isbn}`,
      imageUrl: ndlCover,
    };

    if (downloadImage) {
      const imageData = await downloadAndEncodeImage(ndlCover);
      if (imageData) {
        result.imageData = imageData;
      } else {
        result.imageData = generateDefaultCoverImage(result.title);
      }
    }

    return result;
  }

  return null;
}

/**
 * 書影URLをダウンロードしてS3に保存
 */
export async function downloadAndSaveCover(imageUrl: string, isbn: string): Promise<string | null> {
  try {
    const response = await axios.get(imageUrl, { responseType: "arraybuffer" });
    const buffer = Buffer.from(response.data);
    
    // S3に保存する処理は後で実装
    // 現在は元のURLをそのまま返す
    return imageUrl;
  } catch (error) {
    console.error("Failed to download cover:", error);
    return null;
  }
}
