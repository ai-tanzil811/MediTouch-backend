<?php
session_start();
require_once 'db_connection.php';

// Validate user session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get raw POST data
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

// Validate input
if (!isset($data['appointment_id']) || !isset($data['medication']) || !isset($data['dosage'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

// Fetch appointment details
$stmt_appointment = $conn->prepare("
    SELECT doctor_id, patient_id 
    FROM appointments 
    WHERE appointment_id = ?
");
$stmt_appointment->bind_param("i", $data['appointment_id']);
$stmt_appointment->execute();
$result = $stmt_appointment->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Appointment not found']);
    exit();
}

$appointment = $result->fetch_assoc();

$stmt = $conn->prepare("
    INSERT INTO prescriptions 
    (appointment_id, patient_id, doctor_id, medication, dosage) 
    VALUES (?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "iiiss", 
    $data['appointment_id'], 
    $appointment['patient_id'], 
    $appointment['doctor_id'], 
    $data['medication'], 
    $data['dosage']
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Prescription saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save prescription']);
}

$stmt->close();
$conn->close();
?>