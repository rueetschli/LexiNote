# LexiNote 🎙️

LexiNote ist dein persönlicher KI-Assistent für Vorlesungen, Meetings und jede andere Gesprächssituation. Dieses selbst-gehostete Web-Tool transkribiert Gespräche in Echtzeit, beantwortet automatisch gestellte Fragen mithilfe der OpenAI-API und fasst den gesamten Inhalt auf Knopfdruck zusammen. Verpasse nie wieder wichtige Informationen, nur weil die Umgebung zu laut ist oder die Konzentration nachlässt.

Alle Transkripte können sicher auf deinem eigenen Server gespeichert und jederzeit wieder aufgerufen werden.

![Screenshot von LexiNote]
![image](https://github.com/user-attachments/assets/99c14844-7fba-4d1d-aa0c-310c98c72260)
![image](https://github.com/user-attachments/assets/ed1c9ba2-0be6-40d4-92bf-eacf69f2c0f7)


---

## ✨ Features

* **Echtzeit-Transkription:** Verfolge das Gesprochene live als Text auf deinem Bildschirm.
* **Live KI-Fragen & Antworten:** Erkennt automatisch Fragen im Transkript (z.B. "Was ist die Quadratwurzel aus 9?") und lässt die OpenAI-API eine Antwort generieren, die in einer separaten Spalte erscheint. Diese Funktion ist optional an- und abschaltbar.
* **Intelligente Zusammenfassungen:** Erstelle nach jeder Sitzung eine prägnante Zusammenfassung der wichtigsten Kernaussagen und einem Fazit.
* **Flexibler Prompt:** Passe den Befehl für die Zusammenfassung nach deinen Wünschen an, um unterschiedliche Ergebnisse zu erzielen.
* **Sicheres Archiv:** Speichere alle Transkripte mit einem Zeitstempel auf deinem eigenen Server und greife jederzeit darauf zu.
* **Passwortschutz:** Die gesamte Anwendung ist durch ein einfaches Login geschützt.
* **Multi-Source-Input:** Unterstützt sowohl die direkte Mikrofon-Aufnahme als auch das Abgreifen von Audio aus einem anderen Browser-Tab (z.B. für Online-Meetings).

---

## 🛠️ Tech Stack

* **Backend:** PHP
* **Frontend:** Vanilla JavaScript (ES6+), HTML5, CSS3
* **API:** OpenAI (GPT-4o, Whisper)
* **Audioverarbeitung:** Web Audio API (`AudioContext`, `AudioWorklet`)

---

## 🚀 Setup & Installation

Folge diesen Schritten, um LexiNote auf deinem eigenen Webserver zum Laufen zu bringen.

### Voraussetzungen
* Ein Webserver mit PHP-Unterstützung (getestet mit PHP 8+).
* Die `cURL`-Erweiterung für PHP muss aktiviert sein.
* Ein gültiger API-Schlüssel von OpenAI.

### 1. Dateien kopieren
Lade alle Projektdateien herunter (oder klone das Repository) und lade sie auf deinen Webserver hoch.

### 2. Konfiguration
Es gibt eine Vorlagedatei namens `config.template.php`.
1.  **Kopiere** `config.template.php` und benenne die Kopie in `config.php` um.
2.  Öffne `config.php` und bearbeite die folgenden Werte:

    * `OPENAI_API_KEY`: Füge hier deinen geheimen API-Schlüssel von OpenAI ein.
    * `PASSWORD_HASH`: Ersetze den Standard-Hash mit deinem eigenen. Du kannst einen neuen Hash auf der Kommandozeile mit `php -r "echo password_hash('DeinSicheresPasswort', PASSWORD_DEFAULT);"` generieren oder ein Online-Tool nutzen.
    * `SUMMARY_PROMPT`: Passe bei Bedarf den Standard-Prompt für die Zusammenfassungen an.

### 3. Verzeichnisrechte setzen
Stelle sicher, dass dein Webserver die Berechtigung hat, in das Verzeichnis zu schreiben. Erstelle einen Ordner namens `transcripts` im Hauptverzeichnis. Das Skript wird versuchen, diesen Ordner selbst zu erstellen, aber auf manchen Servern sind die Berechtigungen dafür eingeschränkt.

```bash
mkdir transcripts
chmod 755 transcripts
```

### 4. Loslegen
Rufe in deinem Browser die `login.php` auf und melde dich mit dem Passwort an, das du in Schritt 2 festgelegt hast. Fertig!

---

##  license
Dieses Projekt steht unter der MIT-Lizenz. Siehe die `LICENSE`-Datei für weitere Details.
