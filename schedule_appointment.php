<?php
session_start();
require_once 'db_connection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized access. Please log in.'
    ]);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method.'
    ]);
    exit();
}

$delete_old_query = "
    DELETE FROM appointments 
    WHERE TIMESTAMPDIFF(MINUTE, appointment_date, NOW()) > 5
";

$conn->query($delete_old_query);


$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);


if (!$data || 
    !isset($data['doctor_id']) || 
    !isset($data['date_time']) || 
    !isset($data['reason'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Missing required appointment details.'
    ]);
    exit();
}

$doctor_id = filter_var($data['doctor_id'], FILTER_VALIDATE_INT);
$date_time = filter_var($data['date_time'], FILTER_SANITIZE_STRING);
$reason = filter_var($data['reason'], FILTER_SANITIZE_STRING);


$stmt_patient = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt_patient->bind_param("i", $_SESSION['user_id']);
$stmt_patient->execute();
$patient_result = $stmt_patient->get_result();

if ($patient_result->num_rows === 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'Patient profile not found.'
    ]);
    exit();
}

$patient_data = $patient_result->fetch_assoc();
$patient_id = $patient_data['patient_id'];

$stmt = $conn->prepare("
    INSERT INTO appointments 
    (doctor_id, patient_id, appointment_date, status, reason) 
    VALUES (?, ?, ?, ?, ?)
");

$status = 'scheduled';
$stmt->bind_param(
    "iisss", 
    $doctor_id, 
    $patient_id, 
    $date_time, 
    $status, 
    $reason
);

try {
    $result = $stmt->execute();

    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Appointment scheduled successfully.',
            'appointment_id' => $stmt->insert_id
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to schedule appointment: ' . $stmt->error
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$stmt->close();
$conn->close();
?>
