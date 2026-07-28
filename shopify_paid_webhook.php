<?php
// shopify_paid_webhook.php
//
// Handles the new card_designs_v2 workflow while preserving Shopify HMAC verification.
//
// Registered user flow:
// - Shopify design_id is numeric = card_designs_v2.id
// - Marks card_designs_v2.is_paid=1 and sets download_token if missing
// - Emails info@threadkraze.com with the paid card design information
//
// Guest flow:
// - Shopify design_id is 20-character code = card_designs_v2.design_code
// - Marks card_designs_v2.is_paid=1
// - Emails info@threadkraze.com with the paid card design information
// - Emails the guest ONLY AFTER payment is confirmed
//
// Shopify webhook topic should be:
// Order payment / orders paid
//
// Webhook URL should be:
// https://www.heavenlyid.com/shopify_paid_webhook.php

require_once __DIR__ . "/protected/pdo.php";

function hid_webhook_log($message) {
  $line = "[" . date("Y-m-d H:i:s") . "] " . $message . "\n";
  @file_put_contents(__DIR__ . "/shopify_webhook_debug.log", $line, FILE_APPEND);
}

function hid_webhook_phpmailer_bootstrap(): bool {
  if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) return true;

  $files = [
    __DIR__ . "/PHPMailer/src/Exception.php",
    __DIR__ . "/PHPMailer/src/PHPMailer.php",
    __DIR__ . "/PHPMailer/src/SMTP.php",
  ];

  foreach ($files as $file) {
    if (!is_file($file)) return false;
  }

  require_once $files[0];
  require_once $files[1];
  require_once $files[2];

  return class_exists('\\PHPMailer\\PHPMailer\\PHPMailer');
}

function hid_webhook_email_line(string $label, $value): string {
  $value = trim((string)$value);
  return $label . ": " . ($value !== "" ? $value : "-") . "\r\n";
}

function hid_webhook_customer_name(array $row): string {
  $first = trim((string)($row["user_first_name"] ?? ""));
  $last  = trim((string)($row["user_last_name"] ?? ""));

  if ($first !== "" || $last !== "") {
    return trim($first . " " . $last);
  }

  $full = trim((string)($row["full_name"] ?? ""));
  return $full !== "" ? $full : "Heavenly ID Customer";
}

function hid_webhook_customer_email(array $row): string {
  $email = trim((string)($row["user_email"] ?? ""));
  if ($email !== "") return $email;
  return trim((string)($row["guest_email"] ?? ""));
}

function hid_webhook_build_threadkraze_body(array $row, $orderId, string $orderName): string {
  $body = "";
  $body .= "Heavenly ID Paid Card Design\r\n";
  $body .= "=============================\r\n\r\n";

  $body .= hid_webhook_email_line("Shopify Order ID", $orderId);
  $body .= hid_webhook_email_line("Shopify Order Name", $orderName);
  $body .= hid_webhook_email_line("Design ID", $row["id"] ?? "");
  $body .= hid_webhook_email_line("Design Code", $row["design_code"] ?? "");
  $body .= hid_webhook_email_line("Owner Type", $row["owner_type"] ?? "");
  $body .= hid_webhook_email_line("Customer First Name", $row["user_first_name"] ?? "");
  $body .= hid_webhook_email_line("Customer Last Name", $row["user_last_name"] ?? "");
  $body .= hid_webhook_email_line("Customer Email", hid_webhook_customer_email($row));
  $body .= "\r\n";

  $body .= "Saved Card Information\r\n";
  $body .= "----------------------\r\n";
  $body .= hid_webhook_email_line("Design Title", $row["design_title"] ?? "");
  $body .= hid_webhook_email_line("Full Name On Card", $row["full_name"] ?? "");
  $body .= hid_webhook_email_line("I AM / Status", $row["iam_status"] ?? "");
  $body .= hid_webhook_email_line("Spiritual Gifts", $row["spiritual_gifts"] ?? "");
  $body .= hid_webhook_email_line("Received Jesus Date", $row["received_jesus_date"] ?? "");
  $body .= hid_webhook_email_line("Favorite Bible Verse", $row["favorite_verse_ref"] ?? "");
  $body .= hid_webhook_email_line("Verse Text", $row["verse_text"] ?? "");
  $body .= hid_webhook_email_line("Letter Of Intent", $row["letter_of_intent"] ?? "");
  $body .= hid_webhook_email_line("Name Font Resized", $row["name_font_resized"] ?? "");
  $body .= hid_webhook_email_line("Name Font Size Px", $row["name_font_size_px"] ?? "");
  $body .= hid_webhook_email_line("Letter Font Resized", $row["letter_font_resized"] ?? "");
  $body .= hid_webhook_email_line("Letter Font Size Px", $row["letter_font_size_px"] ?? "");
  $body .= "\r\n";

  $body .= "Selected Card Assets\r\n";
  $body .= "--------------------\r\n";
  $body .= hid_webhook_email_line("Front Theme File", $row["front_theme_file"] ?? "");
  $body .= hid_webhook_email_line("Back Theme File", $row["back_theme_file"] ?? "");
  $body .= hid_webhook_email_line("Front Theme Style", $row["front_theme_style"] ?? "");
  $body .= hid_webhook_email_line("Foreground Image", $row["foreground_file"] ?? "");
  $body .= hid_webhook_email_line("Preview Front PNG Path", $row["preview_front_png_path"] ?? "");
  $body .= hid_webhook_email_line("Preview Back PNG Path", $row["preview_back_png_path"] ?? "");
  $body .= "\r\n";

  $body .= "Payment / Fulfillment\r\n";
  $body .= "---------------------\r\n";
  $body .= hid_webhook_email_line("Paid", $row["is_paid"] ?? "");
  $body .= hid_webhook_email_line("Paid At", $row["paid_at"] ?? "");
  $body .= hid_webhook_email_line("Download Token", $row["download_token"] ?? "");
  $body .= hid_webhook_email_line("Print File Path", $row["print_file_path"] ?? "");
  $body .= "\r\n";

  $body .= "Printer note: use the original high-resolution assets matching the filenames above.\r\n";

  return $body;
}

