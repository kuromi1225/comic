import { useAuth } from "@/_core/hooks/useAuth";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "@/components/ui/alert-dialog";
import { Switch } from "@/components/ui/switch";
import { Label } from "@/components/ui/label";
import { trpc } from "@/lib/trpc";
import { BookOpen, Trash2, ArrowLeft } from "lucide-react";
import { Link, useLocation, useParams } from "wouter";
import { toast } from "sonner";

export default function ComicDetail() {
  const { user } = useAuth();
  const params = useParams();
  const [, setLocation] = useLocation();
  const comicId = Number(params.id);

  const { data: comics } = trpc.comics.list.useQuery(undefined, { enabled: !!user });
  const comic = comics?.find((c) => c.id === comicId);

  const utils = trpc.useUtils();
  const updateStatusMutation = trpc.comics.updateStatus.useMutation({
    onSuccess: () => {
      utils.comics.list.invalidate();
      utils.comics.stats.invalidate();
      toast.success("ステータスを更新しました");
    },
  });

  const deleteMutation = trpc.comics.delete.useMutation({
    onSuccess: () => {
      toast.success("漫画を削除しました");
      setLocation("/library");
    },
  });

  if (!user) {
    return <div>ログインが必要です</div>;
  }

  if (!comic) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-muted-foreground">漫画が見つかりませんでした</div>
      </div>
    );
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
                <a className="text-sm font-medium hover:text-primary transition-colors">
                  漫画を登録
                </a>
              </Link>
              <div className="text-sm text-muted-foreground">{user.name}</div>
            </nav>
          </div>
        </div>
      </header>

      <main className="container py-8">
        <Link href="/library">
          <Button variant="ghost" className="mb-6">
            <ArrowLeft className="h-4 w-4 mr-2" />
            蔵書一覧に戻る
          </Button>
        </Link>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          {/* 書影エリア */}
          <div className="md:col-span-1">
            <div className="aspect-[3/4] bg-muted rounded-lg overflow-hidden sticky top-8">
              {comic.imageUrl ? (
                <img
                  src={comic.imageUrl}
                  alt={comic.title}
                  className="w-full h-full object-cover"
                />
              ) : (
                <div className="w-full h-full flex items-center justify-center">
                  <BookOpen className="h-24 w-24 text-muted-foreground" />
                </div>
              )}
            </div>
          </div>

          {/* 書誌情報・操作エリア */}
          <div className="md:col-span-2 space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>書誌情報</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <div className="text-sm text-muted-foreground mb-1">書名</div>
                  <div className="text-lg font-medium">{comic.title}</div>
                </div>
                {comic.author && (
                  <div>
                    <div className="text-sm text-muted-foreground mb-1">著者名</div>
                    <div>{comic.author}</div>
                  </div>
                )}
                {comic.publisher && (
                  <div>
                    <div className="text-sm text-muted-foreground mb-1">出版社</div>
                    <div>{comic.publisher}</div>
                  </div>
                )}
                {comic.series && (
                  <div>
                    <div className="text-sm text-muted-foreground mb-1">シリーズ名</div>
                    <div>{comic.series}</div>
                  </div>
                )}
                <div>
                  <div className="text-sm text-muted-foreground mb-1">ISBN</div>
                  <div className="font-mono">{comic.isbn}</div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>操作</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center justify-between">
                  <Label htmlFor="status-toggle" className="cursor-pointer">
                    読書ステータス
                  </Label>
                  <div className="flex items-center gap-2">
                    <span className="text-sm text-muted-foreground">
                      {comic.status === "unread" ? "未読" : "既読"}
                    </span>
                    <Switch
                      id="status-toggle"
                      checked={comic.status === "read"}
                      onCheckedChange={(checked) =>
                        updateStatusMutation.mutate({
                          id: comic.id,
                          status: checked ? "read" : "unread",
                        })
                      }
                    />
                  </div>
                </div>

                <AlertDialog>
                  <AlertDialogTrigger asChild>
                    <Button variant="destructive" className="w-full">
                      <Trash2 className="h-4 w-4 mr-2" />
                      削除
                    </Button>
                  </AlertDialogTrigger>
                  <AlertDialogContent>
                    <AlertDialogHeader>
                      <AlertDialogTitle>本当に削除しますか?</AlertDialogTitle>
                      <AlertDialogDescription>
                        この操作は取り消せません。「{comic.title}」を削除してもよろしいですか?
                      </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                      <AlertDialogCancel>キャンセル</AlertDialogCancel>
                      <AlertDialogAction
                        onClick={() => deleteMutation.mutate({ id: comic.id })}
                        className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                      >
                        削除
                      </AlertDialogAction>
                    </AlertDialogFooter>
                  </AlertDialogContent>
                </AlertDialog>
              </CardContent>
            </Card>
          </div>
        </div>
      </main>
    </div>
  );
}
