<?php
require_once 'config.php';

function safe_path($rel) {
    $rel = trim($rel);
    $rel = str_replace(['..', "\0"], '', $rel);
    $rel = trim($rel, '/\\');
    return $rel;
}
function abs_path($rel) {
    $safe = safe_path($rel);
    return rtrim(STORAGE_ROOT, '/\\') . ($safe === '' ? '' : DIRECTORY_SEPARATOR . $safe);
}

$file = isset($_GET['file']) ? safe_path($_GET['file']) : '';
$abs = abs_path($file);
if (!is_file($abs)) { header("HTTP/1.1 404 Not Found"); echo "File not found."; exit; }

$basename = basename($abs);
$size = filesize($abs);
$mime = mime_content_type($abs) ?: 'application/octet-stream';

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="'.rawurlencode($basename).'"');
header('Content-Length: ' . $size);
readfile($abs);
exit;
