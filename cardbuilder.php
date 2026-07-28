<?php
session_start();

$loggedIn = false;
$userName = '';

if (isset($_SESSION['user_id']) && isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
  $loggedIn = true;
  $userName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
}

$cardBgDir = 'newcrdbg';
$backBgDir = 'backcrdbg';
$frontThemes = [];
$themeBacks = [];

/**
 * New background convention:
 *   heavenly_id_<style>_front.png
 *   heavenly_id_<style>_back.png
 *
 * Only *_front files are shown in the picker. When a matching *_back exists,
 * the Flip Card view automatically uses it.
 */
function cb_theme_rel_path(string $dir, string $absPath): string {
  return $dir . '/' . basename($absPath);
}

function cb_derive_back_path(string $frontAbs): string {
  return preg_replace('/_front(\.[^.]+)$/i', '_back$1', $frontAbs);
}

/**
 * Find the matching back-card image for a selected front image.
 * Supports both:
 *   /newcrdbg/heavenly_id_*_back.png
 *   /backcrdbg/heavenly_id_*_back.png
 */
function cb_find_back_path(string $frontAbs, string $frontDir, string $backDir): string {
  $baseName = basename($frontAbs);
  $sameDirBack = preg_match('/_front\.[^.]+$/i', $baseName) ? cb_derive_back_path($frontAbs) : '';
  $candidates = [];

  if ($sameDirBack !== '') {
    $candidates[] = $sameDirBack;
  }

  if (preg_match('/_front(\.[^.]+)$/i', $baseName)) {
    $backName = preg_replace('/_front(\.[^.]+)$/i', '_back$1', $baseName);
    $candidates[] = __DIR__ . '/' . $backDir . '/' . $backName;
  }

  $noExt = pathinfo($baseName, PATHINFO_FILENAME);
  $ext = pathinfo($baseName, PATHINFO_EXTENSION);
  if ($ext !== '') {
    $candidates[] = __DIR__ . '/' . $backDir . '/' . preg_replace('/front/i', 'back', $noExt) . '.' . $ext;
  }

  foreach ($candidates as $candidate) {
    if ($candidate && is_file($candidate)) {
      return $candidate;
    }
  }

  return '';
}

$themeFiles = glob(__DIR__ . '/' . $cardBgDir . '/heavenly_id_*_front.{png,jpg,jpeg,webp,gif}', GLOB_BRACE);

// Fallback: if the new naming convention is not present yet, still show usable images,
// but do not show *_back images as selectable front thumbnails.
if (!$themeFiles) {
  $themeFiles = glob(__DIR__ . '/' . $cardBgDir . '/*.{png,jpg,jpeg,webp,gif}', GLOB_BRACE);
}

if (is_array($themeFiles)) {
  foreach ($themeFiles as $themeFile) {
    if (!is_file($themeFile)) continue;

    $baseName = basename($themeFile);
    if (preg_match('/_back\.[^.]+$/i', $baseName)) continue;

    $frontRel = cb_theme_rel_path($cardBgDir, $themeFile);
    $backAbs = cb_find_back_path($themeFile, $cardBgDir, $backBgDir);
    $backRel = '';

    if ($backAbs && is_file($backAbs)) {
      $backRel = (basename(dirname($backAbs)) === $backBgDir)
        ? cb_theme_rel_path($backBgDir, $backAbs)
        : cb_theme_rel_path($cardBgDir, $backAbs);
    }

    $frontThemes[] = $frontRel;
    $themeBacks[$frontRel] = $backRel;
  }
}

sort($frontThemes, SORT_NATURAL | SORT_FLAG_CASE);

// Fallback only if /newcrdbg is missing or empty.
if (!$frontThemes) {
  $frontThemes = [
    'hvidbg2.png',
    'hvidbg1.png'
  ];
  foreach ($frontThemes as $fallbackTheme) {
    $themeBacks[$fallbackTheme] = '';
  }
}

$defaultBackImage = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAwIiBoZWlnaHQ9IjY2NyIgdmlld0JveD0iMCAwIDEwMDAgNjY3Ij4KPHJlY3Qgd2lkdGg9IjEwMDAiIGhlaWdodD0iNjY3IiByeD0iMjgiIGZpbGw9IiNmM2VmZTciLz4KPHJlY3QgeD0iMTgiIHk9IjE4IiB3aWR0aD0iOTY0IiBoZWlnaHQ9IjYzMSIgcng9IjI2IiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjNhNzEiIHN0cm9rZS13aWR0aD0iNSIvPgo8dGV4dCB4PSI1MDAiIHk9IjMwNSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZmlsbD0iIzIyM2E3MSIgZm9udC1mYW1pbHk9Ikdlb3JnaWEsc2VyaWYiIGZvbnQtc2l6ZT0iNDgiPkNpdGl6ZW4gb2YgSGVhdmVuPC90ZXh0Pgo8dGV4dCB4PSI1MDAiIHk9IjM2MCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZmlsbD0iIzhmNzQ0MyIgZm9udC1mYW1pbHk9Ikdlb3JnaWEsc2VyaWYiIGZvbnQtc2l6ZT0iMjQiPkJhY2sgb2YgQ2FyZDwvdGV4dD4KPC9zdmc+';
$firstFrontPath = $frontThemes[0];
$firstBackPath = $themeBacks[$firstFrontPath] ?? '';

$foregroundDir = 'foreground';
$foregroundImages = [];

$foregroundFiles = glob(__DIR__ . '/' . $foregroundDir . '/*.{png,jpg,jpeg,webp,gif}', GLOB_BRACE);
if (is_array($foregroundFiles)) {
  foreach ($foregroundFiles as $foregroundFile) {
    if (is_file($foregroundFile)) {
      $foregroundImages[] = $foregroundDir . '/' . basename($foregroundFile);
    }
  }
}

sort($foregroundImages, SORT_NATURAL | SORT_FLAG_CASE);

$verseOptions = [];
$defaultLetterIntentBody = 'declare that I am a Citizen of Heaven, Chosen by God, redeemed by Christ, led by the Spirit & Committed to walk in faithful obedience to Jesus Wherever I go.';


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Kingdom ID Card Builder</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />

  <script src="https://accounts.google.com/gsi/client" async defer></script>

  <style type="text/css">
  @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Cormorant+Garamond:wght@500;600;700&family=Great+Vibes&family=Montserrat:wght@500;600;700;800&display=swap');

  @font-face {
    font-family: 'Minion Pro';
    src: url('MinionPro-Medium.woff') format('woff');
    font-weight: 500;
    font-style: normal;
    font-display: swap;
  }

  @font-face {
    font-family: 'Bw Modelica Extra Bold';
    src: local('Bw Modelica Extra Bold'),
         local('BwModelica ExtraBold'),
         url('fonts/BwModelica-ExtraBold.woff2') format('woff2'),
         url('BwModelica-ExtraBold.woff2') format('woff2'),
         url('bw-modelica-extra-bold.woff2') format('woff2');
    font-weight: 800;
    font-style: normal;
    font-display: swap;
  }

  @font-face {
    font-family: 'Bw Modelica Regular';
    src: local('Bw Modelica Regular'),
         local('BwModelica Regular'),
         url('fonts/BwModelica-Regular.woff2') format('woff2'),
         url('BwModelica-Regular.woff2') format('woff2'),
         url('bw-modelica-regular.woff2') format('woff2');
    font-weight: 400;
    font-style: normal;
    font-display: swap;
  }

  @font-face {
    font-family: 'Aurelly Signature';
    src: local('Aurelly Signature'),
         url('fonts/AurellySignature.woff2') format('woff2'),
         url('AurellySignature.woff2') format('woff2'),
         url('Aurelly Signature.woff2') format('woff2');
    font-weight: 400;
    font-style: normal;
    font-display: swap;
  }

