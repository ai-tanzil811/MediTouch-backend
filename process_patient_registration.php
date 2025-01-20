<?php
// Include database connection
require_once 'db_connection.php'; 
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Start session
session_start();

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input data
    $username = $conn->real_escape_string(trim($_POST['username']));
    $password = $conn->real_escape_string(trim($_POST['password']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $medical_record = $conn->real_escape_string(trim($_POST['medical_record']));
    $hashed_password = password_hash($password, PASSWORD_BCRYPT); // Secure password hashing

    // Validate profile photo
    $photo = $_FILES['photo'];
    $upload_dir = 'uploads/';
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

    if ($photo['error'] === UPLOAD_ERR_OK && in_array($photo['type'], $allowed_types)) {
        $photo_name = uniqid('photo_') . '.' . pathinfo($photo['name'], PATHINFO_EXTENSION);
        $photo_path = $upload_dir . $photo_name;

        // Ensure uploads directory exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Move uploaded file to the uploads directory
        if (!move_uploaded_file($photo['tmp_name'], $photo_path)) {
            die("Error uploading profile photo.");
        }
    } else {
        die("Invalid profile photo. Please upload a valid image file.");
    }

    // Check if the email already exists
    $checkEmailQuery = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($checkEmailQuery);

    if ($result->num_rows > 0) {
        echo "Error: The email address '$email' is already registered. Please use a different email.";
        exit();
    }

    // Insert data into the `users` table
    $role = 'patient';
    $query = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$hashed_password', '$role')";

    if ($conn->query($query) === TRUE) {
        $user_id = $conn->insert_id; // Get the user ID of the newly created user

        // Insert data into the `patients` table
        $query = "INSERT INTO patients (user_id, name, contact_number, medical_history) 
                  VALUES ('$user_id', '$username', '$phone', '$medical_record')";

        if ($conn->query($query) === TRUE) {
            // Generate OTP
            $otp = rand(100000, 999999);

            // Store OTP and user details in the session
            $_SESSION['otp'] = $otp;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = 'patient';

            // Send OTP via email using PHPMailer
            $mail = new PHPMailer();
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'meditouchofficial@gmail.com'; // Replace with your email
                $mail->Password = 'gxpviklzyqfpurph'; // Replace with your email password or app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('meditouchofficial@gmail.com', 'MediTouch');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Your OTP Code';
                $mail->Body = "Dear $username,<br>Your OTP for MediTouch registration is: <strong>$otp</strong><br>Please enter this OTP to verify your email address.<br>Thank you,<br>MediTouch Team";

                $mail->send();

                // Redirect to OTP verification page
                header("Location: verify_patient_otp.php");
                exit();
            } catch (Exception $e) {
                echo "Error sending OTP email: {$mail->ErrorInfo}";
            }
        } else {
            echo "Error inserting into patients table: " . $conn->error;
        }
    } else {
        echo "Error inserting into users table: " . $conn->error;
    }

    // Close the database connection
    $conn->close();
} else {
    echo "Invalid request method.";
}
?>