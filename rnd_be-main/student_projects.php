<?php
// ✅ CORS HEADERS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

// ✅ Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include('inc/db.php');

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    // ===========================================================
    // 🟢 GET REQUEST — Fetch Projects
    // ===========================================================
    case 'GET':
        $category = isset($_GET['category']) ? trim($_GET['category']) : '';

        if ($category !== '') {
            $stmt = $conn->prepare("
                SELECT sp.* 
                FROM student_projects sp 
                WHERE sp.category LIKE ? 
                ORDER BY sp.id DESC
            ");
            $likeCategory = "%" . $category . "%";
            $stmt->bind_param("s", $likeCategory);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query("SELECT * FROM student_projects ORDER BY id DESC");
        }

        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn->error]);
            exit();
        }

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $row['archive'] = isset($row['archive']) ? (int)$row['archive'] : 0;

            if (!empty($row['image']) && !preg_match('/^https?:\/\//i', $row['image'])) {
                $row['image'] = $row['image'];
            }
            if (!empty($row['document']) && !preg_match('/^https?:\/\//i', $row['document'])) {
                $row['document'] = $row['document'];
            }

            // Fetch media
            $media = [];
            if (isset($row['id'])) {
                $mediaStmt = $conn->prepare("
                    SELECT file_path, file_type 
                    FROM project_media 
                    WHERE project_id = ? 
                    ORDER BY order_index ASC
                ");
                $mediaStmt->bind_param("i", $row['id']);
                $mediaStmt->execute();
                $mediaResult = $mediaStmt->get_result();

                while ($mediaRow = $mediaResult->fetch_assoc()) {
                    $filePath = $mediaRow['file_path'];
                    if (!preg_match('/^https?:\/\//i', $filePath)) {
                        $filePath = $filePath;
                    }
                    $media[] = [
                        'url' => $filePath,
                        'type' => $mediaRow['file_type']
                    ];
                }
                $mediaStmt->close();
            }

            $row['media'] = $media;
            $data[] = $row;
        }

        echo json_encode(['success' => true, 'data' => $data]);
        break;


    // ===========================================================
    // 🟡 PUT REQUEST — Archive Toggle or JSON Update
    // ===========================================================
    case 'PUT':
        $contentType = $_SERVER["CONTENT_TYPE"] ?? '';

        if (strpos($contentType, 'application/json') !== false) {
            $input = json_decode(file_get_contents("php://input"), true);

            if (isset($input['id']) && isset($input['archive'])) {
                $id = intval($input['id']);
                $archive = intval($input['archive']);
                $stmt = $conn->prepare("UPDATE student_projects SET archive = ? WHERE id = ?");
                $stmt->bind_param("ii", $archive, $id);
                $success = $stmt->execute();
                $stmt->close();
                echo json_encode(['success' => $success, 'message' => $success ? 'Archive status updated' : 'Failed to update archive']);
                exit();
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing required fields for update']);
                exit();
            }
        }
        break;


    // ===========================================================
    // 🟠 POST REQUEST — Handle Edit (FormData Upload)
    // ===========================================================
    case 'POST':
        // Used for editing projects (with image/document upload)
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Missing project ID']);
            exit();
        }

        $title = $_POST['title'] ?? '';
        $student = $_POST['student'] ?? '';
        $category = $_POST['category'] ?? '';
        $link = $_POST['link'] ?? '';

        // Get existing record to keep old files
        $stmt = $conn->prepare("SELECT image, document FROM student_projects WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $old = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $oldImage = $old['image'] ?? '';
        $oldDocument = $old['document'] ?? '';

        // Handle file uploads
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $imagePath = $oldImage;
        $documentPath = $oldDocument;

        if (!empty($_FILES['image']['name'])) {
            $imagePath = $uploadDir . uniqid() . "_" . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
        }

        if (!empty($_FILES['document']['name'])) {
            $documentPath = $uploadDir . uniqid() . "_" . basename($_FILES['document']['name']);
            move_uploaded_file($_FILES['document']['tmp_name'], $documentPath);
        }

        $stmt = $conn->prepare("
            UPDATE student_projects 
            SET title = ?, student = ?, category = ?, link = ?, image = ?, document = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssssssi", $title, $student, $category, $link, $imagePath, $documentPath, $id);
        $success = $stmt->execute();
        $stmt->close();

        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Project updated successfully' : 'Failed to update project'
        ]);
        break;


    // ===========================================================
    // 🔴 DELETE REQUEST — Remove a Project
    // ===========================================================
    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = isset($input['id']) ? intval($input['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Missing project ID']);
            exit();
        }

        $conn->query("DELETE FROM project_media WHERE project_id = $id");
        $deleted = $conn->query("DELETE FROM student_projects WHERE id = $id");

        echo json_encode([
            'success' => $deleted,
            'message' => $deleted ? 'Project deleted successfully' : 'Failed to delete project'
        ]);
        break;


    // ===========================================================
    // ❌ Unsupported Methods
    // ===========================================================
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        break;
}

$conn->close();
?>