:root{
    --cb-page-max: 1560px;
    --cb-sidebar-w: 335px;
    --cb-gap: 18px;
    --cb-card-w: 1000px;
    --cb-card-h: 667px;
    --cb-ink: #3c312c;
    --cb-blue: #223a71;
    --cb-gold: #d0aa55;
    --cb-gold-deep: #a67d35;
    --cb-soft-line: rgba(190,170,132,.65);
    --cb-panel: rgba(255,255,255,.18);
    --cb-shadow: 0 18px 45px rgba(64,48,38,.12);
    --cb-btn-blue-1: #396fcd;
    --cb-btn-blue-2: #234790;
    --cb-btn-border: #1d3871;
    --dl-label: 24px;
    --dl-value: 20px;
    --dl-verse: 17px;
    --cb-name-fit-size: 82px;
  }

  *{ box-sizing:border-box; }

  html, body {
    margin: 0;
    padding: 0;
    font-family: 'Minion Pro', serif !important;
    background: #ece7e0;
    color: var(--cb-ink);
    overflow-x: hidden;
  }

  body::before {
    content: "";
    position: fixed;
    inset: 0;
    background: url("bg_no_text_v3.jpg") center center / cover no-repeat fixed;
    opacity: 0.30;
    z-index: -1;
    pointer-events: none;
  }

  .cb-page,
  .cb-page input,
  .cb-page textarea,
  .cb-page button,
  .cb-page select,
  #authModal input,
  #authModal button,
  #contactModal input,
  #contactModal textarea,
  #contactModal button,
  #previewModal button {
    font-family: 'Minion Pro', serif !important;
  }

  .cb-page {
    max-width: var(--cb-page-max);
    margin: 0 auto;
    padding: 16px 18px 26px;
  }

  .cb-shell {
    display: grid;
    grid-template-columns: var(--cb-sidebar-w) minmax(0, 1fr);
    gap: var(--cb-gap);
    align-items: start;
  }

  .cb-sidebar,
  .cb-main {
    min-width: 0;
  }

  .cb-sidebar {
    padding-right: 14px;
    border-right: 1px solid rgba(184,169,141,.42);
  }

  .cb-sidebar-title {
    margin: 8px 0 16px;
    font-size: 30px;
    line-height: 1.05;
    color: #312621;
    letter-spacing: .2px;
    padding-left: 6px;
  }

  .cb-theme-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
    max-height: calc(100vh - 255px);
    overflow: auto;
    padding: 4px 6px 6px;
  }

  .cb-theme-list::-webkit-scrollbar { width: 10px; }
  .cb-theme-list::-webkit-scrollbar-thumb {
    background: rgba(91,73,54,.22);
    border-radius: 999px;
  }

  .theme-option {
    border: 3px solid rgba(255,255,255,.72);
    border-radius: 18px;
    padding: 10px;
    cursor: pointer;
    background: rgba(255,255,255,.30);
    box-shadow: 0 12px 24px rgba(67,51,39,.10);
    transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
  }

  .theme-option:hover {
    transform: translateY(-1px);
    box-shadow: 0 16px 30px rgba(67,51,39,.14);
  }

  .theme-option.active {
    border-color: #fff;
    box-shadow: 0 0 0 3px rgba(214,173,85,.28), 0 18px 34px rgba(67,51,39,.18);
  }

  .theme-preview {
    display: flex;
    justify-content: center;
    align-items: center;
  }

  .theme-preview img {
    width: 100%;
    height: auto;
    aspect-ratio: 3 / 2;
    object-fit: contain;
    border-radius: 12px;
    border: 1px solid rgba(0,0,0,.10);
    display: block;
  }

  .theme-label { display: none; }

  .cb-main-title {
    margin: 6px 0 12px;
    font-size: 33px;
    line-height: 1.08;
    color: var(--cb-blue);
    font-weight: 600;
    letter-spacing: .2px;
  }

  .cb-main {
    padding-left: 2px;
  }

  .cb-stage-wrap {
    width: 100%;
    overflow-x: auto;
    padding-bottom: 10px;
  }

  .cb-stage-wrap::-webkit-scrollbar { height: 10px; }
  .cb-stage-wrap::-webkit-scrollbar-thumb {
    background: rgba(91,73,54,.22);
    border-radius: 999px;
  }

  .cb-stage-inner {
    display: inline-block;
  }

  .card-wrapper {
    width: var(--cb-card-w);
    height: var(--cb-card-h);
    position: relative;
    color: #000;
    max-width: none;
  }

  .card-face {
    position: absolute;
    inset: 0;
    transition: opacity 0.45s ease;
  }

  .card-front {
    background-color: #f6edd7;
    background-image: url('<?= htmlspecialchars($firstFrontPath, ENT_QUOTES) ?>');
    background-repeat: no-repeat;
    background-position: center center;
    background-size: 100% 100%;
    border-radius: 28px;
    overflow: hidden;
    box-shadow: var(--cb-shadow);
    opacity: 1;
    pointer-events: auto;
    z-index: 2;
  }

  .card-back {
    opacity: 0;
    pointer-events: none;
    z-index: 1;
  }

  .card-back img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    border-radius: 28px;
  }

  .card-wrapper.flipped .card-front {
    opacity: 0;
    pointer-events: none;
  }

  .card-wrapper.flipped .card-back {
    opacity: 1;
    pointer-events: auto;
    z-index: 3;
  }

  .cb-card-border {
    position: absolute;
    inset: 18px;
    border: 5px solid rgba(255,255,255,.72);
    border-radius: 34px;
    pointer-events: none;
    box-shadow: inset 0 0 0 1px rgba(217,203,176,.42);
  }

  .cb-brand {
    position: absolute;
    top: 55px;
    left: 22px;
    width: 230px;
    height: 128px;
    z-index: 4;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
  }

  .cb-brand img {
    width: 230px;
    height: auto;
    display: block;
    object-fit: contain;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,.10));
  }

  .cb-card-title {
    position: absolute;
    top: 40px;
    left: 230px;
    color: #26376d;
    font-weight: 700;
    font-size: 58px;
    line-height: 1;
    letter-spacing: .25px;
    white-space: nowrap;
    text-shadow: 0 1px 0 rgba(255,255,255,.38);
    z-index: 2;
  }

  .photo-section {
    position: absolute;
    top: 178px;
    left: 56px;
    width: 160px;
    height: 294px;
    border-radius: 14px;
    background: rgba(255,255,255,.18);
    box-shadow: inset 0 0 0 2px rgba(255,255,255,.62), inset 0 0 5px rgba(0,0,0,.10);
    overflow: hidden;
    z-index: 2;
    cursor: pointer;
  }

  #photoDropzone {
    position: absolute;
    inset: 0;
    border-radius: 14px;
    background: transparent;
    pointer-events: none;
    z-index: 1;
  }

  .cb-dropzone-inner {
    width: 100%;
    height: 100%;
    padding: 0 12px;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    justify-content: center;
    gap: 8px;
    pointer-events: none;
  }

  .cb-dropzone-title,
  .cb-dropzone-sub {
    display: block;
    width: 100%;
    max-width: 100%;
    margin: 0;
    padding: 0;
    color: #000;
    font-family: 'Minion Pro', serif !important;
    font-size: 17px;
    font-weight: 600;
    line-height: 1.15;
    text-align: justify;
    text-align-last: center;
    white-space: normal;
    word-break: normal;
    overflow-wrap: normal;
    pointer-events: none;
  }

  .photo-section.has-photo #photoDropzone {
    display: none;
  }

  .photo-section img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0;
    transition: opacity 0.3s ease;
    display: block;
    z-index: 2;
  }

  .photo-section img[src] { opacity: 1; }

  .cb-remove {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 24px;
    height: 24px;
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 3;
    border: 1px solid rgba(35,58,113,.35);
    border-radius: 999px;
    background: rgba(255,255,255,.86);
    color: #223a71;
    font-size: 18px;
    line-height: 1;
    cursor: pointer;
    padding: 0;
  }

  .photo-section.has-photo .cb-remove {
    display: inline-flex;
  }

  .cb-left-caption {
    position: absolute;
    left: 66px;
    top: 486px;
    width: 140px;
    text-align: center;
    color: #2c3470;
    font-weight: 700;
    font-size: 24px;
    line-height: 1.02;
    z-index: 2;
  }

  .cb-left-status {
    position: absolute;
    left: 62px;
    top: 528px;
    width: 150px;
    text-align: center;
    color: #9f3732;
    font-style: italic;
    font-size: 41px;
    line-height: 1;
    z-index: 2;
  }

  
  .info-block {
    position: absolute;
    top: 165px;
    left: 315px;
    width: 585px;
    height: auto;
    display: grid;
    grid-template-rows: 104px 48px 48px 48px;
    row-gap: 45px;
    justify-content: flex-start;
    align-items: start;
    z-index: 6;
  }

  .cb-name-row {
    width: 426px;
    height: 104px;
    margin: 0;
    padding: 0;
    border-bottom: 0;
    flex: 0 0 auto;
  }

  .cb-name-input {
    width: 100%;
    height: 104px;
    line-height: .92;
    border: 0;
    outline: none;
    background: transparent;
    padding: 0;
    margin: 0;
    color: #d01818;
    font-family: 'Aurelly Signature', 'Great Vibes', cursive !important;
    font-size: var(--cb-name-fit-size, 82px);
    font-weight: 400;
    letter-spacing: 0;
    text-align: center;
    text-transform: none;
    text-shadow: 0 1px 0 rgba(255,255,255,.20);
  }

  .cb-name-input::placeholder {
    color: #d01818;
    opacity: 1;
    text-transform: none;
    font-weight: 400;
    font-family: 'Aurelly Signature', 'Great Vibes', cursive !important;
  }

  .info-row {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 12px;
    width: 100%;
    min-height: 48px;
    position: relative;
    margin: 0;
    flex: 0 0 auto;
  }

.info-row.is-hidden {
    display: none !important;
  }

  .info-row strong {
    min-width: 226px;
    white-space: nowrap;
    font-size: 29px;
    line-height: 1.08;
    color: #182f66;
    font-family: 'Bw Modelica Extra Bold', 'Montserrat', Arial, sans-serif !important;
    font-weight: 800;
    text-transform: none;
    letter-spacing: 0;
  }
  .card-input {
    width: 100%;
    height: 42px;
    line-height: 1.05;
    font-size: 27px;
    border: 0;
    outline: none;
    background: transparent;
    padding: 0;
    margin: 0;
    color: #182f66;
    font-style: normal;
    font-family: 'Bw Modelica Regular', 'Montserrat', Arial, sans-serif !important;
    font-weight: 400;
    box-shadow: none;
  }

  .card-input::placeholder {
    color: #223a71;
    opacity: 1;
    font-family: 'Bw Modelica Regular', 'Montserrat', Arial, sans-serif !important;
    font-weight: 400;
  }
  .card-input:hover,
  .card-input:focus {
    box-shadow: none;
  }


  .combo-wrap {
    display: flex;
    gap: 8px;
    align-items: center;
    width: 100%;
  }

  .combo-btn {
    width: 34px;
    height: 28px;
    border-radius: 6px;
    border: 1px solid #555;
    background: #111;
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
  }

  .verse-text {
    display: none !important;
  }

  .cb-theme-caption {
    display: none !important;
  }

  .cb-scripture-caption {
    display: none !important;
  }

  .illustration-box {
    position: absolute;
    top: 118px;
    right: 34px;
    width: 332px;
    height: 438px;
    border-radius: 12px;
    overflow: hidden;
    background: transparent;
    border: none;
    box-shadow: none;
    z-index: 2;
  }

  .illustration-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0;
    transition: opacity 0.25s ease;
    display: block;
  }

  .illustration-box img[src] { opacity: 1; }

  .cb-card-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    flex-wrap: wrap;
    margin: 16px 6px 0 0;
  }

  .btn {
    min-width: 150px;
    padding: 12px 18px;
    border-radius: 13px;
    border: 2px solid var(--cb-btn-border);
    background: linear-gradient(180deg, var(--cb-btn-blue-1) 0%, var(--cb-btn-blue-2) 100%);
    color: #fff;
    cursor: pointer;
    font-weight: 700;
    font-size: 19px;
    letter-spacing: .15px;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.28), 0 4px 10px rgba(0,0,0,.12);
  }

  .btn[disabled],
  .btn-login-disabled,
  .btn[aria-disabled="true"] {
    opacity: 0.55;
    cursor: not-allowed;
  }

  .btn-login-disabled {
    pointer-events: auto;
  }

  .letter-intent {
    position: absolute;
    top: 116px;
    left: 92px;
    right: 92px;
    height: 486px;
    border-radius: 10px;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.18);
  }

  .letter-editor-shell {
    width: 100%;
    height: 100%;
    padding: 18px 22px;
    border-radius: 10px;
    color: #000;
    font-family: 'Montserrat', Arial, sans-serif !important;
    font-size: 18px;
    line-height: 1.45;
    text-align: left;
    word-wrap: break-word;
    hyphens: auto;
    display: flex;
    flex-direction: column;
    gap: 4px;
    background: rgba(255, 255, 255, 0.26);
  }

  .letter-fixed-prefix {
    flex: 0 0 auto;
    color: #000;
    font-family: 'Montserrat', Arial, sans-serif !important;
    font-size: 18px;
    line-height: 1.45;
    font-weight: 500;
    white-space: normal;
  }

  .letter-fixed-prefix strong {
    font-weight: 800;
  }

  #letterEditor {
    width: 100%;
    flex: 1 1 auto;
    min-height: 0;
    border: 0;
    outline: none;
    resize: none;
    background: transparent;
    color: #000;
    font-family: 'Montserrat', Arial, sans-serif !important;
    font-size: 18px;
    font-weight: 500;
    line-height: 1.45;
    text-align: left;
    padding: 0;
    border-radius: 0;
    word-wrap: break-word;
    hyphens: auto;
    display: block;
  }

  #letterEditor::placeholder {
    color: rgba(0,0,0,.52);
    font-family: 'Montserrat', Arial, sans-serif !important;
    font-weight: 500;
  }

