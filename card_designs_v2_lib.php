<?php
declare(strict_types=1);

/**
 * Heavenly ID card_designs_v2 helper functions.
 * Requires the including endpoint to load protected/pdo.php first when $pdo is needed.
 */

function hid_v2_json(array $payload, int $code = 200): void {
  http_response_code($code);
  header("Content-Type: application/json; charset=utf-8");
  echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
  exit;
}

function hid_v2_clean_text($value, int $max = 255): string {
  $s = trim((string)$value);
  $s = str_replace(["\r\n", "\r"], "\n", $s);
  $s = preg_replace('/[^\P{C}\n\t]+/u', '', $s) ?? $s;
  if ($max > 0 && function_exists('mb_substr')) return mb_substr($s, 0, $max);
  if ($max > 0) return substr($s, 0, $max);
  return $s;
}

function hid_v2_clean_long_text($value): string {
  $s = trim((string)$value);
  $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $s = strip_tags($s);
  $s = str_replace(["\r\n", "\r"], "\n", $s);
  $s = preg_replace('/[^\P{C}\n\t]+/u', '', $s) ?? $s;
  return $s;
}

function hid_v2_bool_int($value): int {
  if (is_bool($value)) return $value ? 1 : 0;
  $s = strtolower(trim((string)$value));
  return in_array($s, ['1','true','yes','on'], true) ? 1 : 0;
}

function hid_v2_decimal($value, float $default): float {
  if ($value === null || $value === '') return $default;
  $n = (float)$value;
  return $n > 0 ? $n : $default;
}

function hid_v2_decimal_clamped($value, float $default, float $min, float $max): float {
  if ($value === null || $value === '') return $default;
  $n = (float)$value;
  if (!is_finite($n)) return $default;
  if ($n < $min) return $min;
  if ($n > $max) return $max;
  return $n;
}

function hid_v2_text_align($value): string {
  $s = strtolower(trim((string)$value));
  return in_array($s, ['left', 'center', 'right'], true) ? $s : 'left';
}

function hid_v2_date_or_null($value): ?string {
  $s = trim((string)$value);
  if ($s === '') return null;

  // New cardbuilder rule: Received Jesus is entered as a strict 4-digit year.
  // The existing DB column is DATE, so store the year as YYYY-01-01 for compatibility.
  if (preg_match('/^\d{4}$/', $s)) {
    $y = (int)$s;
    return checkdate(1, 1, $y) ? sprintf('%04d-01-01', $y) : null;
  }

  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return null;
  [$y, $m, $d] = array_map('intval', explode('-', $s));
  return checkdate($m, $d, $y) ? $s : null;
}

function hid_v2_asset_name($value): string {
  $s = trim((string)$value);
  if ($s === '') return '';
  $s = str_replace('\\', '/', $s);
  $base = basename($s);
  if (!preg_match('/^[A-Za-z0-9._ -]+\.(png|jpe?g|webp|gif)$/i', $base)) return '';
  return $base;
}

function hid_v2_style_key_from_front(string $frontFile): string {
  $file = hid_v2_asset_name($frontFile);
  if (preg_match('/^heavenly_id_(.+)_front\.[^.]+$/i', $file, $m)) {
    return strtolower($m[1]);
  }
  return '';
}

function hid_v2_derive_back_file(string $frontFile, string $fallback = ''): string {
  $front = hid_v2_asset_name($frontFile);
  if (preg_match('/^heavenly_id_(.+)_front\.([^.]+)$/i', $front, $m)) {
    return 'heavenly_id_' . strtolower($m[1]) . '_back.' . strtolower($m[2]);
  }
  return hid_v2_asset_name($fallback);
}

function hid_v2_asset_path(string $dir, string $file): string {
  $file = hid_v2_asset_name($file);
  return $file !== '' ? trim($dir, '/') . '/' . $file : '';
}

function hid_v2_gen_code_20(): string {
  $chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
  $out = "";
  for ($i = 0; $i < 20; $i++) {
    $out .= $chars[random_int(0, strlen($chars) - 1)];
  }
  return $out;
}

