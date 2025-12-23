import { useAuth } from "@/_core/hooks/useAuth";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { trpc } from "@/lib/trpc";
import { BookOpen, Search, AlertCircle, CheckCircle2 } from "lucide-react";
import { useState } from "react";
import { Link } from "wouter";

export default function Series() {
  const { user } = useAuth();
  const [searchQuery, setSearchQuery] = useState("");

  const { data: seriesList, isLoading } = trpc.series.list.useQuery(undefined, {
    enabled: !!user,
  });

  if (!user) {
    return <div>ログインが必要です</div>;
  }

  const filteredSeries = seriesList?.filter((series) =>
    series.seriesName.toLowerCase().includes(searchQuery.toLowerCase())
  );

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
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">シリーズ一覧</h1>
          <p className="text-gray-600">
            シリーズごとにグループ化された蔵書を確認できます
          </p>
        </div>

        {/* 検索バー */}
        <div className="mb-6">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5" />
            <Input
              type="text"
              placeholder="シリーズ名で検索..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="pl-10"
            />
          </div>
        </div>

        {/* シリーズ一覧 */}
        {isLoading ? (
          <div className="text-center py-12">
            <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p className="mt-4 text-gray-600">読み込み中...</p>
          </div>
        ) : filteredSeries && filteredSeries.length > 0 ? (
          <div className="grid gap-4">
            {filteredSeries.map((series) => (
              <Link key={series.seriesName} href={`/series/${encodeURIComponent(series.seriesName)}`}>
                <a>
                  <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                    <CardHeader>
                      <div className="flex items-start justify-between">
                        <div className="flex-1">
                          <CardTitle className="text-xl mb-2">
                            {series.seriesName}
                          </CardTitle>
                          <div className="flex items-center gap-4 text-sm text-gray-600">
                            <span>全{series.totalVolumes}巻</span>
                            <span>所有: {series.comics.length}冊</span>
                          </div>
                        </div>
                        <div>
                          {series.hasAllVolumes ? (
                            <div className="flex items-center gap-2 text-green-600 bg-green-50 px-3 py-1 rounded-full">
                              <CheckCircle2 className="h-4 w-4" />
                              <span className="text-sm font-medium">全巻所有</span>
                            </div>
                          ) : (
                            <div className="flex items-center gap-2 text-orange-600 bg-orange-50 px-3 py-1 rounded-full">
                              <AlertCircle className="h-4 w-4" />
                              <span className="text-sm font-medium">
                                抜け巻: {series.missingVolumes.length}巻
                              </span>
                            </div>
                          )}
                        </div>
                      </div>
                    </CardHeader>
                    <CardContent>
                      <div className="flex flex-wrap gap-2">
                        {series.comics.slice(0, 10).map((comic) => (
                          <div
                            key={comic.id}
                            className="w-16 h-24 bg-gray-200 rounded flex items-center justify-center text-xs font-medium text-gray-600"
                          >
                            {comic.volume ? `${comic.volume}巻` : "?"}
                          </div>
                        ))}
                        {series.comics.length > 10 && (
                          <div className="w-16 h-24 bg-gray-100 rounded flex items-center justify-center text-xs text-gray-500">
                            +{series.comics.length - 10}
                          </div>
                        )}
                      </div>
                      {series.missingVolumes.length > 0 && series.missingVolumes.length <= 10 && (
                        <div className="mt-3 text-sm text-orange-600">
                          抜け巻: {series.missingVolumes.join(", ")}巻
                        </div>
                      )}
                    </CardContent>
                  </Card>
                </a>
              </Link>
            ))}
          </div>
        ) : (
          <Card>
            <CardContent className="py-12 text-center">
              <BookOpen className="h-12 w-12 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-600">
                {searchQuery
                  ? "該当するシリーズが見つかりませんでした"
                  : "シリーズ情報がある漫画がまだ登録されていません"}
              </p>
            </CardContent>
          </Card>
        )}
      </main>
    </div>
  );
}
