<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Consultation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/4.0.1/socket.io.min.js"></script>
<link rel="stylesheet" href="call.css">
</head>
<body>
    <div class="container my-4">
        <h1 class="text-center mb-4">Patient Consultation</h1>

        
        <div id="video-container" class="row">
            <div class="col-md-6">
                <h5 class="text-center">Your Video</h5>
                <video id="localVideo" autoplay muted></video>
            </div>
            <div class="col-md-6">
                <h5 class="text-center">Doctor's Video</h5>
                <video id="remoteVideo" autoplay></video>
            </div>
        </div>

       
        <div class="card">
            <div class="card-header">Chat</div>
            <div id="chat-container" class="card-body" style="max-height: 300px; overflow-y: auto;">
               
            </div>
            <div class="card-footer d-flex">
                <textarea id="messageInput" class="form-control me-2" placeholder="Type a message"></textarea>
                <button id="sendMessage" class="btn btn-primary">Send</button>
            </div>
        </div>

        
        <div class="mt-3 d-flex justify-content-center gap-3">
            <button id="toggleVideo" class="btn btn-secondary">Toggle Video</button>
            <button id="toggleAudio" class="btn btn-secondary">Toggle Audio</button>
            <button id="endCall" class="btn btn-danger">End Call</button>
        </div>
    </div>

    <script>
        let localStream;
        let peerConnection;
        let ws;
        const configuration = {
            iceServers: [
                { urls: "stun:stun.l.google.com:19302" }
            ]
        };

        const signalingServerUrl = 'ws://localhost:8080'; 
        ws = new WebSocket(signalingServerUrl);

        ws.onopen = () => {
            console.log('WebSocket connected');
        };

        ws.onerror = (error) => {
            console.error('WebSocket error:', error);
            alert('Error with WebSocket connection');
        };

        ws.onmessage = (message) => {
            const data = JSON.parse(message.data);
            if (data.type === 'chat') {
                displayChatMessage(data.message);
            } else if (data.type === 'offer') {
                handleOffer(data.offer);
            } else if (data.type === 'answer') {
                handleAnswer(data.answer);
            } else if (data.type === 'candidate') {
                handleCandidate(data.candidate);
            }
        };

        async function initializeCall() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                localStream = stream;
                document.getElementById('localVideo').srcObject = stream;

                peerConnection = new RTCPeerConnection(configuration);

                stream.getTracks().forEach(track => {
                    peerConnection.addTrack(track, stream);
                });

                peerConnection.onicecandidate = event => {
                    if (event.candidate) {
                        ws.send(JSON.stringify({
                            type: 'candidate',
                            candidate: event.candidate
                        }));
                    }
                };

                peerConnection.ontrack = event => {
                    document.getElementById('remoteVideo').srcObject = event.streams[0];
                };

                createOffer();
            } catch (err) {
                console.error('Error accessing media devices:', err);
                alert('Please enable camera and microphone permissions.');
            }
        }

        async function createOffer() {
            try {
                const offer = await peerConnection.createOffer();
                await peerConnection.setLocalDescription(offer);
                ws.send(JSON.stringify({
                    type: 'offer',
                    offer: offer
                }));
            } catch (err) {
                console.error('Error creating offer:', err);
            }
        }

        function handleOffer(offer) {
            peerConnection.setRemoteDescription(new RTCSessionDescription(offer));
            createAnswer();
        }

        async function createAnswer() {
            try {
                const answer = await peerConnection.createAnswer();
                await peerConnection.setLocalDescription(answer);
                ws.send(JSON.stringify({
                    type: 'answer',
                    answer: answer
                }));
            } catch (err) {
                console.error('Error creating answer:', err);
            }
        }

        function handleAnswer(answer) {
            peerConnection.setRemoteDescription(new RTCSessionDescription(answer));
        }

        function handleCandidate(candidate) {
            peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
        }

        function displayChatMessage(message) {
            const chatContainer = document.getElementById('chat-container');
            const messageElement = document.createElement('div');
            messageElement.className = 'chat-message';
            messageElement.textContent = message;
            chatContainer.appendChild(messageElement);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        function sendChatMessage(message) {
            const chatContainer = document.getElementById('chat-container');
            const messageElement = document.createElement('div');
            messageElement.className = 'chat-message';
            messageElement.textContent = `You: ${message}`;
            chatContainer.appendChild(messageElement);
            chatContainer.scrollTop = chatContainer.scrollHeight;

            ws.send(JSON.stringify({
                type: 'chat',
                message: message
            }));
        }

        document.getElementById('toggleVideo').addEventListener('click', () => {
            localStream.getVideoTracks().forEach(track => track.enabled = !track.enabled);
        });

        document.getElementById('toggleAudio').addEventListener('click', () => {
            localStream.getAudioTracks().forEach(track => track.enabled = !track.enabled);
        });

        document.getElementById('endCall').addEventListener('click', () => {
            if (ws) ws.close();
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
            }
            if (peerConnection) {
                peerConnection.close();
            }
            window.location.href = 'doctor_consultation.php';
        });

        document.getElementById('sendMessage').addEventListener('click', () => {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            if (message) {
                sendChatMessage(message);
                input.value = '';
            }
        });

        window.addEventListener('load', initializeCall);
    </script>
</body>
</html>
