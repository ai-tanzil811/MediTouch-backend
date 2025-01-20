<?php
session_start();
require_once 'db_connection.php';  
require_once 'vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
 // Include Composer's autoloader
use Twilio\Rest\Client;

// If the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone']);
    
    // Validate the phone number (11 digits)
    if (!preg_match('/^[0-9]{11}$/', $phone)) {
        $_SESSION['message'] = "Invalid phone number format. Please enter an 11-digit phone number.";
        header("Location: forgot_password_page.php");
        exit();
    }

    try {
        // Prepare SQL query to check if the phone number exists in the database
        $stmt = $conn->prepare("SELECT d.doctor_id, d.contact_number, u.user_id 
                               FROM doctors d 
                               JOIN users u ON d.user_id = u.user_id 
                               WHERE d.contact_number = ?
                               UNION
                               SELECT p.patient_id, p.contact_number, u.user_id 
                               FROM patients p 
                               JOIN users u ON p.user_id = u.user_id 
                               WHERE p.contact_number = ?");
        $stmt->bind_param("ss", $phone, $phone);
        $stmt->execute();
        $result = $stmt->get_result();

        // If the phone number is found in the database
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Twilio credentials (replace with your actual credentials)
            $sid = getenv('TWILIO_SID');  // Set these in your environment variables
            $token = getenv('TWILIO_AUTH_TOKEN');
            $twilioServiceSid = getenv('TWILIO_SERVICE_SID');
            
            $twilio = new Client($sid, $token);

            try {
                // Send OTP using Twilio Verify
                $verification = $twilio->verify->v2->services($twilioServiceSid)
                    ->verifications
                    ->create("+880" . $phone, "sms");

                // Store phone number and user ID in the session for further steps
                $_SESSION['reset_phone'] = $phone;
                $_SESSION['user_id'] = $user['user_id'];

                // Set a success message to inform the user
                $_SESSION['message'] = "OTP has been sent to your phone.";
                header("Location: verify_otp.php");
                exit();

            } catch (Exception $e) {
                // Handle any error during OTP sending
                $_SESSION['message'] = "Failed to send OTP. Please try again.";
                header("Location: forgot_password_page.php");
                exit();
            }
        } else {
            // If phone number is not found in the database
            $_SESSION['message'] = "Phone number not found. Please try again.";
            header("Location: forgot_password_page.php");
            exit();
        }
    } catch (Exception $e) {
        // General error handling
        $_SESSION['message'] = "An error occurred. Please try again later.";
        header("Location: forgot_password_page.php");
        exit();
    }
}
?>