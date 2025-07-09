<?php
// Dein GEHEIMER OpenAI API Key.
define('OPENAI_API_KEY', 'sk-DEIN-API-SCHLUESSEL-HIER');

// Passwort-Hash für den Login.
// Generiere einen neuen Hash z.B. mit: php -r "echo password_hash('DeinSicheresPasswort', PASSWORD_DEFAULT);"
define('PASSWORD_HASH', '$2y$10$w4B.p2G4CVoAlZ1x6AgwUuH19d9s0VzX0Cj8.X.p8s5N2I.s5O2.K'); // Standard: '123456789'

// Standard-Prompt für die Zusammenfassung
define('SUMMARY_PROMPT', 'Du bist ein brillanter Studienassistent. Deine Aufgabe ist es, die folgende Vorlesungsmitschrift zu analysieren. Erstelle eine prägnante Zusammenfassung. Gliedere die Antwort klar und deutlich in die folgenden zwei Abschnitte:
1.  **Kernaussagen:** Fasse die 3-5 wichtigsten Punkte oder Thesen der Vorlesung in Stichpunkten zusammen.
2.  **Fazit:** Schreibe ein kurzes, abschliessendes Fazit, das die Quintessenz des Gesagten auf den Punkt bringt.');
