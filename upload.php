<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Files</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 500px;
            margin: 50px auto;
            background: #f4f6f8;
            text-align: center;
        }
        .upload-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        input[type="file"] {
            margin: 10px 0;
        }
        button {
            background: #007bff;
            border: none;
            padding: 8px 14px;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background: #0056b3;
        }
        .back-btn {
            display: inline-block;
            margin-top: 15px;
            color: #007bff;
            text-decoration: none;
        }
        /* Progress bar */
        .progress-container {
            width: 100%;
            background: #ddd;
            border-radius: 10px;
            margin-top: 15px;
            display: none;
        }
        .progress-bar {
            height: 20px;
            width: 0;
            background: #28a745;
            border-radius: 10px;
            text-align: center;
            color: white;
            font-size: 12px;
        }
    </style>
</head>
<body>

<div class="upload-box">
    <h2>📤 Upload Files</h2>
    <form id="uploadForm" enctype="multipart/form-data">
        <input type="file" name="files[]" multiple required><br>
        <button type="submit">Upload</button>
    </form>
    
    <div class="progress-container">
        <div class="progress-bar" id="progressBar">0%</div>
    </div>

    <a class="back-btn" href="index.php">⬅ Back to File List</a>
</div>

<script>
document.getElementById("uploadForm").addEventListener("submit", function(e){
    e.preventDefault();

    let formData = new FormData(this);
    let xhr = new XMLHttpRequest();
    let progressContainer = document.querySelector(".progress-container");
    let progressBar = document.getElementById("progressBar");

    progressContainer.style.display = "block";

    xhr.upload.addEventListener("progress", function(e) {
        if (e.lengthComputable) {
            let percent = Math.round((e.loaded / e.total) * 100);
            progressBar.style.width = percent + "%";
            progressBar.textContent = percent + "%";
        }
    });

    xhr.onload = function() {
        if (xhr.status === 200) {
            progressBar.style.background = "#007bff";
            progressBar.textContent = "✅ Upload Complete";
            setTimeout(() => { window.location.href = "index.php"; }, 1000);
        } else {
            progressBar.style.background = "#dc3545";
            progressBar.textContent = "❌ Upload Failed";
        }
    };

    xhr.open("POST", "upload_action.php", true);
    xhr.send(formData);
});
</script>

</body>
</html>
