// Wir warten, bis die gesamte Seite geladen ist, bevor wir unser Skript ausführen.
document.addEventListener('DOMContentLoaded', () => {

    // --- 1. Alle DOM-Elemente an einem Ort definieren ---
    const startBtn = document.getElementById('startBtn');
    const stopBtn = document.getElementById('stopBtn');
    const transcriptPre = document.getElementById('transcriptText');
    const summarizeBtn = document.getElementById('summarizeBtn');
    const saveTranscriptBtn = document.getElementById('saveTranscriptBtn');
    
    // Q&A Elemente
    const qaContainer = document.getElementById('qaContainer');
    const qaToggle = document.getElementById('qaToggle');

    // Zusammenfassungs-Modal
    const summaryModal = document.getElementById('summaryModal');
    const summaryTextContainer = document.getElementById('summaryTextContainer');
    const copySummaryBtn = document.getElementById('copySummaryBtn');
    const summaryCloseBtn = summaryModal.querySelector('.close-btn');

    // Archiv-Modal
    const archiveList = document.getElementById('archiveList');
    const archiveViewModal = document.getElementById('archiveViewModal');
    const archiveTitle = document.getElementById('archiveTitle');
    const archiveText = document.getElementById('archiveText');
    const customPrompt = document.getElementById('customPrompt');
    const reSummarizeBtn = document.getElementById('reSummarizeBtn');
    const diarizeBtn = document.getElementById('diarizeBtn'); // NEU
    const archiveCloseBtn = archiveViewModal.querySelector('.close-btn');

    // --- 2. Globale Variablen ---
    let ws, audioContext, mediaStream, pcmNode;
    let finalTranscriptSegments = [];
    let currentDeltaText = '';
    let mediaRecorder; // NEU: Für die Audioaufnahme
    let audioChunks = []; // NEU: Speichert die Audio-Daten

    // --- 3. Kernfunktionen ---

    const updateTranscriptDisplay = () => {
        transcriptPre.innerText = finalTranscriptSegments.join(' ') + ' ' + currentDeltaText;
        transcriptPre.parentElement.scrollTop = transcriptPre.parentElement.scrollHeight;
    };

    startBtn.onclick = async () => {
        // UI für einen neuen Lauf zurücksetzen
        summarizeBtn.style.display = 'none';
        saveTranscriptBtn.style.display = 'none';
        saveTranscriptBtn.disabled = false;
        saveTranscriptBtn.textContent = 'Transkript & Audio speichern';
        qaContainer.innerHTML = '';
        qaToggle.disabled = true;
        
        startBtn.disabled = true;
        transcriptPre.innerText = '';
        finalTranscriptSegments = [];
        currentDeltaText = '';
        audioChunks = []; // NEU: Audio-Chunks zurücksetzen

        try {
            const res = await fetch('start_session.php', { method: 'POST' });
            const data = await res.json();
            if (data.error) { throw new Error(data.error + (data.details?.error?.message ? ` - ${data.details.error.message}` : '')); }
            const token = data.token;

            mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
            
            // NEU: MediaRecorder initialisieren
            mediaRecorder = new MediaRecorder(mediaStream, { mimeType: 'audio/webm' });
            mediaRecorder.ondataavailable = event => {
                if (event.data.size > 0) {
                    audioChunks.push(event.data);
                }
            };
            mediaRecorder.start(); // Aufnahme starten

            const wsUrl = "wss://api.openai.com/v1/realtime?intent=transcription";
            const wsProtocols = ["realtime", `openai-insecure-api-key.${token}`, "openai-beta.realtime-v1"];
            ws = new WebSocket(wsUrl, wsProtocols);

            ws.onopen = () => {
                console.log("WebSocket-Verbindung zu OpenAI geöffnet. ✅");
                stopBtn.disabled = false;
                qaToggle.disabled = false;
                initAudioStreaming();
            };
        
            ws.onmessage = ({ data }) => {
                try {
                    const msg = JSON.parse(data);
                    if (msg.type === 'conversation.item.input_audio_transcription.completed') {
                        const final = msg.transcript?.trim();
                        if (final) {
                            finalTranscriptSegments.push(final);
                            if (final.includes('?') && qaToggle.checked) {
                                getAnswerForQuestion(final);
                            }
                        }
                        currentDeltaText = '';
                    } else if (msg.type === 'conversation.item.input_audio_transcription.delta') {
                        currentDeltaText = msg.delta || '';
                    }
                    updateTranscriptDisplay();
                } catch (e) { console.error("Fehler beim Verarbeiten der Server-Nachricht:", e, data); }
            };

            ws.onclose = (event) => { console.log(`WebSocket geschlossen: Code ${event.code}`); cleanup(); };
            ws.onerror = (err) => { console.error('WebSocket Fehler:', err); cleanup(); };
        } catch (err) { alert(`Fehler beim Starten der Session: ${err.message}`); cleanup(); }
    };

    const cleanup = () => {
        if (ws) { ws.close(1000); }
        if (mediaStream) { mediaStream.getTracks().forEach(t => t.stop()); }
        if (pcmNode) { pcmNode.disconnect(); }
        if (audioContext && audioContext.state !== 'closed') { audioContext.close(); }

        // NEU: Aufnahme stoppen
        if (mediaRecorder && mediaRecorder.state === 'recording') {
            mediaRecorder.stop();
        }

        startBtn.disabled = false;
        stopBtn.disabled = true;
        qaToggle.disabled = true;
        console.log("Session bereinigt.");
        if (finalTranscriptSegments.length > 0) {
            summarizeBtn.style.display = 'inline-block';
            saveTranscriptBtn.style.display = 'inline-block';
        }
        loadArchive(); 
    };

    stopBtn.onclick = cleanup;

    function initAudioStreaming() {
        audioContext = new AudioContext({ sampleRate: 16000 });
        audioContext.audioWorklet.addModule('pcmWorklet.js').then(() => {
            const source = audioContext.createMediaStreamSource(mediaStream);
            pcmNode = new AudioWorkletNode(audioContext, 'pcm-worklet');
            pcmNode.port.onmessage = ({ data }) => {
                if (ws && ws.readyState === WebSocket.OPEN) {
                    let binaryString = '';
                    const uint8 = new Uint8Array(data);
                    for (let i = 0; i < uint8.length; i++) { binaryString += String.fromCharCode(uint8[i]); }
                    const payload = { type: 'input_audio_buffer.append', audio: btoa(binaryString) };
                    ws.send(JSON.stringify(payload));
                }
            };
            source.connect(pcmNode);
        }).catch(err => { console.error("Fehler beim Laden des AudioWorklet:", err); cleanup(); });
    }

    // --- 4. Q&A-Funktionalität (unverändert) ---
    async function getAnswerForQuestion(question) {
        const qaItem = document.createElement('div');
        qaItem.className = 'qa-item';
        const questionEl = document.createElement('div');
        questionEl.className = 'question';
        questionEl.textContent = question;
        const answerEl = document.createElement('div');
        answerEl.className = 'answer loading';
        answerEl.textContent = 'Antwort wird gesucht...';
        qaItem.appendChild(questionEl);
        qaItem.appendChild(answerEl);
        qaContainer.prepend(qaItem);
        try {
            const response = await fetch('answer_question.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ question: question })
            });
            const result = await response.json();
            if (!response.ok) { throw new Error(result.error || 'Unbekannter Fehler'); }
            answerEl.textContent = result.answer;
            answerEl.classList.remove('loading');
        } catch (err) {
            answerEl.textContent = `Fehler: ${err.message}`;
            answerEl.style.color = '#ff6b6b';
        }
    }

    // --- 5. Archiv-Funktionalität (mit Anpassungen) ---
    async function loadArchive() {
        try {
            const response = await fetch('archive_handler.php?action=list_files');
            const files = await response.json();
            archiveList.innerHTML = '';
            files.forEach(file => {
                const li = document.createElement('li');
                const date = new Date(file.date * 1000);
                li.textContent = `Transkript vom ${date.toLocaleDateString('de-CH')} um ${date.toLocaleTimeString('de-CH')} Uhr`;
                li.dataset.filename = file.filename;
                li.addEventListener('click', () => openTranscript(file.filename));
                archiveList.appendChild(li);
            });
        } catch (err) {
            console.error("Archiv konnte nicht geladen werden:", err);
            archiveList.innerHTML = '<li>Fehler beim Laden des Archivs.</li>';
        }
    }

    async function openTranscript(filename) {
        try {
            const response = await fetch(`archive_handler.php?action=get_content&file=${encodeURIComponent(filename)}`);
            const data = await response.json();
            if (data.error) throw new Error(data.error);

            const friendlyName = filename.replace('.txt', '').replace('transkript_', '').replace(/_/g, ' um ').replace('Uhr', '') + ' Uhr';
            archiveTitle.textContent = `Archiviertes Transkript: ${friendlyName}`;
            archiveText.textContent = data.content; // Originaltext anzeigen
            customPrompt.value = defaultSummaryPrompt;
            
            // NEU: Dateinamen am Modal speichern für die Diarization
            archiveViewModal.dataset.filename = filename;
            
            // NEU: Diarize-Button initialisieren
            diarizeBtn.disabled = false;
            diarizeBtn.textContent = 'Sprecher analysieren';

            archiveViewModal.style.display = 'block';
        } catch (err) {
            alert(`Fehler beim Öffnen des Transkripts: ${err.message}`);
        }
    }

    // NEU: Funktion zur Sprecheranalyse (Diarization)
    diarizeBtn.onclick = async () => {
        const filename = archiveViewModal.dataset.filename;
        if (!filename) {
            alert('Keine Datei für die Analyse ausgewählt.');
            return;
        }

        const originalText = diarizeBtn.textContent;
        diarizeBtn.disabled = true;
        diarizeBtn.textContent = 'Analysiere...';
        archiveText.textContent = 'Audiodatei wird zur Sprecheranalyse an OpenAI gesendet...';

        try {
            const response = await fetch(`diarize.php?file=${encodeURIComponent(filename)}`);
            const result = await response.json();

            if (!response.ok || result.error) {
                throw new Error(result.error?.message || 'Unbekannter Fehler bei der Analyse.');
            }

            if (result.segments && result.segments.length > 0) {
                // Ordne Sprecher-IDs den Labels A, B, C... zu
                const speakerLabels = {};
                let nextSpeakerLabel = 'A';
                
                const formattedText = result.segments.map(segment => {
                    const speakerId = segment.speaker;
                    if (!(speakerId in speakerLabels)) {
                        speakerLabels[speakerId] = `Sprecher ${nextSpeakerLabel}`;
                        nextSpeakerLabel = String.fromCharCode(nextSpeakerLabel.charCodeAt(0) + 1);
                    }
                    return `${speakerLabels[speakerId]}: ${segment.text.trim()}`;
                }).join('\n');
                
                archiveText.textContent = formattedText;
            } else {
                archiveText.textContent = "Keine Sprecherinformationen gefunden. Das Transkript lautet:\n\n" + result.text;
            }

        } catch (err) {
            alert(`Fehler bei der Sprecheranalyse: ${err.message}`);
            // Bei Fehler den Originaltext wieder laden
            openTranscript(filename);
        } finally {
            diarizeBtn.disabled = false;
            diarizeBtn.textContent = originalText;
        }
    };


    // --- 6. UI-Funktionen (mit Anpassungen beim Speichern) ---
    async function executeSummarization(text, prompt) {
        const btn = document.activeElement.id === 'reSummarizeBtn' ? reSummarizeBtn : summarizeBtn;
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Erstelle...';
        try {
            const response = await fetch('summarize.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ text, prompt })
            });
            if (!response.ok) throw new Error(`Server-Fehler: ${response.statusText}`);
            const summary = await response.json();
            summaryTextContainer.innerText = summary.text;
            summaryModal.style.display = 'block';
        } catch (err) {
            alert(`Fehler bei der Zusammenfassung: ${err.message}`);
        } finally {
            btn.disabled = false;
            btn.textContent = originalText;
        }
    }

    summarizeBtn.onclick = () => executeSummarization(finalTranscriptSegments.join(' '), defaultSummaryPrompt);
    reSummarizeBtn.onclick = () => {
        archiveViewModal.style.display = 'none';
        executeSummarization(archiveText.textContent, customPrompt.value);
    };

    // NEU/GEÄNDERT: Speichert Audio und Text zusammen
    saveTranscriptBtn.onclick = async () => {
        const fullText = finalTranscriptSegments.join('\n\n');
        if (fullText.length < 1 || audioChunks.length === 0) {
            alert('Keine Daten zum Speichern vorhanden.');
            return;
        }

        saveTranscriptBtn.disabled = true;
        saveTranscriptBtn.textContent = 'Speichere...';

        // Erstelle eine einzelne Audiodatei aus den Chunks
        const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });

        // Sende beides (Audio und Text) an den Server
        const formData = new FormData();
        formData.append('text', fullText);
        formData.append('audio', audioBlob, 'aufnahme.webm');

        try {
            // Beachte: Der Endpunkt wurde geändert
            const response = await fetch('save_session.php', {
                method: 'POST',
                body: formData // Kein Content-Type Header bei FormData, der Browser setzt ihn korrekt
            });
            const result = await response.json();
            if (!response.ok) { throw new Error(result.error || 'Unbekannter Serverfehler.'); }
            saveTranscriptBtn.textContent = 'Gespeichert!';
            loadArchive(); // Archiv neu laden, um die neue Datei anzuzeigen
        } catch (err) {
            alert(`Fehler beim Speichern: ${err.message}`);
            saveTranscriptBtn.disabled = false;
            saveTranscriptBtn.textContent = 'Transkript & Audio speichern';
        }
    };

    copySummaryBtn.onclick = async () => {
        try {
            await navigator.clipboard.writeText(summaryTextContainer.innerText);
            copySummaryBtn.textContent = 'Kopiert! ✅';
            setTimeout(() => { copySummaryBtn.textContent = 'Text kopieren'; }, 2000);
        } catch (err) {
            console.error('Fehler beim Kopieren: ', err);
            alert('Konnte den Text nicht kopieren.');
        }
    };

    // Logik zum Schliessen der Modals
    summaryCloseBtn.onclick = () => summaryModal.style.display = 'none';
    archiveCloseBtn.onclick = () => archiveViewModal.style.display = 'none';
    window.onclick = (event) => {
        if (event.target == summaryModal) { summaryModal.style.display = 'none'; }
        if (event.target == archiveViewModal) { archiveViewModal.style.display = 'none'; }
    };

    // --- 7. Initialer Aufruf ---
    loadArchive();
});
