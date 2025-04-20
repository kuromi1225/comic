<?php

namespace App\Controllers;

use App\Core\Database;
use PDO;

class ComicController
{


    // ディレクトリパスを定数または設定ファイルで管理するのが望ましい
    private const COVER_IMAGE_DIR_PATH = __DIR__ . '/../../public/images/covers'; // 保存する物理パス
    private const COVER_IMAGE_URL_BASE = '/images/covers';          // DBに保存し、HTMLで使うURLパス

    public function listComics() // 引数 $title は一旦削除 (クエリパラメータ処理は後述)
    {
        $titleFilter = $_GET['title'] ?? null; // GETパラメータからタイトルを取得
        $db = Database::getConnection(); // データベース接続を取得
        $comics = []; // 初期化

        try {
            $query = "SELECT id, title, author, publisher, total_volumes, cover_image FROM comics"; // 必要なカラムを選択
            $params = [];

            if ($titleFilter) {
                $query .= " WHERE title LIKE :title";
                $params[':title'] = "%" . $titleFilter . "%";
            }
            $query .= " ORDER BY title"; // タイトル順にソート (任意)

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $comics = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error fetching comics: " . $e->getMessage());
            // エラー処理 (例: エラーページ表示など)
            // ここでは空の配列を渡すか、エラーメッセージを表示する
            echo "漫画データの取得中にエラーが発生しました。";
            // デバッグ用にエラー詳細を表示 (本番では削除)
            // echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
            // exit; // 必要に応じて処理を中断
        }


        // 取得したデータをビューに渡して表示
        require_once __DIR__ . '/../../views/comic_list.php'; // <<<--- この行を追加
        // return $this->db->fetchAll($query, $params); // <<<--- 変更前
    }
    // --- ここから追加 ---

    /**
     * 漫画登録画面を表示
     */
    public function showRegisterForm(): void
    {
        // ログインチェック (他のページと同様に)
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        require_once __DIR__ . '/../../views/comic_register.php';
    }

    /**
     * ISBNから国立国会図書館サーチAPIを使って書籍情報を取得するAPI
     */
    /**
     * ISBNから国立国会図書館サーチAPIを使って書籍情報を取得するAPI
     * file_get_contents から curl に変更し、エラーハンドリングを強化
     */
    public function fetchComicInfo(): void
    {
        header('Content-Type: application/json');
        $isbn = $_GET['isbn'] ?? null;

        // ISBNのバリデーション (13桁 or 10桁)
        if (!$isbn || !preg_match('/^([0-9]{13}|[0-9]{9}[0-9X])$/', $isbn)) {
            // 10桁ISBNは後で13桁に変換する必要があるか確認 (thumbnail URLは13桁想定)
            // ここでは一旦、入力された形式でバリデーション
            if ($isbn && !preg_match('/^[0-9]{13}$/', $isbn)) {
                // thumbnail URLが厳密に13桁のみを受け付ける場合、
                // ここで10桁->13桁変換処理を入れるか、エラーにする必要がある。
                // NDLのドキュメントでは「13桁かつハイフン区切りなし」と明記されているため、
                // 10桁の場合はエラーにするか、変換を試みる。
                // 今回はシンプルに、13桁でない場合はthumbnail URLは空にする。
                // (OpenSearch自体は10桁でも検索できる)
                error_log("ISBN {$isbn} is not 13 digits, thumbnail URL might not work.");
                // または、ここでエラーを返す
                // http_response_code(400);
                // echo json_encode(['error' => '書影URL生成のため、13桁のISBNを指定してください。']);
                // return;
            } else if (!$isbn) {
                http_response_code(400);
                echo json_encode(['error' => '有効なISBNコードを指定してください。']);
                return;
            }
        }
        // --- 書誌情報取得のための OpenSearch API 呼び出し (cURL部分は変更なし) ---
        $openSearchApiUrl = "https://iss.ndl.go.jp/api/opensearch?isbn=" . urlencode($isbn);
        $userAgent = 'MyComicApp/1.0 (+http://your-contact-page-or-email.com)';

        // cURLセッションを初期化
        $ch = curl_init();

        // cURLオプションを設定
        curl_setopt($ch, CURLOPT_URL, $openSearchApiUrl); // 取得するURL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 結果を文字列で取得
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // タイムアウトを10秒に設定
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // リダイレクトを追跡
        curl_setopt($ch, CURLOPT_FAILONERROR, true); // HTTPステータスコード400以上で失敗とみなす
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent); // UserAgentを設定
        // SSL証明書の検証を有効にする（通常は推奨）。開発環境で問題が出る場合は一時的に無効化も検討。
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        // 証明書パスが必要な場合（環境による）
        // curl_setopt($ch, CURLOPT_CAINFO, '/path/to/ca-bundle.crt');


