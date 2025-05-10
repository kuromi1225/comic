<?php

namespace App\Controllers;

use App\Core\Database;
use PDO;
use DOMDocument;
use DOMXPath; // Ensure DOMXPath is available

class ComicController
{
    // Directory path constants for cover images
    private const COVER_IMAGE_DIR_PATH = __DIR__ . '/../../public/images/covers'; // Physical path for saving
    private const COVER_IMAGE_URL_BASE = '/images/covers';          // URL path for DB and HTML

    public function listComics()
    {
        $titleFilter = $_GET['title'] ?? null;
        $db = Database::getConnection();
        $comics = [];

        try {
            $query = "SELECT id, title, author, publisher, total_volumes, cover_image FROM comics";
            $params = [];

            if ($titleFilter) {
                $query .= " WHERE title LIKE :title";
                $params[':title'] = "%" . $titleFilter . "%";
            }
            $query .= " ORDER BY title";

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $comics = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error fetching comics: " . $e->getMessage());
            // In a real app, you might want to show an error page or message
            echo "漫画データの取得中にエラーが発生しました。";
        }

        // Pass data to the view
        require_once __DIR__ . '/../../views/comic_list.php';
    }

    /**
     * Displays the comic registration form.
     */
    public function showRegisterForm(): void
    {
        // Login check
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        require_once __DIR__ . '/../../views/comic_register.php';
    }

    /**
     * Fetches comic information from NDL API based on ISBN.
     */
    public function fetchComicInfo(): void
    {
        header('Content-Type: application/json');
        $isbn = $_GET['isbn'] ?? null;

        // ISBN validation (13-digit or 10-digit)
        if (!$isbn || !preg_match('/^([0-9]{13}|[0-9]{9}[0-9X])$/', $isbn)) {
            http_response_code(400);
            echo json_encode(['error' => '有効なISBNコードを指定してください。']);
            return;
        }
        
        // NDL OpenSearch API URL
        $openSearchApiUrl = "https://iss.ndl.go.jp/api/opensearch?isbn=" . urlencode($isbn);
        $userAgent = 'MyComicApp/1.0 (Contact: your-email@example.com)'; // Replace with your app's info

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $openSearchApiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Increased timeout
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true); // Fail on 4xx/5xx HTTP codes
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Enable SSL verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        $xmlResponse = curl_exec($ch);

        if (curl_errno($ch)) {
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            curl_close($ch);
            error_log("cURL Error for ISBN {$isbn}: [{$curlErrno}] {$curlError}");
            http_response_code(503); // Service Unavailable
            $userMessage = '書誌情報の取得に失敗しました。(外部API接続エラー)';
            if (in_array($curlErrno, [CURLE_COULDNT_RESOLVE_HOST, CURLE_COULDNT_CONNECT])) {
                $userMessage .= ' サーバーがAPIに接続できませんでした。';
            } elseif ($curlErrno == CURLE_OPERATION_TIMEDOUT) {
                $userMessage .= ' APIからの応答がタイムアウトしました。';
            }
            echo json_encode(['error' => $userMessage, 'details' => "cURL Error: [{$curlErrno}] {$curlError}"]);
            return;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch); // Close cURL session

        if ($httpCode >= 400) {
            error_log("NDL API HTTP Error for ISBN {$isbn}: {$httpCode}. Response: " . $xmlResponse);
            http_response_code($httpCode === 404 ? 404 : 503);
            echo json_encode(['error' => "書誌情報の取得に失敗しました。(HTTP {$httpCode})"]);
            return;
        }

        // Generate cover image URL (NDL Thumbnail)
        $cover_image_url = '';
        if (preg_match('/^[0-9]{13}$/', $isbn)) { // NDL thumbnails usually require 13-digit ISBN
            $cover_image_url = "https://ndlsearch.ndl.go.jp/thumbnail/{$isbn}.jpg";
        } elseif (preg_match('/^([0-9]{9}[0-9X])$/', $isbn)) {
            // Attempt to convert 10-digit ISBN to 13-digit for thumbnail (basic conversion)
            // This is a simplified conversion and might not always be correct for all NDL cases.
            // $isbn13 = '978' . substr($isbn, 0, 9);
            // // Calculate check digit (simplified, real calculation is more complex)
            // // For a robust solution, use a library or proper algorithm for ISBN10 to ISBN13 conversion.
            // // $check_digit = ...; $isbn13 .= $check_digit;
            // // $cover_image_url = "https://ndlsearch.ndl.go.jp/thumbnail/{$isbn13}.jpg";
            error_log("ISBN {$isbn} is 10-digit. Thumbnail URL might not be available directly without conversion to 13-digit for NDL.");
        }


        try {
            libxml_use_internal_errors(true);
            $xml = new \SimpleXMLElement($xmlResponse);
            $xml_errors = libxml_get_errors();
            libxml_clear_errors();
            libxml_use_internal_errors(false);

            if (!empty($xml_errors)) {
                $errorMessages = [];
                foreach($xml_errors as $xml_err) $errorMessages[] = $xml_err->message;
                throw new \Exception("XML Parsing Error: " . implode("; ", $errorMessages));
            }

            $xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
            $xml->registerXPathNamespace('openSearch', 'http://a9.com/-/spec/opensearch/1.1/');
            $xml->registerXPathNamespace('dcndl', 'http://ndl.go.jp/dcndl/terms/');


            $totalResultsNodes = $xml->xpath('//openSearch:totalResults');
            $totalResults = $totalResultsNodes ? (int)$totalResultsNodes[0] : 0;

            if ($totalResults === 0) {
                http_response_code(404);
                echo json_encode(['error' => '指定されたISBNの書籍が見つかりませんでした。']);
                return;
            }

            $item = $xml->channel->item[0] ?? null;
            if (!$item) {
                http_response_code(404);
                echo json_encode(['error' => '書籍情報の主要データが見つかりませんでした。']);
                return;
            }

            $title = (string)($item->xpath('./dc:title')[0] ?? 'タイトル不明');
            $author = (string)($item->xpath('./dc:creator')[0] ?? '著者不明');
            $publisher = (string)($item->xpath('./dc:publisher')[0] ?? '出版社不明');
            
            $volume = null;
            $volumeNodes = $item->xpath('./dcndl:volume'); // NDL specific volume
            if ($volumeNodes && !empty((string)$volumeNodes[0])) {
                if (preg_match('/([0-9]+)/', (string)$volumeNodes[0], $matches)) {
                    $volume = (int)$matches[1];
                }
            } else { // Fallback to title parsing if dcndl:volume is not present
                if (preg_match('/(?:第|Vol\.?|\()\s*([0-9]+)(?:\s*巻|\)|)/iu', $title, $matches)) {
                    $volume = (int)$matches[1];
                } elseif (preg_match('/([0-9]+)\s*巻/iu', $title, $matches)) { // e.g., "1巻"
                    $volume = (int)$matches[1];
                } elseif (preg_match('/\s+([0-9]+)$/u', $title, $matches)) { // e.g., "タイトル 1"
                    $volume = (int)$matches[1];
                }
            }

            $comicData = [
                'title' => $title,
                'author' => rtrim(preg_replace('/,?\s+[0-9]{4}-$/', '', $author), ','), // Clean up author string
                'publisher' => $publisher,
                'cover_image' => $cover_image_url,
                'isbn' => $isbn, // Return original ISBN used for search
                'volume' => $volume,
            ];

            echo json_encode($comicData);

        } catch (\Exception $e) {
            error_log("NDL API XML processing failed for ISBN {$isbn}: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => '書誌情報の解析中にエラーが発生しました。']);
        }
    }
    
