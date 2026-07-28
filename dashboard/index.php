<?php
declare(strict_types=1);
require_once __DIR__ . "/auth.php";

if (hid_staff_current()) {
  header("Location: dashboard.php");
  exit;
}

/*
  Exact hardcoded staff credentials requested.

  Printer username:
  info@threadkraze.com

  Admin username:
  goodracer1992@gmail.com

  Password comparison is exact and case-sensitive.
*/
$HID_STAFF_ACCOUNTS = [
  "info@threadkraze.com" => [
    "id" => 1,
    "role" => "printer",
    "name" => "Thread Kraze Printer",
    "email" => "info@threadkraze.com",
    "password" => "Heavenlyid2026!#",
  ],
  "goodracer1992@gmail.com" => [
    "id" => 2,
    "role" => "admin",
    "name" => "Heavenly ID Admin",
    "email" => "goodracer1992@gmail.com",
    "password" => "Heavenlyid2026#!",
  ],
];

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = strtolower(trim((string)($_POST["email"] ?? "")));
  $password = (string)($_POST["password"] ?? "");

  $staff = $HID_STAFF_ACCOUNTS[$email] ?? null;

  if (!$staff || !hash_equals((string)$staff["password"], $password)) {
    $error = "Invalid staff login.";
  } else {
    session_regenerate_id(true);
    $_SESSION["hid_staff_id"] = (int)$staff["id"];
    $_SESSION["hid_staff_role"] = (string)$staff["role"];
    $_SESSION["hid_staff_name"] = (string)$staff["name"];
    $_SESSION["hid_staff_email"] = (string)$staff["email"];

    header("Location: dashboard.php");
    exit;
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Heavenly ID Staff Login</title>
  <style>
    @font-face{font-family:"Minion Pro";src:url("../MinionPro-Medium.woff") format("woff");font-weight:500;font-style:normal;font-display:swap}
    body{margin:0;min-height:100vh;font-family:"Minion Pro",Georgia,serif;background:#f6f1ef url("../bgtest.jpg") center center / cover no-repeat;color:#1d3468;display:flex;align-items:center;justify-content:center;padding:20px}
    .box{width:min(460px,94vw);background:rgba(255,255,255,.78);border:1px solid rgba(216,174,85,.42);border-radius:22px;padding:24px;box-shadow:0 18px 42px rgba(67,51,39,.18);backdrop-filter:blur(8px)}
    h1{margin:0 0 8px;font-size:42px;line-height:1;color:#1d3468}
    p{margin:0 0 18px;color:#5f452f;font-size:18px}
    label{display:block;margin:12px 0 6px;font-weight:800;color:#5f452f}
    input{width:100%;padding:12px 13px;border-radius:12px;border:1px solid rgba(95,69,47,.25);font-size:16px;box-sizing:border-box}
    button{width:100%;margin-top:18px;border:0;border-radius:12px;padding:12px 14px;background:#1d3468;color:#fff;font-size:17px;font-weight:900;cursor:pointer}
    .err{background:#fff0f0;border:1px solid #ffb7b7;color:#a40000;border-radius:12px;padding:10px;margin:10px 0;font-weight:800}
    .hint{margin-top:14px;font-size:13px;color:#6f5336;line-height:1.35}
  </style>
</head>
<body>
  <main class="box">
    <h1>Staff Login</h1>
    <p>Admin / printer dashboard for Heavenly ID designs.</p>

    <?php if ($error): ?><div class="err"><?= hid_staff_h($error) ?></div><?php endif; ?>

    <form method="post" action="index.php" autocomplete="on">
      <label>Email</label>
      <input type="email" name="email" required autocomplete="username" autocapitalize="none" spellcheck="false">

      <label>Password</label>
      <input type="password" name="password" required autocomplete="current-password">

      <button type="submit">Sign In</button>
    </form>

    <div class="hint">
      Passwords are case-sensitive. The printer/admin passwords are different at the final two symbols.
    </div>
  </main>
</body>
</html>
