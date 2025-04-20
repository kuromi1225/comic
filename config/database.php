<?php

declare(strict_types=1);

// Read database configuration from environment variables
return [
    'host' => getenv('MYSQL_HOST') ?: 'db', // Default to service name if not set
    'dbname' => getenv('MYSQL_DATABASE') ?: '',
    'user' => getenv('MYSQL_USER') ?: '',
    'password' => getenv('MYSQL_PASSWORD') ?: '',
];
