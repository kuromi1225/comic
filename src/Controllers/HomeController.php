<?php

declare(strict_types=1);

namespace App\Controllers;

class HomeController
{
    public function index(): void
    {
        // Check if user is logged in (basic check)
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        // Render the top page view
        require_once __DIR__ . '/../../views/top.php';
    }
}
