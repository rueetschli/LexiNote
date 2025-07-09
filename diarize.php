<?php
session_start();
require_once 'config.php';

// Schutzmechanismus
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Nicht authorisiert.']);
    exit;
}

header('Content-Type: application/json');

// Dateinamen aus dem GET-Parameter holen
$filename = $_GET['file'] ?? '';
if (empty($filename)) {
    http_response_code(400);
    echo json_encode(['error' => ['message' => 'Kein Dateiname angegeben.']]);
    exit;
}

// Sicherheitscheck und Pfade erstellen
$baseFilename = basename($filename, '.txt');
$audioFilePath = __DIR__ . '/audio/' . $baseFilename . '.webm';

if (!file_exists($audioFilePath)) {
    http_response_code(404);
    echo json_encode(['error' => ['message' => 'Zugehörige Audiodatei nicht gefunden.']]);
    exit;
}

// OpenAI API-Aufruf mit cURL
$apiUrl = 'https://api.openai.com/v1/audio/transcriptions';

// Daten für den POST-Request vorbereiten
$postData = [
    'file' => new CURLFile($audioFilePath, 'audio/webm', $baseFilename . '.webm'),
    'model' => 'whisper-1',
    'response_format' => 'verbose_json', // Wichtig für Sprecher-IDs
    'diarize' => true,                 // Wichtig, um die Analyse zu aktivieren
    'language' => 'de'                 // Verbessert die Genauigkeit
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . OPENAI_API_KEY,
    // Bei multipart/form-data setzt cURL den Content-Type-Header selbst
]);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(['error' => ['message' => 'cURL Fehler: ' . $curlError]]);
    exit;
}

if ($httpcode !== 200) {
    http_response_code($httpcode);
    // Gib die Fehlerantwort von OpenAI direkt weiter
    echo $response;
    exit;
}

// Erfolgreiche Antwort von OpenAI an das Frontend weiterleiten
echo $response;
?>