body.cb-modal-open { overflow: hidden !important; }

  #previewMount .card-front {
    background-size: 100% 100% !important;
    background-position: center center !important;
    background-repeat: no-repeat !important;
  }

  #previewMount .card-back img {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    display: block !important;
  }

  #previewMount .cb-preview-value {
    display: inline-block;
    width: 100%;
    min-height: 22px;
    line-height: 1.1;
    font-size: inherit;
    color: #000;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  #previewMount input.card-input { display: none !important; }

  .builder-actions,
  .builder-themes h4,
  .theme-arrow,
  .watermark,
  .bottom-info,
  .previewFields,
  .photo-dropzone,
  .builder-controls,
  .theme-label {
    display: none !important;
  }

  @media (max-width: 1200px) {
    .cb-shell {
      grid-template-columns: 1fr;
    }

    .cb-sidebar {
      border-right: 0;
      padding-right: 0;
    }

    .cb-theme-list {
      max-height: none;
      display: grid;
      grid-template-columns: repeat(2, minmax(220px, 1fr));
    }
  }

  @media (max-width: 760px) {
    .cb-page {
      padding: 12px 12px 24px;
    }

    .cb-main-title,
    .cb-sidebar-title {
      font-size: 28px;
    }

    .cb-theme-list {
      grid-template-columns: 1fr;
    }
  }

  /* Header styling intentionally left to header.php for consistency across pages. */
  /* Hide only the foreground card-builder logo and title.
     All other card builder fields and overlays remain visible/functioning. */
  .card-front .cb-brand,
  .card-front .cb-card-title {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    pointer-events: none !important;
  }


  /* Foreground image carousel replaces the old photo upload box.
     The asset canvas ratio is locked to 708 x 1163 and images are centered with no cropping. */
  .foreground-section {
    position: absolute;
    left: 82px;
    top: 208px;
    width: 205px;
    aspect-ratio: 708 / 1163;
    z-index: 6;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: visible;
    background: transparent;
  }

