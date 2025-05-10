<?php
// views/new-releases.php

// Ensure variables are defined to avoid errors if not set by controller
$pageTitle = $viewData['pageTitle'] ?? '新刊発売日カレンダー';
$selectedYear = $viewData['selectedYear'] ?? null;
$selectedMonth = $viewData['selectedMonth'] ?? null;
$calendarData = $viewData['calendarData'] ?? [];
$availableYears = $viewData['availableYears'] ?? range(date('Y') + 1, date('Y') - 5);
$availableMonths = $viewData['availableMonths'] ?? range(1, 12);

// Helper function for escaping HTML
function html_escape($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo html_escape($pageTitle); ?> - 漫画管理</title>
    <style>
        /* Basic Reset and Global Styles (WCAG Friendly) */
        :root {
            --text-color: #1a1a1a; /* WCAG AAA for normal text on white */
            --background-color: #ffffff;
            --primary-color: #005fcc; /* Example Blue (WCAG AA on white for text, AAA for large) */
            --primary-text-color: #ffffff; /* Text on primary color */
            --border-color: #cccccc;
            --focus-outline-color: #ffbf47; /* Bright color for focus */
            --error-color: #d93025;
            --error-background-color: #fbe9e7;
        }
        body, h1, h2, h3, p, ul, ol, li, table, th, td, form, label, select, button {
            margin: 0;
            padding: 0;
            border: 0;
            font-size: 100%;
            font: inherit;
            vertical-align: baseline;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            font-size: 1rem; /* Base font size for scalability */
            line-height: 1.6;
            padding: 20px;
        }
        .container {
            max-width: 960px;
            margin: 0 auto;
            padding: 0 15px;
        }

        /* Header (Simplified, adapt from top.php if needed for full header) */
        .page-header {
            background-color: #f0f0f0;
            padding: 15px 0;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--border-color);
        }
        .page-header h1 {
            font-size: 1.8em; /* Relative unit */
            color: var(--text-color);
            text-align: center;
            font-weight: bold;
        }
        .page-header nav {
            text-align: center;
            margin-top: 10px;
        }
        .page-header nav a {
            color: var(--primary-color);
            text-decoration: none;
            padding: 5px 10px;
            font-weight: bold;
        }
        .page-header nav a:hover,
        .page-header nav a:focus {
            text-decoration: underline;
            outline: 2px solid var(--focus-outline-color);
        }

        /* Form elements for year/month selection */
        .controls-form {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f9f9f9;
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }
        .controls-form fieldset {
            border: none;
            padding: 0;
            margin: 0;
        }
        .controls-form legend {
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 15px;
            padding: 0; /* Remove default padding */
            color: var(--text-color);
        }
        .form-group {
            margin-bottom: 15px;
            display: flex; /* For alignment */
            align-items: center;
            gap: 10px; /* Space between label and select */
        }
        .form-group label {
            font-weight: bold;
            color: var(--text-color);
            min-width: 80px; /* Align selects */
            font-size: 1em;
        }
        .form-group select, .form-group button {
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 1em;
            background-color: var(--background-color);
            color: var(--text-color);
        }
        .form-group select:focus, .form-group button:focus {
            outline: 3px solid var(--focus-outline-color);
            border-color: var(--primary-color);
        }
        .form-group button {
            background-color: var(--primary-color);
            color: var(--primary-text-color);
            cursor: pointer;
            font-weight: bold;
        }
        .form-group button:hover {
            background-color: #004a9e; /* Darker shade of primary */
        }

        /* Calendar Display Area */
        .calendar-area {
            margin-top: 20px;
        }
        .calendar-area h2 {
            font-size: 1.5em;
            margin-bottom: 15px;
            color: var(--text-color);
            font-weight: bold;
        }
        .release-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .release-table th, .release-table td {
            border: 1px solid var(--border-color);
            padding: 10px 12px;
            text-align: left;
            font-size: 0.95em;
        }
        .release-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            color: var(--text-color);
        }
        .release-table td {
            background-color: var(--background-color);
        }
        .release-table tr:nth-child(even) td {
            background-color: #f9f9f9; /* Zebra striping */
        }

        .status-message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 1em;
            border: 1px solid transparent;
        }
        .status-message.error {
            color: var(--error-color);
            background-color: var(--error-background-color);
            border-color: var(--error-color);
        }
        .status-message.info {
            color: #004085; /* Dark blue for info */
            background-color: #cce5ff;
            border-color: #b8daff;
        }

        /* Accessibility */
        .sr-only { /* Screen reader only */
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

        /* Footer (Simplified, adapt from top.php if needed) */
        .page-footer {
            margin-top: 40px;
            padding: 20px 0;
            text-align: center;
            font-size: 0.9em;
            color: #555;
            border-top: 1px solid var(--border-color);
        }

        @media (max-width: 600px) {
            .form-group {
                flex-direction: column;
                align-items: flex-start;
            }
            .form-group label {
                margin-bottom: 5px;
            }
            .form-group select, .form-group button {
                width: 100%;
            }
            .release-table th, .release-table td {
                font-size: 0.85em;
                padding: 8px;
            }
            .release-table th:nth-child(3), .release-table td:nth-child(3) { /* Hide publisher on small screens for example */
                /* display: none; */
            }
        }

    </style>
</head>
<body>
    <header class="page-header">
        <div class="container">
            <h1><?php echo html_escape($pageTitle); ?></h1>
            <nav>
                <a href="/">トップへ戻る</a>
            </nav>
        </div>
    </header>

    <main class="container" role="main">
        <section aria-labelledby="calendar-controls-heading">
            <form method="GET" action="/new-releases" class="controls-form">
                <fieldset>
                    <legend id="calendar-controls-heading">表示する年月を選択</legend>
                    <div class="form-group">
                        <label for="year-select">年:</label>
                        <select name="year" id="year-select">
                            <?php foreach ($availableYears as $year): ?>
                                <option value="<?php echo html_escape($year); ?>" <?php echo ($year == $selectedYear) ? 'selected' : ''; ?>>
                                    <?php echo html_escape($year); ?>年
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="month-select">月:</label>
                        <select name="month" id="month-select">
                            <?php foreach ($availableMonths as $month): ?>
                                <option value="<?php echo html_escape($month); ?>" <?php echo ($month == $selectedMonth) ? 'selected' : ''; ?>>
                                    <?php echo html_escape($month); ?>月
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                         <label for="submit-button" class="sr-only">表示ボタン</label> {/* Invisible label for button if needed */}
                        <button type="submit" id="submit-button">表示</button>
                    </div>
                </fieldset>
            </form>
        </section>

        <section class="calendar-area" aria-labelledby="release-calendar-heading">
            <?php if ($selectedYear && $selectedMonth): ?>
                <h2 id="release-calendar-heading"><?php echo html_escape($selectedYear); ?>年<?php echo html_escape($selectedMonth); ?>月の新刊</h2>
                <?php if (!empty($calendarData) && !isset($calendarData[0]['error'])): ?>
                    <table class="release-table">
                        <caption><?php echo html_escape($selectedYear); ?>年<?php echo html_escape($selectedMonth); ?>月 新刊発売情報</caption>
                        <thead>
                            <tr>
                                <th scope="col">発売日</th>
                                <th scope="col">タイトル</th>
                                <th scope="col">著者</th>
                                <th scope="col">出版社</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($calendarData as $item): ?>
                                <tr>
                                    <td><?php echo html_escape($item['date'] ?? '未定'); ?></td>
                                    <td><?php echo html_escape($item['title'] ?? 'N/A'); ?></td>
                                    <td><?php echo html_escape($item['author'] ?? 'N/A'); ?></td>
                                    <td><?php echo html_escape($item['publisher'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php elseif (isset($calendarData[0]['error'])): ?>
                    <p class="status-message error"><?php echo html_escape($calendarData[0]['error']); ?></p>
                <?php else: ?>
                    <p class="status-message info">選択された年月の新刊情報は見つかりませんでした。</p>
                <?php endif; ?>
            <?php else: ?>
                <p class="status-message info">年月を選択して「表示」ボタンを押してください。</p>
            <?php endif; ?>
        </section>
    </main>

    <footer class="page-footer">
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> 漫画管理システム</p>
        </div>
    </footer>
</body>
</html>