<?php
// views/comic_register.php

// ログインチェック (コントローラーでも行っているが念のため)
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>漫画一括登録</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #007bff;
            /* Primary Blue */
            --primary-hover-color: #0056b3;
            --secondary-color: #6c757d;
            /* Gray */
            --success-color: #28a745;
            /* Green */
            --success-hover-color: #218838;
            --danger-color: #dc3545;
            /* Red */
            --danger-hover-color: #c82333;
            --warning-color: #ffc107;
            /* Yellow */
            --light-color: #f8f9fa;
            /* Light Gray */
            --dark-color: #343a40;
            /* Dark Gray */
            --white-color: #ffffff;
            --border-color: #dee2e6;
            --text-color: #212529;
            --text-muted-color: #6c757d;

            --font-family-sans-serif: 'Noto Sans JP', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            --font-size-base: 1rem;
            /* 16px */
            --line-height-base: 1.6;

            /* WCAG AAA Contrast Ratios (Examples vs white/light background) */
            --wcag-aaa-text: #333;
            /* 7.06:1 */
            --wcag-aaa-primary-text: #004085;
            /* 7.08:1 on light, use white text on primary bg */
            --wcag-aaa-button-bg: #005cbf;
            /* 4.54:1, ok for large text/graphics */
            --wcag-aaa-button-text: var(--white-color);
            /* 5.66:1 on aaa-button-bg */

        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-family-sans-serif);
            font-size: var(--font-size-base);
            line-height: var(--line-height-base);
            color: var(--text-color);
            /* WCAG: Use high contrast text */
            background-color: var(--light-color);
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Header Styles (Similar to top.php) */
        header {
            background-color: var(--white-color);
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 40px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        header .container {
            max-width: 960px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            /* Space between title and button */
            align-items: center;
        }

        header h1 {
            font-size: 1.8rem;
            color: var(--dark-color);
            margin: 0;
            font-weight: 700;
        }

        main {
            max-width: 960px;
            margin: 0 auto;
            padding: 0 20px 40px;
            /* Add bottom padding */
        }

        /* Button Styles (Consistent) */
        .button {
            display: inline-block;
            font-weight: 500;
            color: var(--white-color);
            text-align: center;
            vertical-align: middle;
            cursor: pointer;
            user-select: none;
            background-color: var(--primary-color);
            border: 1px solid var(--primary-color);
            padding: 0.5rem 1rem;
            /* Adjusted padding */
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.3rem;
            text-decoration: none;
            transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            outline: 3px solid transparent;
            /* For focus */
        }

        .button:hover {
            background-color: var(--primary-hover-color);
            border-color: var(--primary-hover-color);
            color: var(--white-color);
            text-decoration: none;
        }

        .button:focus {
            outline: 3px solid var(--warning-color);
            /* WCAG: Clear focus indicator */
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.5);
        }

        .button-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .button-success:hover {
            background-color: var(--success-hover-color);
            border-color: var(--success-hover-color);
        }

        .button-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }

        .button-danger:hover {
            background-color: var(--danger-hover-color);
            border-color: var(--danger-hover-color);
        }

        .button-secondary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .button-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 1.5rem;
            /* Increased spacing */
        }

        label {
            display: inline-block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-control {
            display: block;
            width: 100%;
            padding: 0.6rem 0.9rem;
            /* Adjusted padding */
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: var(--text-color);
            background-color: var(--white-color);
            background-clip: padding-box;
            border: 1px solid var(--border-color);
            border-radius: 0.25rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .form-control:focus {
            border-color: var(--primary-hover-color);
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            /* WCAG: Clear focus */
        }

        /* ISBN入力欄コンテナ */
        #isbn-list {
            margin-bottom: 1rem;
        }

        .isbn-input-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .isbn-input-item input[type="text"] {
            flex-grow: 1;
            /* できるだけ幅を取る */
            margin-right: 0.5rem;
        }

        /* Preview Area */
        #preview-area {
            margin-top: 2rem;
            border-top: 1px solid var(--border-color);
            padding-top: 1.5rem;
        }

        .comic-preview-item {
            display: flex;
            align-items: flex-start;
            /* Align top */
            margin-bottom: 1.5rem;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.3rem;
            background-color: var(--white-color);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            position: relative;
            /* For remove button */
        }

        .comic-preview-item img {
            width: 60px;
            /* Fixed width */
            height: auto;
            /* Maintain aspect ratio */
            max-height: 90px;
            /* Limit height */
            object-fit: cover;
            /* Cover the area */
            margin-right: 1.5rem;
            /* Spacing */
            border: 1px solid var(--border-color);
            flex-shrink: 0;
            /* Prevent shrinking */
        }

        .comic-preview-item .info {
            flex: 1 1 auto;
            /* Take remaining space */
        }

        .comic-preview-item p {
            margin-bottom: 0.3rem;
            /* Smaller spacing */
            font-size: 0.95rem;
            /* Slightly smaller text */
            color: var(--text-color);
        }

        .comic-preview-item p strong {
            font-weight: 500;
            color: var(--dark-color);
            /* Darker label */
            margin-right: 0.5em;
        }

        .comic-preview-item .remove-preview {
            position: absolute;
            top: 5px;
            right: 5px;
            padding: 2px 6px;
            font-size: 0.8rem;
            line-height: 1;
        }

        /* Loading and Status Messages */
        .status-message {
            padding: 0.75rem 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 0.25rem;
            font-weight: 500;
        }

        .status-loading {
            color: var(--secondary-color);
            background-color: #e2e3e5;
            border-color: #d6d8db;
        }

        .status-error {
            color: #721c24;
            /* WCAG AAA Text on light red */
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .status-success {
            color: #155724;
            /* WCAG AAA Text on light green */
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        #bulk-status {
            margin-top: 1rem;
        }

        /* 一括処理用ステータス */

        /* Action Buttons Area */
        .action-buttons {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
            /* Space between buttons */
            border-top: 1px solid var(--border-color);
            padding-top: 1.5rem;
        }

        /* Footer Styles (Consistent) */
        footer {
            margin-top: 60px;
            padding: 20px 0;
            text-align: center;
            font-size: 0.9rem;
            color: var(--text-muted-color);
            border-top: 1px solid var(--border-color);
            background: var(--white-color);
        }

        /* Accessibility */
        .sr-only {
            /* Screen Reader Only */
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            header .container {
                flex-direction: column;
                align-items: flex-start;
            }

            header h1 {
                margin-bottom: 10px;
            }

            .isbn-input-item {
                flex-direction: column;
                align-items: stretch;
            }

            .isbn-input-item input[type="text"] {
                margin-right: 0;
                margin-bottom: 0.5rem;
            }

            .isbn-input-item .button-danger {
                width: 100%;
            }

            /* 削除ボタンを幅一杯に */
            .comic-preview-item {
                flex-direction: column;
                /* Stack image and info */
                align-items: center;
                /* Center items */
                text-align: center;
            }

            .comic-preview-item img {
                margin-right: 0;
                margin-bottom: 1rem;
            }

            .comic-preview-item .remove-preview {
                top: auto;
                bottom: 5px;
                right: 5px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-buttons .button {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <h1>漫画一括登録</h1>
            <a href="/comic_list" class="button button-secondary">一覧に戻る</a>
        </div>
    </header>

    <main>
        <section aria-labelledby="ocr-section-title">
            <h2 id="ocr-section-title">0. (オプション) レシートからISBNを読み取る</h2>
            <div class="form-group">
                <label for="receipt-image-input">レシート画像ファイルを選択:</label>
                <input type="file" id="receipt-image-input" class="form-control" accept="image/*">
                <small class="form-text text-muted">購入した漫画のISBNが記載されたレシート画像を選択してください。</small>
            </div>
            <button type="button" id="ocr-button" class="button button-info">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                    class="bi bi-camera-fill" viewBox="0 0 16 16" aria-hidden="true" style="vertical-align: -0.125em;">
                    <path d="M10.5 8.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0" />
                    <path
                        d="M2 4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-1.172a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 9.172 2H6.828a2 2 0 0 0-1.414.586l-.828.828A2 2 0 0 1 3.172 4zm.5 4.5a.5.5 0 1 1 0-1 .5.5 0 0 1 0 1m9-1a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0" />
                </svg>
                選択した画像を読み取ってISBNを追加
            </button>
            <div id="ocr-status" class="status-message" style="display: none; margin-top: 1rem;" role="status"
                aria-live="polite"></div>
        </section>
        <hr style="margin: 2rem 0;">
        <section aria-labelledby="isbn-section-title">
            <h2 id="isbn-section-title">1. ISBNコード入力</h2>
            <p>登録したい漫画のISBNコード（10桁または13桁）を下の欄に入力してください。「入力欄を追加」で複数のISBNを追加できます。</p>
            <div id="isbn-list">
            </div>
            <button type="button" id="add-isbn-button" class="button button-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-lg"
                    viewBox="0 0 16 16" aria-hidden="true" style="vertical-align: -0.125em;">
                    <path fill-rule="evenodd"
                        d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2" />
                </svg>
                入力欄を追加
            </button>
            <button type="button" id="bulk-fetch-button" class="button button-primary" style="margin-left: 1rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                    class="bi bi-cloud-download-fill" viewBox="0 0 16 16" aria-hidden="true"
                    style="vertical-align: -0.125em;">
                    <path fill-rule="evenodd"
                        d="M8 0a5.53 5.53 0 0 0-3.594 1.342c-.766.66-1.321 1.52-1.464 2.383C1.266 4.095 0 5.555 0 7.318 0 9.366 1.708 11 3.781 11H7.5V5.5a.5.5 0 0 1 1 0V11h4.188C14.502 11 16 9.57 16 7.773c0-1.636-1.242-2.969-2.834-3.194C12.923 1.999 10.69 0 8 0m-.354 15.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 14.293V11h-1v3.293l-2.146-2.147a.5.5 0 0 0-.708.708z" />
                </svg>
                入力されたISBNの情報を一括取得
            </button>
            <div id="bulk-status" class="status-message" style="display: none;" role="alert" aria-live="assertive">
            </div>
        </section>

        <section aria-labelledby="preview-section-title">
            <h2 id="preview-section-title">2. 登録候補の確認</h2>
            <p>取得した書籍情報が表示されます。内容を確認し、不要なものは削除してください。</p>
            <div id="preview-area">
                <p id="preview-placeholder">まだ登録候補はありません。</p>
            </div>
        </section>

        <section class="action-buttons" aria-label="登録操作">
            <button type="button" id="save-comics-button" class="button button-success" disabled>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                    class="bi bi-check-circle-fill" viewBox="0 0 16 16" aria-hidden="true"
                    style="vertical-align: -0.125em;">
                    <path
                        d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                </svg>
                選択した漫画をDBに保存
            </button>
        </section>
    </main>


    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> 漫画管理システム</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const isbnListDiv = document.getElementById('isbn-list');
            const addIsbnButton = document.getElementById('add-isbn-button');
            const bulkFetchButton = document.getElementById('bulk-fetch-button');
            const previewArea = document.getElementById('preview-area');
            const previewPlaceholder = document.getElementById('preview-placeholder');
            const saveButton = document.getElementById('save-comics-button');
            const bulkStatusDiv = document.getElementById('bulk-status');

            let inputCounter = 0;
            const comicsToSave = new Map(); // key: isbn, value: comicData

            // --- OCR関連の要素を取得 (新規追加) ---
            const ocrButton = document.getElementById('ocr-button');
            const imageInput = document.getElementById('receipt-image-input');
            const ocrStatusDiv = document.getElementById('ocr-status');
            // --- OCR関連ここまで ---


            // --- 初期入力欄の追加 ---
            addIsbnInput();

            // --- イベントリスナー ---
            addIsbnButton.addEventListener('click', addIsbnInput);
            bulkFetchButton.addEventListener('click', handleBulkFetch);
            saveButton.addEventListener('click', handleSaveComics); // 保存処理は変更なし
            ocrButton.addEventListener('click', handleOcr); // ← OCRボタンのリスナーを追加


            // --- 関数定義 ---

            // ISBN入力欄を追加
            function addIsbnInput(initialValue = '') {
                inputCounter++;
                const inputId = `isbn-input-${inputCounter}`;

                const itemDiv = document.createElement('div');
                itemDiv.className = 'isbn-input-item';
                itemDiv.id = `isbn-item-${inputCounter}`; // 要素識別のためのID
                itemDiv.innerHTML = `
            <label for="${inputId}" class="sr-only">ISBN ${inputCounter}</label>
            <input type="text" id="${inputId}" class="form-control" placeholder="ISBNコード (例: 9784088802641)">
            <button type="button" class="button button-danger remove-isbn-button" data-target="isbn-item-${inputCounter}" aria-label="ISBN入力欄 ${inputCounter} を削除">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle-fill" viewBox="0 0 16 16" aria-hidden="true" style="vertical-align: -0.125em;">
                  <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293z"/>
                </svg>
                 削除
            </button>
        `;
                isbnListDiv.appendChild(itemDiv);

                // 削除ボタンにイベントリスナー追加
                itemDiv.querySelector('.remove-isbn-button').addEventListener('click', (e) => {
                    const targetId = e.currentTarget.dataset.target;
                    const targetElement = document.getElementById(targetId);
                    if (targetElement) {
                        targetElement.remove();
                        // プレビューからも対応するISBNを削除（必要であれば）
                        const inputField = targetElement.querySelector('input');
                        if (inputField && inputField.value.trim()) {
                            const isbnToRemove = inputField.value.trim().replace(/-/g, '');
                            removePreviewByIsbn(isbnToRemove);
                        }
                        targetElement.remove();
                    }
                });

                // 初期値があれば、関連するプレビューも探して削除する (OCRで重複追加しないように)
                if (initialValue) {
                    const isbn = initialValue.trim().replace(/-/g, '');
                    removePreviewByIsbn(isbn);
                }

                return itemDiv.querySelector('input'); // 作成したinput要素を返す
            }


            // --- OCR処理関数 (新規追加) ---
            // --- OCR処理関数 (バックエンドAPI呼び出し版) ---
            async function handleOcr() {
                const file = imageInput.files[0];
                if (!file) {
                    showOcrStatus('画像ファイルが選択されていません。', 'warning');
                    return;
                }
                if (!file.type.startsWith('image/')) {
                    showOcrStatus('画像ファイルを選択してください。', 'warning');
                    return;
                }

                showOcrStatus('画像をサーバーにアップロード中...', 'loading');
                ocrButton.disabled = true;
                imageInput.disabled = true;

                // FormDataオブジェクトを作成して画像を追加
                const formData = new FormData();
                formData.append('receiptImage', file); // キー名はPHP側の $_FILES['receiptImage'] と一致させる

                try {
                    // PHPの新しいエンドポイントにPOSTリクエスト
                    const response = await fetch('/api/ocr_receipt', {
                        method: 'POST',
                        body: formData
                        // Content-Type は FormData を使う場合、ブラウザが自動設定するので不要
                    });

                    // レスポンスをJSONとして解析
                    const result = await response.json();

                    if (!response.ok) {
                        // PHP側 or OCRサービス側でエラーが発生した場合
                        throw new Error(result.error || `サーバーエラーが発生しました (HTTP ${response.status})`);
                    }

                    // --- 成功時の処理 ---
                    showOcrStatus('OCR処理完了。ISBNを検索中...', 'loading'); // メッセージ変更
                    console.log("ISBNs received from server:", result.isbns);

                    const isbns = result.isbns || []; // isbns配列を取得

                    // 既存の入力欄の値を取得 (変更なし)
                    const existingIsbns = new Set();
                    isbnListDiv.querySelectorAll('input[type="text"]').forEach(input => { /* ... */ });

                    // 新しいISBNを入力欄に追加 (変更なし)
                    let addedCount = 0;
                    if (isbns.length > 0) {
                        isbns.forEach(isbn => {
                            if (!existingIsbns.has(isbn)) {
                                const targetInput = addIsbnInput();
                                targetInput.value = isbn;
                                existingIsbns.add(isbn);
                                addedCount++;
                            }
                        });
                    }

                    showOcrStatus(`読み取り完了。${addedCount}件の新しいISBNが見つかりました。`, 'success');

                } catch (error) {
                    console.error("OCR Upload/Process Error:", error);
                    showOcrStatus(`処理中にエラーが発生しました: ${error.message || error}`, 'error');
                } finally {
                    ocrButton.disabled = false;
                    imageInput.disabled = false;
                    // 選択されたファイルをリセット
                    imageInput.value = '';
                }
            }
            
            // 空のISBN入力欄を探す（任意）
            function findFirstEmptyIsbnInput() {
                const inputs = isbnListDiv.querySelectorAll('input[type="text"]');
                for (const input of inputs) {
                    if (input.value.trim() === '') {
                        return input;
                    }
                }
                return null;
            }


            // OCRステータス表示関数 (新規追加)
            function showOcrStatus(message, type = 'info') {
                ocrStatusDiv.textContent = message;
                ocrStatusDiv.className = `status-message status-${type}`;
                ocrStatusDiv.style.display = 'block';
            }
            // --- OCR関連関数ここまで ---

            // 一括情報取得ボタンの処理
            async function handleBulkFetch() {
                const isbnInputs = isbnListDiv.querySelectorAll('input[type="text"]');
                const isbnsToFetch = [];
                const invalidIsbns = [];
                const alreadyAddedIsbns = [];

                // 入力値の収集とバリデーション
                isbnInputs.forEach(input => {
                    const isbn = input.value.trim().replace(/-/g, '');
                    if (isbn) { // 空欄は無視
                        if (!/^[0-9]{10,13}$/.test(isbn)) {
                            invalidIsbns.push(isbn || '空欄');
                            input.classList.add('is-invalid'); // エラー表示（CSSで要定義）
                            input.setAttribute('aria-invalid', 'true');
                        } else if (comicsToSave.has(isbn)) {
                            alreadyAddedIsbns.push(isbn);
                            input.classList.add('is-warning'); // 警告表示（CSSで要定義）
                        } else if (!isbnsToFetch.includes(isbn)) { // 重複取得防止
                            isbnsToFetch.push(isbn);
                            input.classList.remove('is-invalid', 'is-warning');
                            input.setAttribute('aria-invalid', 'false');
                        } else {
                            // 同じISBNが複数入力されている場合（警告など）
                            input.classList.add('is-warning');
                        }
                    } else {
                        input.classList.remove('is-invalid', 'is-warning');
                        input.setAttribute('aria-invalid', 'false');
                    }
                });

                // バリデーションメッセージ表示
                let errorMsg = '';
                if (invalidIsbns.length > 0) {
                    errorMsg += `無効なISBN: ${invalidIsbns.join(', ')}。 `;
                }
                if (alreadyAddedIsbns.length > 0) {
                    errorMsg += `既に追加済みのISBN: ${alreadyAddedIsbns.join(', ')}。 `;
                }
                if (errorMsg) {
                    showBulkStatus(errorMsg, 'warning');
                    // return; // 処理を中断する場合はコメント解除
                } else {
                    hideBulkStatus(); // エラーがなければメッセージを消す
                }


                if (isbnsToFetch.length === 0) {
                    showBulkStatus('情報を取得する新しいISBNが入力されていません。', 'info');
                    return;
                }

                showBulkStatus(`処理中: ${isbnsToFetch.length}件のISBN情報を取得しています...`, 'loading');
                bulkFetchButton.disabled = true;
                addIsbnButton.disabled = true; // 処理中は追加も不可

                // 各ISBNに対してAPIリクエストを作成 (Promise配列)
                const fetchPromises = isbnsToFetch.map(isbn =>
                    fetch(`/api/fetch_comic_info?isbn=${encodeURIComponent(isbn)}`)
                        .then(response => response.json().then(data => ({ isbn, ok: response.ok, data }))) // レスポンスとデータをセットに
                        .catch(error => ({ isbn, ok: false, data: { error: '通信エラー' }, networkError: error })) // 通信自体のエラーも捕捉
                );

                // 全てのPromiseが完了するのを待つ
                const results = await Promise.all(fetchPromises);

                // 結果を処理
                let successCount = 0;
                let failCount = 0;
                const errorDetails = [];

                results.forEach(result => {
                    if (result.ok && !result.data.error) {
                        addComicPreview(result.data); // 成功したらプレビュー追加
                        successCount++;
                        // 成功した入力欄を無効化またはクリア
                        const inputField = findInputElementByIsbn(result.isbn);
                        if (inputField) {
                            inputField.disabled = true; // or inputField.value = '';
                            inputField.closest('.isbn-input-item')?.classList.add('processed-success'); // CSS用クラス
                        }
                    } else {
                        failCount++;
                        const errorMessage = result.data?.error || '不明なエラー';
                        errorDetails.push(`ISBN ${result.isbn}: ${errorMessage}`);
                        console.error(`Failed fetch for ${result.isbn}:`, result.data, result.networkError);
                        // 失敗した入力欄に印をつける
                        const inputField = findInputElementByIsbn(result.isbn);
                        if (inputField) {
                            inputField.classList.add('is-invalid');
                            inputField.closest('.isbn-input-item')?.classList.add('processed-fail'); // CSS用クラス
                        }
                    }
                });

                // 最終的なステータス表示
                let finalMessage = `${successCount}件の情報を取得しました。`;
                let finalType = 'success';
                if (failCount > 0) {
                    finalMessage += ` ${failCount}件の取得に失敗しました。詳細はコンソールを確認してください。`;
                    finalType = 'warning';
                    // 詳細エラーを画面にも表示する場合
                    // finalMessage += '<ul>' + errorDetails.map(e => `<li>${htmlEscape(e)}</li>`).join('') + '</ul>';
                }
                showBulkStatus(finalMessage, finalType);


                bulkFetchButton.disabled = false;
                addIsbnButton.disabled = false;
                updateSaveButtonState();
            }

            // ISBNから対応する入力要素を見つける（ヘルパー関数）
            function findInputElementByIsbn(isbn) {
                const isbnInputs = isbnListDiv.querySelectorAll('input[type="text"]');
                for (const input of isbnInputs) {
                    if (input.value.trim().replace(/-/g, '') === isbn) {
                        return input;
                    }
                }
                return null;
            }


            // 漫画プレビューを追加 (引数から sourceId を削除)
            function addComicPreview(comicData) {
                // 同じISBNのプレビューが既に存在しないか確認
                if (document.getElementById(`preview-${comicData.isbn}`)) {
                    console.log(`Preview for ISBN ${comicData.isbn} already exists.`);
                    return; // 既に表示されている場合は追加しない
                }


                if (previewPlaceholder) {
                    previewPlaceholder.style.display = 'none';
                }

                const previewId = `preview-${comicData.isbn}`; // ISBNをIDの一部に
                comicsToSave.set(comicData.isbn, comicData); // key を ISBN に変更

                const previewDiv = document.createElement('div');
                previewDiv.className = 'comic-preview-item';
                previewDiv.id = previewId;
                previewDiv.setAttribute('role', 'listitem');
                previewDiv.innerHTML = `
             <img src="${comicData.cover_image || './placeholder.png'}" alt="${comicData.title ? htmlEscape(comicData.title) + 'の表紙' : '書影なし'}" onerror="this.onerror=null;this.src='./placeholder.png';">
             <div class="info">
                 <p><strong>タイトル:</strong> ${htmlEscape(comicData.title || 'N/A')}</p>
                 <p><strong>著者:</strong> ${htmlEscape(comicData.author || 'N/A')}</p>
                 <p><strong>出版社:</strong> ${htmlEscape(comicData.publisher || 'N/A')}</p>
                 <p><strong>ISBN:</strong> ${htmlEscape(comicData.isbn || 'N/A')}</p>
                 ${comicData.volume ? `<p><strong>巻数:</strong> ${htmlEscape(String(comicData.volume))}</p>` : ''}
             </div>
             <button type="button" class="button button-danger button-small remove-preview" data-isbn="${comicData.isbn}" aria-label="${htmlEscape(comicData.title)} の登録候補を削除">
                 <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash-fill" viewBox="0 0 16 16" aria-hidden="true" style="vertical-align: -0.125em;">
                    <path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5M8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5m3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0"/>
                 </svg>
                 削除
              </button>
         `;
                previewArea.appendChild(previewDiv);

                previewDiv.querySelector('.remove-preview').addEventListener('click', handleRemovePreview);
                updateSaveButtonState();
            }

            // プレビュー削除 (ISBNで削除)
            function handleRemovePreview(event) {
                const button = event.currentTarget;
                const isbnToRemove = button.dataset.isbn;
                const previewId = `preview-${isbnToRemove}`;
                const previewDiv = document.getElementById(previewId);

                if (previewDiv) {
                    previewDiv.remove();
                    comicsToSave.delete(isbnToRemove); // 保存候補から削除 (keyはISBN)
                }

                // 対応する入力欄を再度有効化 (見つかれば)
                const inputField = findInputElementByIsbn(isbnToRemove);
                if (inputField) {
                    inputField.disabled = false;
                    inputField.classList.remove('is-invalid', 'is-warning'); // エラー/警告状態を解除
                    inputField.closest('.isbn-input-item')?.classList.remove('processed-success', 'processed-fail');
                    // inputField.focus(); // フォーカスを戻す
                }

                if (comicsToSave.size === 0 && previewPlaceholder) {
                    previewPlaceholder.style.display = 'block';
                }
                updateSaveButtonState();
            }

            // ISBNを指定してプレビューを削除する関数 (入力欄削除時に使用)
            function removePreviewByIsbn(isbn) {
                const previewId = `preview-${isbn}`;
                const previewDiv = document.getElementById(previewId);
                if (previewDiv) {
                    previewDiv.remove();
                    comicsToSave.delete(isbn);
                    updateSaveButtonState();
                    if (comicsToSave.size === 0 && previewPlaceholder) {
                        previewPlaceholder.style.display = 'block';
                    }
                }
            }


            // 保存ボタンの状態更新
            function updateSaveButtonState() {
                saveButton.disabled = comicsToSave.size === 0;
            }

            // 一括処理ステータス表示
            function showBulkStatus(message, type = 'info') {
                bulkStatusDiv.innerHTML = message; // HTMLを許容するように変更（エラーリスト表示のため）
                bulkStatusDiv.className = `status-message status-${type}`;
                bulkStatusDiv.style.display = 'block';
            }
            // 一括処理ステータス非表示
            function hideBulkStatus() {
                bulkStatusDiv.innerHTML = '';
                bulkStatusDiv.style.display = 'none';
            }


            // 「DBに保存」ボタンの処理
            async function handleSaveComics() {
                if (comicsToSave.size === 0) {
                    showBulkStatus('保存する漫画がありません。', 'warning');
                    return;
                }

                showBulkStatus('保存処理を実行中...', 'loading');
                saveButton.disabled = true;

                const comicsDataArray = Array.from(comicsToSave.values());

                try {
                    const response = await fetch('/api/save_comics', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json' // サーバーからのJSONレスポンスを期待
                        },
                        body: JSON.stringify({ comics: comicsDataArray })
                    });

                    const result = await response.json(); // レスポンスボディをJSONとして解析

                    if (!response.ok) {
                        // HTTPステータスがエラー(4xx, 5xx)の場合
                        const errorMessage = result.error || `保存処理中にエラーが発生しました (HTTP ${response.status})。`;
                        showBulkStatus(`保存失敗: ${errorMessage}`, 'error');
                        if (result.details) {
                            console.error("Details:", result.details);
                            // 詳細エラーを表示する処理を追加可能
                            const detailList = result.details.map(d => `<li>${htmlEscape(d)}</li>`).join('');
                            globalStatus.innerHTML += `<ul>${detailList}</ul>`;
                        }
                    } else {
                        // HTTPステータスが成功 (2xx) の場合
                        showBulkStatus(result.message || '保存が完了しました。一覧画面に移動します...', 'success');
                        comicsToSave.clear(); // 保存候補をクリア
                        previewArea.innerHTML = ''; // プレビューをクリア
                        previewPlaceholder.style.display = 'block';
                        isbnListDiv.innerHTML = ''; // 入力欄もクリア
                        addIsbnInput(); // 新しい入力欄を追加
                        updateSaveButtonState(); // ボタン状態更新

                        // 少し待ってから一覧ページへリダイレクト
                        setTimeout(() => {
                            window.location.href = '/comic_list';
                        }, 2000); // 2秒後
                    }

                } catch (error) {
                    console.error('Save Error:', error);
                    showBulkStatus('通信エラーが発生しました。保存処理を完了できませんでした。', 'error');
                } finally {
                    saveButton.disabled = comicsToSave.size === 0; // 保存候補がなければ無効、あれば有効
                }
            }

            // 保存ボタンの状態を更新する関数
            function updateSaveButtonState() {
                saveButton.disabled = comicsToSave.size === 0;
            }

            // ステータスメッセージ表示関数
            function showStatus(element, message, type = 'info') { // type: 'info', 'success', 'warning', 'error', 'loading'
                if (!element) return;
                element.textContent = message;
                element.className = `status-message status-${type}`; // クラスを適切に設定
                element.style.display = 'block';
                // Aria-live属性があるのでスクリーンリーダーが読み上げるはず
            }
            // ステータスメッセージ非表示関数
            function hideStatus(element) {
                if (!element) return;
                element.textContent = '';
                element.style.display = 'none';
            }

            // グローバルステータスメッセージ表示関数
            function showGlobalStatus(message, type = 'info') {
                showStatus(globalStatus, message, type);
                // エラーや警告以外は数秒後に消す (任意)
                // if (type !== 'error' && type !== 'warning') {
                //     setTimeout(() => hideStatus(globalStatus), 5000);
                // }
            }


            // HTMLエスケープ関数
            function htmlEscape(str) {
                if (!str) return '';
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }

        });
    </script>

</body>

</html>