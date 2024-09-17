<?php
session_start();
include 'config/db.php';

if (isset($_SESSION['user_id']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $contact_username = $_POST['contact_username'];

    $stmt = $koneksi->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $contact_username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($contact_id);
    if ($stmt->num_rows === 1) {
        $stmt->fetch();

        if ($contact_id == $user_id) {
            echo "You can't add yourself as a contact";
            exit();
        }

        if ($stmt->num_rows > 0) {
            $stmt = $koneksi->prepare("SELECT * FROM contacts WHERE user_id = ? AND contact_id = ?");
            $stmt->bind_param("ii", $user_id, $contact_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                echo "Contact already added";
                exit();
            }
        }

        $stmt = $koneksi->prepare("INSERT IGNORE INTO contacts (user_id, contact_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $contact_id);
        $stmt->execute();
        echo "Contact added";
    } else {
        echo "Contact not found";
    }
}
