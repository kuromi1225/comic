# 漫画管理システム

7000冊を超える蔵書を効率的に管理するためのWebアプリケーションです。

## 主な機能

- **蔵書管理**: ISBN登録、検索、削除、未読/既読管理
- **CSV一括登録**: ISBNリストから一括で漫画を登録
- **シリーズ管理**: 自動グループ化と抜け巻検出
- **OCR機能**: レシートからISBNを自動読み取り
- **外部API連携**: openBD・国立国会図書館APIから書誌情報を自動取得
- **書影表示**: 漫画の表紙画像を表示
- **新刊通知**: 新刊情報の管理

## 技術スタック

- **Frontend**: React 19 + TypeScript + Tailwind CSS 4
- **Backend**: Node.js + Express + tRPC
- **Database**: MySQL (Drizzle ORM)
- **OCR**: Tesseract.js
- **Testing**: Vitest

## Docker での起動方法（推奨）

### 前提条件

- Docker
- Docker Compose

### 起動手順

1. **リポジトリをクローン**
```bash
git clone https://github.com/kuromi1225/comic.git
cd comic
```

2. **Docker Composeで起動**
```bash
docker compose up -d
```

これだけで、以下が自動的に実行されます:
- MySQLデータベースの起動
- データベースの初期化
- アプリケーションのビルドと起動

3. **ブラウザでアクセス**
```
http://localhost:3000
```

### 停止方法

```bash
docker compose down
```

### データを完全に削除して再起動

```bash
docker compose down -v
docker compose up -d
```

### ログの確認

```bash
# 全サービスのログ
docker compose logs -f

# アプリケーションのログのみ
docker compose logs -f app

# データベースのログのみ
docker compose logs -f db
```

## ローカル開発環境での起動方法

### 前提条件

- Node.js 22.x以上
- pnpm
- MySQL 8.0以上

### 起動手順

1. **依存関係のインストール**
```bash
pnpm install
```

2. **環境変数の設定**

`.env`ファイルを作成:
```env
DATABASE_URL=mysql://manga_user:manga_password@localhost:3306/manga_manager
JWT_SECRET=your-secret-key-here
VITE_APP_TITLE=漫画管理システム
```

3. **データベースのセットアップ**
```bash
pnpm db:push
```

4. **開発サーバーの起動**
```bash
pnpm dev
```

5. **ブラウザでアクセス**
```
http://localhost:3000
```

## 本番環境へのデプロイ

### ビルド

```bash
pnpm build
```

### 起動

```bash
pnpm start
```

## テストの実行

```bash
pnpm test
```

## データベース管理

### マイグレーションの生成と適用

```bash
pnpm db:push
```

### データベースのリセット

```bash
# Docker環境の場合
docker compose down -v
docker compose up -d

# ローカル環境の場合
# MySQLに接続してデータベースを削除・再作成後
pnpm db:push
```

## 使い方

### 1. 漫画の登録

#### 手動登録
1. 「漫画を登録」ページに移動
2. 「手動入力」タブでISBNを入力
3. 「登録」ボタンをクリック

#### CSV一括登録
1. ISBNのリストを含むCSVファイルを準備（各行の最初の列がISBN）
2. 「CSV一括登録」タブでファイルをアップロード
3. 「一括登録を開始」ボタンをクリック

#### OCR登録
1. 「OCR」タブでレシートを撮影またはアップロード
2. 自動的にISBNが抽出されます
3. 各ISBNの「登録」ボタンをクリック

### 2. 蔵書の検索

1. 「蔵書一覧」ページで検索ボックスにキーワードを入力
2. 未読/既読でフィルタリング
3. タイトル/著者/出版社でソート

### 3. シリーズ管理

1. 「シリーズ一覧」ページで全シリーズを確認
2. シリーズ名をクリックして詳細表示
3. 抜け巻が赤色でハイライト表示されます

### 4. 新刊確認

1. トップページで新刊通知を確認
2. 「新刊一覧」ページで詳細を表示

## トラブルシューティング

### Docker起動時にエラーが出る

```bash
# コンテナとボリュームを完全に削除して再起動
docker compose down -v
docker compose up -d --build
```

### データベース接続エラー

- `docker compose logs db` でデータベースのログを確認
- データベースが起動するまで少し時間がかかる場合があります

### ポート3000が既に使用されている

`docker-compose.yml`の`ports`セクションを変更:
```yaml
ports:
  - "8080:3000"  # 8080など別のポートに変更
```

## ライセンス

MIT

## 作者

Manga Manager System
