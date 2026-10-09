<?php
// ============================================================
// University Alumni Network - Authentication & Session Helper
// ============================================================
require_once __DIR__ . '/../config/db.php';

// Auth guard functions are defined in config/db.php and exposed globally:
// - is_logged_in()
// - current_user_id()
// - is_admin()
// - require_login()
// - require_admin()
// - generate_csrf_token()
// - csrf_field()
// - verify_csrf_token()