function hid_webhook_send_threadkraze_design_email(array $row, $orderId, string $orderName): bool {
  $customerName = hid_webhook_customer_name($row);
  $subjectTitle = trim((string)($row["design_title"] ?? ""));
  $subject = "Paid Card Design Information";
  if ($subjectTitle !== "") $subject .= " - " . $subjectTitle;
  $subject .= " - " . $customerName;

  $body = hid_webhook_build_threadkraze_body($row, $orderId, $orderName);
  $replyTo = hid_webhook_customer_email($row);

  @file_put_contents(
    __DIR__ . "/threadkraze_paid_design_email.log",
    "[" . date("Y-m-d H:i:s") . "] Paid design email for design " . (string)($row["id"] ?? "") . "\n" . $body . "\n\n",
    FILE_APPEND
  );

  if (hid_webhook_phpmailer_bootstrap()) {
    try {
      $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
      $mail->isSMTP();
      $mail->Host = "localhost";
      $mail->Port = 25;
      $mail->SMTPAuth = false;
      $mail->SMTPAutoTLS = false;
      $mail->SMTPSecure = false;

      $mail->CharSet = "UTF-8";
      $mail->setFrom("info@heavenlyid.com", "Heavenly ID");
      $mail->addReplyTo($replyTo !== "" ? $replyTo : "info@heavenlyid.com", $customerName);
      $mail->addAddress("info@threadkraze.com", "Thread Kraze");

      $mail->Subject = $subject;
      $mail->Body = $body;
      $mail->AltBody = $body;
      $mail->isHTML(false);

      $mail->send();

      hid_webhook_log("Thread Kraze paid design email sent. Design ID: " . (string)($row["id"] ?? ""));
      return true;
    } catch (Throwable $e) {
      hid_webhook_log("Thread Kraze paid design PHPMailer failed: " . $e->getMessage());
    }
  }

  $headers = [
    "From: Heavenly ID <info@heavenlyid.com>",
    "Reply-To: " . ($replyTo !== "" ? $replyTo : "info@heavenlyid.com"),
    "Content-Type: text/plain; charset=UTF-8",
  ];

  $ok = @mail("info@threadkraze.com", $subject, $body, implode("\r\n", $headers));
  hid_webhook_log("Thread Kraze paid design mail() fallback: " . ($ok ? "sent" : "failed"));
  return (bool)$ok;
}

