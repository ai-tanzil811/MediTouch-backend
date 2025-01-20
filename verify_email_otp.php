<?php
session_start();


if (!isset($_SESSION['otp'])) {
    echo "Error: No OTP found. Please register again.";
    exit();
}


$otpError = '';
$otpSuccess = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredOtp = $_POST['otp'];

    
    if ($enteredOtp == $_SESSION['otp']) {
        
        $otpSuccess = "OTP verified successfully! You can now log in.";

        
        unset($_SESSION['otp']);
        unset($_SESSION['user_id']);
        unset($_SESSION['email']);
        unset($_SESSION['role']);
    } else {
        $otpError = "Invalid OTP. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        form {
            margin-top: 1rem;
        }
        form input {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        form button {
            width: 100%;
            padding: 0.5rem;
            background-color: #00796b;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        form button:hover {
            background-color: #004d40;
        }
        .message {
            text-align: center;
            margin-top: 1rem;
        }
        .message.success {
            color: green;
        }
        .message.error {
            color: red;
        }
        .login-button {
            display: block;
            width: 100%;
            padding: 0.5rem;
            background-color: #00796b;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            text-align: center;
            margin-top: 1rem;
        }
        .login-button:hover {
            background-color: #004d40;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>OTP Verification</h2>
        <form method="POST" action="">
            <label for="otp">Enter OTP:</label>
            <input type="text" id="otp" name="otp" placeholder="Enter the OTP sent to your email" required>
            <button type="submit">Verify OTP</button>
        </form>

        <!-- Display success or error messages -->
        <?php if ($otpError): ?>
            <p class="message error"><?php echo $otpError; ?></p>
        <?php endif; ?>

        <?php if ($otpSuccess): ?>
            <p class="message success"><?php echo $otpSuccess; ?></p>
            <a href="doctor_login_page.html" class="login-button">Login Now</a>
        <?php endif; ?>
    </div>
</body>
</html>