.foreground-image-wrap {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
  }

  #foregroundImg {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center center;
    opacity: 1;
    border: 0;
    background: transparent;
  }

  .foreground-empty {
    display: none;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    padding: 12px;
    border: 1px dashed rgba(34,58,113,.45);
    border-radius: 10px;
    color: rgba(34,58,113,.78);
    background: rgba(255,255,255,.18);
    font-size: 14px;
    line-height: 1.2;
    text-align: center;
    box-sizing: border-box;
  }

  .foreground-section.is-empty #foregroundImg {
    display: none;
  }

  .foreground-section.is-empty .foreground-empty {
    display: flex;
  }

  .fg-arrow {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 20%;
    min-width: 28px;
    z-index: 7;
    border: 0;
    background: rgba(255,255,255,0);
    color: rgba(34,58,113,.72);
    font-size: 34px;
    line-height: 1;
    font-family: Georgia, 'Times New Roman', serif;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    opacity: .72;
    transition: opacity .16s ease, background .16s ease;
  }

  .fg-arrow:hover,
  .fg-arrow:focus {
    opacity: 1;
    background: rgba(255,255,255,.12);
    outline: none;
  }

  .fg-arrow-prev { left: 0; }
  .fg-arrow-next { right: 0; }

  .foreground-section.is-single .fg-arrow,
  .foreground-section.is-empty .fg-arrow {
    display: none;
  }

  .cb-left-caption,
  .cb-left-status {
    display: none !important;
  }

  @media (max-width: 760px) {
    .foreground-section {
      left: 8.2%;
      top: 31.2%;
      width: 20.5%;
    }

    .info-block {
      left: 31.5%;
      top: 24.7%;
      width: 58.5%;
      display: grid;
      grid-template-rows: minmax(64px, auto) repeat(3, minmax(32px, auto));
      row-gap: 5vw;
    }

    .cb-name-row {
      width: 42.6%;
      height: auto;
      margin: 0;
    }

    .cb-name-input {
      height: auto;
      font-size: var(--cb-name-fit-size, clamp(30px, 8.0vw, 54px));
    }

.info-row {
      gap: 1.3vw;
    }

    .info-row strong {
      min-width: 22.6vw;
      font-size: clamp(13px, 2.9vw, 21px);
    }

    .card-input {
      font-size: clamp(13px, 2.7vw, 20px);
      height: auto;
    }

    .fg-arrow {
      width: 20%;
      min-width: 20px;
      font-size: clamp(18px, 4vw, 34px);
    }
  }


  /* Restored Bible Verse dropdown for #verseRef. Hidden until the field is focused/typed. */
  .verse-select-wrap {
    position: relative;
    flex: 1 1 auto;
    min-width: 0;
  }

  .verse-select-wrap .card-input {
    width: 100% !important;
  }

  .verse-listbox {
    display: none;
    position: absolute;
    z-index: 9999;
    left: 0;
    right: 0;
    top: calc(100% + 5px);
    max-height: 190px;
    overflow: auto;
    padding: 6px;
    border: 1px solid rgba(34,58,113,.28);
    border-radius: 10px;
    background: rgba(255,255,255,.96);
    box-shadow: 0 10px 28px rgba(32,27,22,.18);
    color: #223a71;
    font-family: 'Cormorant Garamond', 'Minion Pro', serif !important;
    box-sizing: border-box;
  }

  .verse-listbox.is-open {
    display: block;
  }

  .verse-option {
    display: block;
    width: 100%;
    border: 0;
    border-radius: 8px;
    background: transparent;
    color: #223a71;
    padding: 7px 9px;
    text-align: left;
    cursor: pointer;
    font-size: 17px;
    line-height: 1.1;
    font-family: 'Cormorant Garamond', 'Minion Pro', serif !important;
  }

  .verse-option:hover,
  .verse-option:focus,
  .verse-option.is-active {
    background: rgba(34,58,113,.10);
    outline: none;
  }

  @media (max-width: 760px) {
    .verse-listbox {
      max-height: 130px;
      top: calc(100% + 3px);
    }

    .verse-option {
      font-size: 13px;
      padding: 5px 7px;
    }
  }


  /* PHOTO-2026-07-06-20-59-25 layout match
     Layout-only override: card size and element positions only. */
  :root{
    --cb-card-w: 1000px;
    --cb-card-h: 655px;
    --cb-name-fit-size: 88px;
  }

  .theme-preview img{
    aspect-ratio: 715 / 468 !important;
  }

  .card-wrapper{
    width: var(--cb-card-w) !important;
    height: var(--cb-card-h) !important;
  }

  .cb-card-border{
    display:none !important;
  }

  .foreground-section{
    left:154px !important;
    top:207px !important;
    width:215px !important;
    aspect-ratio:708 / 1163 !important;
  }

  .info-block{
    top:252px !important;
    left:405px !important;
    width:545px !important;
    height:auto !important;
    display:block !important;
    grid-template-rows:none !important;
    row-gap:0 !important;
    justify-content:initial !important;
    align-items:initial !important;
  }

  .cb-name-row{
    width:520px !important;
    height:112px !important;
    margin:0 0 43px 0 !important;
    padding:0 !important;
  }

  .cb-name-input{
    width:100% !important;
    height:112px !important;
    line-height:.92 !important;
    font-size:var(--cb-name-fit-size, 88px) !important;
    text-align:center !important;
  }

  .info-row{
    display:grid !important;
    grid-template-columns:190px minmax(0, 1fr) !important;
    column-gap:30px !important;
    align-items:center !important;
    justify-content:initial !important;
    width:100% !important;
    min-height:34px !important;
    margin:0 0 18px 0 !important;
    gap:0 !important;
  }

  .info-row.is-hidden{
    display:none !important;
  }

  .info-row strong{
    min-width:0 !important;
    width:190px !important;
    font-size:26px !important;
    line-height:1 !important;
    color:#182f66 !important;
  }

  .card-input{
    width:100% !important;
    height:32px !important;
    line-height:1 !important;
    font-size:25px !important;
    color:#182f66 !important;
  }

  .verse-select-wrap{
    width:100% !important;
    min-width:0 !important;
  }

  @media (max-width: 760px) {
    .foreground-section{
      left:15.4% !important;
      top:31.6% !important;
      width:21.5% !important;
    }

    .info-block{
      left:40.5% !important;
      top:38.5% !important;
      width:54.5% !important;
      display:block !important;
    }

    .cb-name-row{
      width:52% !important;
      height:auto !important;
      margin:0 0 6.6vw 0 !important;
    }

    .cb-name-input{
      height:auto !important;
      font-size:var(--cb-name-fit-size, clamp(38px, 8.8vw, 72px)) !important;
    }

    .info-row{
      grid-template-columns:19vw minmax(0, 1fr) !important;
      column-gap:3vw !important;
      margin-bottom:2.8vw !important;
      min-height:auto !important;
    }

    .info-row strong{
      width:19vw !important;
      font-size:clamp(14px, 2.6vw, 24px) !important;
    }

    .card-input{
      font-size:clamp(14px, 2.5vw, 23px) !important;
      height:auto !important;
    }
  }


  /* Label/value spacing correction
     Prevents "Received Jesus" from colliding with the date/value field and
     aligns all right-column inputs to the same indentation. */
  .info-block{
    width:630px !important;
  }

  .info-row{
    grid-template-columns:230px minmax(0, 1fr) !important;
    column-gap:34px !important;
  }

  .info-row strong{
    width:230px !important;
    font-size:24px !important;
    line-height:1.05 !important;
  }

  .card-input{
    font-size:24px !important;
    height:31px !important;
    line-height:1.05 !important;
  }

  .verse-select-wrap{
    grid-column:2 !important;
  }

  @media (max-width: 760px) {
    .info-block{
      width:60% !important;
    }

    .info-row{
      grid-template-columns:23vw minmax(0, 1fr) !important;
      column-gap:3.4vw !important;
    }

    .info-row strong{
      width:23vw !important;
      font-size:clamp(13px, 2.35vw, 22px) !important;
    }

    .card-input{
      font-size:clamp(13px, 2.35vw, 22px) !important;
    }
  }


  /* 3-pass name indentation alignment
     Keeps functionality untouched. Aligns the name field's starting position
     with Spiritual Gift / Received Jesus / Bible Verse label column. */
  .cb-name-row{
    width:545px !important;
    margin-left:0 !important;
  }

  .cb-name-input{
    text-align:left !important;
    padding-left:0 !important;
  }

  @media (max-width: 760px) {
    .cb-name-row{
      width:54.5% !important;
      margin-left:0 !important;
    }

    .cb-name-input{
      text-align:left !important;
      padding-left:0 !important;
    }
  }


  /* Back letter-of-intent default statement
     Visible formatted text matches the reference; hidden textarea keeps save workflow intact. */
  .letter-intent {
    top: 94px !important;
    left: 86px !important;
    right: 108px !important;
    height: 200px !important;
    background: transparent !important;
    z-index: 5 !important;
  }

  .letter-editor-shell {
    padding: 0 !important;
    background: transparent !important;
    border-radius: 0 !important;
    display: block !important;
    color: #223a71 !important;
    font-family: 'Bw Modelica Regular', 'Montserrat', Arial, sans-serif !important;
  }

  .letter-display {
    display: block;
    width: 100%;
    max-width: 770px;
    color: #223a71;
    font-family: 'Bw Modelica Regular', 'Montserrat', Arial, sans-serif !important;
    font-size: 24px;
    line-height: 1.42;
    font-weight: 400;
    text-align: left;
    white-space: normal;
    overflow-wrap: normal;
    word-break: normal;
  }

  .letter-display strong {
    font-family: 'Bw Modelica Extra Bold', 'Montserrat', Arial, sans-serif !important;
    font-weight: 800;
  }

  #letterEditor {
    position: absolute !important;
    left: -9999px !important;
    top: auto !important;
    width: 1px !important;
    height: 1px !important;
    opacity: 0 !important;
    pointer-events: none !important;
  }


  /* Letter intent padding/margin pass from reference photo
     Scaled from PHOTO-2026-07-06-20-59-25 back reference.
     Layout-only; save workflow still uses #letterEditor. */
  .card-back .letter-intent {
    top: 132px !important;
    left: 124px !important;
    right: auto !important;
    width: 720px !important;
    height: 190px !important;
    margin: 0 !important;
    padding: 0 !important;
    background: transparent !important;
    overflow: visible !important;
  }

  .card-back .letter-editor-shell {
    width: 100% !important;
    height: auto !important;
    margin: 0 !important;
    padding: 0 !important;
    background: transparent !important;
    display: block !important;
  }

  .card-back .letter-display {
    width: 100% !important;
    max-width: 720px !important;
    margin: 0 !important;
    padding: 0 !important;
    color: #223a71 !important;
    font-size: 24px !important;
    line-height: 1.42 !important;
    letter-spacing: .1px !important;
    word-spacing: 2px !important;
    text-align: left !important;
  }

  .card-back .letter-display strong {
    font-weight: 800 !important;
  }


  /* Back letter intent is display-only.
     The hidden textarea remains only as a save-workflow storage field. */
  .card-back .letter-display{
    user-select:text !important;
    pointer-events:none !important;
  }

  #letterEditor[readonly]{
    user-select:none !important;
    caret-color:transparent !important;
  }


  /* Name fit border safety
     Keeps the visible name inside the card border while JS recalculates font size. */
  .cb-name-row{
    overflow:hidden !important;
  }

  .cb-name-input{
    max-width:100% !important;
    white-space:nowrap !important;
  }


  /* Name no-clip precise fit
     Do not clip cursive/script glyphs; JS now shrinks text before it reaches the border. */
  .cb-name-row{
    overflow:visible !important;
  }

  .cb-name-input{
    overflow:visible !important;
    text-overflow:clip !important;
    white-space:nowrap !important;
  }


  /* Received Jesus year: strict four-digit numeric input, no browser date/autocomplete UI. */
  #received.is-invalid-year{
    box-shadow:0 0 0 2px rgba(208,24,24,.40) !important;
    border-radius:6px !important;
  }


  /* RESTORED / FINAL: typed name must use the same script font as the placeholder.
     This selector intentionally beats the global ".cb-page input" Minion Pro rule. */
  .cb-page input#line1.cb-name-input,
  .cb-page #card input#line1.cb-name-input,
  #card input#line1.cb-name-input,
  input#line1.cb-name-input,
  .cb-name-row input#line1.cb-name-input{
    font-family:'Aurelly Signature','Great Vibes',cursive !important;
    font-weight:400 !important;
    font-style:normal !important;
    font-variant:normal !important;
    letter-spacing:0 !important;
    text-transform:none !important;
    color:#d01818 !important;
    -webkit-text-fill-color:#d01818 !important;
  }

  .cb-page input#line1.cb-name-input::placeholder,
  .cb-page #card input#line1.cb-name-input::placeholder,
  #card input#line1.cb-name-input::placeholder,
  input#line1.cb-name-input::placeholder,
  .cb-name-row input#line1.cb-name-input::placeholder{
    font-family:'Aurelly Signature','Great Vibes',cursive !important;
    font-weight:400 !important;
    font-style:normal !important;
    font-variant:normal !important;
    letter-spacing:0 !important;
    text-transform:none !important;
    color:#d01818 !important;
    opacity:1 !important;
    -webkit-text-fill-color:#d01818 !important;
  }

  .cb-page input#line1.cb-name-input::-webkit-input-placeholder,
  .cb-page #card input#line1.cb-name-input::-webkit-input-placeholder,
  #card input#line1.cb-name-input::-webkit-input-placeholder,
  input#line1.cb-name-input::-webkit-input-placeholder,
  .cb-name-row input#line1.cb-name-input::-webkit-input-placeholder{
    font-family:'Aurelly Signature','Great Vibes',cursive !important;
    font-weight:400 !important;
    font-style:normal !important;
    color:#d01818 !important;
    opacity:1 !important;
    -webkit-text-fill-color:#d01818 !important;
  }


  /* Restored visible Design Title panel
     Current source-of-truth placement: logged-in users see this under
     the Select Background heading in the left sidebar. */
  .builder-side-controls{
    width:100%;
    margin:0 0 16px;
    padding:0 6px;
  }

  .design-title-panel{
    width:100%;
    padding:12px 14px;
    border-radius:16px;
    border:1px solid rgba(34,58,113,.18);
    background:rgba(255,255,255,.54);
    box-shadow:0 10px 22px rgba(67,51,39,.08);
    backdrop-filter:blur(4px);
  }

  .design-title-panel label{
    display:block;
    margin:0 0 7px;
    color:#223a71;
    font-family:'Minion Pro',serif !important;
    font-size:19px;
    font-weight:700;
    line-height:1.1;
  }

  .design-title-panel input{
    width:100%;
    height:42px;
    border:1px solid rgba(34,58,113,.24);
    border-radius:10px;
    background:rgba(255,255,255,.90);
    color:#223a71;
    font-family:'Minion Pro',serif !important;
    font-size:18px;
    line-height:1.1;
    padding:8px 11px;
    outline:none;
    box-shadow:none;
  }

  .design-title-panel input:focus{
    border-color:rgba(34,58,113,.55);
    box-shadow:0 0 0 3px rgba(34,58,113,.12);
  }

  .design-title-panel input.is-invalid{
    border-color:#d01818;
    box-shadow:0 0 0 3px rgba(208,24,24,.14);
  }

  .design-title-error{
    display:none;
    margin-top:7px;
    color:#d01818;
    font-size:14px;
    line-height:1.2;
  }

  .design-title-error.is-visible{
    display:block;
  }

  .design-title-panel.shake{
    animation:designTitleShake .28s linear 1;
  }

  @keyframes designTitleShake{
    0%,100%{transform:translateX(0)}
    25%{transform:translateX(-4px)}
    75%{transform:translateX(4px)}
  }

</style>
</head>
<body>
<?php
$headerContext = [
  'active' => 'build',
  'show_contact' => true,
  'show_socials' => true,
];
include_once __DIR__ . '/header.php';
?>