function hid_webhook_send_guest_paid_email(string $guestEmail, string $guestCode): void {
  if ($guestEmail === "" || $guestCode === "") return;

  $host = $_SERVER["HTTP_HOST"] ?? "heavenlyid.com";
  $scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
  $link = $scheme . "://" . $host . "/guest_download.php?code=" . urlencode($guestCode);

  $subject = "Heavenly ID Payment Confirmed — Your Download Code";

  $message =
    "Thanks! Your Heavenly ID payment is confirmed.\r\n\r\n" .
    "Your access code:\r\n" . $guestCode . "\r\n\r\n" .
    "Download here:\r\n" . $link . "\r\n\r\n" .
    "If you created multiple designs with this email, you'll see them listed on the download page.\r\n";

  if (hid_webhook_phpmailer_bootstrap()) {
    try {
      $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
      $mail->isSMTP();
      $mail->Host = "localhost";
      $mail->Port = 25;
      $mail->SMTPAuth = false;
      $mail->SMTPAutoTLS = false;
      $mail->SMTPSecure = false;

      $mail->CharSet = "UTF-8";
      $mail->setFrom("info@heavenlyid.com", "Heavenly ID");
      $mail->addReplyTo("info@heavenlyid.com", "Heavenly ID");
      $mail->addAddress($guestEmail);

      $mail->Subject = $subject;
      $mail->Body = $message;
      $mail->AltBody = $message;
      $mail->isHTML(false);

      $mail->send();
      hid_webhook_log("Guest paid email sent by PHPMailer. Email: " . $guestEmail);
      return;
    } catch (Throwable $e) {
      hid_webhook_log("Guest paid PHPMailer failed: " . $e->getMessage());
    }
  }

  $headers = [
    "From: Heavenly ID <info@heavenlyid.com>",
    "Reply-To: info@heavenlyid.com",
    "Content-Type: text/plain; charset=UTF-8",
  ];

  @mail($guestEmail, $subject, $message, implode("\r\n", $headers));
  hid_webhook_log("Guest paid email attempted by mail(). Email: " . $guestEmail);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  echo "Method not allowed";
  exit;
}

$body = file_get_contents("php://input");
$hmacHeader = $_SERVER["HTTP_X_SHOPIFY_HMAC_SHA256"] ?? "";

// IMPORTANT:
// This is the Shopify webhook signing secret shown on your Shopify Webhooks screen.
$secret = "17ed626cd2d0bd00db1baa046071d43eaecc3dfbdfaecedb5488e3cccb5b7fd8";

hid_webhook_log("Webhook hit. Body length: " . strlen($body));

if ($hmacHeader === "") {
  hid_webhook_log("Missing HMAC header.");
  http_response_code(401);
  echo "Missing webhook signature";
  exit;
}

$calculated = base64_encode(hash_hmac("sha256", $body, $secret, true));

if (!hash_equals($calculated, $hmacHeader)) {
  hid_webhook_log("Invalid signature. Header: " . $hmacHeader . " Calculated: " . $calculated);
  http_response_code(401);
  echo "Invalid webhook signature";
  exit;
}

$payload = json_decode($body, true);

if (!is_array($payload)) {
  hid_webhook_log("Invalid JSON.");
  http_response_code(400);
  echo "Invalid JSON";
  exit;
}

$orderId = $payload["id"] ?? null;
$orderName = (string)($payload["name"] ?? "");
$financialStatus = strtolower(trim((string)($payload["financial_status"] ?? "")));

hid_webhook_log("Verified webhook. Order ID: " . (string)$orderId . " Order name: " . $orderName . " Financial status: " . $financialStatus);

if (!$orderId) {
  hid_webhook_log("Missing order id.");
  http_response_code(400);
  echo "Missing order id";
  exit;
}

if ($financialStatus !== "paid") {
  hid_webhook_log("Ignored because financial_status is not paid.");
  http_response_code(200);
  echo "ignored - not paid";
  exit;
}

$designRef = "";

if (!empty($payload["note_attributes"]) && is_array($payload["note_attributes"])) {
  foreach ($payload["note_attributes"] as $attr) {
    $name  = (string)($attr["name"] ?? "");
    $value = (string)($attr["value"] ?? "");

    if ($name === "design_id" && trim($value) !== "") {
      $designRef = trim($value);
      break;
    }
  }
}

if ($designRef === "" && !empty($payload["line_items"]) && is_array($payload["line_items"])) {
  foreach ($payload["line_items"] as $li) {
    $props = $li["properties"] ?? null;
    if (!is_array($props)) {
      continue;
    }

    foreach ($props as $key => $p) {
      if (is_array($p)) {
        $name  = (string)($p["name"] ?? "");
        $value = (string)($p["value"] ?? "");

        if ($name === "design_id" && trim($value) !== "") {
          $designRef = trim($value);
          break 2;
        }
      }

      if ((string)$key === "design_id" && trim((string)$p) !== "") {
        $designRef = trim((string)$p);
        break 2;
      }
    }
  }
}

if ($designRef === "" && !empty($payload["line_items"]) && is_array($payload["line_items"])) {
  foreach ($payload["line_items"] as $li) {
    $attrs = $li["customAttributes"] ?? null;
    if (!is_array($attrs)) continue;

    foreach ($attrs as $attr) {
      $key = (string)($attr["key"] ?? $attr["name"] ?? "");
      $value = (string)($attr["value"] ?? "");
      if ($key === "design_id" && trim($value) !== "") {
        $designRef = trim($value);
        break 2;
      }
    }
  }
}

$designRef = trim((string)$designRef);

hid_webhook_log("Extracted designRef: " . ($designRef !== "" ? $designRef : "[empty]"));

$designId = 0;
$designCode = "";

