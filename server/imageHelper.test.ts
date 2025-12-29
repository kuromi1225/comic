import { describe, expect, it } from "vitest";
import { generateDefaultCoverImage } from "./imageHelper";

describe("imageHelper", () => {
  describe("generateDefaultCoverImage", () => {
    it("タイトルからデフォルト書影を生成できる", () => {
      const title = "テスト漫画";
      const imageData = generateDefaultCoverImage(title);

      // Base64エンコードされた画像データが返される
      expect(imageData).toMatch(/^data:image\/svg\+xml;base64,/);
      expect(imageData.length).toBeGreaterThan(100);
    });

    it("長いタイトルでもデフォルト書影を生成できる", () => {
      const title = "これは非常に長いタイトルの漫画です。タイトルが長すぎる場合でも適切に処理されるべきです。";
      const imageData = generateDefaultCoverImage(title);

      expect(imageData).toMatch(/^data:image\/svg\+xml;base64,/);
      expect(imageData.length).toBeGreaterThan(100);
    });

    it("空のタイトルでもデフォルト書影を生成できる", () => {
      const title = "";
      const imageData = generateDefaultCoverImage(title);

      expect(imageData).toMatch(/^data:image\/svg\+xml;base64,/);
      expect(imageData.length).toBeGreaterThan(100);
    });
  });
});
