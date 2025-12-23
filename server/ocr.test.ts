import { describe, expect, it } from "vitest";

/**
 * OCR機能のテスト
 * 
 * 注意: Tesseract.jsはブラウザ環境で動作するため、
 * サーバーサイドでの単体テストは実装していません。
 * 
 * 代わりに、ISBN抽出ロジックのテストを実装します。
 */

// ISBN抽出ロジック（Register.tsxから抽出）
function extractIsbnFromText(text: string): string[] {
  const isbns: string[] = [];
  
  // ISBN-13のパターン: 978 or 979で始まる13桁の数字
  const isbn13Pattern = /(?:978|979)[\s-]?\d{1,5}[\s-]?\d{1,7}[\s-]?\d{1,7}[\s-]?\d{1}/g;
  const matches13 = text.match(isbn13Pattern);
  if (matches13) {
    matches13.forEach(match => {
      const cleaned = match.replace(/[\s-]/g, "");
      if (cleaned.length === 13) {
        isbns.push(cleaned);
      }
    });
  }

  // ISBN-10のパターン: 10桁の数字（最後はXも可）
  const isbn10Pattern = /\b\d{9}[\dXx]\b/g;
  const matches10 = text.match(isbn10Pattern);
  if (matches10) {
    matches10.forEach(match => {
      isbns.push(match.toUpperCase());
    });
  }

  return Array.from(new Set(isbns)); // 重複を除去
}

describe("ISBN Extraction from OCR Text", () => {
  describe("ISBN-13 extraction", () => {
    it("should extract ISBN-13 without hyphens", () => {
      const text = "商品名: ワンピース 1巻\nISBN: 9784088801234\n価格: 500円";
      const result = extractIsbnFromText(text);
      expect(result).toContain("9784088801234");
    });

    it("should extract ISBN-13 with hyphens", () => {
      const text = "ISBN: 978-4-08-880123-4";
      const result = extractIsbnFromText(text);
      expect(result).toContain("9784088801234");
    });

    it("should extract ISBN-13 with spaces", () => {
      const text = "ISBN: 978 4 08 880123 4";
      const result = extractIsbnFromText(text);
      expect(result).toContain("9784088801234");
    });

    it("should extract multiple ISBN-13", () => {
      const text = `
        商品1: 9784088801234
        商品2: 9784088801241
        商品3: 9794088801258
      `;
      const result = extractIsbnFromText(text);
      expect(result).toHaveLength(3);
      expect(result).toContain("9784088801234");
      expect(result).toContain("9784088801241");
      expect(result).toContain("9794088801258");
    });
  });

  describe("ISBN-10 extraction", () => {
    it("should extract ISBN-10", () => {
      const text = "ISBN: 4088801237";
      const result = extractIsbnFromText(text);
      expect(result).toContain("4088801237");
    });

    it("should extract ISBN-10 ending with X", () => {
      const text = "ISBN: 408880123X";
      const result = extractIsbnFromText(text);
      expect(result).toContain("408880123X");
    });

    it("should extract ISBN-10 ending with lowercase x", () => {
      const text = "ISBN: 408880123x";
      const result = extractIsbnFromText(text);
      expect(result).toContain("408880123X"); // 大文字に変換される
    });
  });

  describe("Mixed ISBN formats", () => {
    it("should extract both ISBN-10 and ISBN-13", () => {
      const text = `
        旧版: 4088801237
        新版: 9784088801234
      `;
      const result = extractIsbnFromText(text);
      expect(result).toHaveLength(2);
      expect(result).toContain("4088801237");
      expect(result).toContain("9784088801234");
    });
  });

  describe("No ISBN found", () => {
    it("should return empty array when no ISBN is found", () => {
      const text = "商品名: ワンピース 1巻\n価格: 500円";
      const result = extractIsbnFromText(text);
      expect(result).toHaveLength(0);
    });

    it("should not extract incomplete ISBN", () => {
      const text = "978408880"; // 不完全なISBN
      const result = extractIsbnFromText(text);
      expect(result).toHaveLength(0);
    });
  });

  describe("Duplicate removal", () => {
    it("should remove duplicate ISBNs", () => {
      const text = `
        9784088801234
        9784088801234
        9784088801234
      `;
      const result = extractIsbnFromText(text);
      expect(result).toHaveLength(1);
      expect(result).toContain("9784088801234");
    });
  });

  describe("Real-world receipt examples", () => {
    it("should extract ISBN from typical Japanese receipt", () => {
      const text = `
        書籍名: ワンピース 第1巻
        ISBN: 978-4-08-880123-4
        定価: 500円(税込)
        購入日: 2024/01/15
      `;
      const result = extractIsbnFromText(text);
      expect(result).toContain("9784088801234");
    });

    it("should extract multiple ISBNs from multi-item receipt", () => {
      const text = `
        レシート
        -------------------------
        1. ワンピース 1巻
           ISBN: 9784088801234
           500円
        
        2. 鬼滅の刃 1巻
           ISBN: 9784088820019
           500円
        
        3. 呪術廻戦 1巻
           ISBN: 9784088815442
           500円
        -------------------------
        合計: 1,500円
      `;
      const result = extractIsbnFromText(text);
      expect(result).toHaveLength(3);
      expect(result).toContain("9784088801234");
      expect(result).toContain("9784088820019");
      expect(result).toContain("9784088815442");
    });
  });
});
