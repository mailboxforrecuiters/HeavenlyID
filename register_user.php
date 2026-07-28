<?php
session_start();
header('Content-Type: application/json');
$response = ['success' => false];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    // --- Get POST data ---
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';

    // --- Validation ---
    if (!$first_name || !$last_name || !$email || !$phone || !$password || !$confirm) {
        throw new Exception('Please fill out all required fields.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format.');
    }
    if (strlen($password) < 12 || !preg_match('/[A-Z]/', $password) || !preg_match('/[^a-zA-Z0-9]/', $password)) {
        throw new Exception('Password must be 12+ chars, 1 uppercase, 1 special char.');
    }
    if ($password !== $confirm) {
        throw new Exception('Passwords do not match.');
    }

    // --- Database connection ---
    require_once __DIR__ . "/protected/pdo.php";

    // --- Check if email exists ---
    $stmt = $pdo->prepare("SELECT site_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception('Email already registered.');
    }

    // --- Hash password for main site ---
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // --- Insert main site user ---
    $stmt = $pdo->prepare("INSERT INTO users (first_name,last_name,email,phone,password,created_at) VALUES (?,?,?,?,?,NOW())");
    $stmt->execute([$first_name,$last_name,$email,$phone,$password_hash]);
    $site_id = $pdo->lastInsertId();

    // --- phpBB 3.3 integration ---
    $phpbbPrefix = 'phpbb_';
    $base_username = strtoupper(substr($first_name,0,1)) . '.' . ucfirst($last_name);
    $username = $base_username;
    $username_clean = strtolower($username);
    $counter = 1;
    $stmt = $pdo->prepare("SELECT user_id FROM {$phpbbPrefix}users WHERE username_clean=?");
    while (true) {
        $stmt->execute([$username_clean]);
        if (!$stmt->fetch()) break;
        $username = $base_username.$counter;
        $username_clean = strtolower($username);
        $counter++;
    }

    // Include phpBB hashing
    require_once __DIR__.'/form/includes/functions.php';
    $phpbb_password_hash = phpbb_hash($password);

    // Insert phpBB user
    $stmt = $pdo->prepare("
        INSERT INTO {$phpbbPrefix}users 
        (user_type, group_id, user_permissions, user_perm_from, user_ip, user_regdate, username, username_clean, user_password, user_email, user_new, site_id)
        VALUES
        (0, 2, '', 0, ?, ?, ?, ?, ?, ?, 1, ?)
    ");
    $stmt->execute([
        $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        time(),
        $username,
        $username_clean,
        $phpbb_password_hash,
        $email,
        $site_id
    ]);
    $phpbb_user_id = $pdo->lastInsertId();

    // --- Create main site session ---
    $_SESSION['user_id'] = $site_id;
    $_SESSION['username'] = $first_name.' '.$last_name;
    $_SESSION['email'] = $email;

    // --- Create phpBB session (auto-login) ---
    require_once __DIR__.'/forum/includes/constants.php';
    require_once __DIR__.'/forum/includes/functions.php';
    require_once __DIR__.'/forum/includes/functions_session.php';
    require_once __DIR__.'/forum/includes/sessions.php';

    // phpBB session creation
    $user_row = [
        'user_id' => $phpbb_user_id,
        'username' => $username,
        'user_password' => $phpbb_password_hash,
        'user_email' => $email,
    ];

    $phpbb_session = new session();
    $phpbb_session->session_create($user_row['user_id'], $user_row['user_id'], true);

    $response['success'] = true;
    $response['username'] = $_SESSION['username'];
    $response['forum_username'] = $username;

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
exit;
