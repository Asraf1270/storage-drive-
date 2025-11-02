<?php
$uploadDir = "uploads/";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['files'])) {
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $totalFiles = count($_FILES['files']['name']);

        for ($i = 0; $i < $totalFiles; $i++) {
            $fileName = basename($_FILES['files']['name'][$i]);
            $tmpName  = $_FILES['files']['tmp_name'][$i];
            $error    = $_FILES['files']['error'][$i];

            if ($error === UPLOAD_ERR_OK) {
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($tmpName, $targetPath)) {
                    // File uploaded successfully
                }
            }
        }

        // After all uploads, go back to index
        header("Location: index.php");
        exit;
    } else {
        die("Error: No files uploaded.");
    }
}
