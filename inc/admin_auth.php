<?php
require_once __DIR__ . '/auth.php';
auth_start_session();
auth_require_role(['super_admin'], APP_URL . '/login');
