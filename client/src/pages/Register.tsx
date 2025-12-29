import { useAuth } from "@/_core/hooks/useAuth";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { trpc } from "@/lib/trpc";
import { BookOpen, Upload, Camera } from "lucide-react";
import { useState, useEffect, useRef } from "react";
import { createWorker } from "tesseract.js";
import { Link } from "wouter";
import { toast } from "sonner";

export default function Register() {
  const { user } = useAuth();
  const [isbn, setIsbn] = useState("");
  const [csvFile, setCsvFile] = useState<File | null>(null);
  const [jobId, setJobId] = useState<string | null>(null);
  const [isImporting, setIsImporting] = useState(false);
  const [ocrImage, setOcrImage] = useState<File | null>(null);
  const [isOcrProcessing, setIsOcrProcessing] = useState(false);
  const [ocrResult, setOcrResult] = useState<string>("");
  const [extractedIsbns, setExtractedIsbns] = useState<string[]>([]);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const cameraInputRef = useRef<HTMLInputElement>(null);

  const utils = trpc.useUtils();
  const createMutation = trpc.comics.create.useMutation({
    onSuccess: () => {
      utils.comics.list.invalidate();
      utils.comics.stats.invalidate();
      toast.success("漫画を登録しました");
      setIsbn("");
    },
    onError: (error) => {
      toast.error(`登録に失敗しました: ${error.message}`);
    },
  });

  const fetchBookInfoQuery = trpc.comics.fetchBookInfo.useQuery(
    { isbn: isbn.trim() },
    { enabled: false }
  );

  const handleIsbnSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!isbn.trim()) {
      toast.error("ISBNを入力してください");
      return;
    }

    try {
      // 外部APIから書誌情報を取得
      const bookInfo = await fetchBookInfoQuery.refetch();
      
      if (bookInfo.data) {
        createMutation.mutate({
          isbn: isbn.trim(),
          title: bookInfo.data.title,
          author: bookInfo.data.author,
          publisher: bookInfo.data.publisher,
          series: bookInfo.data.series,
          imageUrl: bookInfo.data.imageUrl,
          imageData: bookInfo.data.imageData,
        });
      } else {
        toast.error("書誌情報が見つかりませんでした");
      }
    } catch (error) {
      toast.error("書誌情報の取得に失敗しました");
    }
  };

  const startImportMutation = trpc.csvImport.start.useMutation({
    onSuccess: (data) => {
      setJobId(data.jobId);
      setIsImporting(true);
      toast.success(`${data.total}件のISBNを検出しました。登録を開始します...`);
    },
    onError: (error) => {
      toast.error(`エラー: ${error.message}`);
    },
  });

  const { data: progressData } = trpc.csvImport.progress.useQuery(
    { jobId: jobId! },
    { 
      enabled: !!jobId && isImporting,
      refetchInterval: 1000, // 1秒ごとに進捗をチェック
    }
  );

  const cleanupMutation = trpc.csvImport.cleanup.useMutation();

  useEffect(() => {
    if (progressData && progressData.status === "completed") {
      setIsImporting(false);
      toast.success(
        `登録完了: 成功 ${progressData.progress.success}件 / 失敗 ${progressData.progress.failed}件`
      );
      utils.comics.list.invalidate();
      utils.comics.stats.invalidate();
    }
  }, [progressData]);

  const extractIsbnFromText = (text: string): string[] => {
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
  };

  const handleOcrImage = async (file: File) => {
    setIsOcrProcessing(true);
    setOcrResult("");
    setExtractedIsbns([]);

    try {
      const worker = await createWorker("jpn");
      const { data } = await worker.recognize(file);
      await worker.terminate();

      setOcrResult(data.text);
      const isbns = extractIsbnFromText(data.text);
      setExtractedIsbns(isbns);

      if (isbns.length === 0) {
        toast.error("ISBNが見つかりませんでした。画像を鮮明に撮影し直してください。");
      } else {
        toast.success(`${isbns.length}件のISBNを検出しました`);
      }
    } catch (error) {
      console.error("OCR error:", error);
      toast.error("OCR処理に失敗しました");
    } finally {
      setIsOcrProcessing(false);
    }
  };

  const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      setOcrImage(file);
      handleOcrImage(file);
    }
  };

  const handleRegisterFromOcr = async (isbn: string) => {
    try {
      // 外部APIから書誌情報を取得
      const bookInfo = await utils.client.comics.fetchBookInfo.query({ isbn });
      if (bookInfo) {
        await createMutation.mutateAsync({
          isbn: bookInfo.isbn,
          title: bookInfo.title,
          author: bookInfo.author || "",
          publisher: bookInfo.publisher || "",
          series: bookInfo.series,
          imageUrl: bookInfo.imageUrl,
          imageData: bookInfo.imageData,
        });
        toast.success(`${bookInfo.title}を登録しました`);
        // 登録済みのISBNをリストから削除
        setExtractedIsbns(prev => prev.filter(i => i !== isbn));
      } else {
        toast.error(`ISBN: ${isbn} の書誌情報が見つかりませんでした`);
      }
    } catch (error) {
      toast.error(`ISBN: ${isbn} の登録に失敗しました`);
    }
  };

  const handleCsvUpload = async () => {
    if (!csvFile) {
      toast.error("CSVファイルを選択してください");
      return;
    }

    try {
      const csvContent = await csvFile.text();
      startImportMutation.mutate({ csvContent });
    } catch (error) {
      toast.error("CSVファイルの読み込みに失敗しました");
    }
  };

  if (!user) {
    return <div>ログインが必要です</div>;
  }

  return (
    <div className="min-h-screen bg-background">
      <header className="border-b bg-card">
        <div className="container py-4">
          <div className="flex items-center justify-between">
            <h1 className="text-2xl font-bold">漫画管理システム</h1>
            <nav className="flex items-center gap-4">
              <Link href="/">
                <a className="text-sm font-medium hover:text-primary transition-colors">
                  ダッシュボード
                </a>
              </Link>
              <Link href="/library">
                <a className="text-sm font-medium hover:text-primary transition-colors">
                  蔵書一覧
                </a>
              </Link>
              <Link href="/new-releases">
                <a className="text-sm font-medium hover:text-primary transition-colors">
                  新刊一覧
                </a>
              </Link>
              <Link href="/register">
                <a className="text-sm font-medium text-primary">漫画を登録</a>
              </Link>
              <div className="text-sm text-muted-foreground">{user.name}</div>
            </nav>
          </div>
        </div>
      </header>

      <main className="container py-8 max-w-4xl">
        <Card>
          <CardHeader>
            <CardTitle>漫画を登録</CardTitle>
            <CardDescription>
              ISBNから登録、レシート撮影、またはCSVファイルから一括登録できます
            </CardDescription>
          </CardHeader>
          <CardContent>
            <Tabs defaultValue="isbn" className="w-full">
              <TabsList className="grid w-full grid-cols-3">
                <TabsTrigger value="isbn">
                  <BookOpen className="h-4 w-4 mr-2" />
                  ISBNで登録
                </TabsTrigger>
                <TabsTrigger value="receipt">
                  <Camera className="h-4 w-4 mr-2" />
                  レシート撮影
                </TabsTrigger>
                <TabsTrigger value="csv">
                  <Upload className="h-4 w-4 mr-2" />
                  CSV一括登録
                </TabsTrigger>
              </TabsList>

              <TabsContent value="isbn" className="space-y-4 mt-6">
                <form onSubmit={handleIsbnSubmit} className="space-y-4">
                  <div className="space-y-2">
                    <Label htmlFor="isbn">ISBN</Label>
                    <Input
                      id="isbn"
                      placeholder="978-4-06-XXXXXX-X"
                      value={isbn}
                      onChange={(e) => setIsbn(e.target.value)}
                    />
                    <p className="text-sm text-muted-foreground">
                      ISBNを入力すると、書誌情報が自動で取得されます
                    </p>
                  </div>
                  <Button type="submit" className="w-full" disabled={createMutation.isPending}>
                    {createMutation.isPending ? "登録中..." : "登録"}
                  </Button>
                </form>
              </TabsContent>

              <TabsContent value="receipt" className="space-y-4 mt-6">
                <div className="border-2 border-dashed border-muted rounded-lg p-12 text-center">
                  <Camera className="h-12 w-12 mx-auto mb-4 text-muted-foreground" />
                  <p className="text-muted-foreground mb-4">
                    OCR機能は実装中です
                  </p>
                  <p className="text-sm text-muted-foreground">
                    レシートからISBNを読み取る機能を準備しています
                  </p>
                </div>
              </TabsContent>

              <TabsContent value="csv" className="space-y-4 mt-6">
                <div className="space-y-4">
                  <div className="space-y-2">
                    <Label htmlFor="csv-file">CSVファイル</Label>
                    <Input
                      id="csv-file"
                      type="file"
                      accept=".csv"
                      onChange={(e) => setCsvFile(e.target.files?.[0] || null)}
                      disabled={isImporting}
                    />
                    <p className="text-sm text-muted-foreground">
                      ISBNのリストを含むCSVファイルをアップロードしてください。
                      各行の最初の列がISBNとして読み込まれます。
                    </p>
                  </div>
                  {csvFile && !isImporting && (
                    <div className="text-sm text-muted-foreground">
                      選択されたファイル: {csvFile.name}
                    </div>
                  )}
                  
                  {isImporting && progressData && (
                    <div className="space-y-3">
                      <div className="space-y-2">
                        <div className="flex justify-between text-sm">
                          <span>進捗状況</span>
                          <span className="font-medium">
                            {progressData.progress.processed} / {progressData.progress.total}
                          </span>
                        </div>
                        <div className="w-full bg-muted rounded-full h-2">
                          <div
                            className="bg-primary h-2 rounded-full transition-all"
                            style={{
                              width: `${(progressData.progress.processed / progressData.progress.total) * 100}%`,
                            }}
                          />
                        </div>
                      </div>
                      <div className="grid grid-cols-2 gap-4 text-sm">
                        <div>
                          <span className="text-muted-foreground">成功: </span>
                          <span className="font-medium text-green-600">
                            {progressData.progress.success}件
                          </span>
                        </div>
                        <div>
                          <span className="text-muted-foreground">失敗: </span>
                          <span className="font-medium text-red-600">
                            {progressData.progress.failed}件
                          </span>
                        </div>
                      </div>
                      {progressData.progress.errors.length > 0 && (
                        <details className="text-sm">
                          <summary className="cursor-pointer text-muted-foreground hover:text-foreground">
                            エラー詳細 ({progressData.progress.errors.length}件)
                          </summary>
                          <div className="mt-2 space-y-1 max-h-40 overflow-y-auto">
                            {progressData.progress.errors.slice(0, 10).map((error, i) => (
                              <div key={i} className="text-xs text-red-600">
                                ISBN: {error.isbn} - {error.error}
                              </div>
                            ))}
                            {progressData.progress.errors.length > 10 && (
                              <div className="text-xs text-muted-foreground">
                                ...他 {progressData.progress.errors.length - 10}件
                              </div>
                            )}
                          </div>
                        </details>
                      )}
                    </div>
                  )}

                  <Button 
                    onClick={handleCsvUpload} 
                    className="w-full" 
                    disabled={!csvFile || isImporting || startImportMutation.isPending}
                  >
                    {isImporting ? "登録中..." : "一括登録を開始"}
                  </Button>
                </div>
              </TabsContent>

              {/* OCRタブ */}
              <TabsContent value="ocr">
                <div className="space-y-4">
                  <div className="space-y-2">
                    <Label>レシート画像をアップロード</Label>
                    <div className="grid grid-cols-2 gap-4">
                      <div>
                        <input
                          ref={fileInputRef}
                          type="file"
                          accept="image/*"
                          onChange={handleFileSelect}
                          className="hidden"
                        />
                        <Button
                          type="button"
                          variant="outline"
                          className="w-full"
                          onClick={() => fileInputRef.current?.click()}
                          disabled={isOcrProcessing}
                        >
                          <Upload className="h-4 w-4 mr-2" />
                          ファイルを選択
                        </Button>
                      </div>
                      <div>
                        <input
                          ref={cameraInputRef}
                          type="file"
                          accept="image/*"
                          capture="environment"
                          onChange={handleFileSelect}
                          className="hidden"
                        />
                        <Button
                          type="button"
                          variant="outline"
                          className="w-full"
                          onClick={() => cameraInputRef.current?.click()}
                          disabled={isOcrProcessing}
                        >
                          <Camera className="h-4 w-4 mr-2" />
                          カメラで撮影
                        </Button>
                      </div>
                    </div>
                    <p className="text-sm text-muted-foreground">
                      レシートを撮影またはアップロードすると、ISBNを自動で読み取ります。
                    </p>
                  </div>

                  {isOcrProcessing && (
                    <div className="flex items-center justify-center py-8">
                      <div className="text-center">
                        <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                        <p className="mt-4 text-sm text-muted-foreground">OCR処理中...</p>
                      </div>
                    </div>
                  )}

                  {ocrImage && !isOcrProcessing && (
                    <div className="space-y-4">
                      <div>
                        <Label>アップロードされた画像</Label>
                        <img
                          src={URL.createObjectURL(ocrImage)}
                          alt="レシート"
                          className="w-full max-w-md mx-auto rounded-lg border"
                        />
                      </div>

                      {extractedIsbns.length > 0 && (
                        <div className="space-y-3">
                          <Label>検出されたISBN ({extractedIsbns.length}件)</Label>
                          <div className="space-y-2">
                            {extractedIsbns.map((isbn, index) => (
                              <div key={index} className="flex items-center justify-between p-3 bg-muted rounded-lg">
                                <span className="font-mono text-sm">{isbn}</span>
                                <Button
                                  size="sm"
                                  onClick={() => handleRegisterFromOcr(isbn)}
                                  disabled={createMutation.isPending}
                                >
                                  登録
                                </Button>
                              </div>
                            ))}
                          </div>
                        </div>
                      )}

                      {ocrResult && (
                        <details className="text-sm">
                          <summary className="cursor-pointer text-muted-foreground hover:text-foreground">
                            OCR読み取り結果を表示
                          </summary>
                          <pre className="mt-2 p-3 bg-muted rounded text-xs overflow-auto max-h-40">
                            {ocrResult}
                          </pre>
                        </details>
                      )}
                    </div>
                  )}
                </div>
              </TabsContent>
            </Tabs>
          </CardContent>
        </Card>
      </main>
    </div>
  );
}
