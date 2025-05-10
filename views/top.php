<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>トップページ - 漫画管理</title>
    <style>
        body,
        h1,
        h2,
        p,
        ul {
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fa;
            color: #212529;
            font-size: 1.1rem;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 960px;
            margin: 0 auto;
            padding: 0 20px;
        }

        header {
            background-color: #ffffff;
            padding: 15px 0;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 40px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo img {
            height: 50px;
            width: auto;
            display: block;
        }

        .logout-btn {
            background: #dc3545;
            color: #fff;
            font-size: 1rem;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.2s, box-shadow 0.2s;
            outline: 3px solid transparent;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .logout-btn:focus,
        .logout-btn:hover {
            background: #c82333;
            outline: 3px solid #ffc107;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .main-actions {
            display: flex;
            flex-direction: row;
            gap: 30px;
            justify-content: center;
            margin: 48px 0 32px 0;
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #007bff;
            border: none;
            border-radius: 12px;
            padding: 30px 35px;
            font-size: 1.3rem;
            font-weight: bold;
            color: #ffffff;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
            transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
            min-width: 200px;
            min-height: 160px;
            outline: 3px solid transparent;
            text-align: center;
        }

        .action-btn:focus,
        .action-btn:hover {
            background: #0056b3;
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 86, 179, 0.4);
            outline: 3px solid #ffc107;
            color: #ffffff;
        }

        .action-btn svg {
            margin-bottom: 15px;
            width: 40px;
            height: 40px;
            fill: #ffffff;
        }

        main h2 {
            font-size: 1.8rem;
            color: #343a40;
            margin-bottom: 20px;
            font-weight: 600;
        }

        main p {
            margin-bottom: 16px;
            font-size: 1.05rem;
            color: #495057;
        }

        footer {
            margin-top: 60px;
            padding: 20px 0;
            text-align: center;
            font-size: 0.9rem;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
            background: #ffffff;
        }

        .sr-only {
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

        @media (max-width: 768px) {
            .logo img {
                height: 40px;
            }

            .logout-btn {
                font-size: 0.9rem;
                padding: 8px 16px;
            }

            .main-actions {
                flex-direction: column;
                gap: 20px;
            }

            .action-btn {
                min-width: 0;
                width: 100%;
                padding: 25px 0;
                font-size: 1.2rem;
                min-height: auto;
            }

            main h2 {
                font-size: 1.6rem;
            }

            main p {
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <div class="logo">
                <a href="/" aria-label="トップページへ">
                    <img src="./banner.png" alt="漫画管理システム バナー">
                </a>
            </div>
            <form action="/logout" method="post" style="margin:0;">
                <button type="submit" class="logout-btn" aria-label="ログアウト">ログアウト</button>
            </form>
        </div>
    </header>

    <main class="container" role="main">
        <nav class="main-actions" aria-label="メイン機能">
            <a href="/comic_list" class="action-btn" aria-label="漫画一覧へ移動">
                漫画一覧
            </a>
            <a href="/comic_register" class="action-btn" aria-label="新しい漫画を登録へ移動">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor"
                    class="bi bi-journal-plus" viewBox="0 0 16 16" aria-hidden="true">
                    <path fill-rule="evenodd"
                        d="M8 5.5a.5.5 0 0 1 .5.5v1.5H10a.5.5 0 0 1 0 1H8.5V10a.5.5 0 0 1-1 0V8.5H6a.5.5 0 0 1 0-1h1.5V6a.5.5 0 0 1 .5-.5" />
                    <path
                        d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2" />
                    <path
                        d="M1 5v-.5a.5.5 0 0 1 1 0V5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1zm0 3v-.5a.5.5 0 0 1 1 0V8h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1zm0 3v-.5a.5.5 0 0 1 1 0v.5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1z" />
                </svg>
                漫画登録
            </a>
            <a href="/new-releases" class="action-btn" aria-label="新刊発売日確認へ移動">
                 <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="bi bi-calendar-month" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M2.56 11.332L3.43 10h1.142L3.43 11.332zM3.43 12.417l-.87-1.085h1.74l-.87 1.085zM5.66 10H4.528l.87-1.332L4.528 10zm-.87 1.332L5.66 10h1.142L5.66 11.332zm1.983-.666h1.142L7.073 10l.87 1.332.87-1.332h1.141l-.87 1.332.87 1.085h-1.142l-.87-1.085-.87 1.085H6.793l.87-1.085zM11 10.5a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1 0-1h1a.5.5 0 0 1 .5.5m-2.5.5a.5.5 0 0 1 0-1h1a.5.5 0 0 1 0 1z"/>
                    <path d="M4 .5a.5.5 0 0 0-1 0V1H2a2 2 0 0 0-2 2v1h16V3a2 2 0 0 0-2-2h-1V.5a.5.5 0 0 0-1 0V1H4zM16 14V5H0v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2M8.5 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zM7 9h2v2H7zm4 0h2v2h-2z"/>
                </svg>
                新刊発売日
            </a>
            </nav>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> 漫画管理システム</p>
        </div>
    </footer>
</body>

</html>