<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>漫画一覧</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fa;
            color: #212529;
            font-size: 1.1rem;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #ffffff;
            padding: 15px 0;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 40px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        header h1 {
            font-size: 1.8rem;
            color: #343a40;
            margin: 0;
            text-align: center;
        }

        header form {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }

        header form input[type="text"] {
            padding: 10px;
            font-size: 1rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            margin-right: 10px;
            width: 300px;
        }

        header form button {
            background: #007bff;
            color: #ffffff;
            font-size: 1rem;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }

        header form button:hover {
            background: #0056b3;
        }

        header a.button {
            display: inline-block;
            background: #28a745;
            color: #ffffff;
            font-size: 1rem;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            margin: 0 10px;
            transition: background 0.2s;
        }

        header a.button:hover {
            background: #218838;
        }

        main {
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th, table td {
            border: 1px solid #dee2e6;
            padding: 10px;
            text-align: left;
        }

        table th {
            background-color: #f1f1f1;
            font-weight: bold;
        }

        table img {
            max-width: 50px;
            height: auto;
        }

        footer {
            text-align: center;
            padding: 20px 0;
            background: #ffffff;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0,0,0,0);
            white-space: nowrap;
            border: 0;
        }
    </style>
</head>
<body>
    <header>
        <h1>漫画一覧</h1>
        <form method="GET" action="/comic_list.php">
            <label for="title" class="sr-only">タイトルで絞り込み</label>
            <input type="text" id="title" name="title" placeholder="タイトルで絞り込み" value="<?= htmlspecialchars($_GET['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">検索</button>
        </form>
        <a href="/comic_register" class="button">新しい漫画を登録</a>
        <a href="/" class="button">トップへ戻る</a>
    </header>

    <main>
        <?php if (isset($comics) && !empty($comics)): ?>
            <table>
                <thead>
                    <tr>
                        <th>タイトル</th>
                        <th>著者</th>
                        <th>出版社</th>
                        <th>総巻数</th>
                        <th>表紙画像</th>
                        <th>編集</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comics as $comic): ?>
                    <tr>
                        <td><?= htmlspecialchars($comic['title'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($comic['author'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($comic['publisher'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars((string)($comic['total_volumes'] ?? 'N/A')) ?></td>
                        <td>
                            <?php if (!empty($comic['cover_image'])): ?>
                                <img src="<?= htmlspecialchars($comic['cover_image']) ?>" alt="<?= htmlspecialchars($comic['title']) ?>の表紙">
                            <?php else: ?>
                                (画像なし)
                            <?php endif; ?>
                        </td>
                        <td><a href="/comic_edit.php?id=<?= htmlspecialchars((string)$comic['id']) ?>">編集</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif (isset($comics)): ?>
            <p>該当する漫画は見つかりませんでした。</p>
        <?php else: ?>
            <p>漫画情報を表示できませんでした。</p>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2025 漫画管理システム</p>
    </footer>
</body>
</html>