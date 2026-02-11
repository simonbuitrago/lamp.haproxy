<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Deine spezifischen Datenbank-Zugangsdaten
$host = "10.0.1.5";
$user = "appuser";
$pass = "password";
$db   = "myapp";

mysqli_report(MYSQLI_REPORT_OFF); 
$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);

if (!@$conn->real_connect($host, $user, $pass, $db)) {
    $db_error = "Datenbank nicht erreichbar. Läuft der Container?";
}

// Verbindung aufbauen
$conn = new mysqli($host, $user, $pass, $db);

// Tabellen initialisieren (falls noch nicht vorhanden)
if (!$conn->connect_error) {
    $conn->query("CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50) UNIQUE, password VARCHAR(255))");
    $conn->query("CREATE TABLE IF NOT EXISTS todos (id INT AUTO_INCREMENT PRIMARY KEY, task VARCHAR(255), user_id INT)");
} else {
    $db_error = "Verbindung fehlgeschlagen: " . $conn->connect_error;
}

$message = "";

// REGISTRIERUNG
if (isset($_POST['register'])) {
    $u = $conn->real_escape_string($_POST['username']);
    $p = password_hash($_POST['password'], PASSWORD_DEFAULT);
    if ($conn->query("INSERT INTO users (username, password) VALUES ('$u', '$p')")) {
        $message = "Registrierung erfolgreich! Bitte einloggen.";
    } else {
        $message = "Fehler: Benutzername vergeben oder DB-Problem.";
    }
}

// LOGIN
if (isset($_POST['login'])) {
    $u = $conn->real_escape_string($_POST['username']);
    $res = $conn->query("SELECT * FROM users WHERE username='$u'");
    if ($row = $res->fetch_assoc()) {
        if (password_verify($_POST['password'], $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['username'];
        } else { $message = "Falsches Passwort!"; }
    } else { $message = "User nicht gefunden!"; }
}

// LOGOUT
if (isset($_GET['logout'])) { session_destroy(); header("Location: index.php"); exit; }

// TODO HINZUFÜGEN
if (isset($_POST['add_task']) && isset($_SESSION['user_id'])) {
    $t = $conn->real_escape_string($_POST['task']);
    $uid = $_SESSION['user_id'];
    $conn->query("INSERT INTO todos (task, user_id) VALUES ('$t', '$uid')");
}

$todos = isset($_SESSION['user_id']) ? $conn->query("SELECT * FROM todos WHERE user_id=".$_SESSION['user_id']) : null;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Cloud ToDo App</title>
    <style>
        body { font-family: "Segoe UI", Arial, sans-serif; background: #f4f7f6; display: flex; flex-direction: column; align-items: center; margin: 0; }
        .header { background: #2c3e50; color: white; width: 100%; text-align: center; padding: 15px; }
        .box { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); width: 350px; margin-top: 20px; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #27ae60; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .btn-reg { background: #2980b9; margin-top: 5px; }
        .error { color: #e74c3c; font-weight: bold; text-align: center; }
        .server-info { font-size: 0.8em; color: #bdc3c7; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Backend Server: <?php echo gethostname(); ?></h2>
    </div>

    <div class="box">
        <?php if(isset($db_error)) echo "<p class='error'>$db_error</p>"; ?>
        <p class="error"><?php echo $message; ?></p>

        <?php if (!isset($_SESSION['user_id'])): ?>
            <h3 style="text-align: center;">Login / Registrierung</h3>
            <form method="post">
                <input type="text" name="username" placeholder="Benutzername" required>
                <input type="password" name="password" placeholder="Passwort" required>
                <button type="submit" name="login">Einloggen</button>
                <button type="submit" name="register" class="btn-reg">Registrieren</button>
            </form>
        <?php else: ?>
            <p>Eingeloggt als: <b><?php echo htmlspecialchars($_SESSION['user_name']); ?></b> | <a href="?logout=1">Abmelden</a></p>
            <hr>
            <form method="post">
                <input type="text" name="task" placeholder="Was ist zu tun?" required>
                <button type="submit" name="add_task">Hinzufügen</button>
            </form>
            <ul style="text-align: left; margin-top: 20px;">
                <?php if($todos) while($row = $todos->fetch_assoc()): ?>
                    <li><?php echo htmlspecialchars($row['task']); ?></li>
                <?php endwhile; ?>
            </ul>
        <?php endif; ?>
    </div>
    <div class="server-info">Anfrage verarbeitet von: <?php echo $_SERVER['SERVER_ADDR']; ?></div>
</body>
</html>