        // cURLセッションを実行
        $xmlResponse = curl_exec($ch);



        // cURLエラーチェック
        if (curl_errno($ch)) {
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            curl_close($ch); // セッションを閉じる
            error_log("cURL Error for ISBN {$isbn}: [{$curlErrno}] {$curlError}");
            http_response_code(503); // Service Unavailable or Bad Gateway
            // ユーザー向けのエラーメッセージを改善
            $userMessage = '書誌情報の取得に失敗しました。(外部API接続エラー)';
            if (in_array($curlErrno, [CURLE_COULDNT_RESOLVE_HOST, CURLE_COULDNT_CONNECT])) {
                $userMessage .= ' サーバーがAPIに接続できませんでした。';
            } elseif ($curlErrno == CURLE_OPERATION_TIMEDOUT) {
                $userMessage .= ' APIからの応答がタイムアウトしました。';
            } elseif ($curlErrno == CURLE_SSL_CONNECT_ERROR || $curlErrno == CURLE_PEER_FAILED_VERIFICATION) {
                $userMessage .= ' SSL接続エラーが発生しました。';
            }
            echo json_encode(['error' => $userMessage, 'details' => "[{$curlErrno}] {$curlError}"]); //詳細も返す
            return;
        }

        // HTTPステータスコードチェック (念のため、FAILONERRORが効かない場合も)
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode >= 400) {
            curl_close($ch);
            error_log("NDL API HTTP Error for ISBN {$isbn}: {$httpCode}");
            http_response_code($httpCode === 404 ? 404 : 503); // 404はそのまま、他は503扱い
            echo json_encode(['error' => "書誌情報の取得に失敗しました。(HTTP {$httpCode})"]);
            return;
        }


        curl_close($ch); // セッションを閉じる


        // --- 書影URLの生成 (新しい方式) ---
        // 13桁のISBNからのみサムネイルURLを生成
        $cover_image = '';
        if (preg_match('/^[0-9]{13}$/', $isbn)) {
            $cover_image = "https://ndlsearch.ndl.go.jp/thumbnail/{$isbn}.jpg";
            error_log("ISBN: {$isbn} - Constructed Cover Image URL: " . $cover_image);
        } else {
            error_log("ISBN: {$isbn} - Not a 13-digit ISBN, skipping direct thumbnail URL generation.");
        }

        // XMLパース処理 (変更なし)
        try {
            libxml_use_internal_errors(true); // libxmlのエラーをハンドリング
            $xml = new \SimpleXMLElement($xmlResponse);
            $errors = libxml_get_errors();
            libxml_clear_errors();
            libxml_use_internal_errors(false);

            if (!empty($errors)) {
                throw new \Exception("XML Parsing Error: " . print_r($errors, true));
            }


            // 名前空間の登録
            $xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
            $xml->registerXPathNamespace('openSearch', 'http://a9.com/-/spec/opensearch/1.1/');
            // $xml->registerXPathNamespace('rdfs', 'http://www.w3.org/2000/01/rdf-schema#');
            // $xml->registerXPathNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#'); // 追加: rdfs:seeAlso/@rdf:resourceのため

            // 検索結果が0件かチェック
            $totalResultsNodes = $xml->xpath('//openSearch:totalResults');
            $totalResults = $totalResultsNodes ? (int) $totalResultsNodes[0] : 0; // ノード存在チェック追加
            if ($totalResults === 0) {
                http_response_code(404);
                echo json_encode(['error' => '指定されたISBNの書籍が見つかりませんでした。']);
                return;
            }

            // 最初のitem要素を取得
            $item = $xml->channel->item[0] ?? null; // null合体演算子で未定義エラー回避
            if (!$item) {
                http_response_code(404);
                echo json_encode(['error' => '書籍情報の主要データが見つかりませんでした。']);
                return;
            }

            // Title (dc:title を使用)
            $titleNodes = $item->xpath('./dc:title');
            $title = $titleNodes ? (string) $titleNodes[0] : 'タイトル不明';

            // Author (dc:creator を使用し、末尾を少しクリーンアップ)
            $creatorNodes = $item->xpath('./dc:creator');
            $author = '著者不明';
            if ($creatorNodes && !empty((string) $creatorNodes[0])) {
                $author = (string) $creatorNodes[0];
                // ", YYYY-" または ", YYYY-" のパターンを削除 (例: ", 1975-")
                $author = preg_replace('/,?\s+[0-9]{4}-$/', '', $author);
                // 末尾のカンマを削除
                $author = rtrim($author, ',');
            }

            // Publisher (dc:publisher を使用、なければ「不明」)
            $publisherNodes = $item->xpath('./dc:publisher');
            $publisher = $publisherNodes ? (string) $publisherNodes[0] : '出版社不明';

            // Volume (dcndl:volume を優先し、なければ dc:title から抽出試行)
            $volume = null;
            $volumeNodes = $item->xpath('./dcndl:volume');
            if ($volumeNodes && !empty((string) $volumeNodes[0])) {
                // <dcndl:volume> が存在する場合
                $volumeString = (string) $volumeNodes[0];
                // "巻110" や "110" から数字を抽出
                if (preg_match('/([0-9]+)/', $volumeString, $matches)) {
                    $volume = (int) $matches[1];
                }
                error_log("ISBN: {$isbn} - Found <dcndl:volume>: '{$volumeString}' - Extracted Volume: " . ($volume !== null ? $volume : 'None'));
            }

            // <dcndl:volume> がない、または数字が抽出できなかった場合、タイトルから試す
            if ($volume === null) {
                error_log("ISBN: {$isbn} - <dcndl:volume> not found or invalid. Falling back to title parsing.");
                // タイトル解析ロジック (前回の改善版を使用)
                if (preg_match('/(?:第|Vol\.?|\()\s*([0-9]+)(?:\s*巻|\)|)/iu', $title, $matches)) {
                    $volume = (int) $matches[1];
                } elseif (preg_match('/([0-9]+)\s*巻/iu', $title, $matches)) {
                    $volume = (int) $matches[1];
                } elseif (preg_match('/\s+([0-9]+)$/u', $title, $matches)) {
                    $volume = (int) $matches[1];
                }
                error_log("ISBN: {$isbn} - Title: '{$title}' - Extracted Volume from Title: " . ($volume !== null ? $volume : 'None'));
            }
            // --- データ抽出ここまで ---


            // --- comicData 配列の作成 ---
            $comicData = [
                'title' => $title,         // dc:title から取得
                'author' => $author,       // dc:creator から取得 (簡易クリーンアップ済み)
                'publisher' => $publisher, // dc:publisher から取得
                'cover_image' => $cover_image, // 直接生成したURL
                'isbn' => $isbn,
                'volume' => $volume,        // dcndl:volume 優先、次にタイトル解析
            ];

            echo json_encode($comicData);

        } catch (\Exception $e) {
            error_log("NDL API XML parsing failed for ISBN {$isbn}: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => '書誌情報の解析中にエラーが発生しました。']);
        }
    }

    /**
     * 画像をダウンロードしてローカルに保存するヘルパー関数
     * @param string $imageUrl ダウンロード元URL
     * @param string $savePath 保存先ファイルパス
     * @return bool 成功した場合は true、失敗した場合は false
     */
    private function downloadAndSaveImage(string $imageUrl, string $savePath): bool
    {
        if (empty($imageUrl)) {
            error_log("Image download skipped: URL is empty.");
            return false;
        }

        // ファイルハンドルを開く前に、既存の0KBファイルを削除する（念のため）
        if (file_exists($savePath) && filesize($savePath) === 0) {
            unlink($savePath);
            error_log("Removed existing 0KB file: {$savePath}");
        }

        $userAgent = 'MyComicApp/1.0 (+http://your-contact-page-or-email.com)';
        $ch = curl_init($imageUrl);

        // ファイルハンドルを開く
        $fp = fopen($savePath, 'wb'); // 書き込み用にバイナリモードで開く
        if (!$fp) {
            error_log("Failed to open file for writing: {$savePath}");
            curl_close($ch);
            return false;
        }

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // falseにする (ファイルに直接書き込むため)
        curl_setopt($ch, CURLOPT_TIMEOUT, 20); // 画像ダウンロードは少し長めに
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true); // 4xx/5xxエラーで失敗とみなす
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // SSL検証は有効に
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);


        error_log("Attempting to download image content from: {$imageUrl}");
        $imageData = curl_exec($ch); // 画像データを変数に格納

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        // --- 判定を強化 ---
        $finalSuccess = false;
        if ($imageData !== false && $curlErrno === 0 && $httpCode < 400) {
            // cURLダウンロード自体は成功し、画像データが取得できた場合
            if (strlen($imageData) > 0) { // データが空でないことを確認
                // file_put_contents でファイルに書き込む
                error_log("Attempting to save image data to: {$savePath}");
                if (file_put_contents($savePath, $imageData) !== false) {
                    // 書き込み成功後、念のためファイルサイズを確認
                    clearstatcache(true, $savePath);
                    if (file_exists($savePath) && filesize($savePath) > 0) {
                        $finalSuccess = true;
                        error_log("Image saved successfully: {$savePath}, Size: " . filesize($savePath));
                    } else {
                        error_log("Image save failed (File check): File '{$savePath}' not found or is 0KB after file_put_contents.");
                        if (file_exists($savePath))
                            unlink($savePath); // 失敗したらファイルを削除
                    }
                } else {
                    // file_put_contents が失敗した場合 (パーミッション等の可能性)
                    error_log("Image save failed (file_put_contents error): Failed to write to '{$savePath}'. Check permissions.");
                    if (file_exists($savePath))
                        unlink($savePath); // 失敗したらファイルを削除
                }
            } else {
                // HTTP 200 OK だが、応答ボディが空だった場合
                error_log("Image download failed (Empty body): Received empty content from '{$imageUrl}' despite HTTP {$httpCode}.");
                // ファイルは作成されないはずだが、念のため削除
                if (file_exists($savePath))
                    unlink($savePath);
            }
        } else {
            // cURLが明確に失敗した場合
            error_log("Image download failed (cURL error): URL='{$imageUrl}', CurlErrno={$curlErrno}, CurlError='{$curlError}', HTTPCode={$httpCode}");
            // ファイルは作成されないはずだが、念のため削除
            if (file_exists($savePath))
                unlink($savePath);
        }


        return $finalSuccess;
    }

    /**
     * 複数の漫画情報をまとめてDBに保存するAPI
     * comics テーブルと volumes テーブルに登録・更新を行う
     */
    public function saveComics(): void
    {
        header('Content-Type: application/json');
        // ログインチェック
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'ログインが必要です。']);
            return;
        }

        // POSTされたJSONデータを取得
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['comics']) || !is_array($data['comics'])) {
            http_response_code(400);
            echo json_encode(['error' => '無効なデータ形式です。']);
            return;
        }

        $comicsToSave = $data['comics'];
        if (empty($comicsToSave)) {
            http_response_code(400);
            echo json_encode(['error' => '保存する漫画データがありません。']);
            return;
        }

        $db = Database::getConnection();
        $savedCount = 0;
        $skippedCount = 0;
        $errors = [];


        // --- 画像保存ディレクトリの準備 ---
        $coverImageDir = self::COVER_IMAGE_DIR_PATH;
        if (!is_dir($coverImageDir)) {
            // ディレクトリが存在しない場合、再帰的に作成
            if (!mkdir($coverImageDir, 0775, true)) { // パーミッション注意
                http_response_code(500);
                error_log("Failed to create image directory: {$coverImageDir}");
                echo json_encode(['error' => '画像保存ディレクトリの作成に失敗しました。サーバー管理者に連絡してください。']);
                return; // ディレクトリが作れない場合は処理中断
            }
        }
        // ディレクトリが書き込み可能かチェック (より確実に)
        if (!is_writable($coverImageDir)) {
            http_response_code(500);
            error_log("Image directory is not writable: {$coverImageDir}");
            echo json_encode(['error' => '画像保存ディレクトリに書き込みできません。サーバー管理者に連絡してください。']);
            return; // 書き込めない場合は処理中断
        }
        // --- ディレクトリ準備ここまで ---

        // トランザクション開始
        $db->beginTransaction();

        try {
            // SQL文を準備
            // comics テーブル: ISBNが存在すれば更新、なければ挿入
            $comicStmt = $db->prepare(
                "INSERT INTO comics (title, author, publisher, isbn, cover_image, updated_at, created_at)
                 VALUES (:title, :author, :publisher, :isbn, :cover_image, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    title = VALUES(title),        -- タイトルも更新対象に（揺れがある場合考慮）
                    author = VALUES(author),
                    publisher = VALUES(publisher),
                    cover_image = VALUES(cover_image),
                    updated_at = NOW()"
                // description, publication_year, total_volumes, status はここでは更新しない
                // total_volumes は volumes 登録後に更新する可能性がある
            );

            // volumes テーブル: comic_id と volume_number が存在しなければ挿入 (存在すれば無視 or 更新)
            // INSERT IGNORE を使うと、重複キーエラーを無視して挿入しない
            $volumeStmt = $db->prepare(
                "INSERT IGNORE INTO volumes (comic_id, volume_number, title, created_at, updated_at)
                  VALUES (:comic_id, :volume_number, :title, NOW(), NOW())"
                // ON DUPLICATE KEY UPDATE title = VALUES(title), updated_at = NOW() // 更新もしたい場合
            );

            // comics テーブルの total_volumes 更新用 (任意)
            $updateTotalVolumesStmt = $db->prepare(
                "UPDATE comics SET total_volumes = GREATEST(total_volumes, :new_volume), updated_at = NOW()
                  WHERE id = :comic_id"
            );

            // comics テーブルの ID 取得用
            $selectComicIdStmt = $db->prepare("SELECT id FROM comics WHERE isbn = :isbn");


            // 各漫画データを処理
            foreach ($comicsToSave as $comic) {
                // データ存在チェック
                if (empty($comic['isbn']) || !preg_match('/^[0-9]{13}$/', $comic['isbn'])) {
                    $errors[] = ($comic['title'] ?? 'タイトル不明') . ': 有効な13桁のISBNがありません。スキップします。';
                    $skippedCount++;
                    continue;
                }
                if (empty($comic['title'])) {
                    $errors[] = "ISBN {$comic['isbn']}: タイトルが空です。スキップします。";
                    $skippedCount++;
                    continue;
                }

                // --- 画像ダウンロード処理 ---
                $ndlCoverImageUrl = $comic['cover_image'] ?? null; // NDLサムネイルURL
                $localCoverImageUrlPath = null; // DBに保存するパス (初期値 NULL)

                if (!empty($ndlCoverImageUrl) && filter_var($ndlCoverImageUrl, FILTER_VALIDATE_URL)) {
                    $imageFilename = $comic['isbn'] . '.jpg';
                    $localSavePath = $coverImageDir . '/' . $imageFilename;

                    if (!file_exists($localSavePath)) {
                        // ↓↓↓ 出力バッファリング開始 ↓↓↓
                        ob_start();
                        $downloadSuccess = $this->downloadAndSaveImage($ndlCoverImageUrl, $localSavePath);
                        // ↓↓↓ 出力バッファをクリア（画面に出力しない）↓↓↓
                        ob_end_clean();

                        if ($downloadSuccess) {
                            // ダウンロード成功
                            $localCoverImageUrlPath = self::COVER_IMAGE_URL_BASE . '/' . $imageFilename;
                            error_log("Image downloaded successfully for ISBN {$comic['isbn']} to {$localSavePath}");
                        } else {
                            // ダウンロード失敗
                            $errors[] = "ISBN {$comic['isbn']}: 書影画像のダウンロード/保存に失敗しました。URL: {$ndlCoverImageUrl}";
                            error_log("Failed to download image for ISBN {$comic['isbn']} from {$ndlCoverImageUrl}");
                        }
                    } else {
                        // 既にファイルが存在する場合
                        $localCoverImageUrlPath = self::COVER_IMAGE_URL_BASE . '/' . $imageFilename;
                        error_log("Image already exists locally for ISBN {$comic['isbn']}");
                    }
                } else {
                    error_log("No valid cover image URL provided for ISBN {$comic['isbn']}");
                }
                // --- 画像ダウンロード処理ここまで ---


                // 1. comics テーブルへの挿入または更新
                $comicStmt->bindValue(':title', $comic['title'], PDO::PARAM_STR);
                $comicStmt->bindValue(':author', $comic['author'] ?? '著者不明', PDO::PARAM_STR);
                $comicStmt->bindValue(':publisher', $comic['publisher'] ?? '出版社不明', PDO::PARAM_STR);
                $comicStmt->bindValue(':isbn', $comic['isbn'], PDO::PARAM_STR);
                // cover_image が空文字列の場合、NULLをDBに入れる
                $comicStmt->bindValue(':cover_image', $localCoverImageUrlPath, PDO::PARAM_STR);
                if (!$comicStmt->execute()) {
                    // comics テーブルへの保存失敗
                    $errors[] = "ISBN {$comic['isbn']}: 基本情報の保存に失敗しました。詳細: " . implode(' ', $comicStmt->errorInfo());
                    // この時点でトランザクションを継続するか、即時ロールバックするかは要件による
                    // ここではエラーを記録し、次のコミックへ進む
                    $skippedCount++;
                    continue; // 次の漫画へ
                }

                // 挿入/更新された comics の ID を取得
                $selectComicIdStmt->bindValue(':isbn', $comic['isbn'], PDO::PARAM_STR);
                $selectComicIdStmt->execute();
                $comicRow = $selectComicIdStmt->fetch(PDO::FETCH_ASSOC);
                $comicId = $comicRow['id'] ?? null; // PHP 7.0+

                if (!$comicId) {
                    $errors[] = "ISBN {$comic['isbn']}: 保存後のID取得に失敗しました。";
                    $skippedCount++;
                    continue;
                }

                // 2. volumes テーブルへの挿入 (巻数情報がある場合)
                if (isset($comic['volume']) && is_numeric($comic['volume'])) {
                    $volumeNumber = (int) $comic['volume'];

                    $volumeStmt->bindValue(':comic_id', $comicId, PDO::PARAM_INT);
                    $volumeStmt->bindValue(':volume_number', $volumeNumber, PDO::PARAM_INT);
                    // volumes.title には comics.title を入れる (または空でも良い)
                    $volumeStmt->bindValue(':title', $comic['title'], PDO::PARAM_STR);

                    if ($volumeStmt->execute()) {
                        if ($volumeStmt->rowCount() > 0) {
                            // 新しい巻が挿入された場合のみカウント（任意）
                            // $savedVolumeCount++;
                        } else {
                            // 挿入されなかった（既に存在した）場合
                            // $existingVolumeCount++;
                        }

                        // 任意: comics.total_volumes を更新
                        $updateTotalVolumesStmt->bindValue(':new_volume', $volumeNumber, PDO::PARAM_INT);
                        $updateTotalVolumesStmt->bindValue(':comic_id', $comicId, PDO::PARAM_INT);
                        if (!$updateTotalVolumesStmt->execute()) {
                            $errors[] = "ISBN {$comic['isbn']}, Volume {$volumeNumber}: 総巻数の更新に失敗しました。";
                            // これは致命的ではないのでエラー記録のみ
                        }

                    } else {
                        $errors[] = "ISBN {$comic['isbn']}, Volume {$volumeNumber}: 巻数情報の保存に失敗しました。詳細: " . implode(' ', $volumeStmt->errorInfo());
                        // エラーを記録し、次のコミックへ進む
                        // $skippedCount++; // 巻数登録失敗はスキップ扱いにするか？
                        // continue; // 処理を中断しない場合
                    }
                } else {
                    // 巻数情報がない場合 (例: 単巻、または抽出失敗)
                    // volumes テーブルには登録しない
                    error_log("ISBN {$comic['isbn']}: Volume number is missing or invalid, skipping volume insertion.");
                }

                $savedCount++; // 基本情報が保存できたらカウント

            } // end foreach

            // ループ終了後、エラーの有無でコミット/ロールバック
            if (empty($errors)) {
                $db->commit(); // 全て成功したらコミット
                http_response_code(201); // Created or 200 OK
                $message = $savedCount . '件の漫画情報を処理しました。';
                if ($skippedCount > 0) {
                    $message .= ' (' . $skippedCount . '件はスキップされました)';
                }
                echo json_encode(['message' => $message]);
            } else {
                // 何らかのエラーがあった場合 (部分的な成功でもロールバックする方針)
                $db->rollBack();
                http_response_code(400); // Bad Request (or 500 if critical)
                // ユーザーに見せるエラーメッセージを生成
                $userErrorMessage = $savedCount . '件の処理を試みましたが、' . count($errors) . '件のエラーが発生したため、全ての変更を元に戻しました。';
                if ($skippedCount > 0) {
                    $userErrorMessage .= ' また、' . $skippedCount . '件は処理前にスキップされました。';
                }

                echo json_encode([
                    'error' => $userErrorMessage,
                    'details' => $errors // 詳細なエラー内容
                ]);
            }

        } catch (PDOException $e) {
            // 予期せぬ PDO エラーが発生した場合
            $db->rollBack(); // 必ずロールバック
            error_log("Database error during saveComics: " . $e->getMessage());
            http_response_code(500); // Internal Server Error
            echo json_encode(['error' => 'データベースエラーが発生しました。処理を完了できませんでした。']);
        }
    }


    // --- 追加ここまで ---
}