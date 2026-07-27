<?php
declare(strict_types=1);

/*
  bible_verse_search.php
  Autocomplete endpoint for Heavenly ID cardbuilder.

  Returns only bible_verses.verse_reference values.
  Starts returning results after 3 characters.
*/

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

$q = trim((string)($_GET['q'] ?? ''));

if (strlen($q) < 3) {
  echo json_encode(['success' => true, 'items' => []], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  exit;
}

$items = [];

function hvid_output(array $items): void {
  $items = array_values(array_unique(array_filter(array_map('strval', $items), static function ($v) {
    return trim($v) !== '';
  })));

  echo json_encode([
    'success' => true,
    'items' => array_slice($items, 0, 25)
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  exit;
}

function hvid_search_json(string $q): array {
  $jsonFile = __DIR__ . '/bible_verse_references.json';
  if (!is_file($jsonFile)) {
    return [];
  }

  $allRefs = json_decode((string)file_get_contents($jsonFile), true);
  if (!is_array($allRefs)) {
    return [];
  }

  $needle = strtolower($q);
  $items = [];

  foreach ($allRefs as $ref) {
    $ref = trim((string)$ref);
    if ($ref !== '' && strpos(strtolower($ref), $needle) !== false) {
      $items[] = $ref;
      if (count($items) >= 25) {
        break;
      }
    }
  }

  return $items;
}

/*
  Primary source: database.
  This uses the existing protected/pdo.php if present.
  The query SELECTs ONLY verse_reference.
*/
try {
  $pdoFile = __DIR__ . '/protected/pdo.php';

  if (is_file($pdoFile)) {
    require_once $pdoFile;
  }

  if (isset($pdo) && is_object($pdo) && method_exists($pdo, 'prepare')) {
    $stmt = $pdo->prepare("
      SELECT DISTINCT verse_reference
      FROM bible_verses
      WHERE verse_reference LIKE ?
      ORDER BY verse_reference ASC
      LIMIT 25
    ");

    $stmt->execute(['%' . $q . '%']);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $ref = trim((string)($row['verse_reference'] ?? ''));
      if ($ref !== '') {
        $items[] = $ref;
      }
    }

    hvid_output($items);
  }
} catch (Throwable $e) {
  // Fall through to JSON backup. Never return HTTP 500 for autocomplete.
}

/*
  Backup source: generated JSON from bible_verses.sql.
  This file contains only verse_reference values, not verse_text.
*/
hvid_output(hvid_search_json($q));
