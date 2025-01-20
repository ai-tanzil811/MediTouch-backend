<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: reset_password.php");
        exit();
    }

    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    try {
        // Update the password in the database
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
        $stmt->execute();

        $_SESSION['message'] = "Password has been successfully reset.";
        header("Location: index.html");  // Redirect to login page
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "An error occurred. Please try again later.";
        header("Location: reset_password.php");
        exit();
    }
}
