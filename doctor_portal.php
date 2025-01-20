<?php
session_start();
require_once 'db_connection.php';

// Ensure the user is logged in as a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: doctor_login_page.html?error=Please login to access the portal.");
    exit();
}

// Get the logged-in doctor's ID
$stmt_doctor = $conn->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
$stmt_doctor->bind_param("i", $_SESSION['user_id']);
$stmt_doctor->execute();
$doctor_result = $stmt_doctor->get_result();

if ($doctor_result->num_rows === 0) {
    die("Doctor profile not found.");
}

$doctor_data = $doctor_result->fetch_assoc();
$doctor_id = $doctor_data['doctor_id'];

// Fetch scheduled appointments with patient details
$stmt_appointments = $conn->prepare("
    SELECT 
        a.appointment_id, 
        a.appointment_date, 
        a.status, 
        a.reason,
        p.patient_id,
        p.name AS patient_name,
        p.contact_number AS patient_contact
    FROM 
        appointments a
    JOIN 
        patients p ON a.patient_id = p.patient_id 
    WHERE 
        a.doctor_id = ?
    ORDER BY 
        a.appointment_date ASC
");

$stmt_appointments->bind_param("i", $doctor_id);
$stmt_appointments->execute();
$appointments = $stmt_appointments->get_result();

// Fetch doctor's details
$stmt_doctor_details = $conn->prepare("
    SELECT name, specialization, availability_status 
    FROM doctors 
    WHERE doctor_id = ?
");
$stmt_doctor_details->bind_param("i", $doctor_id);
$stmt_doctor_details->execute();
$doctor_details = $stmt_doctor_details->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Portal - MediTouch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="doctor.css">
</head>
<body>
    <header class="bg-dark text-white py-3">
        <div class="container d-flex justify-content-between align-items-center">
            <h1 class="h4 m-0">MediTouch - Doctor Portal</h1>
            <div>
                <span class="me-3">Dr. <?= htmlspecialchars($doctor_details['name']) ?></span>
                <a href="index.html" class="btn btn-light btn-sm">Logout</a>
            </div>
        </div>
    </header>

    <main class="container my-4">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        Doctor Profile
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($doctor_details['name']) ?></h5>
                        <p class="card-text">
                            <strong>Specialization:</strong> <?= htmlspecialchars($doctor_details['specialization']) ?><br>
                            <strong>Availability:</strong> 
                            <span class="<?= $doctor_details['availability_status'] === 'available' ? 'text-success' : 'text-danger' ?>">
                                <?= ucfirst(htmlspecialchars($doctor_details['availability_status'])) ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        Scheduled Appointments
                    </div>
                    <div class="card-body">
                        <?php if ($appointments->num_rows > 0): ?>
                            <div class="list-group">
                                <?php while ($appointment = $appointments->fetch_assoc()): ?>
                                    <div class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1"><?= htmlspecialchars($appointment['patient_name']) ?></h5>
                                            <small class="text-muted">
                                                <?= date('F d, Y h:i A', strtotime($appointment['appointment_date'])) ?>
                                            </small>
                                        </div>
                                        <p class="mb-1">
                                            <strong>Reason:</strong> <?= htmlspecialchars($appointment['reason'] ?? 'Not specified') ?><br>
                                            <strong>Status:</strong> 
                                            <span class="<?= 
                                                $appointment['status'] === 'scheduled' ? 'text-primary' : 
                                                ($appointment['status'] === 'completed' ? 'text-success' : 'text-danger')
                                            ?>">
                                                <?= ucfirst(htmlspecialchars($appointment['status'])) ?>
                                            </span>
                                        </p>
                                        
                                        <small class="text-muted">
                                            Contact: <?= htmlspecialchars($appointment['patient_contact']) ?>
                                        </small>
                                        <div class="mt-2">
                                            <button class="btn btn-sm btn-outline-primary me-2 view-patient-details" 
                                                    data-patient-id="<?= $appointment['patient_id'] ?>" 
                                                    data-appointment-id="<?= $appointment['appointment_id'] ?>">
                                                View Details
                                            </button>
                                            <button class="btn btn-sm btn-outline-success start-consultation" 
                                                    data-patient-id="<?= $appointment['patient_id'] ?>" 
                                                    data-appointment-id="<?= $appointment['appointment_id'] ?>">
                                                Start Consultation
                                                
                                            </button>
                                            
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No appointments scheduled at the moment.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-light py-3 text-center">
        <p class="mb-0">© 2025 MediTouch. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // View Patient Details and Start Consultation
        document.addEventListener('click', function(e) {
            // View Patient Details
            if (e.target.classList.contains('view-patient-details')) {
                const patientId = e.target.getAttribute('data-patient-id');
                const appointmentId = e.target.getAttribute('data-appointment-id');
                
                fetch(`patient_details.php?patient_id=${patientId}`)
                    .then(response => response.json())
                    .then(data => {
                        // Create modal dynamically
                        const modalHtml = `
                            <div class="modal fade" id="patientDetailsModal" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Patient Details: ${data.personal_info.name}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <h6>Personal Information</h6>
                                                    <p><strong>Name:</strong> ${data.personal_info.name}</p>
                                                    <p><strong>Email:</strong> ${data.personal_info.email}</p>
                                                    <p><strong>DOB:</strong> ${data.personal_info.date_of_birth}</p>
                                                    <p><strong>Gender:</strong> ${data.personal_info.gender}</p>
                                                    <p><strong>Contact:</strong> ${data.personal_info.contact_number}</p>
                                                </div>
                                                <div class="col-md-4">
                                                    <h6>Medical History</h6>
                                                    <p>${data.personal_info.medical_history || 'No medical history'}</p>
                                                    <h6>Allergies</h6>
                                                    <p>${data.personal_info.allergies || 'No known allergies'}</p>
                                                </div>
                                                <div class="col-md-4">
                                                    <h6>Emergency Contact</h6>
                                                    <p><strong>Name:</strong> ${data.personal_info.emergency_contact_name}</p>
                                                    <p><strong>Number:</strong> ${data.personal_info.emergency_contact_number}</p>
                                                </div>
                                            </div>

                                            <hr>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6>Recent Appointments</h6>
                                                    <ul class="list-group">
                                                        ${data.recent_appointments.map(appt => `
                                                            <li class="list-group-item">
                                                                <strong>${appt.doctor_name} (${appt.specialization})</strong>
                                                                <br>${new Date(appt.appointment_date).toLocaleString()}
                                                                <br>Status: ${appt.status}
                                                            </li>
                                                        `).join('') || '<li class="list-group-item">No recent appointments</li>'}
                                                    </ul>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6>Recent Prescriptions</h6>
                                                    <ul class="list-group">
                                                        ${data.recent_prescriptions.map(rx => `
                                                            <li class="list-group-item">
                                                                <strong>${rx.medication}</strong>
                                                                <br>Dosage: ${rx.dosage}
                                                                <br>Prescribed by: ${rx.prescribed_by}
                                                            </li>
                                                        `).join('') || '<li class="list-group-item">No recent prescriptions</li>'}
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="button" class="btn btn-primary start-consultation" 
                                                    data-appointment-id="${appointmentId}" 
                                                    data-patient-id="${patientId}">
                                                Start Consultation
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;

                        // Remove existing modal if any
                        const existingModal = document.getElementById('patientDetailsModal');
                        if (existingModal) existingModal.remove();

                        // Add modal to body
                        document.body.insertAdjacentHTML('beforeend', modalHtml);
                        
                        // Show modal
                        const modalInstance = new bootstrap.Modal(document.getElementById('patientDetailsModal'));
                        modalInstance.show();
                    })
                    .catch(error => {
                        console.error('Error fetching patient details:', error);
                        alert('Failed to fetch patient details');
                    });
            }

            // Start Consultation
            if (e.target.classList.contains('start-consultation')) {
                const patientId = e.target.getAttribute('data-patient-id');
                const appointmentId = e.target.getAttribute('data-appointment-id');

                // Redirect to consultation page
                window.location.href = `doctor_consultation.php?appointment_id=${appointmentId}&patient_id=${patientId}`;
            }
        });
    </script>
</body>
</html>