<?php
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Fallback for testing if login isn't ready
    $sender_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; 
    
    $receiver_id = intval($_POST['receiver_id']);
    $message = trim($_POST['message']);

    // Only insert if the message isn't blank and receiver is valid
    if (!empty($message) && $receiver_id > 0) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $sender_id, $receiver_id, $message);
        
        if ($stmt->execute()) {
            // Success
            $stmt->close();
        } else {
            // Log error if needed: error_log($stmt->error);
        }
    }
    
    // Redirect back to the conversation thread immediately
    header("Location: messages.php?user=" . $receiver_id);
    exit();
} else {
    // If someone tries to access this file directly without posting a form
    header("Location: index.php");
    exit();
}
?>