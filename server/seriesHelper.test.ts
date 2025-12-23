import { describe, expect, it } from "vitest";
import { extractVolumeNumber, extractSeriesName, groupBySeries } from "./seriesHelper";
import { Comic } from "../drizzle/schema";

describe("Series Helper", () => {
  describe("extractVolumeNumber", () => {
    it("should extract volume from '1巻' pattern", () => {
      const result = extractVolumeNumber("ワンピース 1巻");
      expect(result.volume).toBe(1);
    });

    it("should extract volume from '第1巻' pattern", () => {
      const result = extractVolumeNumber("進撃の巨人 第10巻");
      expect(result.volume).toBe(10);
    });

    it("should extract volume from '（1）' pattern", () => {
      const result = extractVolumeNumber("鬼滅の刃（5）");
      expect(result.volume).toBe(5);
    });

    it("should extract volume from trailing number", () => {
      const result = extractVolumeNumber("呪術廻戦 15");
      expect(result.volume).toBe(15);
    });

    it("should extract volume from 'vol.1' pattern", () => {
      const result = extractVolumeNumber("チェンソーマン vol.3");
      expect(result.volume).toBe(3);
    });

    it("should return null for title without volume", () => {
      const result = extractVolumeNumber("単行本タイトル");
      expect(result.volume).toBeNull();
    });
  });

  describe("extractSeriesName", () => {
    it("should remove volume number from title", () => {
      const result = extractSeriesName("ワンピース 1巻");
      expect(result).toBe("ワンピース");
    });

    it("should remove '第N巻' from title", () => {
      const result = extractSeriesName("進撃の巨人 第10巻");
      expect(result).toBe("進撃の巨人");
    });

    it("should remove '（N）' from title", () => {
      const result = extractSeriesName("鬼滅の刃（5）");
      expect(result).toBe("鬼滅の刃");
    });

    it("should remove trailing number from title", () => {
      const result = extractSeriesName("呪術廻戦 15");
      expect(result).toBe("呪術廻戦");
    });
  });

  describe("groupBySeries", () => {
    it("should group comics by series", () => {
      const comics: Comic[] = [
        {
          id: 1,
          isbn: "9784088801234",
          title: "ワンピース 1巻",
          author: "尾田栄一郎",
          publisher: "集英社",
          series: "ワンピース",
          imageUrl: null,
          status: "unread",
          userId: 1,
          createdAt: new Date(),
          updatedAt: new Date(),
        },
        {
          id: 2,
          isbn: "9784088801235",
          title: "ワンピース 2巻",
          author: "尾田栄一郎",
          publisher: "集英社",
          series: "ワンピース",
          imageUrl: null,
          status: "unread",
          userId: 1,
          createdAt: new Date(),
          updatedAt: new Date(),
        },
        {
          id: 3,
          isbn: "9784088801236",
          title: "ワンピース 4巻",
          author: "尾田栄一郎",
          publisher: "集英社",
          series: "ワンピース",
          imageUrl: null,
          status: "unread",
          userId: 1,
          createdAt: new Date(),
          updatedAt: new Date(),
        },
      ];

      const result = groupBySeries(comics);

      expect(result).toHaveLength(1);
      expect(result[0].seriesName).toBe("ワンピース");
      expect(result[0].comics).toHaveLength(3);
      expect(result[0].totalVolumes).toBe(4);
    });

    it("should detect missing volumes", () => {
      const comics: Comic[] = [
        {
          id: 1,
          isbn: "9784088801234",
          title: "ワンピース 1巻",
          author: "尾田栄一郎",
          publisher: "集英社",
          series: "ワンピース",
          imageUrl: null,
          status: "unread",
          userId: 1,
          createdAt: new Date(),
          updatedAt: new Date(),
        },
        {
          id: 2,
          isbn: "9784088801235",
          title: "ワンピース 3巻",
          author: "尾田栄一郎",
          publisher: "集英社",
          series: "ワンピース",
          imageUrl: null,
          status: "unread",
          userId: 1,
          createdAt: new Date(),
          updatedAt: new Date(),
        },
        {
          id: 3,
          isbn: "9784088801236",
          title: "ワンピース 5巻",
          author: "尾田栄一郎",
          publisher: "集英社",
          series: "ワンピース",
          imageUrl: null,
          status: "unread",
          userId: 1,
          createdAt: new Date(),
          updatedAt: new Date(),
        },
      ];

      const result = groupBySeries(comics);

      expect(result[0].missingVolumes).toEqual([2, 4]);
      expect(result[0].hasAllVolumes).toBe(false);
    });

    it("should mark series as complete when all volumes are present", () => {
      const comics: Comic[] = [
        {
          id: 1,
          isbn: "9784088801234",
          title: "ワンピース 1巻",
          author: "尾田栄一郎",
          publisher: "集英社",
          series: "ワンピース",
          imageUrl: null,
          status: "unread",
          userId: 1,
          createdAt: new Date(),
          updatedAt: new Date(),
        },
        {
          id: 2,
          isbn: "9784088801235",
          title: "ワンピース 2巻",
          author: "尾田栄一郎",
          publisher: "集英社",
          series: "ワンピース",
          imageUrl: null,
          status: "unread",
          userId: 1,
          createdAt: new Date(),
          updatedAt: new Date(),
        },
        {
          id: 3,
          isbn: "9784088801236",
          title: "ワンピース 3巻",
          author: "尾田栄一郎",
          publisher: "集英社",
          series: "ワンピース",
          imageUrl: null,
          status: "unread",
          userId: 1,
          createdAt: new Date(),
          updatedAt: new Date(),
        },
      ];

      const result = groupBySeries(comics);

      expect(result[0].missingVolumes).toEqual([]);
      expect(result[0].hasAllVolumes).toBe(true);
    });

    it("should sort comics by volume number", () => {
      const comics: Comic[] = [
        {
          id: 3,
          isbn: "9784088801236",
          title: "ワンピース 3巻",
          author: "尾田栄一郎",
          publisher: "集英社",
          series: "ワンピース",
          imageUrl: null,
          status: "unread",
          userId: 1,
          createdAt: new Date(),
          updatedAt: new Date(),
        },
        {
          id: 1,
          isbn: "9784088801234",
          title: "ワンピース 1巻",
          author: "尾田栄一郎",
          publisher: "集英社",
          series: "ワンピース",
          imageUrl: null,
          status: "unread",
          userId: 1,
          createdAt: new Date(),
          updatedAt: new Date(),
        },
        {
          id: 2,
          isbn: "9784088801235",
          title: "ワンピース 2巻",
          author: "尾田栄一郎",
          publisher: "集英社",
          series: "ワンピース",
          imageUrl: null,
          status: "unread",
          userId: 1,
          createdAt: new Date(),
          updatedAt: new Date(),
        },
      ];

      const result = groupBySeries(comics);

      expect(result[0].comics[0].volume).toBe(1);
      expect(result[0].comics[1].volume).toBe(2);
      expect(result[0].comics[2].volume).toBe(3);
    });
  });
});