function hid_v2_token64(): string {
  return bin2hex(random_bytes(32));
}

function hid_v2_dataurl_to_file(string $dataUrl, string $outPathNoExt): ?string {
  if (strpos($dataUrl, "data:image/") !== 0) return null;
  if (!preg_match('#^data:image/(png|jpeg|jpg|webp);base64,(.+)$#', $dataUrl, $m)) return null;
  $ext = strtolower($m[1]);
  if ($ext === "jpeg") $ext = "jpg";
  $bin = base64_decode($m[2], true);
  if ($bin === false) return null;
  $path = $outPathNoExt . "." . $ext;
  if (file_put_contents($path, $bin) === false) return null;
  return $path;
}

function hid_v2_ensure_dir(string $path): void {
  if (!is_dir($path)) mkdir($path, 0755, true);
}

function hid_v2_normalize_payload(array $data): array {
  $frontFile = hid_v2_asset_name($data['front_theme_file'] ?? $data['card_front_path'] ?? '');
  $backFile = hid_v2_asset_name($data['back_theme_file'] ?? $data['card_back_path'] ?? '');
  if ($backFile === '') $backFile = hid_v2_derive_back_file($frontFile);

  $style = hid_v2_clean_text($data['front_theme_style'] ?? '', 120);
  if ($style === '') $style = hid_v2_style_key_from_front($frontFile);

  $received = hid_v2_date_or_null($data['received_jesus_date'] ?? $data['received_jesus'] ?? '');

  return [
    'design_title' => hid_v2_clean_text($data['design_title'] ?? 'My Heavenly ID', 120),
    'full_name' => hid_v2_clean_text($data['full_name'] ?? '', 255),
    'iam_status' => hid_v2_clean_text($data['iam_status'] ?? '', 255),
    'spiritual_gifts' => hid_v2_clean_text($data['spiritual_gifts'] ?? '', 255),
    'received_jesus_date' => $received,
    'favorite_verse_ref' => hid_v2_clean_text($data['favorite_verse_ref'] ?? '', 120),
    'verse_text' => hid_v2_clean_long_text($data['verse_text'] ?? ''),
    'letter_of_intent' => hid_v2_clean_long_text($data['letter_of_intent'] ?? ''),
    'name_font_resized' => hid_v2_bool_int($data['name_font_resized'] ?? 0),
    'name_font_size_px' => function_exists('hid_v2_decimal_clamped') ? hid_v2_decimal_clamped($data['name_font_size_px'] ?? null, 82.00, 20.00, 88.00) : hid_v2_decimal($data['name_font_size_px'] ?? null, 82.00),
    'name_layout_left_px' => function_exists('hid_v2_decimal_clamped') ? hid_v2_decimal_clamped($data['name_layout_left_px'] ?? null, 405.00, 0.00, 1000.00) : hid_v2_decimal($data['name_layout_left_px'] ?? null, 405.00),
    'name_layout_top_px' => function_exists('hid_v2_decimal_clamped') ? hid_v2_decimal_clamped($data['name_layout_top_px'] ?? null, 252.00, 0.00, 655.00) : hid_v2_decimal($data['name_layout_top_px'] ?? null, 252.00),
    'name_layout_width_px' => function_exists('hid_v2_decimal_clamped') ? hid_v2_decimal_clamped($data['name_layout_width_px'] ?? null, 473.00, 80.00, 720.00) : hid_v2_decimal($data['name_layout_width_px'] ?? null, 473.00),
    'name_layout_height_px' => function_exists('hid_v2_decimal_clamped') ? hid_v2_decimal_clamped($data['name_layout_height_px'] ?? null, 112.00, 32.00, 160.00) : hid_v2_decimal($data['name_layout_height_px'] ?? null, 112.00),
    'name_safe_right_px' => function_exists('hid_v2_decimal_clamped') ? hid_v2_decimal_clamped($data['name_safe_right_px'] ?? null, 896.00, 300.00, 1000.00) : hid_v2_decimal($data['name_safe_right_px'] ?? null, 896.00),
    'name_available_width_px' => function_exists('hid_v2_decimal_clamped') ? hid_v2_decimal_clamped($data['name_available_width_px'] ?? null, 473.00, 80.00, 720.00) : hid_v2_decimal($data['name_available_width_px'] ?? null, 473.00),
    'name_text_align' => function_exists('hid_v2_text_align') ? hid_v2_text_align($data['name_text_align'] ?? 'left') : hid_v2_clean_text($data['name_text_align'] ?? 'left', 12),
    'name_padding_left_px' => function_exists('hid_v2_decimal_clamped') ? hid_v2_decimal_clamped($data['name_padding_left_px'] ?? null, 0.00, 0.00, 80.00) : hid_v2_decimal($data['name_padding_left_px'] ?? null, 0.00),
    'letter_font_resized' => hid_v2_bool_int($data['letter_font_resized'] ?? 0),
    'letter_font_size_px' => hid_v2_decimal($data['letter_font_size_px'] ?? null, 18.00),
    'front_theme_file' => $frontFile,
    'back_theme_file' => $backFile,
    'front_theme_style' => $style,
    'foreground_file' => hid_v2_asset_name($data['foreground_file'] ?? $data['foreground_path'] ?? ''),
  ];
}