if ($designRef !== "" && ctype_digit($designRef)) {
  $designId = (int)$designRef;
} else if ($designRef !== "") {
  $u = strtoupper($designRef);
  if (preg_match('/^[A-Z0-9]{20}$/', $u)) {
    $designCode = $u;
  }
}

if ($designId <= 0 && $designCode === "") {
  hid_webhook_log("No usable design_id/design code found. Returning 200 ignored.");
  http_response_code(200);
  echo "ignored - missing design_id";
  exit;
}

$sendGuestPaidEmail = false;
$guestEmail = "";
$guestCode = $designCode;
$emailRow = null;

try {
  $pdo->beginTransaction();

  if ($designId > 0) {
    $check = $pdo->prepare("
      SELECT
        c.*,
        u.first_name AS user_first_name,
        u.last_name AS user_last_name,
        u.email AS user_email
      FROM card_designs_v2 c
      LEFT JOIN users u
        ON u.site_id = c.user_site_id
      WHERE c.id = ?
        AND c.owner_type = 'user'
      LIMIT 1
    ");
    $check->execute([$designId]);
    $design = $check->fetch(PDO::FETCH_ASSOC);

    if (!$design) {
      $pdo->rollBack();
      hid_webhook_log("Registered design not found. ID: " . $designId);
      http_response_code(404);
      echo "Design not found";
      exit;
    }

    $existingToken = (string)($design["download_token"] ?? "");
    $token = $existingToken !== "" ? $existingToken : bin2hex(random_bytes(32));

    $upd = $pdo->prepare("
      UPDATE card_designs_v2
      SET
        is_paid = 1,
        paid_at = COALESCE(paid_at, NOW()),
        shopify_order_id = COALESCE(shopify_order_id, ?),
        shopify_order_name = COALESCE(shopify_order_name, ?),
        download_token = COALESCE(download_token, ?),
        updated_at = NOW()
      WHERE id = ?
        AND owner_type = 'user'
      LIMIT 1
    ");
    $upd->execute([(string)$orderId, $orderName, $token, $designId]);

    $emailStmt = $pdo->prepare("
      SELECT
        c.*,
        u.first_name AS user_first_name,
        u.last_name AS user_last_name,
        u.email AS user_email
      FROM card_designs_v2 c
      LEFT JOIN users u
        ON u.site_id = c.user_site_id
      WHERE c.id = ?
        AND c.owner_type = 'user'
      LIMIT 1
    ");
    $emailStmt->execute([$designId]);
    $emailRow = $emailStmt->fetch(PDO::FETCH_ASSOC) ?: $design;
  } else {
    $stmt = $pdo->prepare("
      SELECT *
      FROM card_designs_v2
      WHERE design_code = ?
        AND owner_type = 'guest'
      LIMIT 1
    ");
    $stmt->execute([$designCode]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
      $pdo->rollBack();
      hid_webhook_log("Guest design not found. Code: " . $designCode);
      http_response_code(404);
      echo "Guest design not found";
      exit;
    }

    $wasPaid = ((int)$row["is_paid"] === 1);
    $guestEmail = (string)($row["guest_email"] ?? "");
    $sendGuestPaidEmail = (!$wasPaid && $guestEmail !== "");

    $upd = $pdo->prepare("
      UPDATE card_designs_v2
      SET
        is_paid = 1,
        paid_at = COALESCE(paid_at, NOW()),
        shopify_order_id = COALESCE(shopify_order_id, ?),
        shopify_order_name = COALESCE(shopify_order_name, ?),
        updated_at = NOW()
      WHERE id = ?
        AND owner_type = 'guest'
      LIMIT 1
    ");
    $upd->execute([(string)$orderId, $orderName, (int)$row["id"]]);

    $emailStmt = $pdo->prepare("
      SELECT *
      FROM card_designs_v2
      WHERE design_code = ?
        AND owner_type = 'guest'
      LIMIT 1
    ");
    $emailStmt->execute([$designCode]);
    $emailRow = $emailStmt->fetch(PDO::FETCH_ASSOC) ?: $row;
  }

  $pdo->commit();

  if ($emailRow) {
    hid_webhook_send_threadkraze_design_email($emailRow, $orderId, $orderName);
  }

  if ($designId > 0) {
    hid_webhook_log("Registered design marked paid. Design ID: " . $designId . " Order ID: " . (string)$orderId);
  } else {
    hid_webhook_log("Guest design marked paid. Code: " . $designCode . " Order ID: " . (string)$orderId);
  }
} catch (Throwable $e) {
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }

  hid_webhook_log("Server error: " . $e->getMessage());

  http_response_code(500);
  echo "Server error";
  exit;
}

if ($sendGuestPaidEmail) {
  hid_webhook_send_guest_paid_email($guestEmail, $guestCode);
}

http_response_code(200);
echo "ok";
exit;
