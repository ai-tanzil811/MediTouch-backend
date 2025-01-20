<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>Forgot Password - MediTouch</title>

 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
 <link rel="stylesheet" href="patient_login_page.css">
</head>
<body>
 <div class="container d-flex justify-content-center align-items-center vh-100">
   <div class="reset-container card p-4 shadow">
     <div class="text-center">
       <img src="images/logo.png" alt="MediTouch Logo" class="brand-logo mb-3" style="max-width: 100px;">
       <h4>Password Reset</h4>
       <p class="text-muted">Enter your registered phone number to reset your password</p>
     </div>

     <!-- Display any message from the session -->
     <?php if (isset($_SESSION['message'])): ?>
         <div class="alert alert-info text-center mt-3" role="alert">
             <?= htmlspecialchars($_SESSION['message']); ?>
         </div>
         <?php unset($_SESSION['message']); // Clear message after display ?>
     <?php endif; ?>

     <form action="forgot_password.php" method="POST" class="mt-3">
       <div class="mb-3">
         <label for="phone" class="form-label">Phone Number</label>
         <input type="tel" class="form-control" id="phone" name="phone" placeholder="Enter your phone number" pattern="[0-9]{11}" required>
       </div>
       <button type="submit" class="btn btn-primary w-100">Send OTP</button>
     </form>

     <div class="text-center mt-3">
       <a href="index.html" class="text-decoration-none">Back to Home</a>
     </div>
   </div>
 </div>

 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
