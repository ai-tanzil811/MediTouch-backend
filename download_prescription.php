<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: patient_login_page.html?error=Unauthorized access.");
    exit();
}

$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;

$stmt = $conn->prepare("
    SELECT p.medication, p.notes 
    FROM prescriptions p
    JOIN appointments a ON p.appointment_id = a.appointment_id
    WHERE a.patient_id = ? AND a.doctor_id = ?
");
$stmt->bind_param("ii", $_SESSION['user_id'], $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $prescriptions = $result->fetch_all(MYSQLI_ASSOC);
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="prescriptions.json"');
    echo json_encode($prescriptions);
} else {
    echo "No prescriptions found for this doctor.";
}
?>