<div class="cb-page">
  <div class="cb-shell">
    <aside class="cb-sidebar">
      <h2 class="cb-sidebar-title">Select Background</h2>

      <?php if ($loggedIn): ?>
      <div class="builder-side-controls" id="builderSideControls">
        <div class="design-title-panel" id="designTitlePanel">
          <label for="designTitle">Design Title</label>
          <input id="designTitle" type="text" placeholder="e.g. My Heavenly ID #1" autocomplete="off">
          <div id="designTitleError" class="design-title-error" aria-live="polite"></div>
        </div>
      </div>

      <div style="display:none;">
        <label for="savedDesigns">My Saved Designs</label>
        <select id="savedDesigns">
          <option value="">Loading&hellip;</option>
        </select>
      </div>
      <?php endif; ?>

      <div class="cb-theme-list">
        <?php foreach ($frontThemes as $i => $src): ?>
          <div
            class="theme-option <?= $i === 0 ? 'active' : '' ?>"
            data-front="<?= htmlspecialchars($src, ENT_QUOTES) ?>"
            data-back="<?= htmlspecialchars($themeBacks[$src] ?? '', ENT_QUOTES) ?>"
          >
            <div class="theme-preview">
              <img src="<?= htmlspecialchars($src, ENT_QUOTES) ?>" alt="Front theme preview">
            </div>
            <div class="theme-label"><?= htmlspecialchars(basename($src), ENT_QUOTES) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </aside>

    <section class="cb-main">
      <h1 class="cb-main-title">Heavenly ID Card Builder</h1>

      <div class="cb-stage-wrap">
        <!-- ADDED: cb-stage-inner holds perspective without overflow interference -->
        <div class="cb-stage-inner">
          <div class="card-wrapper" id="card">
            <div class="card-face card-front">
              <div class="cb-card-border"></div>

              <div class="cb-brand">
                <img src="card_logo.png" alt="Heavenly ID">
              </div>

              <div class="cb-card-title">CITIZEN of HEAVEN</div>

              <div class="foreground-section" id="foregroundCarousel" aria-label="Foreground image selector">
                <button type="button" class="fg-arrow fg-arrow-prev" id="fgPrev" aria-label="Previous foreground image">&#8249;</button>

                <div class="foreground-image-wrap">
                  <img id="foregroundImg" alt="Selected foreground image" />
                  <div class="foreground-empty" id="foregroundEmpty">Add foreground images to /foreground</div>
                </div>

                <button type="button" class="fg-arrow fg-arrow-next" id="fgNext" aria-label="Next foreground image">&#8250;</button>
              </div>

              <div class="cb-left-caption">PHOTO ID</div>
              <div class="cb-left-status">saved</div>

              <div class="info-block">
                <div class="cb-name-row">
                  <input class="cb-name-input" type="text" id="line1" placeholder="Enter Full Name" aria-label="Full Name">
                </div>

                <div class="info-row is-hidden">
                  <strong>I AM:</strong>
                  <input class="card-input" type="text" id="statusVisible" placeholder="Made in the image of God" aria-label="I Am Statement">
                </div>

                <div class="info-row">
                  <strong>Spiritual Gift:</strong>
                  <input class="card-input" type="text" id="gifts" placeholder="Encouragement" aria-label="Spiritual Gifts">
                </div>

                <div class="info-row">
                  <strong>Received Jesus:</strong>
                  <input
                    class="card-input"
                    type="text"
                    id="received"
                    placeholder="1992"
                    aria-label="Received Jesus Year"
                    inputmode="numeric"
                    pattern="[0-9]{4}"
                    maxlength="4"
                    autocomplete="off"
                    autocorrect="off"
                    autocapitalize="off"
                    spellcheck="false"
                    data-form-type="other"
                  >
                </div>

                <div class="info-row verse-row">
                  <strong>Bible Verse:</strong>
                  <div class="verse-select-wrap">
                    <input
                      class="card-input"
                      type="text"
                      id="verseRef"
                      placeholder="Type 3+ characters"
                      aria-label="Bible Verse"
                      role="combobox"
                      aria-autocomplete="list"
                      aria-expanded="false"
                      aria-controls="verse_listbox"
                      aria-haspopup="listbox"
                      autocomplete="off"
                      autocorrect="off"
                      autocapitalize="off"
                      spellcheck="false"
                      data-form-type="other"
                    >
                    <div id="verse_listbox" class="verse-listbox" role="listbox" tabindex="-1"></div>
                  </div>
                </div>
              </div>

              <div class="verse-text" id="previewVerseText"></div>
              <div class="cb-theme-caption" id="statusDisplay">&nbsp;</div>
              <div class="cb-scripture-caption" id="scriptureDisplay">&nbsp;</div>

              <div class="illustration-box" aria-label="Biblical illustration preview">
                <img alt="Biblical illustration" style="opacity:0; display:none;">
              </div>
            </div>

            <div class="card-face card-back">
              <img id="cardBackImage" src="<?= htmlspecialchars($firstBackPath ?: $defaultBackImage, ENT_QUOTES) ?>" alt="Back of card">
              <!-- CHANGED: replaced TinyMCE contenteditable + toolbar with plain textarea -->
              <div class="letter-intent" aria-label="Letter of Intent">
                <div class="letter-editor-shell" id="letterEditorShell">
                  <div class="letter-display" id="letterDisplay">
                    I <strong id="letterNameMirror">Enter Full Name</strong>
                    <span id="letterBodyMirror"><?= htmlspecialchars($defaultLetterIntentBody, ENT_QUOTES) ?></span>
                  </div>
                  <textarea id="letterEditor" aria-label="Letter of Intent" readonly tabindex="-1" aria-hidden="true"><?= htmlspecialchars($defaultLetterIntentBody, ENT_NOQUOTES) ?></textarea>
                </div>
              </div>
            </div>
          </div>
        </div><!-- /.cb-stage-inner -->
      </div>

      <div class="cb-card-buttons">
        <button id="btnFlip" class="btn">Flip Card</button>
        <button id="btnPreview" class="btn">Preview</button>
        <?php if ($loggedIn): ?>
          <button id="btnSave" class="btn">Save</button>
        <?php else: ?>
          <button id="btnSave" class="btn btn-login-disabled" data-requires-login="1" aria-disabled="true" title="Please Join / Sign In to save">Save</button>
        <?php endif; ?>
        <?php if ($loggedIn): ?>
          <button id="btnDownload" class="btn">Checkout</button>
        <?php else: ?>
          <button id="btnDownload" class="btn btn-login-disabled" data-requires-login="1" aria-disabled="true" title="Please Join / Sign In to checkout">Checkout</button>
        <?php endif; ?>
      </div>

      <!-- stores selected asset paths for save/guest checkout/registered checkout -->
      <input type="hidden" id="cardFrontPath" name="card_front_path" value="<?= htmlspecialchars($firstFrontPath, ENT_QUOTES) ?>">
      <input type="hidden" id="cardBackPath" name="card_back_path" value="<?= htmlspecialchars($firstBackPath, ENT_QUOTES) ?>"><!-- back path now checks /backcrdbg as well as /newcrdbg -->
      <input type="hidden" id="foregroundPath" name="foreground_path" value="<?= htmlspecialchars($foregroundImages[0] ?? '', ENT_QUOTES) ?>">

      <!-- name layout tracking for preview/rebuild/print designer -->
      <input type="hidden" id="name_font_resized" name="name_font_resized" value="0">
      <input type="hidden" id="name_font_size_px" name="name_font_size_px" value="88">
      <input type="hidden" id="name_layout_left_px" name="name_layout_left_px" value="">
      <input type="hidden" id="name_layout_top_px" name="name_layout_top_px" value="">
      <input type="hidden" id="name_layout_width_px" name="name_layout_width_px" value="">
      <input type="hidden" id="name_layout_height_px" name="name_layout_height_px" value="">
      <input type="hidden" id="name_safe_right_px" name="name_safe_right_px" value="">
      <input type="hidden" id="name_available_width_px" name="name_available_width_px" value="">
      <input type="hidden" id="name_text_align" name="name_text_align" value="left">
      <input type="hidden" id="name_padding_left_px" name="name_padding_left_px" value="0">

      <div id="downloadNote" style="display:none;"></div>

      <!-- hidden compatibility controls preserved for card.js hooks -->
      <div style="display:none;">
        <div class="info-row" style="position:relative;">
          <strong>I AM:</strong>
          <div class="combo-wrap">
            <input
              class="card-input"
              type="text"
              id="status"
              placeholder="Select or type status"
              role="combobox"
              aria-autocomplete="list"
              aria-expanded="false"
              aria-controls="status_listbox"
              aria-haspopup="listbox"
              autocomplete="off"
              list="statusOptions"
            />

            <button
              type="button"
              class="combo-btn"
              id="status_btn"
              aria-label="Show status options"
              aria-controls="status_listbox"
              aria-expanded="false"
            >
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>

          <div
            id="status_listbox"
            role="listbox"
            tabindex="-1"
            style="display:none; position:absolute; z-index:9999; left:0; right:0; margin-top:6px; background:#111; border:1px solid #555; border-radius:10px; max-height:220px; overflow:auto; padding:6px; box-shadow:0 8px 24px rgba(0,0,0,0.45);"
          ></div>

          <datalist id="statusOptions">
            <option value="Saved"></option>
            <option value="Redeemed"></option>
            <option value="Born Again"></option>
            <option value="Child of God"></option>
            <option value="Follower of Christ"></option>
            <option value="Washed by the Blood of Christ"></option>
            <option value="Set Free"></option>
            <option value="Forgiven"></option>
            <option value="Sanctified"></option>
            <option value="Justified"></option>
            <option value="Spirit-Filled"></option>
            <option value="New Creation"></option>
            <option value="Heir of the Kingdom"></option>
            <option value="Walking by Faith"></option>
            <option value="Alive in Christ"></option>
            <option value="Called and Chosen"></option>
            <option value="Delivered"></option>
            <option value="Restored"></option>
            <option value="Adopted into God&#8217;s Family"></option>
            <option value="Sealed by the Holy Spirit"></option>
          </datalist>
        </div>
      </div>
    </section>
  </div>
</div>

<div id="authModal" style="position:fixed; inset:0; background:rgba(0,0,0,0.6); display:none; justify-content:center; align-items:flex-start; padding-top:20px; z-index:2000;">
  <div style="background:#fff; width:80vw; max-width:600px; height:calc(100vh - 40px); padding:24px; position:relative; overflow-y:auto; overflow-x:hidden; border-radius:12px; box-sizing:border-box;">
    <div style="display:flex; justify-content:center; gap:16px; margin-bottom:20px;">
      <button id="joinTab" style="font-weight:600;">Join</button>
      <button id="signinTab">Sign In</button>
    </div>

    <form id="joinForm" autocomplete="off" style="display:flex; flex-direction:column; align-items:center; gap:14px;">
      <input name="first_name" placeholder="First Name" required>
      <input name="last_name" placeholder="Last Name" required>
      <input type="email" name="email" placeholder="Email" required>
      <input name="phone" placeholder="Phone" required>
      <input name="address" placeholder="Street Address" required>
      <input name="city" placeholder="City" required>
      <input name="state" placeholder="State" required>
      <input name="zipcode" placeholder="Zip Code (optional)">
      <input type="password" id="password" name="password" placeholder="Password" required>
      <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>

      <small id="pwHelp" style="font-size:12px;">Must be 12+ chars, 1 uppercase, 1 special character</small>

      <button type="submit" style="padding:12px;">Create Account</button>
      <div style="text-align:center;">OR</div>

      <button
        type="button"
        id="googleSignup"
        style="padding:12px; background:url(4LSMF.png) no-repeat center center; background-size:contain; width:290px; height:61px; border:0; cursor:pointer;"
      ></button>
    </form>

    <form id="signinForm" style="display:none; flex-direction:column; gap:10px;">
      <input name="email" type="email" placeholder="Email" required>
      <input name="password" type="password" placeholder="Password" required>
      <button type="submit" style="background:#0078d4; color:#fff; padding:10px; border:none;">Sign In</button>
    </form>
  </div>
