<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン - 漫画書籍管理システム</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6200ea;
            --primary-light: #7c4dff;
            --primary-dark: #4a148c;
            --accent: #ff4081;
            --text: #3c3c3c;
            --text-light: #757575;
            --background: #f9f9f9;
            --card-bg: #ffffff;
            --error: #e53935;
            --success: #43a047;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Noto Sans JP', sans-serif;
            background: var(--background);
            background-image: linear-gradient(135deg, rgba(98, 0, 234, 0.05), rgba(255, 64, 129, 0.05));
            color: var(--text);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: "";
            position: absolute;
            width: 200%;
            height: 200%;
            top: -50%;
            left: -50%;
            z-index: -1;
            background: url('https://source.unsplash.com/random/1920x1080/?manga,comic,library') center center no-repeat;
            background-size: cover;
            filter: blur(8px) brightness(0.3);
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            position: relative;
            z-index: 1;
            animation: fadeIn 0.6s ease-out;
        }
        
        .login-header {
            background: var(--primary);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .login-header p {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .login-form {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 24px;
            position: relative;
        }
        
        .form-group label {
            font-size: 14px;
            color: var(--text-light);
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.3s;
            color: var(--text);
            background: rgba(255, 255, 255, 0.9);
        }
        
        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(124, 77, 255, 0.2);
            outline: none;
        }
        
        .form-group .input-icon {
            position: absolute;
            right: 12px;
            top: 38px;
            color: var(--text-light);
        }
        
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            font-size: 14px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .remember-me input {
            margin-right: 8px;
        }
        
        .forgot-password {
            color: var(--primary);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .forgot-password:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 6px rgba(98, 0, 234, 0.2);
        }
        
        .btn:hover {
            background: var(--primary-dark);
            box-shadow: 0 6px 10px rgba(98, 0, 234, 0.3);
            transform: translateY(-2px);
        }
        
        .btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(98, 0, 234, 0.2);
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: var(--text-light);
        }
        
        .register-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .register-link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* エラーメッセージ用スタイル */
        .error-message {
            color: var(--error);
            font-size: 14px;
            margin-top: 4px;
            display: none;
        }

        /* サーバーサイドエラーメッセージ用スタイル */
        .server-error-message {
            background-color: rgba(229, 57, 53, 0.1); /* var(--error) with alpha */
            color: var(--error);
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            border: 1px solid rgba(229, 57, 53, 0.3);
            text-align: center;
            font-size: 14px;
            font-weight: 500;
        }
        
        /* レスポンシブ対応 */
        @media (max-width: 480px) {
            .login-container {
                max-width: 95%;
                margin: 0 10px;
            }
            
            .login-header {
                padding: 20px;
            }
            
            .login-form {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>漫画書籍管理システム</h1>
            <p>アカウントにログインして、あなたの漫画コレクションを管理しましょう</p>
        </div>
        
        <div class="login-form-wrapper" style="padding: 30px;">
            <?php
            // セッションにエラーメッセージがあれば表示
            if (isset($_SESSION['login_error'])) {
                echo '<div class="server-error-message">' . htmlspecialchars($_SESSION['login_error'], ENT_QUOTES, 'UTF-8') . '</div>';
                // 一度表示したらセッションから削除する（リロードで消えるように）
                unset($_SESSION['login_error']);
            }
            ?>

            <form class="login-form" action="/login" method="POST" style="padding: 0;">
                <div class="form-group">
                    <label for="username">ユーザー名</label>
                    <input type="text" id="username" name="username" placeholder="ユーザー名を入力" required>
                    <div class="error-message" id="username-error">ユーザー名を入力してください</div>
                </div>
                
                <div class="form-group">
                    <label for="password">パスワード</label>
                    <input type="password" id="password" name="password" placeholder="パスワードを入力" required>
                    <div class="error-message" id="password-error">パスワードを入力してください</div>
                </div>
                
                <div class="remember-forgot">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" id="remember">
                        ログイン状態を保持する
                    </label>
                    <a href="#" class="forgot-password">パスワードをお忘れですか？</a>
                </div>
                
                <button type="submit" class="btn">ログイン</button>
                
                <div class="register-link">
                    アカウントをお持ちでないですか？ <a href="#">新規登録</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.login-form');
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            const usernameError = document.getElementById('username-error');
            const passwordError = document.getElementById('password-error');
            
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                // ユーザー名の検証
                if (usernameInput.value.trim() === '') {
                    usernameError.style.display = 'block';
                    usernameInput.style.borderColor = 'var(--error)';
                    isValid = false;
                } else {
                    usernameError.style.display = 'none';
                    usernameInput.style.borderColor = '#ddd';
                }
                
                // パスワードの検証
                if (passwordInput.value.trim() === '') {
                    passwordError.style.display = 'block';
                    passwordInput.style.borderColor = 'var(--error)';
                    isValid = false;
                } else {
                    passwordError.style.display = 'none';
                    passwordInput.style.borderColor = '#ddd';
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
            
            // 入力中にエラー表示を消す
            usernameInput.addEventListener('input', function() {
                if (usernameInput.value.trim() !== '') {
                    usernameError.style.display = 'none';
                    usernameInput.style.borderColor = '#ddd';
                }
            });
            
            passwordInput.addEventListener('input', function() {
                if (passwordInput.value.trim() !== '') {
                    passwordError.style.display = 'none';
                    passwordInput.style.borderColor = '#ddd';
                }
            });
        });
    </script>
</body>
</html>
