<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: patient_login_page.html?error=Please login to access the portal.");
    exit();
}

$stmt = $conn->prepare("SELECT d.doctor_id, d.name, d.specialization, d.availability_status FROM doctors d");
$stmt->execute();
$doctors = $stmt->get_result();

$stmt_patient = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt_patient->bind_param("i", $_SESSION['user_id']);
$stmt_patient->execute();
$patient_result = $stmt_patient->get_result();
$patient_data = $patient_result->fetch_assoc();
$patient_id = $patient_data['patient_id'];

$stmt_appointments = $conn->prepare("SELECT 
    a.appointment_id, 
    a.appointment_date, 
    a.status, 
    a.reason, 
    d.name AS doctor_name, 
    d.specialization AS doctor_specialization, 
    d.availability_status
FROM 
    appointments a
JOIN 
    doctors d ON a.doctor_id = d.doctor_id
WHERE 
    a.patient_id = ?
ORDER BY 
    a.appointment_date ASC");
$stmt_appointments->bind_param("i", $patient_id);
$stmt_appointments->execute();
$appointments = $stmt_appointments->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Portal - MediTouch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="patient.css">
</head>
<body>
    <header class="bg-primary text-white py-3">
        <div class="container d-flex justify-content-between align-items-center">
            <h1 class="h4 m-0">MediTouch - Patient Portal</h1>
            <a href="index.html" class="btn btn-light btn-sm">Logout</a>
        </div>
    </header>

    <main class="container my-4">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        Specialist Doctors
                    </div>
                    <div class="list-group">
                        <?php if ($doctors->num_rows > 0): ?>
                            <?php while ($doctor = $doctors->fetch_assoc()): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5><?= htmlspecialchars($doctor['name']) ?> - <?= htmlspecialchars($doctor['specialization']) ?></h5>
                                        <p class="mb-1 <?= $doctor['availability_status'] === 'available' ? 'text-success' : 'text-danger' ?>">
                                            <?= ucfirst($doctor['availability_status']) ?>
                                        </p>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#appointmentModal" 
                                            data-doctor-id="<?= $doctor['doctor_id'] ?>"
                                            data-doctor-name="<?= htmlspecialchars($doctor['name']) ?>">
                                        Schedule Appointment
                                    </button>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-muted p-3">No doctors are available at the moment.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        My Appointments
                    </div>
                    <div class="list-group">
                        <?php if ($appointments->num_rows > 0): ?>
                            <?php while ($appointment = $appointments->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">Dr. <?= htmlspecialchars($appointment['doctor_name']) ?></h5>
                                        <small class="text-muted">
                                            <?= date('F d, Y h:i A', strtotime($appointment['appointment_date'])) ?>
                                        </small>
                                    </div>
                                    <p class="mb-1">
                                        <strong>Specialization:</strong> <?= htmlspecialchars($appointment['doctor_specialization']) ?><br>
                                        <strong>Status:</strong> 
                                        <span class="<?= 
                                            $appointment['status'] === 'scheduled' ? 'text-primary' : 
                                            ($appointment['status'] === 'completed' ? 'text-success' : 'text-danger')
                                        ?>">
                                            <?= ucfirst(htmlspecialchars($appointment['status'])) ?>
                                        </span>
                                    </p>
                                    <?php if ($appointment['status'] === 'scheduled' && $appointment['availability_status'] === 'available'): ?>
                                        <a href="patient_consultation.php?appointment_id=<?= $appointment['appointment_id'] ?>" 
                                           class="btn btn-success btn-sm">Video Call</a>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-muted p-3">No upcoming appointments.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="appointmentModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Schedule Appointment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="appointmentForm">
                                <input type="hidden" id="doctor_id" name="doctor_id">
                                <div class="mb-3">
                                    <label for="reason" class="form-label">Reason for Appointment</label>
                                    <input type="text" class="form-control" id="reason" name="reason" required>
                                </div>
                                <div class="mb-3">
                                    <label for="date_time" class="form-label">Preferred Date and Time</label>
                                    <input type="datetime-local" class="form-control" id="date_time" name="date_time" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Schedule Appointment</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-light py-3 text-center">
        <p class="mb-0">&copy; 2025 MediTouch. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.querySelectorAll('[data-bs-target="#appointmentModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const doctorId = this.getAttribute('data-doctor-id');
                const doctorName = this.getAttribute('data-doctor-name');

                document.getElementById('doctor_id').value = doctorId;

                document.querySelector('#appointmentModal .modal-title').textContent = 
                    `Schedule Appointment with Dr. ${doctorName}`;
            });
        });

        document.getElementById('appointmentForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const reason = document.getElementById('reason').value.trim();
            const dateTime = document.getElementById('date_time').value;
            const doctorId = document.getElementById('doctor_id').value;

            if (!reason || !dateTime || !doctorId) {
                alert('Please fill in all fields');
                return;
            }

            const submitButton = event.target.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Scheduling...';

            fetch('schedule_appointment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    reason: reason,
                    date_time: dateTime,
                    doctor_id: doctorId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Appointment scheduled successfully!');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('appointmentModal'));
                    modal.hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An unexpected error occurred. Please try again.');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = 'Schedule Appointment';
            });
        });
    </script>
</body>
</html>
