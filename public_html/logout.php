<?php
require_once __DIR__ . '/config/db_config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
start_secure_session();
logout_user();
redirect('login');
