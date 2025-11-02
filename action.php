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

$action = $_REQUEST['action'] ?? '';

$current = isset($_REQUEST['current_path']) ? safe_path($_REQUEST['current_path']) : '';
$abs_current = abs_path($current);

// Ensure current is a dir
if (!is_dir($abs_current)) {
    $current = '';
    $abs_current = abs_path('');
}

switch ($action) {
    case 'create_folder':
        $name = trim($_POST['folder_name'] ?? '');
        if ($name === '') { header('Location: index.php?path='.urlencode($current)); exit; }
        $target = $abs_current . DIRECTORY_SEPARATOR . basename($name);
        if (!file_exists($target)) mkdir($target, 0755, true);
        header('Location: index.php?path='.urlencode($current));
        break;

    case 'upload':
        if (!isset($_FILES['files'])) { header('Location: index.php?path='.urlencode($current)); exit; }
        foreach ($_FILES['files']['error'] as $idx => $err) {
            if ($err !== UPLOAD_ERR_OK) continue;
            $tmp = $_FILES['files']['tmp_name'][$idx];
            $name = basename($_FILES['files']['name'][$idx]);
            $dest = $abs_current . DIRECTORY_SEPARATOR . $name;
            // avoid overwrite by renaming if exists
            $i=1; $base=$name;
            while (file_exists($dest)) {
                $dest = $abs_current . DIRECTORY_SEPARATOR . pathinfo($base, PATHINFO_FILENAME) . "({$i})." . pathinfo($base, PATHINFO_EXTENSION);
                $i++;
            }
            move_uploaded_file($tmp, $dest);
        }
        header('Location: index.php?path='.urlencode($current));
        break;

    case 'rename':
        $item = $_POST['item'] ?? '';
        $new = trim($_POST['new_name'] ?? '');
        if ($item === '' || $new === '') { header('Location: index.php?path='.urlencode($current)); exit; }
        $src = abs_path($item);
        $dest = dirname($src) . DIRECTORY_SEPARATOR . basename($new);
        if (file_exists($src) && !file_exists($dest)) {
            rename($src, $dest);
        }
        header('Location: index.php?path='.urlencode($current));
        break;

    case 'delete':
        $items = $_POST['items'] ?? [];
        foreach ($items as $it) {
            $src = abs_path($it);
            if (!file_exists($src)) continue;
            if (is_dir($src)) rrmdir($src);
            else @unlink($src);
        }
        header('Location: index.php?path='.urlencode($current));
        break;

    case 'copy':
    case 'cut':
        $items = $_POST['items'] ?? [];
        // store in session
        $_SESSION['clipboard'] = ['op' => $action, 'items' => $items];
        header('Location: index.php?path='.urlencode($current));
        break;

    case 'paste':
        $cb = $_SESSION['clipboard'] ?? null;
        if (!$cb) { header('Location: index.php?path='.urlencode($current)); exit; }
        $destFolder = $abs_current;
        foreach ($cb['items'] as $it) {
            $src = abs_path($it);
            if (!file_exists($src)) continue;
            $name = basename($src);
            $dest = $destFolder . DIRECTORY_SEPARATOR . $name;
            $i=1; $base=$name;
            while (file_exists($dest)) {
                $ext = pathinfo($base, PATHINFO_EXTENSION);
                $fname = pathinfo($base, PATHINFO_FILENAME) . "($i)";
                $dest = $destFolder . DIRECTORY_SEPARATOR . ($ext ? $fname . '.' . $ext : $fname);
                $i++;
            }
            if ($cb['op'] === 'copy') {
                if (is_dir($src)) copy_dir($src, $dest);
                else copy($src, $dest);
            } else { // cut -> move
                rename($src, $dest);
            }
        }
        if ($cb['op'] === 'cut') {
            // clipboard cleared after move
            unset($_SESSION['clipboard']);
        }
        header('Location: index.php?path='.urlencode($current));
        break;

    case 'clear_clipboard':
        unset($_SESSION['clipboard']);
        header('Location: index.php?path='.urlencode($current));
        break;

    default:
        header('Location: index.php?path='.urlencode($current));
        break;
}

// helpers
function rrmdir($dir) {
    if (!is_dir($dir)) return;
    $items = array_diff(scandir($dir), ['.','..']);
    foreach ($items as $it) {
        $p = $dir . DIRECTORY_SEPARATOR . $it;
        if (is_dir($p)) rrmdir($p);
        else @unlink($p);
    }
    @rmdir($dir);
}

function copy_dir($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while(false !== ($file = readdir($dir))) {
        if (($file !== '.') && ($file !== '..')) {
            $srcPath = $src . DIRECTORY_SEPARATOR . $file;
            $dstPath = $dst . DIRECTORY_SEPARATOR . $file;
            if (is_dir($srcPath)) copy_dir($srcPath, $dstPath);
            else copy($srcPath, $dstPath);
        }
    }
    closedir($dir);
}
