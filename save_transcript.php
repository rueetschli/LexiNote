<?php
session_start();
// Schutzmechanismus
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Nicht authorisiert.']);
    exit;
}

// Empfange den Text vom Frontend
$input = json_decode(file_get_contents('php://input'), true);
$transcript = $input['text'] ?? '';

if (empty($transcript)) {
    http_response_code(400);
    echo json_encode(['error' => 'Kein Text zum Speichern erhalten.']);
    exit;
}

// Definiere den Speicherort
$directory = __DIR__ . '/transcripts/';

// Erstelle das Verzeichnis, falls es nicht existiert
if (!is_dir($directory)) {
    mkdir($directory, 0755, true);
}

// Erstelle einen sicheren Dateinamen mit Zeitstempel
date_default_timezone_set('Europe/Zurich');
$filename = 'transkript_' . date('Y-m-d_H-i-s') . '.txt';
$filepath = $directory . $filename;

// Schreibe den Inhalt in die Datei
if (file_put_contents($filepath, $transcript) !== false) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => "Transkript gespeichert als: $filename"]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Datei konnte nicht auf dem Server gespeichert werden.']);
}