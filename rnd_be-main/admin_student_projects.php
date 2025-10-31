<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include('inc/db.php');

if (mysqli_connect_error()) {
    echo json_encode(["success" => false, "message" => "Database connection error: " . mysqli_connect_error()]);
    exit();
}

$title = mysqli_real_escape_string($conn, $_POST['title'] ?? '');
$description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
$student = mysqli_real_escape_string($conn, $_POST['student'] ?? '');
$link = mysqli_real_escape_string($conn, $_POST['link'] ?? '');
$category = mysqli_real_escape_string($conn, $_POST['category'] ?? '');

$targetDir = "adminprojectuploads/";
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$documentPath = NULL;
$allowedImageTypes = ["jpg", "jpeg", "png"];
$allowedVideoTypes = ["mp4", "webm", "ogg"];
$allowedDocumentTypes = ["pdf", "doc", "docx"];
$errors = [];
$uploadOk = true;

// Handle document (unchanged)
if (isset($_FILES["document"]) && $_FILES["document"]["error"] === UPLOAD_ERR_OK) {
    $documentFile = $_FILES["document"];
    $documentFileType = strtolower(pathinfo($documentFile["name"], PATHINFO_EXTENSION));
    if (!in_array($documentFileType, $allowedDocumentTypes)) {
        $errors[] = "Only PDF, DOC, and DOCX document files are allowed.";
        $uploadOk = false;
    }
    if ($documentFile["size"] > 20000000) {
        $errors[] = "Document file size exceeds the allowed limit (20MB).";
        $uploadOk = false;
    }
    $documentPath = $targetDir . uniqid('doc_') . '.' . $documentFileType;
    if (!move_uploaded_file($documentFile["tmp_name"], $documentPath)) {
        $errors[] = "Error uploading document file.";
        $uploadOk = false;
    }
}

// Insert main project record FIRST (to get ID for media)
$sql = "INSERT INTO student_projects (title, description, link, student, category, document) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssssss", $title, $description, $link, $student, $category, $documentPath);

if (!mysqli_stmt_execute($stmt)) {
    echo json_encode(["success" => false, "message" => "Failed to create project record: " . mysqli_error($conn)]);
    exit();
}

$projectId = mysqli_insert_id($conn); // Get new project ID

// Now handle multiple media files
$mediaFiles = [];
if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
    $fileCount = count($_FILES['images']['name']);
    for ($i = 0; $i < $fileCount; $i++) {
        if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;

        $tmpName = $_FILES['images']['tmp_name'][$i];
        $fileName = $_FILES['images']['name'][$i];
        $fileSize = $_FILES['images']['size'][$i];
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $isImage = in_array($fileType, $allowedImageTypes);
        $isVideo = in_array($fileType, $allowedVideoTypes);

        if (!$isImage && !$isVideo) {
            $errors[] = "File '$fileName' is not an allowed image or video type.";
            $uploadOk = false;
            continue;
        }

        if ($fileSize > 10000000) { // 10MB limit for media
            $errors[] = "File '$fileName' exceeds size limit (10MB).";
            $uploadOk = false;
            continue;
        }

        $newFileName = uniqid() . '.' . $fileType;
        $filePath = $targetDir . $newFileName;

        if (!move_uploaded_file($tmpName, $filePath)) {
            $errors[] = "Failed to upload file: $fileName";
            $uploadOk = false;
            continue;
        }

        $mediaType = $isImage ? 'image' : 'video';
        $mediaFiles[] = [
            'path' => $filePath,
            'type' => $mediaType,
            'order' => $i
        ];
    }
}

// Insert media records
if ($uploadOk && !empty($mediaFiles)) {
    $mediaStmt = mysqli_prepare($conn, "INSERT INTO project_media (project_id, file_path, file_type, order_index) VALUES (?, ?, ?, ?)");
    foreach ($mediaFiles as $media) {
        mysqli_stmt_bind_param($mediaStmt, "issi", $projectId, $media['path'], $media['type'], $media['order']);
        if (!mysqli_stmt_execute($mediaStmt)) {
            $errors[] = "Failed to save media record: " . mysqli_error($conn);
            $uploadOk = false;
        }
    }
    mysqli_stmt_close($mediaStmt);
}

if ($uploadOk) {
    echo json_encode(["success" => true, "message" => "Project uploaded successfully!"]);
} else {
    if (empty($errors)) $errors[] = "Unknown error occurred.";
    echo json_encode(["success" => false, "message" => implode(" | ", $errors)]);
}

mysqli_stmt_close($stmt);
$conn->close();
?>