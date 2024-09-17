<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['contact_id']) && isset($_POST['message'])) {
        $contact_id = $_POST['contact_id'];
        $message = $_POST['message'];

        // Validate contact id
        $stmt = $koneksi->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->bind_param("i", $contact_id);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($contact_username);
        if ($stmt->num_rows === 1) {
            $stmt->fetch();

            $stmt = $koneksi->prepare("INSERT INTO messages (user_id, contact_id, message) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $user_id, $contact_id, $message);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Message sent']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to send message']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid contact']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    }
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['contact_id'])) {
    $contact_id = $_GET['contact_id'];

    $stmt = $koneksi->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $contact_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($contact_username);
    if ($stmt->num_rows === 1) {
        $stmt->fetch();

        $stmt = $koneksi->prepare("SELECT m.message, u.username, m.timestamp FROM messages m JOIN users u ON u.id = m.user_id WHERE (m.user_id = ? AND m.contact_id = ?) OR (m.user_id = ? AND m.contact_id = ?) ORDER BY m.timestamp ASC");
        $stmt->bind_param("iiii", $user_id, $contact_id, $contact_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $chat = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($chat);
    } else {
        echo json_encode([]);
    }
    exit();
}