</div>

<div id="contactModal" style="position:fixed; inset:0; background:rgba(0,0,0,.58); display:none; align-items:center; justify-content:center; padding:18px; z-index:2200; box-sizing:border-box;">
  <div style="width:min(860px, 96vw); max-height:calc(100vh - 36px); overflow:auto; background:rgba(255,255,255,.94); border-radius:22px; border:1px solid rgba(0,0,0,.08); box-shadow:0 24px 54px rgba(0,0,0,.18); backdrop-filter:blur(8px); padding:22px 22px 18px;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:14px; margin-bottom:12px;">
      <div>
        <h2 style="margin:0; font-size:clamp(28px, 3vw, 42px); color:#24416f;">Contact Us</h2>
        <p style="margin:6px 0 0; font-size:18px; line-height:1.4;">Send us a message and we'll get back to you as soon as we can.</p>
      </div>
      <button id="contactClose" type="button" aria-label="Close contact form" style="border:none; background:rgba(33,55,98,.10); color:#213762; width:44px; height:44px; border-radius:999px; font-size:28px; line-height:1; cursor:pointer; flex:0 0 auto;">&times;</button>
    </div>

    <form id="contactForm">
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:14px;">
        <div style="display:flex; flex-direction:column; gap:6px; text-align:left; min-width:0;">
          <label for="c_name" style="font-size:14px; opacity:.9;">Name</label>
          <input id="c_name" name="name" placeholder="Your name" required style="width:100%; max-width:100%; font-size:16px; padding:12px 12px; border:1px solid rgba(0,0,0,.14); border-radius:12px; background:rgba(255,255,255,.90); outline:none;">
        </div>

        <div style="display:flex; flex-direction:column; gap:6px; text-align:left; min-width:0;">
          <label for="c_email" style="font-size:14px; opacity:.9;">Email</label>
          <input id="c_email" name="email" type="email" placeholder="you@example.com" required style="width:100%; max-width:100%; font-size:16px; padding:12px 12px; border:1px solid rgba(0,0,0,.14); border-radius:12px; background:rgba(255,255,255,.90); outline:none;">
        </div>

        <div style="display:flex; flex-direction:column; gap:6px; text-align:left; min-width:0; grid-column:1 / -1;">
          <label for="c_message" style="font-size:14px; opacity:.9;">Message</label>
          <textarea id="c_message" name="message" rows="5" placeholder="How can we help?" required style="width:100%; max-width:100%; font-size:16px; padding:12px 12px; border:1px solid rgba(0,0,0,.14); border-radius:12px; background:rgba(255,255,255,.90); outline:none; resize:vertical; min-height:140px;"></textarea>
        </div>

        <div style="grid-column:1 / -1; display:flex; flex-direction:column; gap:10px; margin-top:4px;">
          <button type="submit" id="contactSubmit" style="border:none; border-radius:12px; padding:12px 14px; background:#0078d4; color:#fff; font-size:16px; cursor:pointer; box-shadow:0 10px 20px rgba(0,0,0,.10);">Send Message</button>
          <p id="contactFine" aria-live="polite" style="margin:0; text-align:center; font-size:14px; opacity:.95; min-height:18px;"></p>
        </div>
      </div>
    </form>
  </div>
</div>

<div id="previewModal" style="position:fixed; inset:0; background:rgba(0,0,0,0.65); display:none; justify-content:center; align-items:flex-start; padding:30px 12px; z-index:3000;">
  <div style="background:#fff; width:min(92vw, 520px); border-radius:14px; padding:16px 16px 14px; box-sizing:border-box; position:relative; box-shadow:0 18px 60px rgba(0,0,0,0.35);">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px;">
      <div style="font-weight:800; font-size:14px; letter-spacing:0.3px;">Preview</div>
      <div style="display:flex; gap:10px; align-items:center;">
        <button type="button" id="previewCloseBtn" class="btn" style="margin-top:0; background:#111; border-color:#111; min-width:auto;">Close</button>
      </div>
    </div>

    <div id="previewStage" style="width:100%; display:flex; justify-content:center; align-items:center; padding:10px 0 2px; box-sizing:border-box;">
      <div id="previewMount" style="width:333px; height:222px; border-radius:18px; overflow:hidden; border:1px solid rgba(0,0,0,0.18); box-shadow:0 14px 36px rgba(0,0,0,0.22); background:#f8f8f8; position:relative; cursor:pointer;"></div>
    </div>

    <div style="margin-top:10px; font-size:12px; color:#666; text-align:center;">This preview is scaled to wallet-size style.</div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
  window.CB_SHOPIFY = window.CB_SHOPIFY || {
    domain: 'heavenly-id-checkout.myshopify.com',
    storefrontAccessToken: '0f5a95b76314849b31cd92d0e1b7ef66',
    apiVersion: '2026-01',
    productId: '14870420685165'
  };

  window.CB_CURRENT_DESIGN_ID = window.CB_CURRENT_DESIGN_ID || null;
  window.CB_setCurrentDesignId = window.CB_setCurrentDesignId || function(id) {
    const n = parseInt(id, 10);
    window.CB_CURRENT_DESIGN_ID = (Number.isFinite(n) && n > 0) ? String(n) : null;
    return window.CB_CURRENT_DESIGN_ID;
  };

  document.addEventListener('DOMContentLoaded', function(){
    const authModal = document.getElementById('authModal');
    const joinForm = document.getElementById('joinForm');
    const signinForm = document.getElementById('signinForm');
    const joinTab = document.getElementById('joinTab');
    const signinTab = document.getElementById('signinTab');
    const contactModal = document.getElementById('contactModal');
    const contactClose = document.getElementById('contactClose');
    const contactFine = document.getElementById('contactFine');
    const contactForm = document.getElementById('contactForm');
    const statusInput = document.getElementById('status');
    const statusVisible = document.getElementById('statusVisible');
    const statusDisplay = document.getElementById('statusDisplay');
    const verseRef = document.getElementById('verseRef');
    const scriptureDisplay = document.getElementById('scriptureDisplay');

    function syncStatusValue(value){
      if (statusInput) statusInput.value = value || '';
      if (statusVisible && statusVisible.value !== value) statusVisible.value = value || '';
    }

    function showJoin(){
      if (joinForm) joinForm.style.display = 'flex';
      if (signinForm) signinForm.style.display = 'none';
      if (joinTab) joinTab.style.fontWeight = '700';
      if (signinTab) signinTab.style.fontWeight = '400';
    }

    function showSignin(){
      if (joinForm) joinForm.style.display = 'none';
      if (signinForm) signinForm.style.display = 'flex';
      if (joinTab) joinTab.style.fontWeight = '400';
      if (signinTab) signinTab.style.fontWeight = '700';
    }

    document.addEventListener('heavenly:open-auth', function(){
      if (!authModal) return;
      showJoin();
      authModal.style.display = 'flex';
    });

    document.addEventListener('heavenly:open-contact', function(){
      if (!contactModal) return;
      contactModal.style.display = 'flex';
    });

    if (authModal) {
      authModal.addEventListener('click', function(e){
        if (e.target === authModal) authModal.style.display = 'none';
      });
    }

    if (joinTab) joinTab.addEventListener('click', showJoin);
    if (signinTab) signinTab.addEventListener('click', showSignin);

    if (contactClose) {
      contactClose.addEventListener('click', function(){
        contactModal.style.display = 'none';
      });
    }

    if (contactModal) {
      contactModal.addEventListener('click', function(e){
        if (e.target === contactModal) contactModal.style.display = 'none';
      });
    }

    if (contactForm) {
      contactForm.addEventListener('submit', function(e){
        e.preventDefault();
        if (contactFine) contactFine.textContent = 'Demo contact form ready. Wire to contact_send.php if needed.';
      });
    }

    if (statusVisible) {
      statusVisible.addEventListener('input', function(){
        syncStatusValue(statusVisible.value);
      });
      syncStatusValue(statusVisible.value || statusInput?.value || '');
    } else if (statusInput && statusDisplay) {
      statusInput.addEventListener('input', function(){
        syncStatusValue(statusInput.value);
      });
    }

    if (verseRef && scriptureDisplay) {
      function syncBottomVerse(){
        scriptureDisplay.textContent = verseRef.value || '';
      }

      verseRef.addEventListener('input', syncBottomVerse);
      verseRef.addEventListener('change', syncBottomVerse);
      syncBottomVerse();
    }
  });
</script>