function hid_v2_manifest_from_row(array $row): array {
  return [
    'table' => 'card_designs_v2',
    'id' => isset($row['id']) ? (int)$row['id'] : null,
    'design_code' => (string)($row['design_code'] ?? ''),
    'owner_type' => (string)($row['owner_type'] ?? ''),
    'user_site_id' => $row['user_site_id'] ?? null,
    'guest_email' => (string)($row['guest_email'] ?? ''),
    'design_title' => (string)($row['design_title'] ?? ''),
    'full_name' => (string)($row['full_name'] ?? ''),
    'iam_status' => (string)($row['iam_status'] ?? ''),
    'spiritual_gifts' => (string)($row['spiritual_gifts'] ?? ''),
    'received_jesus_date' => (string)($row['received_jesus_date'] ?? ''),
    'favorite_verse_ref' => (string)($row['favorite_verse_ref'] ?? ''),
    'verse_text' => (string)($row['verse_text'] ?? ''),
    'letter_of_intent' => (string)($row['letter_of_intent'] ?? ''),
    'name_font_resized' => (int)($row['name_font_resized'] ?? 0),
    'name_font_size_px' => (float)($row['name_font_size_px'] ?? 82),
    'name_layout_left_px' => (float)($row['name_layout_left_px'] ?? 405),
    'name_layout_top_px' => (float)($row['name_layout_top_px'] ?? 252),
    'name_layout_width_px' => (float)($row['name_layout_width_px'] ?? 473),
    'name_layout_height_px' => (float)($row['name_layout_height_px'] ?? 112),
    'name_safe_right_px' => (float)($row['name_safe_right_px'] ?? 896),
    'name_available_width_px' => (float)($row['name_available_width_px'] ?? 473),
    'name_text_align' => (string)($row['name_text_align'] ?? 'left'),
    'name_padding_left_px' => (float)($row['name_padding_left_px'] ?? 0),
    'letter_font_resized' => (int)($row['letter_font_resized'] ?? 0),
    'letter_font_size_px' => (float)($row['letter_font_size_px'] ?? 18),
    'front_theme_file' => (string)($row['front_theme_file'] ?? ''),
    'front_theme_path' => hid_v2_asset_path('newcrdbg', (string)($row['front_theme_file'] ?? '')),
    'back_theme_file' => (string)($row['back_theme_file'] ?? ''),
    'back_theme_path' => hid_v2_asset_path('backcrdbg', (string)($row['back_theme_file'] ?? '')),
    'front_theme_style' => (string)($row['front_theme_style'] ?? ''),
    'foreground_file' => (string)($row['foreground_file'] ?? ''),
    'foreground_path' => hid_v2_asset_path('foreground', (string)($row['foreground_file'] ?? '')),
    'preview_front_png_path' => (string)($row['preview_front_png_path'] ?? ''),
    'preview_back_png_path' => (string)($row['preview_back_png_path'] ?? ''),
    'is_paid' => (int)($row['is_paid'] ?? 0),
    'paid_at' => (string)($row['paid_at'] ?? ''),
    'shopify_order_id' => (string)($row['shopify_order_id'] ?? ''),
    'shopify_order_name' => (string)($row['shopify_order_name'] ?? ''),
  ];
}


