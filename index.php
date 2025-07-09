<?php
session_start();
require_once 'config.php'; // Wir brauchen die Config für den Standard-Prompt
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Live Transkription</title>
    <link rel="stylesheet" href="styles.css" /> 
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🎙️</text></svg>">
</head>
<body>
    <h1>Live Transkription</h1>
    <p>Klicke auf "Start", um die Echtzeit-Transkription zu beginnen. Nach dem Stoppen kann eine Zusammenfassung erstellt oder das Transkript gespeichert werden.</p>

    <div class="main-container">

        <div class="transcription-column">
            <h2>Live-Untertitel</h2>
            <div class="controls">
                <button id="startBtn" class="btn">Start</button>
                <button id="stopBtn" class="btn" disabled>Stop</button>
                <button id="summarizeBtn" class="btn" style="display: none;">Zusammenfassung</button>
                <button id="saveTranscriptBtn" class="btn btn-secondary" style="display: none;">Transkript & Audio speichern</button>
            </div>
            <div id="transcriptContainer">
                <pre id="transcriptText"></pre>
            </div>
        </div>

        <div class="qa-column">
            <div class="qa-header">
                <h2>Fragen & Antworten</h2>
                <div class="toggle-switch">
                    <input type="checkbox" id="qaToggle" checked>
                    <label for="qaToggle" class="slider"></label>
                    <span>KI aktiv</span>
                </div>
            </div>
            <div id="qaContainer">
                </div>
        </div>

    </div> <div id="archiveContainer">
        <h2>Archiv</h2>
        <ul id="archiveList">
            </ul>
    </div>

    <a href="logout.php" id="logoutBtn" class="btn">Logout</a>

    <div id="summaryModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2>Zusammenfassung der Vorlesung</h2>
            <div id="summaryTextContainer"></div>
            <button id="copySummaryBtn" class="btn" style="margin-top: 20px;">Text kopieren</button>
        </div>
    </div>
    <div id="archiveViewModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2 id="archiveTitle"></h2>
            <div class="modal-scroll-content" id="archiveText"></div>
            <div class="summarize-section">
                <h3>Erneut zusammenfassen</h3>
                <p style="font-size: 0.9rem; margin-bottom: 1rem;">Passe den Befehl an, um die Zusammenfassung nach deinen Wünschen zu gestalten.</p>
                <textarea id="customPrompt" rows="5"></textarea>
                <button id="reSummarizeBtn" class="btn">Zusammenfassung mit diesem Prompt erstellen</button>
                <button id="diarizeBtn" class="btn" style="margin-top: 10px;">Sprecher analysieren</button>
            </div>
        </div>
    </div>
    
    <script>
        const defaultSummaryPrompt = <?php echo json_encode(SUMMARY_PROMPT); ?>;
    </script>
    <script src="transcribe.js"></script>
</body>
</html>
