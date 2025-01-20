<?php
require 'db_connection.php'; 

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Start session
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data and sanitize input
    $username = $conn->real_escape_string($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password for security
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $specialty = $conn->real_escape_string($_POST['specialty']);
    $license = $conn->real_escape_string($_POST['license']);
    $experience = $conn->real_escape_string($_POST['experience']);

    // Check if the email already exists
    $checkEmailQuery = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($checkEmailQuery);

    if ($result->num_rows > 0) {
        echo "Error: The email address '$email' is already registered. Please use a different email.";
        exit();
    }

    // Handle file upload
    $photo = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        $photo = $uploadDir . basename($_FILES['photo']['name']);
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo)) {
            die("Error uploading photo.");
        }
    }

    // Insert into the users table
    $sqlUser = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$password', 'doctor')";
    if ($conn->query($sqlUser)) {
        $userId = $conn->insert_id; // Get the newly created user's ID

        // Insert into the doctors table
        $sqlDoctor = "INSERT INTO doctors (user_id, name, specialization, contact_number, availability_status) 
                      VALUES ($userId, '$username', '$specialty', '$phone', 'available')";
        if ($conn->query($sqlDoctor)) {
            // Generate OTP
            $otp = rand(100000, 999999);

            // Store OTP in the session
            $_SESSION['otp'] = $otp;
            $_SESSION['user_id'] = $userId;
            $_SESSION['email'] = $email;

            // Send OTP to the user's email using PHPMailer
            $mail = new PHPMailer();
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'meditouchofficial@gmail.com'; // Your email
                $mail->Password = 'gxpviklzyqfpurph'; // Your email password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('meditouchofficial@gmail.com', 'MediTouch');
                $mail->addAddress($email, $username);
                $mail->isHTML(true);
                $mail->Subject = 'MediTouch OTP Verification';
                $mail->Body = "Dear $username,<br>Your OTP for MediTouch registration is: <strong>$otp</strong><br>Please enter this OTP to verify your email address.<br>Thank you,<br>MediTouch Team";

                $mail->send();
                // Redirect to OTP verification page
                header("Location: verify_email_otp.php");
                exit();
            } catch (Exception $e) {
                echo "Error sending OTP email: {$mail->ErrorInfo}";
            }
        } else {
            echo "Error inserting into doctors table: " . $conn->error;
        }
    } else {
        echo "Error inserting into users table: " . $conn->error;
    }
}
?>