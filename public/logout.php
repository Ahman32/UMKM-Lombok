<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

if (is_post()) {
    verify_csrf();
    Auth::logout(true);
} else {
    Auth::logout(false);
}

redirect('/login.php');
