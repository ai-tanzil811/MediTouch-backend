<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['appointment_id']) || !isset($data['notes'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid data received'
    ]);
    exit();
}

try {
    
    $stmt = $conn->prepare("
        SELECT notes_id 
        FROM consultation_notes 
        WHERE appointment_id = ?
    ");
    $stmt->bind_param("i", $data['appointment_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $stmt = $conn->prepare("
            UPDATE consultation_notes 
            SET notes = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE appointment_id = ?
        ");
        $stmt->bind_param("si", $data['notes'], $data['appointment_id']);
    } else {

        $stmt = $conn->prepare("
            INSERT INTO consultation_notes 
            (appointment_id, notes) 
            VALUES (?, ?)
        ");
        $stmt->bind_param("is", $data['appointment_id'], $data['notes']);
    }

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Consultation notes saved successfully'
        ]);
    } else {
        throw new Exception('Failed to save consultation notes');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}