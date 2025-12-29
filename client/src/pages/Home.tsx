import { useAuth } from "@/_core/hooks/useAuth";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { getLoginUrl } from "@/const";
import { trpc } from "@/lib/trpc";
import { BookOpen, BookMarked, Library, AlertCircle } from "lucide-react";
import { Link } from "wouter";

export default function Home() {
  const { user, loading } = useAuth();
  const { data: stats } = trpc.comics.stats.useQuery(undefined, { enabled: !!user });
  const { data: recentComics } = trpc.comics.recent.useQuery({ limit: 5 }, { enabled: !!user });
  const { data: unpurchasedCount } = trpc.newReleases.unpurchasedCount.useQuery(undefined, { enabled: !!user });

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-muted-foreground">読み込み中...</div>
      </div>
    );
  }

  if (!user) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center bg-gradient-to-b from-background to-muted/20">
        <div className="container max-w-2xl text-center space-y-8">
          <div className="space-y-4">
            <h1 className="text-5xl font-bold tracking-tight">漫画管理システム</h1>
            <p className="text-xl text-muted-foreground">
              あなたの蔵書を効率的に管理しましょう
            </p>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-left">
            <Card>
              <CardHeader>
                <Library className="h-8 w-8 text-primary mb-2" />
                <CardTitle className="text-lg">蔵書管理</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-muted-foreground">
                  ISBNから簡単登録。書影付きで見やすく管理。
                </p>
              </CardContent>
            </Card>
            <Card>
              <CardHeader>
                <BookOpen className="h-8 w-8 text-primary mb-2" />
                <CardTitle className="text-lg">読書記録</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-muted-foreground">
                  未読・既読を管理して読書の進捗を把握。
                </p>
              </CardContent>
            </Card>
            <Card>
              <CardHeader>
                <BookMarked className="h-8 w-8 text-primary mb-2" />
                <CardTitle className="text-lg">新刊通知</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-muted-foreground">
                  シリーズの新刊情報を自動でチェック。
                </p>
              </CardContent>
            </Card>
          </div>
          <Button size="lg" asChild>
            <a href={getLoginUrl()}>ログインして始める</a>
          </Button>
        </div>
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
              <Link href="/series">
                <a className="text-gray-600 hover:text-blue-600 transition-colors">
                  シリーズ一覧
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

      <main className="container py-8 space-y-8">
        {/* 新刊通知エリア */}
        {unpurchasedCount && unpurchasedCount > 0 && (
          <Link href="/new-releases">
            <a>
              <Card className="bg-primary/5 border-primary/20 hover:bg-primary/10 transition-colors cursor-pointer">
                <CardHeader>
                  <div className="flex items-center gap-2">
                    <AlertCircle className="h-5 w-5 text-primary" />
                    <CardTitle className="text-primary">
                      未購入の新刊が {unpurchasedCount} 件あります
                    </CardTitle>
                  </div>
                </CardHeader>
              </Card>
            </a>
          </Link>
        )}

        {/* 蔵書統計エリア */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <Card>
            <CardHeader>
              <CardDescription>総蔵書数</CardDescription>
              <CardTitle className="text-4xl">{stats?.total || 0}</CardTitle>
            </CardHeader>
          </Card>
          <Card>
            <CardHeader>
              <CardDescription>未読</CardDescription>
              <CardTitle className="text-4xl text-orange-600">{stats?.unread || 0}</CardTitle>
            </CardHeader>
          </Card>
          <Card>
            <CardHeader>
              <CardDescription>既読</CardDescription>
              <CardTitle className="text-4xl text-green-600">{stats?.read || 0}</CardTitle>
            </CardHeader>
          </Card>
        </div>

        {/* 最近登録した漫画エリア */}
        <Card>
          <CardHeader>
            <CardTitle>最近登録した漫画</CardTitle>
            <CardDescription>直近5件の登録</CardDescription>
          </CardHeader>
          <CardContent>
            {recentComics && recentComics.length > 0 ? (
              <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                {recentComics.map((comic) => (
                  <Link key={comic.id} href={`/comic/${comic.id}`}>
                    <a className="group">
                      <div className="aspect-[3/4] bg-muted rounded-lg overflow-hidden mb-2">
                        {(comic.imageData || comic.imageUrl) ? (
                          <img
                            src={comic.imageData || comic.imageUrl || ""}
                            alt={comic.title}
                            className="w-full h-full object-cover group-hover:scale-105 transition-transform"
                          />
                        ) : (
                          <div className="w-full h-full flex items-center justify-center">
                            <BookOpen className="h-12 w-12 text-muted-foreground" />
                          </div>
                        )}
                      </div>
                      <h3 className="text-sm font-medium line-clamp-2 group-hover:text-primary transition-colors">
                        {comic.title}
                      </h3>
                      <p className="text-xs text-muted-foreground line-clamp-1">{comic.author}</p>
                    </a>
                  </Link>
                ))}
              </div>
            ) : (
              <div className="text-center py-12 text-muted-foreground">
                <BookOpen className="h-12 w-12 mx-auto mb-4 opacity-50" />
                <p>まだ漫画が登録されていません</p>
                <Link href="/register">
                  <Button variant="link" className="mt-2">
                    最初の漫画を登録する
                  </Button>
                </Link>
              </div>
            )}
          </CardContent>
        </Card>
      </main>
    </div>
  );
}
