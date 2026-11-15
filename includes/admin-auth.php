
<?php

/**
 * Froshgah - Administrator Access Protection
 */

require_once __DIR__ . '/auth.php';

// Require an authenticated administrator.
requireAdmin();
