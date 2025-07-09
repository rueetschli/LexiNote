<?php
session_start();
// Schutzmechanismus
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Nicht authorisiert.']);
    exit;
}

header('Content-Type: application/json');

// Verzeichnisse definieren und ggf. erstellen
$transcriptDir = __DIR__ . '/transcripts/';
$audioDir = __DIR__ . '/audio/';

if (!is_dir($transcriptDir)) {
    mkdir($transcriptDir, 0775, true);
}
if (!is_dir($audioDir)) {
    mkdir($audioDir, 0775, true);
}

// Überprüfen, ob alle benötigten Daten vorhanden sind
if (!isset($_POST['text']) || !isset($_FILES['audio'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Fehlende Text- oder Audiodaten.']);
    exit;
}

$transcriptText = $_POST['text'];
$audioFile = $_FILES['audio'];

// Fehler beim Upload der Audiodatei prüfen
if ($audioFile['error'] !== UPLOAD_ERR_OK) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Upload der Audiodatei. Code: ' . $audioFile['error']]);
    exit;
}

// Dateinamen generieren (basierend auf Datum und Zeit)
date_default_timezone_set('Europe/Zurich');
$dateString = date('Y-m-d_H-i-s');
$baseFilename = "transkript_{$dateString}";

$transcriptPath = $transcriptDir . $baseFilename . '.txt';
$audioPath = $audioDir . $baseFilename . '.webm';

// 1. Textdatei speichern
$saveTextSuccess = file_put_contents($transcriptPath, $transcriptText);

// 2. Audiodatei verschieben
$moveAudioSuccess = move_uploaded_file($audioFile['tmp_name'], $audioPath);

if ($saveTextSuccess !== false && $moveAudioSuccess) {
    echo json_encode(['success' => true, 'message' => 'Transkript und Audio erfolgreich gespeichert.', 'filename' => $baseFilename]);
} else {
    // Falls ein Fehler auftritt, versuchen, bereits erstellte Dateien zu löschen
    if (file_exists($transcriptPath)) {
        unlink($transcriptPath);
    }
    if (file_exists($audioPath)) {
        unlink($audioPath);
    }
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Speichern der Dateien auf dem Server.']);
}
?>
