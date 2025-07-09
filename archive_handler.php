<?php
session_start();
// Schutzmechanismus
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Nicht authorisiert.']);
    exit;
}

header('Content-Type: application/json');
$action = $_GET['action'] ?? '';
$transcriptDir = __DIR__ . '/transcripts/';

switch ($action) {
    case 'list_files':
        if (!is_dir($transcriptDir)) {
            echo json_encode([]); // Leeres Array, wenn das Verzeichnis nicht existiert
            exit;
        }
        $files = scandir($transcriptDir, SCANDIR_SORT_DESCENDING);
        $result = [];
        foreach ($files as $file) {
            // Filtere nur .txt Dateien und ignoriere '.' und '..'
            if (pathinfo($file, PATHINFO_EXTENSION) === 'txt') {
                $result[] = [
                    'filename' => $file,
                    'date' => filemtime($transcriptDir . $file) // Unix-Timestamp für Sortierung im Frontend
                ];
            }
        }
        echo json_encode($result);
        break;

    case 'get_content':
        $filename = $_GET['file'] ?? '';
        // Sicherheitscheck: Verhindert Directory Traversal Angriffe
        $filename = basename($filename);
        $filepath = $transcriptDir . $filename;

        if ($filename && file_exists($filepath)) {
            echo json_encode(['success' => true, 'content' => file_get_contents($filepath)]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Datei nicht gefunden.']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Ungültige Aktion.']);
        break;
}