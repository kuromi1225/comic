# コミックコレクション管理

![バナー](public/banner.png)

## 概要

レシートのOCR処理、新刊情報の追跡などの機能を備えた、あなたの漫画コレクションを管理するためのウェブアプリケーションです。

## 目次

- [プロジェクトタイトル](#プロジェクトタイトル)
- [バナー](#バナー)
- [概要](#概要)
- [目次](#目次)
- [主な機能](#主な機能)
- [技術スタック](#技術スタック)
- [システムアーキテクチャ概要](#システムアーキテクチャ概要)
- [前提条件](#前提条件)
- [インストールとセットアップ](#インストールとセットアップ)
- [データベース設定](#データベース設定)
- [アプリケーションの実行](#アプリケーションの実行)
- [主要な機能（使用例）](#主要な機能使用例)
- [OCRサービス詳細](#ocrサービス詳細)
- [環境変数](#環境変数)
- [貢献](#貢献)
- [ライセンス](#ライセンス)

## 主な機能

- ユーザー認証（ログイン/ログアウト）
- 漫画管理（一覧表示、新規漫画登録）
- ISBNを使用した国立国会図書館（NDL）からの漫画データ自動取得
- 表紙画像表示
- 漫画シリーズの巻数管理
- 購入履歴を素早く記録するためのOCRレシート処理
- 「コミック発売日カレンダー」からスクレイピングしたデータによる新刊カレンダー
- タイトル/巻数によるシリーズ検索

## 技術スタック

- **バックエンド:** PHP (カスタムMVC構造)
- **フロントエンド:** HTML, CSS, JavaScript
- **データベース:** MySQL
- **ウェブサーバー:** Nginx
- **コンテナ化:** Docker, Docker Compose
- **OCRサービス:** Python (Flask), Tesseract OCR

## システムアーキテクチャ概要

このアプリケーションは、カスタムMVC構造を使用したモノリシックなPHPアプリケーションであり、ウェブフロントエンドとAPIバックエンドの両方を提供します。データストレージとしてMySQLデータベースと連携します。アップロードされたレシート画像を処理するために、Python (Flask) と Tesseract で構築された外部OCRサービスが使用されます。Nginxがウェブサーバーとして機能します。システム全体はDockerを使用してコンテナ化され、Docker Composeによって管理されます。

## 前提条件

- Docker
- Docker Compose

## インストールとセットアップ

1.  **リポジトリをクローンします:**
    ```bash
    git clone <repository-url>
    cd <repository-name>
    ```

2.  **環境変数を設定します:**
    `.env.example` ファイルが存在する場合はコピーして `.env` ファイルを作成します。存在しない場合は、新しい `.env` ファイルを作成し、以下の変数を追加して、ご自身の環境に合わせて更新してください。これらの変数は `docker-compose.yml` の `php` サービスおよび `config/database.php` で使用されます。

    ```env
    # Database Credentials (as used by docker-compose.yml and config/database.php)
    MYSQL_HOST=db
    MYSQL_DATABASE=comic_db
    MYSQL_USER=comic_user
    MYSQL_PASSWORD=comic_password
    MYSQL_ROOT_PASSWORD=supersecretpassword 
    # Note: MYSQL_ROOT_PASSWORD is for the db service itself, not directly used by the PHP app's config/database.php but essential for phpMyAdmin and DB initialization.
    ```
    **注:** PHPアプリケーション自体は `config/database.php` 内の `getenv()` を介して `MYSQL_HOST`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD` を読み取ります。これらが希望する設定と一致することを確認してください。`APP_KEY` のようなLaravel固有の変数は使用されていません。

3.  **アプリケーションコンテナを起動します:**
    ```bash
    docker-compose up -d
    ```

## データベース設定

アプリケーションはカスタムマイグレーションスクリプトを使用します。

1.  **マイグレーションを実行します:**
    データベーステーブルをセットアップするには、コンテナが実行された後、以下のコマンドを実行します（`migrate.php` と `migrations/` ディレクトリが存在することを確認してください）:
    ```bash
    docker-compose exec php php migrate.php
    ```

2.  **初期管理者ユーザー:**
    データベースシーダーは、以下のユーザー名で初期管理者ユーザーを作成します:
    -   **ユーザー名:** `makkenro`

    このユーザーのパスワードはデータベース内でハッシュ化されています。一般的な開発用パスワードでログインできない場合は、以下のいずれかの対応が必要になる場合があります:
    *   データベースの `users` テーブルでパスワードを直接更新する。
    *   パスワードリセット機能がまだ利用できない場合は実装する。
    `.env` ファイルの認証情報 (`DB_USERNAME`, `DB_PASSWORD`) はMySQLデータベース接続用であり、アプリケーションユーザーのログイン用ではありません。

## アプリケーションの実行

-   **アクセスURL:** コンテナが起動したら、[http://localhost](http://localhost) でアプリケーションにアクセスできます（Nginxはポート80でリッスンするように設定されています）。
-   **デフォルトログイン:** `makkenro` ユーザーの認証情報を使用してログインします。（このパスワードの設定・管理方法は、データベースのシーディングや特定の設定などを通じて確認する必要があります）。

## 主要な機能（使用例）

ルートは `src/Controllers/ComicController.php` のメソッドと一般的なMVCパターンに基づいています。確定的なルート定義については、`src/Core/Router.php` およびアプリケーションのエントリポイント（通常は `public/index.php`、提供されていません）でのその使用法を参照してください。

-   **ユーザー認証:**
    -   ログイン: `/login` (標準と想定)
    -   ログアウト: `/logout` (標準と想定)

-   **漫画管理:**
    -   漫画一覧: `/comics` (GET - `ComicController::listComics`)
    -   登録フォーム表示: `/comics/register` (GET - `ComicController::showRegisterForm`)
    -   新規漫画保存: `/comics/save` (POST - `ComicController::saveComics`)
    -   NDLからISBNで情報取得（登録フォーム上）: アプリケーションは国立国会図書館（NDL）OpenSearch API（ハードコードされたURL: `https://iss.ndl.go.jp/api/opensearch`）を `ComicController::fetchComicInfo` 経由で使用します（登録ページのJavaScriptからのGETリクエスト、例: `/comics/fetch-ndl-info?isbn=...` のようなルートでトリガーされる可能性が高い）。
    -   表紙画像: 漫画一覧ページや詳細ページに表示されます。

-   **巻数管理:**
    -   漫画の登録および更新の一部として管理されます。

-   **OCRレシート処理:**
    -   ワークフロー: 関連ページでレシート画像をアップロードします。バックエンド（PHP）はそれをPython OCRサービス（`ComicController.php` でハードコードされた `http://ocr_service:5000/ocr`）に送信します。OCRサービスは抽出されたISBNを返します。
    -   アップロードURL: `/comics/ocr` (POST - `ComicController::processReceiptOcr`)

-   **新刊カレンダー:**
    -   スクレイピング元: データは「hon-hikidashi.jp」からスクレイピングされます（URLは `ComicController::scrapeHonHikidashi` で構築）。
    -   キャッシュ: リリースデータは `new_releases` データベーステーブルにキャッシュされます。
    -   アクセスURL: `/new-releases` (GET - `ComicController::showNewReleasesPage`)

-   **タイトルと巻数範囲によるシリーズ検索（API風）:**
    -   URL: `/api/comics/fetch-series` (POST - `ComicController::fetchSeriesByTitleAndVolumeRange`)
    -   ペイロード例: `{ "title": "One Piece", "startVolume": "1", "endVolume": "10" }`

-   **ISBNによる単一漫画情報取得（API風）:**
    -   URL: `/api/comics/info?isbn=...` (GET - `ComicController::fetchComicInfoByIsbnPublic`)


## OCRサービス詳細

-   **目的:** OCR（光学文字認識）サービスは、アップロードされたレシート画像からISBNを抽出します。
-   **PHPアプリ用内部APIエンドポイント:** PHPアプリケーションは、（`ocr_service` Dockerコンテナ内で実行されている）OCRサービスと、その内部ネットワークURL `http://ocr_service:5000/ocr` を介して通信します。このURLは現在 `ComicController.php` にハードコードされています。
-   **入力:** multipart/form-data POSTリクエストの一部として送信される画像ファイル（例: JPEG, PNG）。
-   **出力:** 抽出されたISBNを含むJSONオブジェクト。例:
    ```json
    {
      "isbns": ["9784088824225", "9784088825246"]
    }
    ```

## 環境変数

以下の環境変数は、主にデータベース接続のためにアプリケーションを設定するために使用されます。これらはプロジェクトのルートにある `.env` ファイルで定義する必要があります。

-   `MYSQL_HOST`: MySQLデータベースサーバーのホスト名（Docker Compose使用時の `db` など）。
-   `MYSQL_DATABASE`: 使用するデータベースの名前。
-   `MYSQL_USER`: データベース接続用のユーザー名。
-   `MYSQL_PASSWORD`: データベースユーザーのパスワード。
-   `MYSQL_ROOT_PASSWORD`: MySQLサーバーのrootパスワード（`docker-compose.yml` の `db` サービスおよびphpMyAdminで使用）。

**注:**
- 国立国会図書館APIエンドポイント（`https://iss.ndl.go.jp/api/opensearch`）および新刊情報スクレイピング用のベースURL（`https://hon-hikidashi.jp`）は、現在 `src/Controllers/ComicController.php` にハードコードされています。これらは環境変数を介して設定することはできません。
- OCRサービスURL（`http://ocr_service:5000/ocr`）も `src/Controllers/ComicController.php` にハードコードされています。

## 貢献

問題を見つけたり、改善提案がある場合は、このリポジトリのGitHub課題トラッカーを使用して報告してください。

1.  問題がすでに存在するかどうかを確認します。
2.  存在しない場合は、新しい課題を作成し、可能な限り詳細な情報を提供してください:
    *   バグを再現する手順。
    *   期待される動作。
    *   実際の動作。
    *   スクリーンショット（該当する場合）。

## ライセンス

未ライセンス
