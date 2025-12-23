import { useAuth } from "@/_core/hooks/useAuth";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { trpc } from "@/lib/trpc";
import { BookOpen, Upload, Camera } from "lucide-react";
import { useState, useEffect } from "react";
import { Link } from "wouter";
import { toast } from "sonner";

export default function Register() {
  const { user } = useAuth();
  const [isbn, setIsbn] = useState("");
  const [csvFile, setCsvFile] = useState<File | null>(null);
  const [jobId, setJobId] = useState<string | null>(null);
  const [isImporting, setIsImporting] = useState(false);

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
            </Tabs>
          </CardContent>
        </Card>
      </main>
    </div>
  );
}