function hid_v2_phpmailer_bootstrap(): bool {
  if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) return true;

  $base = __DIR__;
  $files = [
    $base . '/PHPMailer/src/Exception.php',
    $base . '/PHPMailer/src/PHPMailer.php',
    $base . '/PHPMailer/src/SMTP.php',
  ];

  foreach ($files as $file) {
    if (!is_file($file)) return false;
  }

  require_once $files[0];
  require_once $files[1];
  require_once $files[2];

  return class_exists('\\PHPMailer\\PHPMailer\\PHPMailer');
}

function hid_v2_email_line(string $label, $value): string {
  $value = trim((string)$value);
  return $label . ': ' . ($value !== '' ? $value : '-') . "\r\n";
}

function hid_v2_build_printer_email_body(array $row): string {
  $m = hid_v2_manifest_from_row($row);

  $body = '';
  $body .= "Heavenly ID paid design received\r\n";
  $body .= "--------------------------------\r\n";
  $body .= hid_v2_email_line('Owner Type', $m['owner_type']);
  $body .= hid_v2_email_line('Design ID', $m['id']);
  $body .= hid_v2_email_line('Guest Code', $m['design_code']);
  $body .= hid_v2_email_line('Guest Email', $m['guest_email']);
  $body .= hid_v2_email_line('Design Title', $m['design_title']);
  $body .= hid_v2_email_line('Full Name', $m['full_name']);
  $body .= hid_v2_email_line('I AM / Status', $m['iam_status']);
  $body .= hid_v2_email_line('Spiritual Gifts', $m['spiritual_gifts']);
  $body .= hid_v2_email_line('Received Jesus Date', $m['received_jesus_date']);
  $body .= hid_v2_email_line('Favorite Bible Verse', $m['favorite_verse_ref']);
  $body .= hid_v2_email_line('Verse Text', $m['verse_text']);
  $body .= hid_v2_email_line('Letter Of Intent', $m['letter_of_intent']);
  $body .= hid_v2_email_line('Name Font Resized', $m['name_font_resized']);
  $body .= hid_v2_email_line('Name Font Size Px', $m['name_font_size_px']);
  $body .= hid_v2_email_line('Name Layout Left Px', $m['name_layout_left_px']);
  $body .= hid_v2_email_line('Name Layout Top Px', $m['name_layout_top_px']);
  $body .= hid_v2_email_line('Name Layout Width Px', $m['name_layout_width_px']);
  $body .= hid_v2_email_line('Name Layout Height Px', $m['name_layout_height_px']);
  $body .= hid_v2_email_line('Name Safe Right Px', $m['name_safe_right_px']);
  $body .= hid_v2_email_line('Name Available Width Px', $m['name_available_width_px']);
  $body .= hid_v2_email_line('Name Text Align', $m['name_text_align']);
  $body .= hid_v2_email_line('Name Padding Left Px', $m['name_padding_left_px']);
  $body .= hid_v2_email_line('Letter Font Resized', $m['letter_font_resized']);
  $body .= hid_v2_email_line('Letter Font Size Px', $m['letter_font_size_px']);
  $body .= hid_v2_email_line('Foreground Image', $m['foreground_file']);
  $body .= hid_v2_email_line('Foreground Path', $m['foreground_path']);
  $body .= hid_v2_email_line('Front Graphic', $m['front_theme_file']);
  $body .= hid_v2_email_line('Front Graphic Path', $m['front_theme_path']);
  $body .= hid_v2_email_line('Back Graphic', $m['back_theme_file']);
  $body .= hid_v2_email_line('Back Graphic Path', $m['back_theme_path']);
  $body .= hid_v2_email_line('Front Theme Style', $m['front_theme_style']);
  $body .= hid_v2_email_line('Preview Front PNG', $m['preview_front_png_path']);
  $body .= hid_v2_email_line('Preview Back PNG', $m['preview_back_png_path']);
  $body .= hid_v2_email_line('Shopify Order ID', $m['shopify_order_id']);
  $body .= hid_v2_email_line('Shopify Order Name', $m['shopify_order_name']);
  $body .= hid_v2_email_line('Paid', $m['is_paid']);
  $body .= hid_v2_email_line('Paid At', $m['paid_at']);
  $body .= "\r\n";
  $body .= "Printer note: use the original high-resolution template assets that correspond to the filenames above.\r\n";

  return $body;
}

