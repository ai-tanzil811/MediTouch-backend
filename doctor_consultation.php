<?php
session_start();
require_once 'db_connection.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: doctor_login_page.html?error=Unauthorized access.");
    exit();
}

$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

$stmt = $conn->prepare("
    SELECT 
        a.*, 
        p.name AS patient_name, 
        d.name AS doctor_name,
        p.medical_history,
        p.user_id AS patient_user_id,
        d.user_id AS doctor_user_id
    FROM 
        appointments a
    JOIN 
        patients p ON a.patient_id = p.patient_id
    JOIN 
        doctors d ON a.doctor_id = d.doctor_id
    WHERE 
        a.appointment_id = ? AND a.patient_id = ?
");
$stmt->bind_param("ii", $appointment_id, $patient_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Invalid appointment or unauthorized access.");
}

$consultation = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation with <?= htmlspecialchars($consultation['patient_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="call.css">
</head>
<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Consultation with <?= htmlspecialchars($consultation['patient_name']) ?></h3>
                <div>
                    <button class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#medicalHistoryModal">
                        Medical History
                    </button>
                    <a href="doctor_portal.php" class="btn btn-outline-secondary">Back to Portal</a>
                </div>
            </div>
            
            <div class="card-body">
                <div id="connection-status" class="connection-status status-connecting">
                    Initializing video call...
                </div>

                <div class="video-grid">
                    <div class="video-container">
                        <video id="localVideo" autoplay muted playsinline></video>
                        <div class="video-label">You (Doctor)</div>
                    </div>
                    <div class="video-container">
                        <video id="remoteVideo" autoplay playsinline></video>
                        <div class="video-label"><?= htmlspecialchars($consultation['patient_name']) ?></div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-8">
                        <div class="card mb-3">
                            <div class="card-header">
                                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#chat">Chat</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="tab" href="#notes">Consultation Notes</a>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="chat">
                                        <div id="chat-container"></div>
                                        <div class="input-group">
                                            <input type="text" id="messageInput" class="form-control" placeholder="Type your message...">
                                            <button id="sendMessage" class="btn btn-primary">Send</button>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="notes">
                                        <textarea id="consultationNotes" class="form-control" rows="10" 
                                                placeholder="Enter consultation notes here..."></textarea>
                                        <button id="saveNotes" class="btn btn-primary mt-2">Save Notes</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-header">Write Prescription</div>
                            <div class="card-body">
                                <form id="prescriptionForm">
                                    <div class="mb-3">
                                        <label class="form-label">Medication Name</label>
                                        <input type="text" class="form-control" name="medication" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Dosage</label>
                                        <input type="text" class="form-control" name="dosage" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Duration</label>
                                        <input type="text" class="form-control" name="duration" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Instructions</label>
                                        <textarea class="form-control" name="instructions" rows="3" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100">Save Prescription</button>
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">Call Controls</div>
                            <div class="card-body">
                                <button id="toggleVideo" class="btn btn-secondary mb-2 w-100">
                                    <i class="bi bi-camera-video"></i> Toggle Video
                                </button>
                                <button id="toggleAudio" class="btn btn-secondary mb-2 w-100">
                                    <i class="bi bi-mic"></i> Toggle Audio
                                </button>
                                <button id="endCall" class="btn btn-danger w-100">
                                    <i class="bi bi-telephone-x"></i> End Call
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="medicalHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Patient Medical History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <h6>Previous Medical Conditions</h6>
                        <p><?= nl2br(htmlspecialchars($consultation['medical_history'] ?? 'No medical history available.')) ?></p>
                    </div>
                    <div class="mb-3">
                        <h6>Previous Prescriptions</h6>
                        <div class="prescription-history">
                            <?php
                            $stmt = $conn->prepare("
                                SELECT p.*, a.appointment_date 
                                FROM prescriptions p 
                                JOIN appointments a ON p.appointment_id = a.appointment_id 
                                WHERE a.patient_id = ? 
                                ORDER BY a.appointment_date DESC
                            ");
                            $stmt->bind_param("i", $patient_id);
                            $stmt->execute();
                            $prescriptions = $stmt->get_result();
                            
                            if ($prescriptions->num_rows > 0):
                                while ($prescription = $prescriptions->fetch_assoc()):
                            ?>
                                <div class="card mb-2">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2 text-muted">
                                            <?= date('M d, Y', strtotime($prescription['appointment_date'])) ?>
                                        </h6>
                                        <p class="card-text">
                                            <strong>Medication:</strong> <?= htmlspecialchars($prescription['medication']) ?><br>
                                            <strong>Notes:</strong> <?= htmlspecialchars($prescription['notes']) ?>
                                        </p>
                                    </div>
                                </div>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <p class="text-muted">No previous prescriptions found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" id="appointmentId" value="<?= $appointment_id ?>">
    <input type="hidden" id="userId" value="<?= $_SESSION['user_id'] ?>">
    <input type="hidden" id="patientId" value="<?= $patient_id ?>">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
       

        let peerConnection;
        let localStream;
        let ws;
        const signalingServerUrl = 'ws://localhost:8080'; // Replace with your server's URL
        ws = new WebSocket(signalingServerUrl);
        const videoControls = {
            video: true,
            audio: true
        };

        async function initializeCall() {
            try {
                localStream = await navigator.mediaDevices.getUserMedia({
                    video: true,
                    audio: true
                });
                document.getElementById('localVideo').srcObject = localStream;

                peerConnection = new RTCPeerConnection({
                    iceServers: [
                        { urls: 'stun:stun.l.google.com:19302' }
                    ]
                });

                localStream.getTracks().forEach(track => {
                    peerConnection.addTrack(track, localStream);
                });

                peerConnection.ontrack = event => {
                    document.getElementById('remoteVideo').srcObject = event.streams[0];
                };

                peerConnection.onicecandidate = event => {
                    if (event.candidate) {
                        sendSignalingMessage({
                            type: 'ice-candidate',
                            candidate: event.candidate
                        });
                    }
                };

                connectSignalingServer();

            } catch (error) {
                console.error('Error initializing call:', error);
                updateConnectionStatus('disconnected', 'Failed to initialize video call');
            }
        }

        function connectSignalingServer() {
            const appointmentId = document.getElementById('appointmentId').value;
            ws = new WebSocket(`${SIGNALING_SERVER}/${appointmentId}`);

            ws.onopen = () => {
                updateConnectionStatus('connecting', 'Waiting for patient to join...');
                sendSignalingMessage({
                    type: 'join',
                    role: 'doctor',
                    appointmentId: appointmentId
                });
            };

            ws.onmessage = async (event) => {
                const message = JSON.parse(event.data);
                await handleSignalingMessage(message);
            };

            ws.onclose = () => {
                updateConnectionStatus('disconnected', 'Connection closed');
            };
        }

        function sendSignalingMessage(message) {
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify(message));
            }
        }

        async function handleSignalingMessage(message) {
            try {
                switch (message.type) {
                    case 'join':
                        if (message.role === 'patient') {
                            updateConnectionStatus('connecting', 'Patient joined, establishing connection...');
                            const offer = await peerConnection.createOffer();
                            await peerConnection.setLocalDescription(offer);
                            sendSignalingMessage({
                                type: 'offer',
                                offer: offer
                            });
                        }
                        break;

                    case 'answer':
                        await peerConnection.setRemoteDescription(new RTCSessionDescription(message.answer));
                        updateConnectionStatus('connected', 'Connected with patient');
                        break;

                    case 'ice-candidate':
                        if (message.candidate) {
                            await peerConnection.addIceCandidate(new RTCIceCandidate(message.candidate));
                        }
                        break;

                    case 'chat':
                        displayChatMessage(message.sender, message.message);
                        break;
                }
            } catch (error) {
                console.error('Error handling signaling message:', error);
            }
        }

        function updateConnectionStatus(status, message) {
            const statusDiv = document.getElementById('connection-status');
            statusDiv.className = `connection-status status-${status}`;
            statusDiv.textContent = message;
        }

        function displayChatMessage(sender, message) {
            const chatContainer = document.getElementById('chat-container');
            const messageElement = document.createElement('div');
            messageElementmessageElement.className = 'mb-2';
            messageElement.innerHTML = `
                <strong>${sender}:</strong> ${message}
                <small class="text-muted ms-2">${new Date().toLocaleTimeString()}</small>
            `;
            chatContainer.appendChild(messageElement);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        document.getElementById('toggleVideo').addEventListener('click', () => {
            videoControls.video = !videoControls.video;
            localStream.getVideoTracks().forEach(track => {
                track.enabled = videoControls.video;
            });
            document.getElementById('toggleVideo').innerText = 
                videoControls.video ? 'Turn Off Video' : 'Turn On Video';
        });

        document.getElementById('toggleAudio').addEventListener('click', () => {
            videoControls.audio = !videoControls.audio;
            localStream.getAudioTracks().forEach(track => {
                track.enabled = videoControls.audio;
            });
            document.getElementById('toggleAudio').innerText = 
                videoControls.audio ? 'Mute Audio' : 'Unmute Audio';
        });

        document.getElementById('endCall').addEventListener('click', async () => {
            try {
 
                await saveConsultationNotes();
   
                if (peerConnection) {
                    peerConnection.close();
                }
                if (localStream) {
                    localStream.getTracks().forEach(track => track.stop());
                }
                if (ws) {
                    ws.close();
                }
   
                window.location.href = 'doctor_portal.php';
            } catch (error) {
                console.error('Error ending call:', error);
            }
        });

        document.getElementById('sendMessage').addEventListener('click', () => {
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            if (message) {
                sendChatMessage(message);
                messageInput.value = '';
            }
        });

        document.getElementById('messageInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                const messageInput = document.getElementById('messageInput');
                const message = messageInput.value.trim();
                if (message) {
                    sendChatMessage(message);
                    messageInput.value = '';
                }
            }
        });

        function sendChatMessage(message) {
            displayChatMessage('Doctor', message);
            sendSignalingMessage({
                type: 'chat',
                message: message,
                sender: 'Doctor'
            });
        }

  
        document.getElementById('prescriptionForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const prescriptionData = {
                appointment_id: document.getElementById('appointmentId').value,
                medication: formData.get('medication'),
                dosage: formData.get('dosage'),
                duration: formData.get('duration'),
                instructions: formData.get('instructions')
            };

            try {
                const response = await fetch('save_prescription.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(prescriptionData)
                });

                const result = await response.json();
                if (result.success) {
                    alert('Prescription saved successfully!');
                    e.target.reset();
                } else {
                    alert('Failed to save prescription: ' + result.message);
                }
            } catch (error) {
                console.error('Error saving prescription:', error);
                alert('An error occurred while saving the prescription');
            }
        });

   
        async function saveConsultationNotes() {
            const notes = document.getElementById('consultationNotes').value;
            const appointmentId = document.getElementById('appointmentId').value;

            try {
                const response = await fetch('save_consultation_notes.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        appointment_id: appointmentId,
                        notes: notes
                    })
                });

                const result = await response.json();
                if (!result.success) {
                    throw new Error(result.message);
                }
            } catch (error) {
                console.error('Error saving consultation notes:', error);
                alert('Failed to save consultation notes');
            }
        }

        document.getElementById('saveNotes').addEventListener('click', async () => {
            await saveConsultationNotes();
            alert('Consultation notes saved successfully!');
        });


        window.addEventListener('beforeunload', async (e) => {
            await saveConsultationNotes();
            if (peerConnection) {
                peerConnection.close();
            }
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
            }
            if (ws) {
                ws.close();
            }
        });


        window.addEventListener('load', initializeCall);
    </script>
</body>
</html>
