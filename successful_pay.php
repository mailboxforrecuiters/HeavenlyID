<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . "/protected/pdo.php";

$t = $_GET["t"] ?? "";
if (!is_string($t) || !preg_match('/^[a-f0-9]{64}$/', $t)) {
  http_response_code(400);
  exit("Invalid token");
}

$stmt = $pdo->prepare("
  SELECT id, user_site_id, design_title, is_paid, download_token
  FROM card_designs_v2
  WHERE checkout_token = ?
    AND owner_type = 'user'
  LIMIT 1
");
$stmt->execute([$t]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  http_response_code(404);
  exit("Design not found");
}

if (!isset($_SESSION['user_id']) || (int)$row["user_site_id"] !== (int)$_SESSION["user_id"]) {
  http_response_code(403);
  exit("Not authorized for this design.");
}

$isPaid = ((int)$row["is_paid"] === 1 && !empty($row["download_token"]));
$downloadUrl = $isPaid ? ("/download_design.php?token=" . urlencode($row["download_token"])) : "";
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Payment Status</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:0;padding:40px;background:#f6f6f6;}
    .card{max-width:720px;margin:0 auto;background:#fff;border:1px solid #e5e5e5;border-radius:14px;padding:18px 16px;box-shadow:0 12px 30px rgba(0,0,0,.08);}
    .btn{display:inline-block;padding:12px 14px;border-radius:10px;background:#1f6feb;color:#fff;text-decoration:none;font-weight:700;}
    .muted{color:#666;line-height:1.5;}
    .warn{background:#fff7e6;border:1px solid #ffe1a6;padding:12px;border-radius:12px;}
  </style>
</head>
<body>
  <div class="card">
    <h2>Payment Status</h2>
    <p class="muted">Design: <strong><?= htmlspecialchars($row["design_title"] ?? ("Design #" . $row["id"]), ENT_QUOTES) ?></strong></p>

    <?php if ($isPaid): ?>
      <p class="muted">Payment verified. Your design bundle is ready.</p>
      <p><a class="btn" href="<?= htmlspecialchars($downloadUrl, ENT_QUOTES) ?>">Download Design Bundle</a></p>
    <?php else: ?>
      <div class="warn">
        <p class="muted" style="margin:0;">
          We are still confirming payment with Shopify.<br>
          Please refresh in a few seconds.
        </p>
      </div>
      <p style="margin-top:14px;">
        <a class="btn" href="<?= htmlspecialchars($_SERVER["REQUEST_URI"], ENT_QUOTES) ?>" style="background:#111;">Refresh</a>
      </p>
    <?php endif; ?>
  </div>
</body>
</html>
