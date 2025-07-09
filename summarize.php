<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Nicht authorisiert.']);
    exit;
}

require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
$transcript = $input['text'] ?? '';

// KORREKTUR: Nutze den übergebenen Prompt, oder falle auf den Standard-Prompt zurück
$prompt = $input['prompt'] ?? SUMMARY_PROMPT;

if (empty($transcript)) {
    http_response_code(400);
    echo json_encode(['error' => 'Kein Text zur Zusammenfassung erhalten.']);
    exit;
}

$apiUrl = 'https://api.openai.com/v1/chat/completions';
$postData = [
    'model' => 'gpt-4o-mini',
    'messages' => [
        ['role' => 'system', 'content' => $prompt], // Verwende die flexible Prompt-Variable
        ['role' => 'user', 'content' => $transcript]
    ],
    'temperature' => 0.5,
];

// ... der Rest des cURL-Requests bleibt unverändert ...
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . OPENAI_API_KEY,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($httpcode !== 200) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler bei der Anfrage an die OpenAI API.', 'details' => json_decode($response)]);
    exit;
}
$responseData = json_decode($response, true);
$summaryText = $responseData['choices'][0]['message']['content'] ?? 'Keine Zusammenfassung erhalten.';
header('Content-Type: application/json');
echo json_encode(['text' => $summaryText]);