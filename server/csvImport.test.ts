import { describe, expect, it } from "vitest";
import { parseCSV } from "./csvImport";

describe("CSV Import", () => {
  describe("parseCSV", () => {
    it("should parse valid ISBNs from CSV", () => {
      const csvContent = `9784065123456
9784063123457
978-4-06-312345-8`;

      const isbns = parseCSV(csvContent);

      expect(isbns).toHaveLength(3);
      expect(isbns).toContain("9784065123456");
      expect(isbns).toContain("9784063123457");
      expect(isbns).toContain("9784063123458");
    });

    it("should parse CSV with multiple columns", () => {
      const csvContent = `9784065123456,書籍タイトル,著者名
9784063123457,別の書籍,別の著者`;

      const isbns = parseCSV(csvContent);

      expect(isbns).toHaveLength(2);
      expect(isbns).toContain("9784065123456");
      expect(isbns).toContain("9784063123457");
    });

    it("should ignore invalid ISBNs", () => {
      const csvContent = `9784065123456
invalid-isbn
123
9784063123457`;

      const isbns = parseCSV(csvContent);

      expect(isbns).toHaveLength(2);
      expect(isbns).toContain("9784065123456");
      expect(isbns).toContain("9784063123457");
    });

    it("should handle empty lines", () => {
      const csvContent = `9784065123456

9784063123457

`;

      const isbns = parseCSV(csvContent);

      expect(isbns).toHaveLength(2);
    });

    it("should normalize ISBNs by removing hyphens", () => {
      const csvContent = `978-4-06-512345-6`;

      const isbns = parseCSV(csvContent);

      expect(isbns).toHaveLength(1);
      expect(isbns[0]).toBe("9784065123456");
    });

    it("should accept both ISBN-10 and ISBN-13", () => {
      const csvContent = `4065123456
9784065123456`;

      const isbns = parseCSV(csvContent);

      expect(isbns).toHaveLength(2);
      expect(isbns).toContain("4065123456");
      expect(isbns).toContain("9784065123456");
    });
  });
});
