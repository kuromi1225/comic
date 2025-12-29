import axios from "axios";

/**
 * 画像URLから画像をダウンロードしてBase64エンコードする
 */
export async function downloadAndEncodeImage(imageUrl: string): Promise<string | null> {
  try {
    const response = await axios.get(imageUrl, {
      responseType: "arraybuffer",
      timeout: 10000, // 10秒タイムアウト
      headers: {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
      },
    });

    const buffer = Buffer.from(response.data, "binary");
    const contentType = response.headers["content-type"] || "image/jpeg";
    const base64 = buffer.toString("base64");
    
    // Data URL形式で返す (data:image/jpeg;base64,...)
    return `data:${contentType};base64,${base64}`;
  } catch (error) {
    console.error(`Failed to download image from ${imageUrl}:`, error);
    return null;
  }
}

/**
 * デフォルトの書影画像（SVG）を生成
 * タイトルの最初の文字を大きく表示
 */
export function generateDefaultCoverImage(title: string): string {
  const firstChar = title.charAt(0) || "?";
  
  // ランダムな背景色を生成（タイトルのハッシュ値から）
  const hash = title.split("").reduce((acc, char) => {
    return char.charCodeAt(0) + ((acc << 5) - acc);
  }, 0);
  
  const hue = Math.abs(hash % 360);
  const backgroundColor = `hsl(${hue}, 60%, 70%)`;
  const textColor = `hsl(${hue}, 60%, 30%)`;
  
  const svg = `
    <svg width="200" height="300" xmlns="http://www.w3.org/2000/svg">
      <rect width="200" height="300" fill="${backgroundColor}"/>
      <text
        x="50%"
        y="50%"
        font-family="Arial, sans-serif"
        font-size="120"
        font-weight="bold"
        fill="${textColor}"
        text-anchor="middle"
        dominant-baseline="middle"
      >${firstChar}</text>
      <text
        x="50%"
        y="85%"
        font-family="Arial, sans-serif"
        font-size="14"
        fill="${textColor}"
        text-anchor="middle"
        opacity="0.7"
      >No Image</text>
    </svg>
  `.trim();
  
  const base64 = Buffer.from(svg).toString("base64");
  return `data:image/svg+xml;base64,${base64}`;
}

/**
 * 画像データのサイズをチェック（MB単位）
 * MySQLのmediumtextは16MBまで、longtextは4GBまで
 * 安全のため10MB以下に制限
 */
export function isImageSizeValid(base64Data: string, maxSizeMB: number = 10): boolean {
  // Base64は元のサイズの約1.37倍になる
  const sizeInBytes = (base64Data.length * 3) / 4;
  const sizeInMB = sizeInBytes / (1024 * 1024);
  return sizeInMB <= maxSizeMB;
}