function hid_v2_send_printer_paid_email(array $row): bool {
  $recipients = [
    ['Robby Connole', 'goodracer1992@gmail.com'],
    ['Thread Kraze', 'info@threadkraze.com'],
    ['Heavenly ID', 'info@heavenlyid.com'],
  ];

  $subjectName = trim((string)($row['full_name'] ?? ''));
  $subjectDesign = trim((string)($row['design_title'] ?? ''));
  $subject = 'Heavenly ID Paid Design';
  if ($subjectName !== '') $subject .= ' - ' . $subjectName;
  if ($subjectDesign !== '') $subject .= ' / ' . $subjectDesign;

  $body = hid_v2_build_printer_email_body($row);

  @file_put_contents(
    __DIR__ . '/printer_email_debug.log',
    "[" . date('Y-m-d H:i:s') . "] Attempting printer email for design " . (string)($row['id'] ?? '') . " / " . (string)($row['design_code'] ?? '') . "\n" . $body . "\n\n",
    FILE_APPEND
  );

  if (hid_v2_phpmailer_bootstrap()) {
    try {
      $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
      $mail->isSMTP();
      $mail->Host = 'localhost';
      $mail->Port = 25;
      $mail->SMTPAuth = false;
      $mail->SMTPAutoTLS = false;
      $mail->SMTPSecure = false;

      $mail->CharSet = 'UTF-8';
      $mail->setFrom('info@heavenlyid.com', 'Heavenly ID');
      $mail->addReplyTo('info@heavenlyid.com', 'Heavenly ID');

      foreach ($recipients as [$name, $email]) {
        $mail->addAddress($email, $name);
      }

      $mail->Subject = $subject;
      $mail->Body = $body;
      $mail->AltBody = $body;
      $mail->isHTML(false);

      $mail->send();

      @file_put_contents(__DIR__ . '/printer_email_debug.log', "[" . date('Y-m-d H:i:s') . "] PHPMailer sent.\n\n", FILE_APPEND);
      return true;
    } catch (Throwable $e) {
      @file_put_contents(__DIR__ . '/printer_email_debug.log', "[" . date('Y-m-d H:i:s') . "] PHPMailer failed: " . $e->getMessage() . "\n\n", FILE_APPEND);
    }
  }

  $to = implode(',', array_map(fn($r) => $r[1], $recipients));
  $headers = [
    'From: Heavenly ID <info@heavenlyid.com>',
    'Reply-To: info@heavenlyid.com',
    'Content-Type: text/plain; charset=UTF-8',
  ];

  $ok = @mail($to, $subject, $body, implode("\r\n", $headers));
  @file_put_contents(__DIR__ . '/printer_email_debug.log', "[" . date('Y-m-d H:i:s') . "] mail() fallback result: " . ($ok ? 'sent' : 'failed') . "\n\n", FILE_APPEND);

  return (bool)$ok;
}

