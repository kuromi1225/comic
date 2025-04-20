<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database; // Databaseクラスをインポート
use PDO; // PDOクラスをインポート

class AuthController
{
    public function showLoginForm(): void
    {
        // Render the login form view
        require_once __DIR__ . '/../../views/login.php';
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            // 簡単な入力チェック (本来はもっと厳密に)
            if (empty($username) || empty($password)) {
                // エラーメッセージを設定してログインフォームに戻す
                $_SESSION['login_error'] = 'ユーザー名またはパスワードを入力してください。';
                header('Location: /login');
                exit;
            }

            try {
                $db = Database::getConnection(); // データベース接続を取得

                // ユーザー名でユーザーを検索
                $stmt = $db->prepare("SELECT id, username, password FROM users WHERE username = :username LIMIT 1");
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // ユーザーが見つかり、かつパスワードが一致するか確認
                if ($user && password_verify($password, $user['password'])) {
                    // パスワードが一致した場合
                    session_regenerate_id(true); // セッションIDを再生成 (セキュリティ対策)
                    $_SESSION['user_id'] = $user['id']; // 実際のユーザーIDをセッションに保存
                    $_SESSION['username'] = $user['username']; // ユーザー名も保存しておくと便利
                    unset($_SESSION['login_error']); // エラーメッセージをクリア
                    header('Location: /'); // トップページへリダイレクト
                    exit;
                } else {
                    // ユーザーが見つからない、またはパスワードが不一致の場合
                    // エラーメッセージを設定してログインフォームに戻す
                    $_SESSION['login_error'] = 'ユーザー名またはパスワードが正しくありません。';
                    error_log("Login failed for user: " . $username); // ログイン失敗をログに残す
                    header('Location: /login');
                    exit;
                }

            } catch (\PDOException $e) {
                // データベースエラー処理
                error_log("Database error during login: " . $e->getMessage());
                // エラーメッセージを設定してログインフォームに戻す
                $_SESSION['login_error'] = 'ログイン処理中にエラーが発生しました。';
                header('Location: /login');
                exit;
            }

        } else {
            // POST以外のリクエストの場合はログインフォームを表示
            $this->showLoginForm();
        }
    }

    public function logout(): void
    {
        session_destroy();
        header('Location: /login');
        exit;
    }
}