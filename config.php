<?php
// config.php
// Adjust this to your real storage root (relative to this file)
define('STORAGE_ROOT', __DIR__ . '/storage'); // make sure this folder exists and is writable

// Optional: allowed upload mime types/extensions (empty = allow all)
$ALLOWED_EXT = ['jpg','jpeg','png','gif','pdf','zip','txt','doc','docx','xls','xlsx','mp4','mp3'];

session_start();
