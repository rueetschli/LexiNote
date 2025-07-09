<?php
session_start();
// Schutzmechanismus
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Nicht authorisiert.']);
    exit;
}

require_once 'config.php';

// Empfange die Frage vom Frontend
$input = json_decode(file_get_contents('php://input'), true);
$question = $input['question'] ?? '';

if (empty($question)) {
    http_response_code(400);
    echo json_encode(['error' => 'Keine Frage erhalten.']);
    exit;
}

// Bereite den Request für die OpenAI Chat API vor
$apiUrl = 'https://api.openai.com/v1/chat/completions';
$postData = [
    'model' => 'gpt-4o-mini', // Schnell und kosteneffizient für kurze Antworten
    'messages' => [
        // Ein System-Prompt, der die KI anweist, sich wie ein Experte zu verhalten
        ['role' => 'system', 'content' => 'Du bist ein allwissender Experte. Beantworte die folgende Frage kurz, präzise und korrekt.'],
        ['role' => 'user', 'content' => $question]
    ],
    'temperature' => 0.3, // Eine niedrige Temperatur für faktenbasierte, weniger kreative Antworten
    'max_tokens' => 150   // Begrenzt die Länge der Antwort
];

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
$answerText = $responseData['choices'][0]['message']['content'] ?? 'Ich konnte keine Antwort finden.';

header('Content-Type: application/json');
echo json_encode(['answer' => $answerText]);