<script>
(function () {
  function initNameFitAndLetterMirror() {
    var nameInput = document.getElementById('line1');
    var letterNameMirror = document.getElementById('letterNameMirror');
    var letterEditor = document.getElementById('letterEditor');
    var canvas = document.createElement('canvas');
    var ctx = canvas.getContext('2d');

    if (!nameInput || !ctx) return;

    function getDisplayedName() {
      var entered = String(nameInput.value || '').trim();
      return entered || nameInput.getAttribute('placeholder') || 'Enter Full Name';
    }

    var defaultLetterBody = <?= json_encode($defaultLetterIntentBody, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    var letterBodyMirror = document.getElementById('letterBodyMirror');

    function syncLetterName() {
      if (letterNameMirror) {
        letterNameMirror.textContent = getDisplayedName();
      }

      if (letterBodyMirror) {
        letterBodyMirror.textContent = defaultLetterBody;
      }

      if (letterEditor && !String(letterEditor.value || '').trim()) {
        letterEditor.value = defaultLetterBody;
      }
    }

    function fontForSize(sizePx) {
      var cs = window.getComputedStyle(nameInput);
      return [
        cs.fontStyle || 'normal',
        cs.fontVariant || 'normal',
        cs.fontWeight || '400',
        sizePx + 'px',
        cs.fontFamily || 'cursive'
      ].join(' ');
    }

    function measuredWidth(text, sizePx) {
      ctx.font = fontForSize(sizePx);
      return ctx.measureText(text || '').width;
    }

    function setHiddenValue(id, value) {
      var el = document.getElementById(id);
      if (el) el.value = value;
    }

    function syncNameLayoutHidden(nextSize, availableWidth, safeRight) {
      var card = document.getElementById('card');
      var row = nameInput ? nameInput.closest('.cb-name-row') : null;
      if (!card || !row || !nameInput) return;

      var cardRect = card.getBoundingClientRect();
      var rowRect = row.getBoundingClientRect();
      var scale = cardRect.width ? (card.offsetWidth / cardRect.width) : 1;

      var left = Math.round((rowRect.left - cardRect.left) * scale);
      var top = Math.round((rowRect.top - cardRect.top) * scale);
      var width = Math.round(row.offsetWidth || ((rowRect.width || 0) * scale));
      var height = Math.round(row.offsetHeight || ((rowRect.height || 0) * scale));
      var paddingLeft = parseFloat(window.getComputedStyle(nameInput).paddingLeft || '0') || 0;

      setHiddenValue('name_font_size_px', String(nextSize));
      setHiddenValue('name_font_resized', nextSize < 88 ? '1' : '0');
      setHiddenValue('name_layout_left_px', String(left));
      setHiddenValue('name_layout_top_px', String(top));
      setHiddenValue('name_layout_width_px', String(width));
      setHiddenValue('name_layout_height_px', String(height));
      setHiddenValue('name_safe_right_px', String(Math.round(safeRight || 0)));
      setHiddenValue('name_available_width_px', String(Math.round(availableWidth || 0)));
      setHiddenValue('name_text_align', window.getComputedStyle(nameInput).textAlign || 'left');
      setHiddenValue('name_padding_left_px', String(Math.round(paddingLeft)));
    }

    function getNameRow() {
      return nameInput ? nameInput.closest('.cb-name-row') : null;
    }

    function getNameMeasureProbe() {
      var probe = document.getElementById('cbNameMeasureProbe');
      if (probe) return probe;

      probe = document.createElement('span');
      probe.id = 'cbNameMeasureProbe';
      probe.setAttribute('aria-hidden', 'true');
      probe.style.position = 'fixed';
      probe.style.left = '-99999px';
      probe.style.top = '-99999px';
      probe.style.visibility = 'hidden';
      probe.style.whiteSpace = 'nowrap';
      probe.style.pointerEvents = 'none';
      document.body.appendChild(probe);
      return probe;
    }

    function measuredNameWidthDom(text, sizePx) {
      var probe = getNameMeasureProbe();
      var cs = window.getComputedStyle(nameInput);

      probe.style.fontFamily = cs.fontFamily || "'Aurelly Signature', cursive";
      probe.style.fontWeight = cs.fontWeight || '400';
      probe.style.fontStyle = cs.fontStyle || 'normal';
      probe.style.letterSpacing = cs.letterSpacing || '0px';
      probe.style.fontSize = sizePx + 'px';
      probe.style.lineHeight = cs.lineHeight || '.92';
      probe.textContent = text || '';

      // Extra overhang buffer protects cursive/script letters from touching the seam border.
      return probe.getBoundingClientRect().width + 18;
    }

    function getNameAvailableWidth() {
      /*
        No-clip, border-aware name fitting:
        - Preserve the current/default left start position.
        - Dynamically set the row width to end comfortably before the seam border.
        - Shrink font until the actual rendered text width fits that safe area.
      */
      var card = document.getElementById('card');
      var row = getNameRow();
      var inputRect = nameInput.getBoundingClientRect();

      if (!card || !row || !inputRect.width) {
        return Math.max(10, nameInput.clientWidth - 18);
      }

      var cardRect = card.getBoundingClientRect();
      var scale = cardRect.width ? (card.offsetWidth / cardRect.width) : 1;
      var inputLeft = (inputRect.left - cardRect.left) * scale;

      // Inside-right seam guard on the 1000px card canvas.
      // This is intentionally more conservative than the card edge so long names shrink
      // before they reach the baseball-seam border.
      var safeRight = card.offsetWidth - 104;
      var rightComfortMargin = 18;
      var available = Math.max(80, safeRight - inputLeft - rightComfortMargin);

      row.style.width = Math.round(available) + 'px';
      nameInput.style.width = '100%';

      nameInput.dataset.safeRightPx = String(safeRight);
      nameInput.dataset.availableWidthPx = String(available);
      nameInput.dataset.nameLeftPx = String(Math.round(inputLeft));

      return available;
    }

    function fitNameText() {
      if (typeof window.CB_forceNameScriptFont === 'function') window.CB_forceNameScriptFont();
      var text = getDisplayedName();
      var maxSize = 88;
      var minSize = 20;
      var availableWidth = getNameAvailableWidth();
      var nextSize = maxSize;

      if (measuredNameWidthDom(text, maxSize) > availableWidth) {
        var low = minSize;
        var high = maxSize;

        for (var i = 0; i < 12; i++) {
          var mid = (low + high) / 2;
          if (measuredNameWidthDom(text, mid) <= availableWidth) {
            low = mid;
          } else {
            high = mid;
          }
        }

        nextSize = Math.max(minSize, Math.floor(low));
      }

      // Final safety pass for browsers that report script fonts slightly differently.
      while (nextSize > minSize && measuredNameWidthDom(text, nextSize) > availableWidth) {
        nextSize -= 1;
      }

      nameInput.style.setProperty('--cb-name-fit-size', nextSize + 'px');
      nameInput.style.fontSize = nextSize + 'px';
      nameInput.style.overflow = 'visible';
      nameInput.style.textOverflow = 'clip';

      syncNameLayoutHidden(
        nextSize,
        parseFloat(nameInput.dataset.availableWidthPx || availableWidth || 0),
        parseFloat(nameInput.dataset.safeRightPx || 0)
      );
      syncLetterName();
    }

    nameInput.addEventListener('input', fitNameText);
    window.addEventListener('resize', fitNameText);

    if (window.ResizeObserver) {
      new ResizeObserver(fitNameText).observe(nameInput);
    }

    if (document.fonts && document.fonts.ready) {
      document.fonts.ready.then(fitNameText).catch(fitNameText);
    }


    window.CB_getNameLayoutForPrint = function () {
      return {
        font_resized: document.getElementById('name_font_resized')?.value || '0',
        font_size_px: document.getElementById('name_font_size_px')?.value || '',
        left_px: document.getElementById('name_layout_left_px')?.value || '',
        top_px: document.getElementById('name_layout_top_px')?.value || '',
        width_px: document.getElementById('name_layout_width_px')?.value || '',
        height_px: document.getElementById('name_layout_height_px')?.value || '',
        safe_right_px: document.getElementById('name_safe_right_px')?.value || '',
        available_width_px: document.getElementById('name_available_width_px')?.value || '',
        text_align: document.getElementById('name_text_align')?.value || 'left',
        padding_left_px: document.getElementById('name_padding_left_px')?.value || '0'
      };
    };

    window.CB_getLetterOfIntentText = function () {
      var name = getDisplayedName();
      var body = letterEditor ? String(letterEditor.value || '').trim() : '';
      if (!body) body = defaultLetterBody;
      return 'I ' + name + ' ' + body;
    };

    fitNameText();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNameFitAndLetterMirror);
  } else {
    initNameFitAndLetterMirror();
  }
})();
</script>

<!-- theme thumbnail click: update card background + save path -->
<script>
(function () {
  function cssUrl(src) {
    src = String(src || '').trim();
    return 'url("' + src.replace(/"/g, '%22') + '")';
  }

  function initThemePicker() {
    var options   = document.querySelectorAll('.theme-option');
    var cardFront = document.querySelector('.card-front');
    var cardBackImage = document.getElementById('cardBackImage');
    var pathField = document.getElementById('cardFrontPath');
    var backPathField = document.getElementById('cardBackPath');
    var fallbackBackSrc = <?= json_encode($defaultBackImage, JSON_UNESCAPED_SLASHES) ?>;

    if (!options.length || !cardFront) return;

    function applyTheme(opt) {
      if (!opt) return;
      var src = opt.getAttribute('data-front') || '';
      var backSrc = opt.getAttribute('data-back') || '';
      if (!src) return;

      cardFront.style.backgroundImage = cssUrl(src);
      if (pathField) pathField.value = src;
      if (backPathField) backPathField.value = backSrc;
      if (cardBackImage) cardBackImage.src = backSrc || fallbackBackSrc;

      options.forEach(function (o) { o.classList.remove('active'); });
      opt.classList.add('active');
    }

    options.forEach(function (opt) {
      opt.addEventListener('click', function () {
        applyTheme(opt);
      });
    });

    applyTheme(document.querySelector('.theme-option.active') || options[0]);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initThemePicker);
  } else {
    initThemePicker();
  }
})();
</script>

<script>
  window.CB_DISABLE_ILLUSTRATION_UPLOAD = true;
</script>


<script>
  window.CB_FOREGROUND_IMAGES = <?= json_encode($foregroundImages, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
</script>

<script>
(function () {
  function initForegroundCarousel() {
    var images = Array.isArray(window.CB_FOREGROUND_IMAGES) ? window.CB_FOREGROUND_IMAGES : [];
    var section = document.getElementById('foregroundCarousel');
    var img = document.getElementById('foregroundImg');
    var empty = document.getElementById('foregroundEmpty');
    var prev = document.getElementById('fgPrev');
    var next = document.getElementById('fgNext');
    var pathField = document.getElementById('foregroundPath');
    var index = 0;

    if (!section || !img) return;

    function render() {
      if (!images.length) {
        section.classList.add('is-empty');
        section.classList.remove('is-single');
        img.removeAttribute('src');
        if (pathField) pathField.value = '';
        return;
      }

      section.classList.remove('is-empty');
      section.classList.toggle('is-single', images.length <= 1);

      if (index < 0) index = images.length - 1;
      if (index >= images.length) index = 0;

      img.src = images[index];
      img.alt = 'Selected foreground image ' + (index + 1);
      if (pathField) pathField.value = images[index];
      if (empty) empty.style.display = '';
    }

    if (prev) {
      prev.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        index -= 1;
        render();
      });
    }

    if (next) {
      next.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        index += 1;
        render();
      });
    }

    render();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initForegroundCarousel);
  } else {
    initForegroundCarousel();
  }
})();
</script>



