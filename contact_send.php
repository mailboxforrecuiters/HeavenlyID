<?php
// contact_send.php
// Sends contact form email via PHPMailer.
// Configure with environment variables; do not hardcode SMTP passwords in source control.
// Required-ish env vars:
//   HEAVENLY_SMTP_HOST, HEAVENLY_SMTP_PORT, HEAVENLY_SMTP_USER, HEAVENLY_SMTP_PASS
// Optional env vars:
//   HEAVENLY_MAIL_FROM, HEAVENLY_CONTACT_TO, HEAVENLY_SMTP_SECURE

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  echo json_encode(["success" => false, "error" => "Method not allowed"]);
  exit;
}

$name    = trim($_POST["name"] ?? "");
$email   = trim($_POST["email"] ?? "");
$message = trim($_POST["message"] ?? "");

if ($name === "" || $email === "" || $message === "") {
  http_response_code(400);
  echo json_encode(["success" => false, "error" => "Missing required fields"]);
  exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  echo json_encode(["success" => false, "error" => "Invalid email"]);
  exit;
}

require_once __DIR__ . "/PHPMailer/src/Exception.php";
require_once __DIR__ . "/PHPMailer/src/PHPMailer.php";
require_once __DIR__ . "/PHPMailer/src/SMTP.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$smtpHost = getenv("HEAVENLY_SMTP_HOST") ?: "localhost";
$smtpPort = intval(getenv("HEAVENLY_SMTP_PORT") ?: "25");
$smtpUser = getenv("HEAVENLY_SMTP_USER") ?: "";
$smtpPass = getenv("HEAVENLY_SMTP_PASS") ?: "";
$smtpSecure = strtolower((string)(getenv("HEAVENLY_SMTP_SECURE") ?: ""));

$fromAddress = getenv("HEAVENLY_MAIL_FROM") ?: ($smtpUser !== "" ? $smtpUser : "info@heavenlyid.com");
$toAddress = getenv("HEAVENLY_CONTACT_TO") ?: "info@heavenlyid.com";

$subject = "New Contact Message from {$name}";
$body = "Heavenly ID Admin,\n\n"
      . "You received a new contact message:\n\n"
      . "Name: {$name}\n"
      . "Email: {$email}\n\n"
      . "Message:\n{$message}\n\n"
      . "Replying to this email will reply to the sender.\n";

$mail = new PHPMailer(true);

try {
  $mail->isSMTP();
  $mail->Host = $smtpHost;
  $mail->Port = $smtpPort;
  $mail->CharSet = "UTF-8";

  if ($smtpUser !== "" && $smtpPass !== "") {
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;

    if ($smtpSecure === "ssl") {
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($smtpSecure === "tls" || $smtpSecure === "starttls" || $smtpPort === 587) {
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }
  } else {
    $mail->SMTPAuth = false;
    $mail->SMTPAutoTLS = false;
    $mail->SMTPSecure = false;
  }

  $mail->setFrom($fromAddress, "Heavenly ID Admin");
  $mail->addAddress($toAddress);
  $mail->addReplyTo($email, $name);

  $mail->isHTML(false);
  $mail->Subject = $subject;
  $mail->Body = $body;
  $mail->AltBody = $body;

  $mail->send();

  echo json_encode(["success" => true]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["success" => false, "error" => "Mailer error. Please try again later."]);
}
