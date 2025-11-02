<?php
$uploadDir = "uploads/";

if (isset($_GET['file'])) {
    $file = basename($_GET['file']);
    $filePath = $uploadDir . $file;

    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

header("Location: index.php");
exit;
