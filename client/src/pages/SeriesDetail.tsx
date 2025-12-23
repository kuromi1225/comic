import { useAuth } from "@/_core/hooks/useAuth";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { trpc } from "@/lib/trpc";
import { BookOpen, ArrowLeft, CheckCircle2, AlertCircle, X } from "lucide-react";
import { Link, useRoute } from "wouter";

export default function SeriesDetail() {
  const { user } = useAuth();
  const [, params] = useRoute("/series/:seriesName");
  const seriesName = params?.seriesName ? decodeURIComponent(params.seriesName) : "";

  const { data: series, isLoading } = trpc.series.detail.useQuery(
    { seriesName },
    { enabled: !!user && !!seriesName }
  );

  if (!user) {
    return <div>ログインが必要です</div>;
  }

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <p className="mt-4 text-gray-600">読み込み中...</p>
        </div>
      </div>
    );
  }

  if (!series) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <p className="text-gray-600">シリーズが見つかりませんでした</p>
          <Link href="/series">
            <Button className="mt-4">シリーズ一覧に戻る</Button>
          </Link>
        </div>
      </div>
    );
  }

  // 全巻のリストを作成（所有・未所有を含む）
  const allVolumes: Array<{ volume: number; comic?: typeof series.comics[0] }> = [];
  for (let i = 1; i <= series.totalVolumes; i++) {
    const comic = series.comics.find((c) => c.volume === i);
    allVolumes.push({ volume: i, comic });
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50">
      {/* ヘッダー */}
      <header className="bg-white border-b sticky top-0 z-10 shadow-sm">
        <div className="container mx-auto px-4 py-4">
          <div className="flex items-center justify-between">
            <Link href="/">
              <a className="text-2xl font-bold text-blue-600 hover:text-blue-700 transition-colors flex items-center gap-2">
                <BookOpen className="h-7 w-7" />
                漫画管理システム
              </a>
            </Link>
            <nav className="flex gap-6">
              <Link href="/">
                <a className="text-gray-600 hover:text-blue-600 transition-colors">
                  ダッシュボード
                </a>
              </Link>
              <Link href="/library">
                <a className="text-gray-600 hover:text-blue-600 transition-colors">
                  蔵書一覧
                </a>
              </Link>
              <Link href="/series">
                <a className="text-blue-600 font-semibold">シリーズ一覧</a>
              </Link>
              <Link href="/new-releases">
                <a className="text-gray-600 hover:text-blue-600 transition-colors">
                  新刊一覧
                </a>
              </Link>
              <Link href="/register">
                <a className="text-gray-600 hover:text-blue-600 transition-colors">
                  漫画を登録
                </a>
              </Link>
            </nav>
          </div>
        </div>
      </header>

      {/* メインコンテンツ */}
      <main className="container mx-auto px-4 py-8">
        <Link href="/series">
          <Button variant="ghost" className="mb-6">
            <ArrowLeft className="h-4 w-4 mr-2" />
            シリーズ一覧に戻る
          </Button>
        </Link>

        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-4">{series.seriesName}</h1>
          <div className="flex items-center gap-6 text-gray-600">
            <div className="flex items-center gap-2">
              <span className="text-sm">全{series.totalVolumes}巻</span>
            </div>
            <div className="flex items-center gap-2">
              <span className="text-sm">所有: {series.comics.length}冊</span>
            </div>
            {series.hasAllVolumes ? (
              <div className="flex items-center gap-2 text-green-600">
                <CheckCircle2 className="h-5 w-5" />
                <span className="text-sm font-medium">全巻所有</span>
              </div>
            ) : (
              <div className="flex items-center gap-2 text-orange-600">
                <AlertCircle className="h-5 w-5" />
                <span className="text-sm font-medium">
                  抜け巻: {series.missingVolumes.length}巻
                </span>
              </div>
            )}
          </div>
        </div>

        {/* 抜け巻の警告 */}
        {series.missingVolumes.length > 0 && (
          <Card className="mb-6 border-orange-200 bg-orange-50">
            <CardContent className="py-4">
              <div className="flex items-start gap-3">
                <AlertCircle className="h-5 w-5 text-orange-600 mt-0.5" />
                <div>
                  <p className="font-medium text-orange-900 mb-1">抜け巻があります</p>
                  <p className="text-sm text-orange-700">
                    {series.missingVolumes.length <= 20
                      ? `${series.missingVolumes.join(", ")}巻`
                      : `${series.missingVolumes.slice(0, 20).join(", ")}巻 他${series.missingVolumes.length - 20}巻`}
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>
        )}

        {/* 巻数一覧 */}
        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4">
          {allVolumes.map(({ volume, comic }) => (
            <div key={volume}>
              {comic ? (
                <Link href={`/comic/${comic.id}`}>
                  <a>
                    <Card className="hover:shadow-lg transition-shadow cursor-pointer h-full">
                      <CardContent className="p-4">
                        <div className="aspect-[3/4] bg-gray-200 rounded mb-3 flex items-center justify-center overflow-hidden">
                          {comic.imageUrl ? (
                            <img
                              src={comic.imageUrl}
                              alt={comic.title}
                              className="w-full h-full object-cover"
                            />
                          ) : (
                            <BookOpen className="h-12 w-12 text-gray-400" />
                          )}
                        </div>
                        <div className="text-center">
                          <p className="font-semibold text-sm mb-1">{volume}巻</p>
                          <div className="flex items-center justify-center gap-1">
                            <CheckCircle2 className="h-3 w-3 text-green-600" />
                            <span className="text-xs text-green-600">所有</span>
                          </div>
                        </div>
                      </CardContent>
                    </Card>
                  </a>
                </Link>
              ) : (
                <Card className="border-dashed border-2 border-orange-300 bg-orange-50/50 h-full">
                  <CardContent className="p-4">
                    <div className="aspect-[3/4] bg-orange-100 rounded mb-3 flex items-center justify-center">
                      <X className="h-12 w-12 text-orange-400" />
                    </div>
                    <div className="text-center">
                      <p className="font-semibold text-sm mb-1 text-orange-900">{volume}巻</p>
                      <div className="flex items-center justify-center gap-1">
                        <AlertCircle className="h-3 w-3 text-orange-600" />
                        <span className="text-xs text-orange-600">未所有</span>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              )}
            </div>
          ))}
        </div>

        {/* 巻数不明の漫画 */}
        {series.comics.some((c) => c.volume === null) && (
          <div className="mt-8">
            <h2 className="text-xl font-bold text-gray-900 mb-4">巻数不明</h2>
            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4">
              {series.comics
                .filter((c) => c.volume === null)
                .map((comic) => (
                  <Link key={comic.id} href={`/comic/${comic.id}`}>
                    <a>
                      <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                        <CardContent className="p-4">
                          <div className="aspect-[3/4] bg-gray-200 rounded mb-3 flex items-center justify-center overflow-hidden">
                            {comic.imageUrl ? (
                              <img
                                src={comic.imageUrl}
                                alt={comic.title}
                                className="w-full h-full object-cover"
                              />
                            ) : (
                              <BookOpen className="h-12 w-12 text-gray-400" />
                            )}
                          </div>
                          <div className="text-center">
                            <p className="text-xs text-gray-600 line-clamp-2">{comic.title}</p>
                          </div>
                        </CardContent>
                      </Card>
                    </a>
                  </Link>
                ))}
            </div>
          </div>
        )}
      </main>
    </div>
  );
}
