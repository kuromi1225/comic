<?php
// マイグレーション実行スクリプト

// データベース接続情報の読み込み
require_once __DIR__ . '/src/Core/Database.php';

use App\Core\Database;

// 環境変数の読み込み (docker-composeで設定されている環境変数を使用)
$host = getenv('MYSQL_HOST') ?: 'db';
$dbname = getenv('MYSQL_DATABASE') ?: 'comic_db';
$user = getenv('MYSQL_USER') ?: 'root';
$password = getenv('MYSQL_PASSWORD') ?: 'password';

// データベース接続
try {
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $db = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    echo "データベース接続成功!\n";
} catch (PDOException $e) {
    error_log("データベース接続エラー: " . $e->getMessage());
    die("データベース接続エラー: " . $e->getMessage() . "\n");
}

// マイグレーションファイルのディレクトリ
$migrationsDir = __DIR__ . '/migrations'; // 修正: プロジェクトルートからの相対パス
$migrationFiles = scandir($migrationsDir);

// migrations テーブルが存在するか確認し、なければ作成
try {
    $db->query("SELECT 1 FROM migrations LIMIT 1");
    echo "migrationsテーブルは既に存在しています。\n";
} catch (PDOException $e) {
    // テーブルが存在しない場合
    if ($e->getCode() == '42S02') {
        echo "migrationsテーブルを作成中...\n";
        $initialMigrationFile = $migrationsDir . '/20250419000000_create_migrations_table.sql';
        if (file_exists($initialMigrationFile)) {
            $sql = file_get_contents($initialMigrationFile);
            try {
                $db->exec($sql);
                // テーブル作成後に記録
                $stmt = $db->prepare("INSERT INTO migrations (migration) VALUES (?)");
                $stmt->execute(['20250419000000_create_migrations_table.sql']);
                echo "migrationsテーブルが作成されました。\n";
            } catch (PDOException $initError) {
                die("migrationsテーブル作成エラー: " . $initError->getMessage() . "\n");
            }
        } else {
            die("初期マイグレーションファイルが見つかりません。\n");
        }
    } else {
        // その他のエラー
        die("データベースエラー: " . $e->getMessage() . "\n");
    }
}

// 実行済みのマイグレーションを取得
$executedMigrations = [];
$stmt = $db->query("SELECT migration FROM migrations");
if ($stmt) {
    $executedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

echo "マイグレーションを開始します...\n";

// マイグレーションファイルをソートして適用
sort($migrationFiles);
foreach ($migrationFiles as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
        if (!in_array($file, $executedMigrations)) {
            echo "マイグレーション実行中: " . $file . "\n";
            $sql = file_get_contents($migrationsDir . '/' . $file);
            try {
                $db->exec($sql);
                // 実行成功したら記録
                $stmt = $db->prepare("INSERT INTO migrations (migration) VALUES (?)");
                $stmt->execute([$file]);
                echo "マイグレーション成功: " . $file . "\n";
            } catch (PDOException $e) {
                die("マイグレーションエラー " . $file . ": " . $e->getMessage() . "\n");
            }
        } else {
            echo "スキップ: " . $file . " (既に実行済み)\n";
        }
    }
}

echo "マイグレーションが完了しました。\n";