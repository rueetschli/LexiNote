<?php
session_start();
require_once 'config.php';

// Wenn bereits eingeloggt, direkt zur App weiterleiten
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['password']) && password_verify($_POST['password'], PASSWORD_HASH)) {
        // Passwort korrekt, Session starten und weiterleiten
        $_SESSION['loggedin'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Falsches Passwort.';
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Live Transkription</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Spezifische Styles für die Login-Seite */
        .login-container {
            background-color: var(--color-bg-light);
            padding: 2rem 3rem;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            text-align: center;
        }
        .login-container input {
            padding: 0.8rem;
            margin-top: 1rem;
            border-radius: 8px;
            border: 1px solid var(--color-border);
            width: 100%;
            background-color: var(--color-bg);
            color: var(--color-text);
        }
        .login-container button {
            margin-top: 1.5rem;
            width: 100%;
        }
        .error {
            color: #ff6b6b;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Anmelden</h1>
        <form method="POST" action="login.php">
            <input type="password" name="password" placeholder="Passwort" required>
            <button type="submit" class="btn">Login</button>
        </form>
        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
    </div>
</body>
</html>