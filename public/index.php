<?php

declare(strict_types=1);

session_start(); // Add session_start() here

// デバッグモードを有効化
ini_set('display_errors', '1');
error_reporting(E_ALL);

// シンプルなオートローダーを追加
spl_autoload_register(function ($class) {
    // 名前空間のプレフィックスを定義
    $prefixes = [
        'App\\Controllers\\' => __DIR__ . '/../src/Controllers/',
        'App\\Core\\' => __DIR__ . '/../src/Core/'
    ];
    
    // デバッグ出力
    error_log("オートロード試行: " . $class);
    
    // 各プレフィックスをチェック
    foreach ($prefixes as $prefix => $baseDir) {
        // クラス名が名前空間のプレフィックスで始まるか確認
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }
        
        // 相対クラス名を取得
        $relativeClass = substr($class, $len);
        
        // 名前空間の区切りをディレクトリの区切りに変換
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        
        error_log("ファイルを探索: " . $file);
        
        // ファイルが存在するならロード
        if (file_exists($file)) {
            require $file;
            error_log("ファイルを読み込みました: " . $file);
            return true;
        } else {
            error_log("ファイルが見つかりません: " . $file);
        }
    }
    
    return false;
});



// コントローラーは明示的にインクルード（念のため）
require_once __DIR__ . '/../src/Core/Router.php';
require_once __DIR__ . '/../src/Controllers/HomeController.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Controllers/ComicController.php';


use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\ComicController;

$router = new Router();

// Define routes
$router->addRoute('GET', '/', [HomeController::class, 'index']);
$router->addRoute('GET', '/login', [AuthController::class, 'showLoginForm']);
$router->addRoute('POST', '/login', [AuthController::class, 'login']);
$router->addRoute('GET', '/logout', [AuthController::class, 'logout']); // Add this line
$router->addRoute('GET', '/comic_list', [ComicController::class, 'listComics']);

// ↓↓↓ OCR処理用ルートを追加 ↓↓↓
$router->addRoute('POST', '/api/ocr_receipt', [ComicController::class, 'processReceiptOcr']);
// ↑↑↑ OCR処理用ルート追加ここまで ↑↑↑


// --- ここから追加 ---
// 漫画登録画面表示
$router->addRoute('GET', '/comic_register', [ComicController::class, 'showRegisterForm']);
// 国立国会図書館APIから書籍情報取得 (APIエンドポイント)
$router->addRoute('GET', '/api/fetch_comic_info', [ComicController::class, 'fetchComicInfo']);
// 登録する漫画情報をDBに保存 (APIエンドポイント)
$router->addRoute('POST', '/api/save_comics', [ComicController::class, 'saveComics']);

// ===>>> NEW ROUTE FOR NEW RELEASES <<<===
$router->addRoute('GET', '/new-releases', [ComicController::class, 'showNewReleasesPage']);
// ===>>> END OF NEW ROUTE <<<===

// ===>>> NEW: シリーズ一括取得APIルート <<<===
$router->addRoute('POST', '/api/fetch_series_info', [ComicController::class, 'fetchSeriesByTitleAndVolumeRange']);
// ===>>> END OF NEW ROUTE <<<===


// --- 追加ここまで ---


$router->dispatch();