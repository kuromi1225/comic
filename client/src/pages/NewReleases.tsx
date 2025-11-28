import { useAuth } from "@/_core/hooks/useAuth";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { trpc } from "@/lib/trpc";
import { BookOpen, Check } from "lucide-react";
import { Link } from "wouter";
import { toast } from "sonner";

export default function NewReleases() {
  const { user } = useAuth();
  const { data: newReleases } = trpc.newReleases.list.useQuery(undefined, { enabled: !!user });

  const utils = trpc.useUtils();
  const markAsPurchasedMutation = trpc.newReleases.markAsPurchased.useMutation({
    onSuccess: () => {
      utils.newReleases.list.invalidate();
      utils.newReleases.unpurchasedCount.invalidate();
      toast.success("購入済みにマークしました");
    },
  });

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
                <a className="text-sm font-medium text-primary">新刊一覧</a>
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

      <main className="container py-8 max-w-4xl">
        <div className="mb-6">
          <h2 className="text-2xl font-bold">新刊一覧</h2>
          <p className="text-muted-foreground">未購入の新刊情報</p>
        </div>

        {newReleases && newReleases.length > 0 ? (
          <div className="space-y-4">
            {newReleases.map((release) => (
              <Card key={release.id}>
                <CardContent className="p-6">
                  <div className="flex gap-4">
                    <div className="w-20 h-28 bg-muted rounded overflow-hidden flex-shrink-0">
                      {release.imageUrl ? (
                        <img
                          src={release.imageUrl}
                          alt={release.title}
                          className="w-full h-full object-cover"
                        />
                      ) : (
                        <div className="w-full h-full flex items-center justify-center">
                          <BookOpen className="h-8 w-8 text-muted-foreground" />
                        </div>
                      )}
                    </div>
                    <div className="flex-1 min-w-0">
                      <h3 className="font-semibold text-lg mb-1">{release.title}</h3>
                      {release.author && (
                        <p className="text-sm text-muted-foreground mb-1">著者: {release.author}</p>
                      )}
                      {release.releaseDate && (
                        <p className="text-sm text-muted-foreground">
                          発売日: {new Date(release.releaseDate).toLocaleDateString("ja-JP")}
                        </p>
                      )}
                    </div>
                    <div className="flex items-center">
                      <Button
                        onClick={() => markAsPurchasedMutation.mutate({ id: release.id })}
                        disabled={markAsPurchasedMutation.isPending}
                        className="bg-green-600 hover:bg-green-700"
                      >
                        <Check className="h-4 w-4 mr-2" />
                        購入済みにする
                      </Button>
                    </div>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        ) : (
          <Card>
            <CardContent className="py-12 text-center">
              <BookOpen className="h-12 w-12 mx-auto mb-4 text-muted-foreground opacity-50" />
              <p className="text-muted-foreground">未購入の新刊はありません</p>
            </CardContent>
          </Card>
        )}
      </main>
    </div>
  );
}