    /**
     * Helper function to download and save an image.
     * (This method was part of the original file structure provided by the user)
     */
    private function downloadAndSaveImage(string $imageUrl, string $savePath): bool
    {
        if (empty($imageUrl)) {
            error_log("Image download skipped: URL is empty.");
            return false;
        }

        // Ensure the directory exists and is writable
        $dir = dirname($savePath);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0775, true)) {
                error_log("Failed to create directory: {$dir}");
                return false;
            }
        }
        if (!is_writable($dir)) {
             error_log("Directory is not writable: {$dir}");
             return false;
        }


        // Remove existing 0KB file, if any
        if (file_exists($savePath) && filesize($savePath) === 0) {
            unlink($savePath);
            error_log("Removed existing 0KB file: {$savePath}");
        }

        $userAgent = 'MyComicApp/1.0 (Contact: your-email@example.com)'; // Be a good internet citizen
        $ch = curl_init($imageUrl);

        $fp = fopen($savePath, 'wb');
        if (!$fp) {
            error_log("Failed to open file for writing: {$savePath}");
            curl_close($ch);
            return false;
        }

        curl_setopt($ch, CURLOPT_FILE, $fp); // Write response directly to file
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Slightly longer timeout for images
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $success = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);

        curl_close($ch);
        fclose($fp);

        if ($success && $httpCode < 400) {
            clearstatcache(true, $savePath); // Clear file status cache
            if (file_exists($savePath) && filesize($savePath) > 0) {
                error_log("Image saved successfully: {$savePath}, Size: " . filesize($savePath));
                return true;
            } else {
                error_log("Image save failed (File check): File '{$savePath}' not found or is 0KB after download.");
                if (file_exists($savePath)) unlink($savePath); // Clean up empty file
                return false;
            }
        } else {
            error_log("Image download failed: URL='{$imageUrl}', CurlErrno={$curlErrno}, CurlError='{$curlError}', HTTPCode={$httpCode}");
            if (file_exists($savePath)) unlink($savePath); // Clean up potentially partial/empty file
            return false;
        }
    }

    /**
     * Saves multiple comic book entries to the database.
     * (This method was part of the original file structure provided by the user)
     */
    public function saveComics(): void
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'ログインが必要です。']);
            return;
        }
        // ... (JSON input processing as before) ...
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['comics']) || !is_array($data['comics'])) {
            http_response_code(400);
            echo json_encode(['error' => '無効なデータ形式です。']);
            return;
        }
        $comicsToSave = $data['comics'];
        // ... (rest of the variable initializations) ...
        $db = Database::getConnection();
        $savedCount = 0;
        $skippedCount = 0;
        $errors = [];
        $coverImageDir = self::COVER_IMAGE_DIR_PATH;

        $db->beginTransaction();
        try {
            $comicStmt = $db->prepare(
                "INSERT INTO comics (title, author, publisher, isbn, cover_image, total_volumes, updated_at, created_at)
                 VALUES (:title, :author, :publisher, :isbn, :cover_image, :total_volumes, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    title = VALUES(title), author = VALUES(author), publisher = VALUES(publisher),
                    cover_image = IF(VALUES(cover_image) IS NOT NULL, VALUES(cover_image), cover_image), 
                    total_volumes = IF(VALUES(total_volumes) IS NOT NULL, GREATEST(IFNULL(total_volumes,0), VALUES(total_volumes)), total_volumes),
                    updated_at = NOW()"
            );
             // ... (volumeStmt, selectComicIdStmt as before) ...
            $volumeStmt = $db->prepare(
                "INSERT IGNORE INTO volumes (comic_id, volume_number, title, release_date, is_owned, created_at, updated_at)
                 VALUES (:comic_id, :volume_number, :title, :release_date, :is_owned, NOW(), NOW())" // Added release_date, is_owned
            );
            $selectComicIdStmt = $db->prepare("SELECT id, total_volumes FROM comics WHERE isbn = :isbn");


            foreach ($comicsToSave as $comic) {
                // ISBN正規化と13桁チェックの強化
                $normalizedIsbn = preg_replace('/[^0-9X]/i', '', $comic['isbn'] ?? '');
                if (strlen($normalizedIsbn) == 10) { // ISBN10 to ISBN13 conversion attempt
                    if (preg_match('/^([0-9]{9})([0-9X])$/', $normalizedIsbn, $matches)) {
                        $base = '978' . $matches[1];
                        $sum = 0;
                        for ($i = 0; $i < 12; $i++) { $sum += (int)$base[$i] * (($i % 2 === 0) ? 1 : 3); }
                        $checkDigit = (10 - ($sum % 10)) % 10;
                        $normalizedIsbn = $base . $checkDigit;
                    } else { $normalizedIsbn = ''; /* Invalid ISBN10 */}
                }

                if (empty($normalizedIsbn) || !preg_match('/^[0-9]{13}$/', $normalizedIsbn)) {
                    $errors[] = ($comic['title'] ?? 'タイトル不明') . ($normalizedIsbn ? " (入力ISBN:{$comic['isbn']})" : "") . ': 有効な13桁のISBNに変換できませんでした。';
                    $skippedCount++;
                    continue;
                }
                // ... (rest of the loop: title check, image download) ...
                if (empty($comic['title'])) {
                    $errors[] = "ISBN {$normalizedIsbn}: タイトルが空です。";
                    $skippedCount++;
                    continue;
                }

                $ndlCoverImageUrl = $comic['cover_image'] ?? null;
                $localCoverImageUrlPath = null;
                if (!empty($ndlCoverImageUrl) && filter_var($ndlCoverImageUrl, FILTER_VALIDATE_URL)) {
                    $imageFilename = $normalizedIsbn . '.jpg'; // Use normalized 13-digit ISBN for filename
                    $localSavePath = $coverImageDir . '/' . $imageFilename;
                    if (!file_exists($localSavePath) || filesize($localSavePath) === 0) {
                        if ($this->downloadAndSaveImage($ndlCoverImageUrl, $localSavePath)) {
                            $localCoverImageUrlPath = self::COVER_IMAGE_URL_BASE . '/' . $imageFilename;
                        }
                    } else {
                        $localCoverImageUrlPath = self::COVER_IMAGE_URL_BASE . '/' . $imageFilename;
                    }
                }
                
                $selectComicIdStmt->execute([':isbn' => $normalizedIsbn]);
                $existingComic = $selectComicIdStmt->fetch(PDO::FETCH_ASSOC);
                $comicId = $existingComic['id'] ?? null;
                $currentTotalVolumes = $existingComic['total_volumes'] ?? 0;
                
                // Determine the new total_volumes. If the current comic entry has a volume, use it.
                $entryVolume = (isset($comic['volume']) && is_numeric($comic['volume'])) ? (int)$comic['volume'] : 0;
                $newTotalVolumes = max($currentTotalVolumes, $entryVolume);


                $comicStmt->bindValue(':title', $comic['title'], PDO::PARAM_STR);
                $comicStmt->bindValue(':author', $comic['author'] ?? '著者不明', PDO::PARAM_STR);
                $comicStmt->bindValue(':publisher', $comic['publisher'] ?? '出版社不明', PDO::PARAM_STR);
                $comicStmt->bindValue(':isbn', $normalizedIsbn, PDO::PARAM_STR);
                $comicStmt->bindValue(':cover_image', $localCoverImageUrlPath, $localCoverImageUrlPath ? PDO::PARAM_STR : PDO::PARAM_NULL);
                // total_volumes should reflect the maximum known volume for this series (ISBN)
                $comicStmt->bindValue(':total_volumes', $newTotalVolumes > 0 ? $newTotalVolumes : null, $newTotalVolumes > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);


                if (!$comicStmt->execute()) {
                     // ... (error handling) ...
                    $errors[] = "ISBN {$normalizedIsbn}: 基本情報の保存に失敗: " . implode(" ", $comicStmt->errorInfo());
                    $skippedCount++;
                    continue;
                }

                if (!$comicId) { 
                    $comicId = $db->lastInsertId();
                     if (!$comicId) {
                        $selectComicIdStmt->execute([':isbn' => $normalizedIsbn]);
                        $comicRowRetry = $selectComicIdStmt->fetch(PDO::FETCH_ASSOC);
                        $comicId = $comicRowRetry['id'] ?? null;
                     }
                }
                // ... (rest of volume insertion logic and loop end) ...
                if (!$comicId) {
                    $errors[] = "ISBN {$normalizedIsbn}: 保存後のID取得に失敗しました。";
                    $skippedCount++;
                    continue;
                }

                if (isset($comic['volume']) && is_numeric($comic['volume']) && (int)$comic['volume'] > 0) {
                    $volumeNumber = (int)$comic['volume'];
                    $volumeStmt->execute([
                        ':comic_id' => $comicId,
                        ':volume_number' => $volumeNumber,
                        ':title' => $comic['title'], 
                        ':release_date' => $comic['release_date'] ?? null, 
                        ':is_owned' => $comic['is_owned'] ?? false 
                    ]);
                }
                $savedCount++;
            }

            // ... (commit/rollback logic as before) ...
             if (empty($errors)) {
                $db->commit();
                http_response_code(201);
                echo json_encode(['message' => "{$savedCount}件の漫画情報を処理しました。" . ($skippedCount > 0 ? " ({$skippedCount}件スキップ)" : "")]);
            } else {
                $db->rollBack();
                http_response_code(400);
                error_log("SaveComics errors: " . print_r($errors, true));
                echo json_encode(['error' => "一部の漫画情報の保存に失敗しました。詳細はログを確認してください。", 'details' => $errors]);
            }

        } catch (PDOException $e) {
            // ... (exception handling as before) ...
            if ($db->inTransaction()) $db->rollBack();
            error_log("Database error during saveComics: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'データベースエラーが発生し、処理を完了できませんでした。']);
        }
    }

    /**
     * Processes receipt OCR via an external Python service.
     * (This method was part of the original file structure provided by the user)
     */
    public function processReceiptOcr(): void
    {
        header('Content-Type: application/json');
        // Optional: Add login check if this API should be protected
        // if (!isset($_SESSION['user_id'])) { ... }

        // ログインチェックは必要に応じて追加

        // --- ファイルアップロード処理 ---
        // PHPの標準的な方法 ($_FILES を使用)
        if (!isset($_FILES['receiptImage']) || $_FILES['receiptImage']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            $errorMessage = '画像ファイルがアップロードされていないか、アップロード中にエラーが発生しました。';
            if (isset($_FILES['receiptImage']['error'])) {
                 // PHPのアップロードエラーコードに基づいてメッセージを具体化 (任意)
                 switch ($_FILES['receiptImage']['error']) {
                     case UPLOAD_ERR_INI_SIZE:
                     case UPLOAD_ERR_FORM_SIZE:
                         $errorMessage = 'ファイルサイズが大きすぎます。';
                         break;
                     case UPLOAD_ERR_NO_FILE:
                         $errorMessage = 'ファイルが選択されていません。';
                         break;
                     default:
                         $errorMessage .= ' (エラーコード: ' . $_FILES['receiptImage']['error'] . ')';
                 }
            }
            error_log("Receipt upload error: " . $errorMessage);
            echo json_encode(['error' => $errorMessage]);
            return;
        }

        $uploadedFile = $_FILES['receiptImage'];
        $filePath = $uploadedFile['tmp_name']; // PHPが一時的に保存したパス
        $fileName = $uploadedFile['name'];
        $fileType = $uploadedFile['type'];

        // 画像ファイルかどうかの簡易チェック (MIMEタイプ)
        if (strpos($fileType, 'image/') !== 0) {
            http_response_code(400);
            error_log("Uploaded file is not an image: " . $fileType);
            echo json_encode(['error' => '画像ファイル形式が無効です。']);
            return;
        }
        // --- ファイルアップロード処理ここまで ---


        // --- Python OCRサービスへのリクエスト ---
        // docker-compose.yml で定義したサービス名 'ocr_service' とポート 5000 を使用
        $ocrServiceUrl = 'http://ocr_service:5000/ocr';
        error_log("Forwarding image to OCR service: " . $ocrServiceUrl);

        $curl = curl_init();
        // cURLFile を使うのが推奨される方法
        // PHP 5.5.0 以降で利用可能
        if (function_exists('curl_file_create')) {
            $cfile = curl_file_create($filePath, $fileType, $fileName);
        } else { // 旧バージョン用 (非推奨)
            $cfile = '@' . realpath($filePath) . ';filename=' . $fileName . ';type=' . $fileType;
        }
        $postData = ['file' => $cfile];

        curl_setopt($curl, CURLOPT_URL, $ocrServiceUrl);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60); // OCR処理は時間がかかる可能性があるので長めに
        // curl_setopt($curl, CURLOPT_VERBOSE, true); // デバッグ用

        $responseJson = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlErrno = curl_errno($curl);
        $curlError = curl_error($curl);
        curl_close($curl);

        // --- OCRサービスからのレスポンス処理 ---
        if ($curlErrno > 0 || $httpCode >= 400 || $responseJson === false) {
            http_response_code(502); // Bad Gateway (上流サーバーからのエラー)
            $errorMessage = "OCRサービスへの接続または処理中にエラーが発生しました。";
            error_log("Error communicating with OCR service: CurlErrno={$curlErrno}, CurlError='{$curlError}', HTTPCode={$httpCode}, Response: " . $responseJson);
             if ($httpCode === 0 || $curlErrno > 0) {
                 $errorMessage = "OCRサービスに接続できませんでした。";
             } else if ($httpCode >= 400) {
                  // OCRサービスがエラーJSONを返しているか試す
                  $responseData = json_decode($responseJson, true);
                  if ($responseData && isset($responseData['error'])) {
                      $errorMessage = "OCRサービスエラー({$httpCode}): " . $responseData['error'];
                  } else {
                      $errorMessage = "OCRサービスがエラー({$httpCode})を返しました。";
                  }
             }
            echo json_encode(['error' => $errorMessage]);
            return;
        }

        // OCRサービスからのレスポンスをそのままブラウザに返す
        // Content-Type は既に application/json のはず
        echo $responseJson;
    }

        if (!isset($_FILES['receiptImage']) || $_FILES['receiptImage']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            $errorMessage = '画像ファイルがアップロードされていないか、アップロード中にエラーが発生しました。';
            if (isset($_FILES['receiptImage']['error'])) {
                 switch ($_FILES['receiptImage']['error']) {
                     case UPLOAD_ERR_INI_SIZE:
                     case UPLOAD_ERR_FORM_SIZE: $errorMessage = 'ファイルサイズが大きすぎます。'; break;
                     case UPLOAD_ERR_NO_FILE: $errorMessage = 'ファイルが選択されていません。'; break;
                     default: $errorMessage .= ' (エラーコード: ' . $_FILES['receiptImage']['error'] . ')';
                 }
            }
            error_log("Receipt upload error: " . $errorMessage);
            echo json_encode(['error' => $errorMessage]);
            return;
        }

        $uploadedFile = $_FILES['receiptImage'];
        $filePath = $uploadedFile['tmp_name'];
        $fileName = $uploadedFile['name']; // Original filename
        $fileType = mime_content_type($filePath); // More reliable MIME type detection

        if (strpos($fileType, 'image/') !== 0) {
            http_response_code(400);
            error_log("Uploaded file is not an image: {$fileType}");
            echo json_encode(['error' => '画像ファイル形式が無効です。']);
            return;
        }
        
        $ocrServiceUrl = 'http://ocr_service:5000/ocr'; // As defined in docker-compose
        error_log("Forwarding image '{$fileName}' to OCR service: {$ocrServiceUrl}");

        $curl = curl_init();
        $cfile = new \CURLFile($filePath, $fileType, $fileName);
        $postData = ['file' => $cfile];

        curl_setopt($curl, CURLOPT_URL, $ocrServiceUrl);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60); // OCR can take time
        // curl_setopt($curl, CURLOPT_VERBOSE, true); // For debugging cURL

        $responseJson = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlErrno = curl_errno($curl);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($curlErrno > 0 || $httpCode >= 400 || $responseJson === false) {
            http_response_code(502); // Bad Gateway
            $errorMessage = "OCRサービスへの接続または処理中にエラーが発生しました。";
            error_log("Error communicating with OCR service: CurlErrno={$curlErrno}, CurlError='{$curlError}', HTTPCode={$httpCode}, Response: {$responseJson}");
             if ($httpCode === 0 || $curlErrno > 0) { // Connection error
                 $errorMessage = "OCRサービスに接続できませんでした。サービスが起動しているか確認してください。";
             } else if ($httpCode >= 400) { // Error from OCR service
                  $responseData = json_decode($responseJson, true);
                  if ($responseData && isset($responseData['error'])) {
                      $errorMessage = "OCRサービスエラー({$httpCode}): " . $responseData['error'];
                  } else {
                      $errorMessage = "OCRサービスがエラー({$httpCode})を返しました。";
                  }
             }
            echo json_encode(['error' => $errorMessage]);
            return;
        }
        
        // Forward OCR service response to client
        // Assuming OCR service returns JSON with 'isbns' array or 'error'
        echo $responseJson;
    }
    

    // ===>>> NEW METHODS FOR NEW RELEASES PAGE <<<===

    public function showNewReleasesPage(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $currentYear = date('Y');
        $currentMonth = date('n'); // 先頭ゼロなしの月 (1-12)

        // GETパラメータから年と月を取得、なければ現在の年月をデフォルトに
        $selectedYear = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT, [
            "options" => ["default" => (int)$currentYear, "min_range" => 2000, "max_range" => (int)$currentYear + 2]
        ]);
        $selectedMonth = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT, [
            "options" => ["default" => (int)$currentMonth, "min_range" => 1, "max_range" => 12]
        ]);
        
        $calendarData = [];
        $errorMessage = null;

        // まずDBキャッシュからデータを取得試行
        $calendarData = $this->getReleasesFromDB($selectedYear, $selectedMonth);

        // GETパラメータで年月が指定されていてキャッシュがない場合、または
        // 初回ロード（GETパラメータなし）で現在の年月のキャッシュがない場合にスクレイピングを実行
        if (empty($calendarData) && isset($_GET['year'])) { // ユーザーが明示的に年月を選択し、キャッシュがない場合
            error_log("No cache for user selected {$selectedYear}-{$selectedMonth}. Scraping...");
            $scrapedResult = $this->scrapeHonHikidashi($selectedYear, $selectedMonth);
            if (isset($scrapedResult['error'])) {
                $errorMessage = $scrapedResult['error'];
                error_log("Scraping error for {$selectedYear}-{$selectedMonth}: {$errorMessage}");
            } else {
                $calendarData = $scrapedResult;
                if (!empty($calendarData)) {
                    $this->saveReleasesToDB($selectedYear, $selectedMonth, $calendarData);
                    error_log("Saved " . count($calendarData) . " items to DB for {$selectedYear}-{$selectedMonth}.");
                } else {
                    // スクレイピング結果が空だった場合 (エラーではないがデータなし)
                    $errorMessage = "{$selectedYear}年{$selectedMonth}月の新刊情報は見つかりませんでした（ウェブサイトに情報がないか、取得できませんでした）。";
                    error_log($errorMessage);
                }
            }
        } elseif (empty($calendarData) && !isset($_GET['year'])) { // 初回ロードで現在の年月のキャッシュがない場合
            error_log("Initial load, no cache for current month {$currentYear}-{$currentMonth}. Scraping...");
            $scrapedResult = $this->scrapeHonHikidashi($currentYear, $currentMonth); // 現在の年月でスクレイピング
            if (isset($scrapedResult['error'])) {
                $errorMessage = $scrapedResult['error'];
                 error_log("Scraping error for current month {$currentYear}-{$currentMonth}: {$errorMessage}");
            } else {
                $calendarData = $scrapedResult;
                if (!empty($calendarData)) {
                    $this->saveReleasesToDB($currentYear, $currentMonth, $calendarData);
                     error_log("Saved " . count($calendarData) . " items to DB for current month {$currentYear}-{$currentMonth}.");
                } else {
                    // 初回ロードで現在の月のデータがない場合は、エラーメッセージは表示せず、単に空のリストを表示
                    // $errorMessage = "{$currentYear}年{$currentMonth}月の新刊情報は見つかりませんでした。";
                    error_log("No data found for current month {$currentYear}-{$currentMonth} on initial load scrape.");
                }
            }
        } else if (!empty($calendarData)) {
             error_log("Cache hit for {$selectedYear}-{$selectedMonth}. Found " . count($calendarData) . " items.");
        }
        
        $availableYears = range((int)date('Y') + 1, (int)date('Y') - 5);
        $availableMonths = range(1, 12);

        $viewData = [
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'calendarData' => $calendarData,
            'availableYears' => $availableYears,
            'availableMonths' => $availableMonths,
            'pageTitle' => '新刊発売日カレンダー',
            'errorMessage' => $errorMessage
        ];

        require_once __DIR__ . '/../../views/new-releases.php';
    }

    private function getReleasesFromDB(int $year, int $month): array
    {
        try {
            $db = Database::getConnection();
            // 発売日順ソートを修正
            $sql = "SELECT release_day_info, title, author, publisher, raw_price, source_article_url 
                    FROM new_releases 
                    WHERE release_year = :year AND release_month = :month 
                    ORDER BY 
                        release_year ASC,
                        release_month ASC,
                        CASE 
                            WHEN release_day_info REGEXP '^[0-9]+$' THEN 0 -- 数字タイプを優先
                            WHEN LOWER(release_day_info) LIKE '%上旬%' THEN 1 -- 次に「上旬」
                            WHEN LOWER(release_day_info) LIKE '%中旬%' THEN 2 -- 次に「中旬」
                            WHEN LOWER(release_day_info) LIKE '%下旬%' THEN 3 -- 次に「下旬」
                            ELSE 4                                     -- その他のテキスト
                        END,
                        CASE 
                            WHEN release_day_info REGEXP '^[0-9]+$' THEN CAST(release_day_info AS UNSIGNED) -- 数字でソート
                            ELSE NULL -- テキストの場合はこのCASEではソートキーを提供しない (上のCASEでグループ化)
                        END ASC,
                        title ASC"; // 最後にタイトルでソート
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':year', $year, PDO::PARAM_INT);
            $stmt->bindParam(':month', $month, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $transformedResults = [];
            foreach ($results as $row) {
                $transformedResults[] = [
                    'date' => $row['release_day_info'],
                    'title' => $row['title'],
                    'author' => $row['author'],
                    'publisher' => $row['publisher'],
                    'price' => $row['raw_price'],
                    'source_url' => $row['source_article_url']
                ];
            }
            return $transformedResults;
        } catch (PDOException $e) {
            error_log("DB Error fetching releases for {$year}-{$month}: " . $e->getMessage());
            return [];
        }
    }

    private function saveReleasesToDB(int $year, int $month, array $releasesData): void
    {
        // このメソッドは前回から変更ありませんが、呼び出し元で利用されるため含めます
        if (empty($releasesData)) return;
        try {
            $db = Database::getConnection();
            $db->beginTransaction();
            $deleteStmt = $db->prepare("DELETE FROM new_releases WHERE release_year = :year AND release_month = :month");
            $deleteStmt->bindParam(':year', $year, PDO::PARAM_INT);
            $deleteStmt->bindParam(':month', $month, PDO::PARAM_INT);
            $deleteStmt->execute();
            $stmt = $db->prepare(
                "INSERT INTO new_releases (release_year, release_month, release_day_info, title, author, publisher, raw_price, source_article_url, scraped_at) 
                 VALUES (:year, :month, :day_info, :title, :author, :publisher, :price, :source_url, NOW())"
            );
            foreach ($releasesData as $item) {
                $day_info = $item['date'] ?? '不明';
                $title = $item['title'] ?? 'タイトル不明';
                $author = $item['author'] ?? null;
                $publisher = $item['publisher'] ?? null;
                $price = $item['price'] ?? null;
                $source_url = $item['source_url'] ?? null;

                $stmt->bindParam(':year', $year, PDO::PARAM_INT);
                $stmt->bindParam(':month', $month, PDO::PARAM_INT);
                $stmt->bindParam(':day_info', $day_info, PDO::PARAM_STR);
                $stmt->bindParam(':title', $title, PDO::PARAM_STR);
                $stmt->bindParam(':author', $author, PDO::PARAM_STR);
                $stmt->bindParam(':publisher', $publisher, PDO::PARAM_STR);
                $stmt->bindParam(':price', $price, PDO::PARAM_STR);
                $stmt->bindParam(':source_url', $source_url, PDO::PARAM_STR);
                $stmt->execute();
            }
            $db->commit();
        } catch (PDOException $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log("DB Error saving releases for {$year}-{$month}: " . $e->getMessage());
        }
    }
    
    private function fetchUrlContent(string $url): string|false
    {
        // このメソッドは前回から変更ありませんが、呼び出し元で利用されるため含めます
        $options = [
            'http' => [
                'method' => "GET",
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.75 Safari/537.36\r\n" .
                            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9\r\n" .
                            "Accept-Language: ja-JP,ja;q=0.9,en-US;q=0.8,en;q=0.7\r\n",
                'timeout' => 20,
                'follow_location' => 1,
                'ignore_errors' => true 
            ]
        ];
        $context = stream_context_create($options);
        $content = @file_get_contents($url, false, $context);
        if (isset($http_response_header)) {
            $status_line = $http_response_header[0];
            preg_match('{HTTP\/\S*\s(\d{3})}', $status_line, $match);
            $status = $match[1] ?? 0;
            if ($status < 200 || $status >= 400) {
                error_log("Failed to fetch URL: {$url} - HTTP Status: {$status}");
                return false;
            }
        } elseif ($content === false) {
             error_log("Failed to fetch URL (file_get_contents error): " . $url);
        }
        return $content;
    }

    private function scrapeHonHikidashi(int $year, int $month): array
    {
        $dataForMonth = [];
        $processedArticleUrls = [];
        $tagPageUrl = "https://hon-hikidashi.jp/tag/%E6%96%B0%E5%88%8A%E3%83%A9%E3%82%A4%E3%83%B3%E3%82%A2%E3%83%83%E3%83%97_{$year}%E5%B9%B4{$month}%E6%9C%88/";
        error_log("Scraping main tag page: " . $tagPageUrl);
        $tagPageHtml = $this->fetchUrlContent($tagPageUrl);
        if (!$tagPageHtml) {
            return ['error' => "「{$year}年{$month}月」のまとめページ（タグページ）を取得できませんでした。URL: {$tagPageUrl}"];
        }
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $tagPageHtml); // Prevent encoding issues
        $xpath = new DOMXPath($dom);
        
        // 記事へのリンクを探す (より具体的な条件に)
        $articleLinkNodes = $xpath->query("//a[contains(@href, '/news/')]");
        $articleUrlsToScrape = [];
        if ($articleLinkNodes) {
            foreach ($articleLinkNodes as $linkNode) {
                $linkHref = $linkNode->getAttribute('href');
                $linkText = trim($linkNode->nodeValue);
                if (strpos($linkHref, 'http') !== 0) $linkHref = "https://hon-hikidashi.jp" . $linkHref; // Ensure full URL

                // リンクテキストに年、月、および関連キーワードが含まれているかを確認
                if (str_contains($linkText, "{$year}年") && str_contains($linkText, "{$month}月") && 
                    (str_contains($linkText, "コミック") || str_contains($linkText, "文庫") || str_contains($linkText, "新書") || str_contains($linkText, "単行本")) &&
                    !in_array($linkHref, $processedArticleUrls)) {
                    $articleUrlsToScrape[] = $linkHref;
                    $processedArticleUrls[] = $linkHref; // Avoid processing the same URL multiple times
                }
            }
        }

        // もし記事リンクが見つからなければ、タグページ自体を解析対象とする（フォールバック）
        if (empty($articleUrlsToScrape)) {
            error_log("No specific article links found for {$year}-{$month} on tag page. Attempting to scrape tag page itself: {$tagPageUrl}");
            $articleUrlsToScrape[] = $tagPageUrl; 
        }

        // ヘッダー行のテキスト（これらを含む行はスキップ）
        $headerTexts = ['発売日', '出版社', 'シリーズ名', '書名', '著者', '価格', '予価', 'ISBN'];

        foreach ($articleUrlsToScrape as $articleUrl) {
            error_log("Processing article URL: " . $articleUrl);
            $articleHtml = $this->fetchUrlContent($articleUrl);
            if (!$articleHtml) {
                error_log("Failed to fetch article content from: " . $articleUrl);
                continue; 
            }
            $articleDom = new DOMDocument();
            @$articleDom->loadHTML('<?xml encoding="utf-8" ?>' . $articleHtml); // Prevent encoding issues
            $articleXpath = new DOMXPath($articleDom);
            
            // '.article__contents' クラスを持つ div または section を探す
            $contentArea = $articleXpath->query("//div[contains(@class, 'article__contents')] | //section[contains(@class, 'article__contents')]")->item(0);
            
            if (!$contentArea) {
                error_log("Could not find '.article__contents' area in: " . $articleUrl);
                continue;
            }

            $tables = $articleXpath->query(".//table", $contentArea); // コンテンツエリア内のテーブルを取得
            if (!$tables || $tables->length === 0) {
                 error_log("No tables found within content area of: " . $articleUrl);
                continue;
            }

            foreach ($tables as $table) {
                $rows = $articleXpath->query(".//tr", $table);
                foreach ($rows as $rowIndex => $row) {
                    $thCells = $articleXpath->query(".//th", $row); // ヘッダーセル(th)を探す
                    $tdCells = $articleXpath->query(".//td", $row); // データセル(td)を探す

                    // ヘッダー行の判定とスキップ処理の強化
                    if ($thCells->length > 0 && $tdCells->length === 0) { // 行全体がthで構成されている場合
                        error_log("Skipping header row (all th) in {$articleUrl}");
                        continue;
                    }
                    if ($tdCells->length > 0) {
                        $firstCellText = trim($tdCells->item(0)->nodeValue);
                        $isHeaderRowByContent = false;
                        foreach ($headerTexts as $ht) {
                            // 最初のセルの内容がヘッダーテキストのいずれかと一致する場合
                            if (strcasecmp($firstCellText, $ht) === 0 || stripos($firstCellText, $ht) !== false) {
                                $isHeaderRowByContent = true;
                                break;
                            }
                        }
                         // もし最初の行で、内容がヘッダーテキストと酷似している場合もスキップ
                        if ($rowIndex < 2 && $isHeaderRowByContent) { // 最初の数行に限定
                            error_log("Skipping potential header row by content '{$firstCellText}' in {$articleUrl}");
                            continue;
                        }
                    } else { // tdセルがない場合はデータ行ではない
                        continue;
                    }
                    
                    // 実際のデータ抽出 (hon-hikidashi.jpのテーブル構造に依存)
                    // 想定されるカラム順: 発売日 | 出版社 | シリーズ名・書名 | 著者 | 予価
                    if ($tdCells->length >= 4) { // 最低4カラムは必要 (日, 出版社, 書名, 著者)
                        $col_date = trim($tdCells->item(0)->nodeValue ?? '');
                        $col_publisher = trim($tdCells->item(1)->nodeValue ?? '');
                        $col_title_series = trim($tdCells->item(2)->nodeValue ?? ''); 
                        $col_author = trim($tdCells->item(3)->nodeValue ?? '');
                        $col_price = ($tdCells->length > 4) ? trim($tdCells->item(4)->nodeValue ?? '') : null;

                        // タイトルと発売日が空でなく、かつ異常に長くないことを確認
                        if (!empty($col_title_series) && !empty($col_date) && 
                            strlen($col_date) < 20 && strlen($col_publisher) < 100 && strlen($col_title_series) < 255 && strlen($col_author) < 255 &&
                            count($dataForMonth) < 500) { // データ件数上限
                            
                            // ヘッダーテキストが誤ってデータとして解釈されるのを防ぐ最終チェック
                            $combinedCellData = strtolower($col_date . $col_publisher . $col_title_series . $col_author);
                            $isLikelyStillHeader = false;
                            foreach($headerTexts as $ht_check) {
                                if(str_contains($combinedCellData, strtolower($ht_check))) {
                                    // If multiple header texts appear in the combined data of a row, it's highly suspect
                                    if(substr_count($combinedCellData, strtolower($ht_check)) > 1 && strlen($combinedCellData) < 50 ) {
                                       $isLikelyStillHeader = true; break;
                                    }
                                }
                            }
                            if($isLikelyStillHeader) {
                                error_log("Skipping row that still looks like a header: {$col_date}|{$col_publisher}|{$col_title_series}");
                                continue;
                            }


                            $dataForMonth[] = [
                                'date' => $col_date,
                                'publisher' => $col_publisher,
                                'title' => $col_title_series,
                                'author' => $col_author,
                                'price' => $col_price,
                                'source_url' => $articleUrl
                            ];
                        }
                    }
                }
            }
        }
        
        if (empty($dataForMonth) && empty($processedArticleUrls) && !empty($articleUrlsToScrape) && $articleUrlsToScrape[0] === $tagPageUrl) {
             return ['error' => "{$year}年{$month}月の新刊情報記事へのリンク、および直接的な情報がまとめページに見つかりませんでした。"];
        }
        return $dataForMonth;
    }

    // Internal method to fetch comic info by ISBN, returns array or null
    private function _fetchComicInfoByIsbn(string $isbn, ?string $expectedTitle = null, ?int $expectedVolume = null): ?array
    {
        if (!preg_match('/^([0-9]{13}|[0-9]{9}[0-9X])$/', $isbn)) {
            error_log("Invalid ISBN format for _fetchComicInfoByIsbn: {$isbn}");
            return ['error' => '無効なISBNフォーマットです。', 'status_code' => 400];
        }

        $openSearchApiUrl = "https://iss.ndl.go.jp/api/opensearch?isbn=" . urlencode($isbn);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $openSearchApiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // curl_setopt($ch, CURLOPT_FAILONERROR, true); // Don't use, handle HTTP codes manually
        curl_setopt($ch, CURLOPT_USERAGENT, self::NDL_API_USER_AGENT);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        $xmlResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $curlError = curl_error($ch); $curlErrno = curl_errno($ch); curl_close($ch);
            error_log("cURL Error for ISBN {$isbn}: [{$curlErrno}] {$curlError}");
            return ['error' => "NDL API接続エラー: {$curlError}", 'status_code' => 503];
        }
        curl_close($ch);

        if ($httpCode >= 400) {
            error_log("NDL API HTTP Error for ISBN {$isbn}: {$httpCode}. Response: " . $xmlResponse);
            return ['error' => "NDL API 書誌情報取得失敗 (HTTP {$httpCode})", 'status_code' => ($httpCode == 404 ? 404 : 503)];
        }
        if (empty($xmlResponse)) {
            error_log("NDL API empty response for ISBN {$isbn}, HTTP {$httpCode}");
            return ['error' => 'NDL APIから空の応答がありました。', 'status_code' => ($httpCode == 204 ? 404 : 500)];
        }

        $cover_image_url = '';
        $normalizedIsbn13 = $isbn; 
        if (strlen($isbn) == 10) { 
            if (preg_match('/^([0-9]{9})([0-9X])$/', $isbn, $matches)) {
                $base = '978' . $matches[1];
                $sum = 0;
                for ($i = 0; $i < 12; $i++) {
                    $sum += (int)$base[$i] * (($i % 2 === 0) ? 1 : 3);
                }
                $checkDigit = (10 - ($sum % 10)) % 10;
                $normalizedIsbn13 = $base . $checkDigit;
            }
        }
        if (preg_match('/^[0-9]{13}$/', $normalizedIsbn13)) {
            $cover_image_url = "https://ndlsearch.ndl.go.jp/thumbnail/{$normalizedIsbn13}.jpg";
        }

        try {
            libxml_use_internal_errors(true);
            $xml = new \SimpleXMLElement($xmlResponse);
            $xml_errors = libxml_get_errors();
            libxml_clear_errors();
            libxml_use_internal_errors(false);

            if (!empty($xml_errors)) {
                $errorMessages = [];
                foreach($xml_errors as $xml_err) $errorMessages[] = $xml_err->message;
                throw new \Exception("XML Parsing Error: " . implode("; ", $errorMessages));
            }
            
            $xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
            $xml->registerXPathNamespace('openSearch', 'http://a9.com/-/spec/opensearch/1.1/');
            $xml->registerXPathNamespace('dcndl', 'http://ndl.go.jp/dcndl/terms/');
            
            $totalResultsNodes = $xml->xpath('//openSearch:totalResults');
            if (!$totalResultsNodes || (int)$totalResultsNodes[0] === 0) {
                return null; 
            }
            $item = $xml->channel->item[0] ?? null;
            if (!$item) return null;

            $title = (string)($item->xpath('./dc:title')[0] ?? 'タイトル不明');
            $author = (string)($item->xpath('./dc:creator')[0] ?? '著者不明');
            $publisher = (string)($item->xpath('./dc:publisher')[0] ?? '出版社不明');
            
            $actualVolume = null;
            $volumeNodes = $item->xpath('./dcndl:volume');
            if ($volumeNodes && !empty((string)$volumeNodes[0])) {
                if (preg_match('/([0-9]+)/', (string)$volumeNodes[0], $matches)) {
                    $actualVolume = (int)$matches[1];
                }
            } else { 
                if (preg_match('/(?:第|Vol\.?|\()\s*([0-9]+)(?:\s*巻|\)|)/iu', $title, $matches) ||
                    preg_match('/([0-9]+)\s*巻/iu', $title, $matches) ||
                    preg_match('/\s+([0-9]+)(?:完)?$/u', $title, $matches)) {
                    $actualVolume = (int)$matches[1];
                }
            }
            
            if ($expectedVolume !== null && $actualVolume !== $expectedVolume) {
                if ($expectedTitle && stripos($title, $expectedTitle) !== false) {
                    error_log("ISBN {$isbn} for '{$title}' (vol {$actualVolume}) does not match expected volume {$expectedVolume} for series '{$expectedTitle}'.");
                    return ['error' => "取得した書籍の巻数({$actualVolume})が期待値({$expectedVolume})と異なります。", 'status_code' => 404, 'data' => ['title' => $title, 'isbn' => $isbn]];
                }
            }

            return [
                'title' => $title,
                'author' => rtrim(preg_replace('/,?\s+[0-9]{4}-$/', '', $author), ','),
                'publisher' => $publisher,
                'cover_image' => $cover_image_url,
                'isbn' => $isbn,
                'volume' => $actualVolume ?? $expectedVolume, 
            ];
        } catch (\Exception $e) {
            error_log("NDL API XML processing failed for ISBN {$isbn}: " . $e->getMessage());
            return ['error' => 'NDL API 書誌情報解析エラー: ' . $e->getMessage(), 'status_code' => 500];
        }
        return null; 
    }

    // Method to search NDL by title (and optionally volume string) to find an ISBN
    private function _findIsbnByTitleAndVolumeString(string $seriesTitle, string $volumeSearchString): ?string
    {
        $searchQuery = $seriesTitle . " " . $volumeSearchString;
        $searchUrl = "https://iss.ndl.go.jp/api/opensearch?title=" . urlencode($searchQuery) . "&mediatype=1&cnt=5"; // mediatype=1 for books, cnt=5 to limit results
        
        error_log("NDL Search for ISBN: Query='{$searchQuery}', URL='{$searchUrl}'");
        usleep(500000); // Rate limit: 0.5 second delay before NDL search call

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $searchUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, self::NDL_API_USER_AGENT);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $xmlResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400 || empty($xmlResponse)) {
            error_log("NDL title search failed for '{$searchQuery}', HTTP {$httpCode}");
            return null;
        }

        try {
            libxml_use_internal_errors(true);
            $xml = new \SimpleXMLElement($xmlResponse);
            $xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
            $xml->registerXPathNamespace('xsi', 'http://www.w3.org/2001/XMLSchema-instance'); // For xsi:type
            $xml->registerXPathNamespace('dcterms', 'http://purl.org/dc/terms/'); // For dcterms:ISBN
            $xml->registerXPathNamespace('dcndl', 'http://ndl.go.jp/dcndl/terms/'); // For dcndl:ISBN
            
            foreach ($xml->channel->item as $item) {
                $itemTitle = (string)($item->xpath('./dc:title')[0] ?? '');
                $itemIsbn = null;
                // Try different ways NDL might format ISBN identifier
                $isbnNodes = $item->xpath('./dc:identifier[@xsi:type="dcndl:ISBN"] | ./dc:identifier[@xsi:type="dcterms:ISBN"] | ./dc:identifier[contains(., "ISBN")]');

                if ($isbnNodes) {
                    foreach ($isbnNodes as $isbnNode) {
                        $isbnCandidate = (string)$isbnNode;
                        // Basic ISBN-like pattern check (can be improved)
                        if (preg_match('/[0-9X-]{10,}/', $isbnCandidate)) {
                             $itemIsbn = preg_replace('/[^0-9X]/i', '', $isbnCandidate); // Normalize
                             if (preg_match('/^([0-9]{13}|[0-9]{9}[0-9X])$/', $itemIsbn)) {
                                break; // Found a valid looking ISBN
                             } else {
                                $itemIsbn = null; // Not a valid format after normalization
                             }
                        }
                    }
                }

                if ($itemIsbn && !empty($itemTitle)) {
                    // More robust check: series title must be a clear substring, and volume string also.
                    // Check for "Series Title" AND ("VolString" OR "VolString巻" OR "巻VolString")
                    $volNumOnly = preg_replace('/[^0-9]/', '', $volumeSearchString); // "5巻" -> "5"
                    $titleLower = strtolower($itemTitle);
                    $seriesTitleLower = strtolower($seriesTitle);
                    
                    $volumePattern = '/' . preg_quote($volNumOnly, '/') . '(?:\s*巻|\s|$)/iu'; // Matches "5巻", "5 巻", "5" (at end or followed by space)
                                        
                    if (str_contains($titleLower, $seriesTitleLower) && preg_match($volumePattern, $itemTitle)) {
                         error_log("NDL title search found potential match for '{$searchQuery}': Title='{$itemTitle}', ISBN='{$itemIsbn}'");
                        return $itemIsbn; 
                    }
                }
            }
            error_log("NDL title search: No definitive ISBN match found for '{$searchQuery}' among results.");
        } catch (\Exception $e) {
            error_log("Error parsing NDL title search results for '{$searchQuery}': " . $e->getMessage());
        }
        return null;
    }

    public function fetchSeriesByTitleAndVolumeRange(): void
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'ログインが必要です。']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $seriesTitle = trim($input['title'] ?? '');
        $startVolume = filter_var($input['startVolume'] ?? 0, FILTER_VALIDATE_INT);
        $endVolume = filter_var($input['endVolume'] ?? 0, FILTER_VALIDATE_INT);

        if (empty($seriesTitle) || $startVolume === false || $endVolume === false || $startVolume < 1 || $endVolume < $startVolume || $endVolume > $startVolume + 49) { // Limit range to 50 volumes at a time
            http_response_code(400);
            echo json_encode(['error' => '無効な入力です。シリーズタイトル、開始巻数（1以上）、終了巻数（開始巻数以上、範囲は50冊以内）を確認してください。']);
            return;
        }

        $fetchedComics = [];
        $messages = []; 

        for ($volume = $startVolume; $volume <= $endVolume; $volume++) {
            // Try common volume suffixes
            $volumeSuffixesToTry = [$volume . "巻", (string)$volume]; // e.g., "1巻", then "1"
            $foundIsbnForVolume = null;

            foreach ($volumeSuffixesToTry as $suffix) {
                $isbn = $this->_findIsbnByTitleAndVolumeString($seriesTitle, $suffix);
                if ($isbn) {
                    $foundIsbnForVolume = $isbn;
                    break; 
                }
            }
            
            if ($foundIsbnForVolume) {
                usleep(750000); // Rate limit: 0.75 second delay before _fetchComicInfoByIsbn call
                $comicData = $this->_fetchComicInfoByIsbn($foundIsbnForVolume, $seriesTitle, $volume);
                if ($comicData && !isset($comicData['error'])) {
                    $comicData['volume'] = $comicData['volume'] ?? $volume; 
                    $fetchedComics[] = $comicData;
                    $messages[] = "{$seriesTitle} {$volume}巻 (ISBN: {$foundIsbnForVolume}): 情報取得成功。";
                } else {
                    $errorMsg = $comicData['error'] ?? "ISBN {$foundIsbnForVolume} の情報取得失敗。";
                    if (isset($comicData['data']['title'])) $errorMsg .= " (取得タイトル候補: " . $comicData['data']['title'] . ")";
                    $messages[] = "{$seriesTitle} {$volume}巻 (ISBN検索試行値: {$foundIsbnForVolume}): {$errorMsg}";
                    error_log("Failed to fetch info for ISBN {$foundIsbnForVolume} (Series: {$seriesTitle} Vol: {$volume}): {$errorMsg}");
                }
            } else {
                $messages[] = "{$seriesTitle} {$volume}巻: ISBNが見つかりませんでした。";
                error_log("Could not find ISBN for Series: {$seriesTitle} Vol: {$volume}");
            }
             if ($volume < $endVolume) { // Add a small delay between processing each volume in the loop
                 usleep(250000); // 0.25 sec additional delay
             }
        }

        if (empty($fetchedComics) && empty($messages)) {
             echo json_encode(['error' => "「{$seriesTitle}」の情報が見つかりませんでした。"]);
        } else {
             echo json_encode(['fetchedComics' => $fetchedComics, 'messages' => $messages]);
        }
    }

    // Public API endpoint for fetching single comic info by ISBN
    public function fetchComicInfoByIsbnPublic(): void
    {
        header('Content-Type: application/json');
        $isbn = $_GET['isbn'] ?? null;
        if (!$isbn) {
            http_response_code(400);
            echo json_encode(['error' => 'ISBNが指定されていません。']);
            return;
        }

        // ISBNを正規化（ハイフン除去など）
        $normalizedIsbn = preg_replace('/[^0-9X]/i', '', $isbn);
        if (!preg_match('/^([0-9]{13}|[0-9]{9}[0-9X])$/', $normalizedIsbn)) {
             http_response_code(400);
             echo json_encode(['error' => '無効なISBNフォーマットです。']);
             return;
        }


        $comicData = $this->_fetchComicInfoByIsbn($normalizedIsbn); // Call the internal method

        if ($comicData && !isset($comicData['error'])) {
            echo json_encode($comicData);
        } elseif (isset($comicData['error'])) {
            // Use the status code from the internal method if available
            $statusCode = isset($comicData['status_code']) ? (int)$comicData['status_code'] : 500;
            // For 404 specifically from NDL for an ISBN, it means not found.
            if ($statusCode == 404 && str_contains($comicData['error'], 'NDL')) {
                 http_response_code(404);
                 echo json_encode(['error' => '指定されたISBNの書籍がNDLで見つかりませんでした。']);
            } else {
                 http_response_code($statusCode);
                 echo json_encode(['error' => $comicData['error']]);
            }
        } else {
            // Generic not found if _fetchComicInfoByIsbn returns null without an error structure
            http_response_code(404);
            echo json_encode(['error' => '指定されたISBNの書籍が見つかりませんでした。']);
        }
    }
    // ===>>> END OF NEW METHODS <<<===
}