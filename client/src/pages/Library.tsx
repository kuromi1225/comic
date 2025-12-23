import { useAuth } from "@/_core/hooks/useAuth";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { trpc } from "@/lib/trpc";
import { BookOpen, Search } from "lucide-react";
import { useState } from "react";
import { Link } from "wouter";

export default function Library() {
  const { user } = useAuth();
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState<"all" | "unread" | "read">("all");
  const [sortBy, setSortBy] = useState<"createdAt" | "title" | "author">("createdAt");

  const { data: comics } = trpc.comics.list.useQuery(
    {
      search: search || undefined,
      status: status === "all" ? undefined : status,
      sortBy,
    },
    { enabled: !!user }
  );

  const utils = trpc.useUtils();
  const updateStatusMutation = trpc.comics.updateStatus.useMutation({
    onSuccess: () => {
      utils.comics.list.invalidate();
      utils.comics.stats.invalidate();
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
                <a className="text-sm font-medium text-primary">蔵書一覧</a>
              </Link>
              <Link href="/series">
                <a className="text-sm font-medium hover:text-primary transition-colors">
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

      <main className="container py-8">
        {/* 検索・フィルタリングエリア */}
        <Card className="mb-6">
          <CardContent className="pt-6">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="書名、著者名、ISBNで検索..."
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  className="pl-9"
                />
              </div>
              <Select value={status} onValueChange={(v) => setStatus(v as typeof status)}>
                <SelectTrigger>
                  <SelectValue placeholder="ステータス" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">すべて</SelectItem>
                  <SelectItem value="unread">未読</SelectItem>
                  <SelectItem value="read">既読</SelectItem>
                </SelectContent>
              </Select>
              <Select value={sortBy} onValueChange={(v) => setSortBy(v as typeof sortBy)}>
                <SelectTrigger>
                  <SelectValue placeholder="並び順" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="createdAt">登録日順</SelectItem>
                  <SelectItem value="title">タイトル順</SelectItem>
                  <SelectItem value="author">著者名順</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </CardContent>
        </Card>

        {/* 蔵書一覧グリッド */}
        {comics && comics.length > 0 ? (
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            {comics.map((comic) => (
              <div key={comic.id} className="group relative">
                <Link href={`/comic/${comic.id}`}>
                  <a>
                    <div className="aspect-[3/4] bg-muted rounded-lg overflow-hidden mb-2 relative">
                      {comic.imageUrl ? (
                        <img
                          src={comic.imageUrl}
                          alt={comic.title}
                          className="w-full h-full object-cover group-hover:scale-105 transition-transform"
                        />
                      ) : (
                        <div className="w-full h-full flex items-center justify-center">
                          <BookOpen className="h-12 w-12 text-muted-foreground" />
                        </div>
                      )}
                      {comic.status === "unread" && (
                        <div className="absolute top-2 right-2 bg-orange-500 text-white text-xs px-2 py-1 rounded">
                          未読
                        </div>
                      )}
                    </div>
                    <h3 className="text-sm font-medium line-clamp-2 group-hover:text-primary transition-colors">
                      {comic.title}
                    </h3>
                    <p className="text-xs text-muted-foreground line-clamp-1">{comic.author}</p>
                  </a>
                </Link>
                {comic.status === "unread" && (
                  <Button
                    size="sm"
                    variant="secondary"
                    className="w-full mt-2 opacity-0 group-hover:opacity-100 transition-opacity"
                    onClick={() =>
                      updateStatusMutation.mutate({ id: comic.id, status: "read" })
                    }
                  >
                    既読にする
                  </Button>
                )}
              </div>
            ))}
          </div>
        ) : (
          <div className="text-center py-12 text-muted-foreground">
            <BookOpen className="h-12 w-12 mx-auto mb-4 opacity-50" />
            <p>該当する漫画が見つかりませんでした</p>
          </div>
        )}
      </main>
    </div>
  );
}
