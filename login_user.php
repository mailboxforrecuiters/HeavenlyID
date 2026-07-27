<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . "/protected/pdo.php";
} catch (Throwable $e) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["error" => "Invalid request"]);
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    echo json_encode(["error" => "Email and password required"]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        site_id,
        first_name,
        last_name,
        email,
        password
    FROM users
    WHERE email = :email
    LIMIT 1
");
$stmt->execute(['email' => $email]);
$siteUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$siteUser || !password_verify($password, $siteUser['password'])) {
    echo json_encode(["error" => "Invalid email or password"]);
    exit;
}

$phpbbUser = null;
try {
    $stmt = $pdo->prepare("
        SELECT user_id
        FROM phpbb_users
        WHERE user_email = :email
          AND user_type <> 2
        LIMIT 1
    ");
    $stmt->execute(['email' => $email]);
    $phpbbUser = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $phpbbUser = null;
}

session_regenerate_id(true);

$_SESSION['user_id']    = $siteUser['site_id'];
$_SESSION['first_name'] = $siteUser['first_name'];
$_SESSION['last_name']  = $siteUser['last_name'];
$_SESSION['email']      = $siteUser['email'];
$_SESSION['username']   = trim($siteUser['first_name'] . ' ' . $siteUser['last_name']);
$_SESSION['logged_in']  = true;
$_SESSION['phpbb_id']   = $phpbbUser['user_id'] ?? null;

echo json_encode(["success" => true]);