<script>
(function () {
  function initVerseDropdown() {
    var input = document.getElementById('verseRef');
    var listbox = document.getElementById('verse_listbox');
    var datalist = null; // Native datalist disabled to prevent browser autocomplete collision.
    var currentItems = [];
    var debounceTimer = null;
    var activeController = null;

    if (!input || !listbox) return;

    function closeList() {
      listbox.classList.remove('is-open');
      input.setAttribute('aria-expanded', 'false');
    }

    function setOptions(items) {
      currentItems = Array.isArray(items) ? items : [];

      renderList();
    }

    function renderList() {
      listbox.innerHTML = '';

      currentItems.forEach(function (value) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'verse-option';
        btn.setAttribute('role', 'option');
        btn.textContent = value;
        btn.addEventListener('mousedown', function (e) {
          e.preventDefault();
          chooseValue(value);
        });
        listbox.appendChild(btn);
      });

      if (currentItems.length) {
        listbox.classList.add('is-open');
        input.setAttribute('aria-expanded', 'true');
      } else {
        closeList();
      }
    }

    function chooseValue(value) {
      input.value = value;
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.dispatchEvent(new Event('change', { bubbles: true }));
      closeList();
      input.focus();
    }

    function lookupVerses() {
      var q = String(input.value || '').trim();

      if (q.length < 3) {
        if (activeController) activeController.abort();
        setOptions([]);
        closeList();
        return;
      }

      if (activeController) activeController.abort();
      activeController = new AbortController();

      fetch('bible_verse_search.php?q=' + encodeURIComponent(q), {
        method: 'GET',
        headers: { 'Accept': 'application/json' },
        signal: activeController.signal
      })
      .then(function (response) {
        if (!response.ok) throw new Error('Verse lookup failed');
        return response.json();
      })
      .then(function (data) {
        setOptions(data && data.success && Array.isArray(data.items) ? data.items : []);
      })
      .catch(function (err) {
        if (err && err.name === 'AbortError') return;
        setOptions([]);
        closeList();
      });
    }

    function scheduleLookup() {
      window.clearTimeout(debounceTimer);
      debounceTimer = window.setTimeout(lookupVerses, 180);
    }

    input.addEventListener('focus', function () {
      if (String(input.value || '').trim().length >= 3) scheduleLookup();
    });

    input.addEventListener('click', function () {
      if (String(input.value || '').trim().length >= 3) scheduleLookup();
    });

    input.addEventListener('input', scheduleLookup);

    input.addEventListener('keydown', function (e) {
      var items = Array.prototype.slice.call(listbox.querySelectorAll('.verse-option'));

      if (e.key === 'ArrowDown') {
        if (!items.length && String(input.value || '').trim().length >= 3) {
          lookupVerses();
          items = Array.prototype.slice.call(listbox.querySelectorAll('.verse-option'));
        }
        if (items[0]) {
          e.preventDefault();
          items[0].focus();
        }
      } else if (e.key === 'Escape') {
        closeList();
      } else if (e.key === 'Enter' && listbox.classList.contains('is-open') && items.length === 1) {
        e.preventDefault();
        chooseValue(items[0].textContent);
      }
    });

    listbox.addEventListener('keydown', function (e) {
      var items = Array.prototype.slice.call(listbox.querySelectorAll('.verse-option'));
      var index = items.indexOf(document.activeElement);

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        var next = items[index + 1] || items[0];
        if (next) next.focus();
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        var prev = items[index - 1] || items[items.length - 1];
        if (prev) prev.focus();
      } else if (e.key === 'Enter' && document.activeElement && document.activeElement.classList.contains('verse-option')) {
        e.preventDefault();
        chooseValue(document.activeElement.textContent);
      } else if (e.key === 'Escape') {
        closeList();
        input.focus();
      }
    });

    document.addEventListener('mousedown', function (e) {
      if (!listbox.contains(e.target) && e.target !== input) closeList();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initVerseDropdown);
  } else {
    initVerseDropdown();
  }
})();
</script>


<script>
  /*
    Legacy card.js compatibility:
    This builder uses #letterEditor as a normal textarea. The older card.js can still reference tinymce.
    This shim prevents ReferenceError while preserving basic get/set content behavior.
  */
  window.tinymce = window.tinymce || (function(){
    var editors = {};

    function makeEditor(id){
      var el = document.getElementById(id);
      if (!el) return null;
      if (editors[id]) return editors[id];

      var editor = {
        id: id,
        targetElm: el,
        getContent: function(){ return (window.CB_getLetterOfIntentText ? window.CB_getLetterOfIntentText() : (el.value || "")); },
        setContent: function(value){
          var v = value || "";
          var defaultBody = <?= json_encode($defaultLetterIntentBody, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
          if (!String(v).trim()) v = defaultBody;
          el.value = v;
          var mirror = document.getElementById("letterBodyMirror");
          if (mirror) {
            mirror.textContent = String(v).replace(/^I\s+.+?\s+declare\s+that\s+/i, "declare that ");
          }
        },
        save: function(){},
        on: function(eventName, handler){
          if (typeof handler === "function") el.addEventListener("input", handler);
        },
        focus: function(){ el.focus(); }
      };

      editors[id] = editor;
      return editor;
    }

    return {
      init: function(options){
        var selector = options && options.selector ? options.selector : "#letterEditor";
        var id = selector.charAt(0) === "#" ? selector.slice(1) : selector.replace(/^[.#]/, "");
        var editor = makeEditor(id);
        if (editor && options && typeof options.setup === "function") options.setup(editor);
        return Promise.resolve(editor ? [editor] : []);
      },
      get: function(id){ return makeEditor(id); },
      triggerSave: function(){},
      activeEditor: null
    };
  })();
</script>


<script>
(function(){
  function lockLetterIntentEditing(){
    var editor = document.getElementById('letterEditor');
    if (!editor) return;

    editor.setAttribute('readonly', 'readonly');
    editor.setAttribute('tabindex', '-1');
    editor.setAttribute('aria-hidden', 'true');

    ['keydown', 'keypress', 'paste', 'drop', 'cut'].forEach(function(eventName){
      editor.addEventListener(eventName, function(e){
        e.preventDefault();
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', lockLetterIntentEditing);
  } else {
    lockLetterIntentEditing();
  }
})();
</script>


<script>
(function(){
  function normalizeReceivedYear(value){
    return String(value || '').replace(/\D/g, '').slice(0, 4);
  }

  function markYearValidity(input){
    if (!input) return true;
    var value = String(input.value || '').trim();
    var ok = value === '' || /^\d{4}$/.test(value);
    input.setAttribute('aria-invalid', ok ? 'false' : 'true');
    input.classList.toggle('is-invalid-year', !ok);
    return ok;
  }

  function initReceivedJesusYear(){
    var input = document.getElementById('received');
    if (!input) return;

    input.setAttribute('type', 'text');
    input.setAttribute('inputmode', 'numeric');
    input.setAttribute('pattern', '[0-9]{4}');
    input.setAttribute('maxlength', '4');
    input.setAttribute('autocomplete', 'off');
    input.setAttribute('autocorrect', 'off');
    input.setAttribute('autocapitalize', 'off');
    input.setAttribute('spellcheck', 'false');
    input.setAttribute('data-form-type', 'other');

    input.addEventListener('keydown', function(e){
      if (e.ctrlKey || e.metaKey || e.altKey) return;
      if (['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight','Home','End'].indexOf(e.key) !== -1) return;
      if (!/^\d$/.test(e.key)) e.preventDefault();
    });

    input.addEventListener('input', function(){
      var cleaned = normalizeReceivedYear(input.value);
      if (input.value !== cleaned) input.value = cleaned;
      markYearValidity(input);
    });

    input.addEventListener('paste', function(){
      window.setTimeout(function(){
        input.value = normalizeReceivedYear(input.value);
        markYearValidity(input);
      }, 0);
    });

    input.addEventListener('blur', function(){
      markYearValidity(input);
    });

    input.value = normalizeReceivedYear(input.value);
    markYearValidity(input);
  }

  window.CB_validateReceivedJesusYear = function(){
    var input = document.getElementById('received');
    if (!input) return true;
    var value = String(input.value || '').trim();
    if (value !== '' && !/^\d{4}$/.test(value)) {
      alert('Received Jesus must be exactly 4 digits, e.g. 1992.');
      input.focus();
      return false;
    }
    return true;
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initReceivedJesusYear);
  } else {
    initReceivedJesusYear();
  }
})();
</script>


<script>
(function(){
  function forceNameScriptFont(){
    var input = document.getElementById('line1');
    if (!input) return;

    var scriptFont = "'Aurelly Signature','Great Vibes',cursive";
    input.style.setProperty('font-family', scriptFont, 'important');
    input.style.setProperty('font-weight', '400', 'important');
    input.style.setProperty('font-style', 'normal', 'important');
    input.style.setProperty('font-variant', 'normal', 'important');
    input.style.setProperty('letter-spacing', '0', 'important');
    input.style.setProperty('text-transform', 'none', 'important');
    input.style.setProperty('color', '#d01818', 'important');
    input.style.setProperty('-webkit-text-fill-color', '#d01818', 'important');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', forceNameScriptFont);
  } else {
    forceNameScriptFont();
  }

  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(forceNameScriptFont).catch(function(){});
  }

  window.CB_forceNameScriptFont = forceNameScriptFont;
})();
</script>

<script>
  window.CB_IS_LOGGED_IN = <?= $loggedIn ? 'true' : 'false' ?>;
</script>

<script>
(function(){
  function openHeavenlyAuthModal(){
    document.dispatchEvent(new Event('heavenly:open-auth'));
    var modal = document.getElementById('authModal');
    if (modal) modal.style.display = 'flex';
  }

  function initLoggedOutGate(){
    if (window.CB_IS_LOGGED_IN === true || window.CB_IS_LOGGED_IN === '1' || window.CB_IS_LOGGED_IN === 1) return;

    ['btnSave', 'btnDownload'].forEach(function(id){
      var btn = document.getElementById(id);
      if (!btn) return;

      btn.setAttribute('aria-disabled', 'true');
      btn.setAttribute('data-requires-login', '1');
      btn.classList.add('btn-login-disabled');

      btn.addEventListener('click', function(e){
        e.preventDefault();
        e.stopImmediatePropagation();
        openHeavenlyAuthModal();
        return false;
      }, true);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLoggedOutGate);
  } else {
    initLoggedOutGate();
  }
})();
</script>

<script src="card.js"></script>
</body>
</html>