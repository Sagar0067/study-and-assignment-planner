<?php
require_once 'db.php';
require_once 'functions.php';
header("Content-Type: application/json");

// Sanitize ALL incoming GET/POST parameters globally
$_GET = sanitize($_GET);
$action = $_GET['action'] ?? '';

// ==========================================
// SECURITY GATEWAY FOR DATA ENDPOINTS
// ==========================================
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit;
}
$user_id = $_SESSION['user_id'];

// ==========================================
// DATA ENDPOINTS (TASKS & SYLLABUS)
// ==========================================
if ($action === 'get_tasks') {
    // Join with syllabus so we get the topic title on each task
    $tasks = fetch_all($pdo,
        "SELECT a.*, s.title AS syllabus_title
         FROM assignments a
         LEFT JOIN syllabus s ON a.syllabus_id = s.id
         WHERE a.user_id = ?
         ORDER BY a.id DESC",
        [$user_id]
    );
    echo json_encode($tasks);
} 
elseif ($action === 'add_task') {
    $data        = sanitize(json_decode(file_get_contents('php://input'), true)); 
    $title       = $data['title']       ?? ''; 
    $deadline    = !empty($data['deadline'])    ? $data['deadline']    : null;
    $status      = !empty($data['status'])      ? $data['status']      : 'todo';
    $priority    = !empty($data['priority'])    ? $data['priority']    : 'medium';
    $syllabus_id = !empty($data['syllabus_id']) ? (int)$data['syllabus_id'] : null;
    $notes       = isset($data['notes'])        ? trim($data['notes']) : null;
    if ($notes === '') $notes = null;

    if ($title) {
        $id = insert_record($pdo,
            "INSERT INTO assignments (user_id, syllabus_id, title, notes, status, priority, deadline) VALUES (?, ?, ?, ?, ?, ?, ?)", 
            [$user_id, $syllabus_id, $title, $notes, $status, $priority, $deadline]
        );
        echo json_encode(["status" => "success", "id" => $id]);
    } else {
        echo json_encode(["status" => "error", "message" => "Title is required"]);
    }
} 
elseif ($action === 'update_task_status') {
    $data   = sanitize(json_decode(file_get_contents('php://input'), true));
    $id     = $data['id']     ?? null;
    $status = $data['status'] ?? null;

    if ($id && $status) {
        execute_query($pdo, "UPDATE assignments SET status = ? WHERE id = ? AND user_id = ?", [$status, $id, $user_id]);
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "ID and status are required"]);
    }
}
elseif ($action === 'delete_task') {
    $data = sanitize(json_decode(file_get_contents('php://input'), true));
    $id   = $data['id'] ?? null;

    if ($id) {
        execute_query($pdo, "DELETE FROM assignments WHERE id = ? AND user_id = ?", [$id, $user_id]);
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "ID is required"]);
    }
}
elseif ($action === 'update_task_notes') {
    // Save notes (and optionally title/priority/deadline) for an existing task
    $data     = sanitize(json_decode(file_get_contents('php://input'), true));
    $id       = $data['id']    ?? null;
    $notes    = isset($data['notes']) ? trim($data['notes']) : '';
    if ($notes === '') $notes = null;

    if ($id) {
        execute_query($pdo,
            "UPDATE assignments SET notes = ? WHERE id = ? AND user_id = ?",
            [$notes, $id, $user_id]
        );
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "ID is required"]);
    }
}
elseif ($action === 'get_syllabus') {
    $syllabus = fetch_all($pdo, "SELECT * FROM syllabus WHERE user_id = ?", [$user_id]);
    echo json_encode($syllabus);
}
elseif ($action === 'add_syllabus') {
    $data  = sanitize(json_decode(file_get_contents('php://input'), true));
    $title = $data['title'] ?? '';
    
    if ($title) {
        $id = insert_record($pdo, "INSERT INTO syllabus (user_id, title, completed) VALUES (?, ?, 0)", [$user_id, $title]);
        echo json_encode(["status" => "success", "id" => $id]);
    } else {
        echo json_encode(["status" => "error", "message" => "Title is required"]);
    }
}
elseif ($action === 'toggle_syllabus') {
    $data      = sanitize(json_decode(file_get_contents('php://input'), true));
    $id        = $data['id']        ?? null;
    $completed = $data['completed'] ?? 0;

    if ($id !== null) {
        execute_query($pdo, "UPDATE syllabus SET completed = ? WHERE id = ? AND user_id = ?", [$completed, $id, $user_id]);
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "ID and status are required"]);
    }
}
elseif ($action === 'delete_syllabus') {
    $data = sanitize(json_decode(file_get_contents('php://input'), true));
    $id   = $data['id'] ?? null;

    if ($id) {
        execute_query($pdo, "DELETE FROM syllabus WHERE id = ? AND user_id = ?", [$id, $user_id]);
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "ID is required"]);
    }
}
elseif ($action === 'reset_data') {
    // Highly destructive: Wiping both schemas cleanly using identical IDs
    try {
        $pdo->beginTransaction();
        execute_query($pdo, "DELETE FROM assignments WHERE user_id = ?", [$user_id]);
        execute_query($pdo, "DELETE FROM syllabus WHERE user_id = ?",    [$user_id]);
        $pdo->commit();
        echo json_encode(["status" => "success"]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(["status" => "error", "message" => "Critical Failure: Could not purge databases"]);
    }
}
else {
    echo json_encode(["status" => "error", "message" => "Invalid action"]);
}
?>
