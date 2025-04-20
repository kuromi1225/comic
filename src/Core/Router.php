<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];
    private bool $debug = true; // デバッグモードを有効に

    public function addRoute(string $method, string $path, array $handler): void
    {
        $this->routes[$method][$path] = $handler;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $raw_path = $_SERVER['REQUEST_URI']; // 元のURIもログに出す
        $path = parse_url($raw_path, PHP_URL_PATH) ?? '/';

        error_log("Raw Request URI: " . $raw_path); // 追加
        error_log("Parsed Path: " . $path);       // 追加

        if ($this->debug) {
            error_log("リクエスト: {$method} {$path}");
            error_log("登録済みルート: " . print_r($this->routes, true));
        }

        if (isset($this->routes[$method][$path])) {
            [$controllerClass, $action] = $this->routes[$method][$path];

            if ($this->debug) {
                error_log("コントローラ: {$controllerClass}, アクション: {$action}");
            }

            if (class_exists($controllerClass)) {
                $controller = new $controllerClass();
                if (method_exists($controller, $action)) {
                    $controller->$action();
                    return;
                } else {
                    if ($this->debug) {
                        $this->showError("メソッドが見つかりません: {$controllerClass}::{$action}");
                        return;
                    }
                }
            } else {
                if ($this->debug) {
                    $this->showError("コントローラクラスが見つかりません: {$controllerClass}");
                    return;
                }
            }
        } else {
            if ($this->debug) {
                $this->showError("ルートが見つかりません: {$method} {$path}");
                return;
            }
        }

        // 本番環境用の404エラー
        if (!$this->debug) {
            http_response_code(404);
            echo "404 Not Found";
        }
    }

    private function showError(string $message): void
    {
        http_response_code(404);
        echo "<h1>404 Not Found</h1>";
        echo "<p>エラー内容: {$message}</p>";
        echo "<h2>登録済みルート:</h2>";
        echo "<pre>";
        print_r($this->routes);
        echo "</pre>";
    }
}

