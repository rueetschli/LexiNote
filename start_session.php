<?php
// start_session.php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$apiUrl = "https://api.openai.com/v1/realtime/transcription_sessions";

// Stellen sicher, dass alle potenziell nötigen Parameter für eine gültige Session gesetzt sind.
$sessionConfig = [
    'input_audio_format' => 'pcm16',
    'input_audio_transcription' => [
        'model' => 'gpt-4o-transcribe',
        'language' => 'de'
    ],
    // Voice Activity Detection (VAD) ist für Echtzeit-Streaming entscheidend
    'turn_detection' => [
        'type' => 'server_vad',
        'threshold' => 0.5,           // Empfindlichkeit der Spracherkennung (0.0-1.0)
        'silence_duration_ms' => 800, // ms Stille, die ein Segment beenden
        'prefix_padding_ms' => 200    // Puffer am Anfang der Aufnahme
    ],
    // Rauschunterdrückung für Umgebungen mit vielen Störgeräuschen
    'input_audio_noise_reduction' => [
        'type' => 'far_field' // 'far_field' ist optimiert für Mikrofone, die weiter von der Quelle entfernt sind
    ]
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . OPENAI_API_KEY,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sessionConfig));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if ($httpcode !== 200 || !isset($data['client_secret']['value'])) {
    http_response_code($httpcode > 0 ? $httpcode : 500);
    echo json_encode(['error' => 'Token konnte nicht von OpenAI abgerufen werden. Die Konfiguration könnte ungültig sein.', 'details' => $data]);
    exit;
}

echo json_encode(['token' => $data['client_secret']['value']]);