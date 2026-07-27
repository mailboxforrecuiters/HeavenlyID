<?php
session_start();

$isAdmin = !empty($_SESSION["admin_logged_in"]);

$loggedIn = false;
$userName = '';

if (isset($_SESSION['user_id']) && isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
  $loggedIn = true;
  $userName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
}

$downloadDesignHref = $loggedIn ? '/my_designs.php' : '/guest_download.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Citizen of Heaven ID | Heavenly ID</title>
  <meta name="description" content="Create and personalize your own wallet-size Citizen of Heaven ID card as a daily reminder of encouragement and identity in Jesus." />

  <script src="https://accounts.google.com/gsi/client" async defer></script>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>

  <style>
    @font-face{
      font-family:"Minion Pro";
      src:url("MinionPro-Medium.woff") format("woff");
      font-weight:500;
      font-style:normal;
      font-display:swap;
    }

    :root{
      --hid-navy:#061d45;
      --hid-navy-2:#092a59;
      --hid-blue:#0c68bf;
      --hid-teal:#086f82;
      --hid-green:#4f9a12;
      --hid-gold:#dca018;
      --hid-gold-2:#e9c46a;
      --hid-text:#171a22;
      --hid-muted:#252b35;
      --hid-cream:#fffdf7;
      --hid-card:#ffffff;
      --hid-shadow:0 18px 42px rgba(19,36,64,.14);
      --hid-shadow-soft:0 10px 28px rgba(19,36,64,.10);
      --hid-max:1320px;
    }

    *{ box-sizing:border-box; }

    html,
    body{
      margin:0;
      min-height:100%;
      overflow-x:hidden;
      color:var(--hid-text);
      font-family:"Minion Pro", Georgia, serif;
      background:#f9fcff;
    }

    body,
    button,
    input,
    textarea{
      font-family:"Minion Pro", Georgia, serif;
    }

    a{ color:inherit; }

    /* Header comfort overrides for the shared header.php include. */
    body .hh-header{
      background:rgba(255,253,247,.96) !important;
      box-shadow:0 8px 28px rgba(6,29,69,.07) !important;
    }

    body .hh-nav{
      border-top:0 !important;
      border-bottom:2px solid rgba(220,160,24,.86) !important;
      background:rgba(255,253,247,.96) !important;
      box-shadow:none !important;
    }

    body .hh-nav-inner{
      min-height:76px !important;
      padding:0 clamp(18px, 3.2vw, 44px) !important;
      gap:22px !important;
    }

    body .hh-nav-logo{
      width:118px !important;
      margin-right:18px !important;
    }

    body .hh-nav-logo img{
      width:92px !important;
      max-width:92px !important;
      filter:drop-shadow(0 7px 14px rgba(6,29,69,.14)) !important;
    }

    body .hh-left{
      gap:32px !important;
    }

    body .hh-link,
    body .hh-utility,
    body .hh-desktop-user a,
    body .hh-mobile-user a{
      color:var(--hid-navy) !important;
      font-size:20px !important;
      line-height:1 !important;
      letter-spacing:.01em;
    }

    body .hh-link--active{
      color:var(--hid-navy) !important;
      font-weight:700 !important;
    }

    body .hh-link--active::after{
      bottom:-22px !important;
      height:2px !important;
      background:linear-gradient(90deg, var(--hid-gold), var(--hid-gold-2)) !important;
    }

    body .hh-right{
      gap:18px !important;
    }

    body .hh-right-stack{
      flex-direction:row !important;
      gap:20px !important;
    }

    body .hh-social{
      width:46px !important;
      height:46px !important;
      background:linear-gradient(180deg,#3e8df5,#1557cc) !important;
      box-shadow:0 9px 18px rgba(6,29,69,.15) !important;
    }

    body .hh-socials a:nth-child(2),
    body .hh-mobile-socials a:nth-child(2){
      background:radial-gradient(circle at 28% 100%, #ffd36b 0 18%, transparent 19%), linear-gradient(135deg,#6b51e6,#e53b91 45%,#ff9c34) !important;
    }

    body .hh-contact-btn{
      display:inline-flex !important;
      align-items:center !important;
      justify-content:center !important;
      gap:10px !important;
      min-width:150px !important;
      padding:11px 22px !important;
      border-radius:999px !important;
      border:1px solid rgba(151,101,10,.45) !important;
      background:linear-gradient(180deg,#ffe5a5 0%, #efbd35 100%) !important;
      color:#071d42 !important;
      font-size:20px !important;
      box-shadow:0 10px 20px rgba(96,61,8,.16), inset 0 1px 0 rgba(255,255,255,.65) !important;
    }

    body .hh-contact-btn::before{
      content:"✉";
      font-size:18px;
      line-height:1;
    }

    body .hh-spacer{
      height:76px !important;
    }

    body .hk-footer{
      margin-top:0 !important;
      padding:24px 18px 26px !important;
      background:linear-gradient(180deg,#062653,#001d3f) !important;
      color:#fffdf7 !important;
      border-top:1px solid rgba(220,160,24,.45) !important;
      font-size:18px !important;
      box-shadow:0 -12px 28px rgba(0,0,0,.10) !important;
    }

    .hid-page{
      min-height:calc(100vh - 76px);
      background:#fbfdff;
    }

    .hid-hero{
      position:relative;
      overflow:hidden;
      padding:clamp(34px, 4vw, 64px) clamp(18px, 4vw, 56px) 28px;
      background:
        linear-gradient(90deg, rgba(255,255,255,.12), rgba(255,255,255,.34)),
        url("new_bg.png") center center / cover no-repeat;
    }

    .hid-hero::before{
      content:"";
      position:absolute;
      inset:0;
      pointer-events:none;
      background:
        radial-gradient(circle at 63% 0%, rgba(255,239,181,.38), transparent 28%),
        linear-gradient(180deg, rgba(255,255,255,.12), rgba(255,255,255,.60) 100%);
      opacity:.72;
    }

    .hid-shell{
      position:relative;
      z-index:1;
      width:100%;
      max-width:var(--hid-max);
      margin:0 auto;
    }

    .hid-hero-grid{
      display:grid;
      grid-template-columns:minmax(0, .96fr) minmax(360px, .78fr);
      align-items:center;
      gap:clamp(34px, 5vw, 72px);
    }

    .hid-card-column{
      min-width:0;
    }

    .hid-card-stage{
      display:flex;
      flex-direction:column;
      align-items:center;
      width:100%;
    }

    .flip-card{
      width:100%;
      max-width:680px;
      aspect-ratio:1448 / 1086;
      perspective:1300px;
      border-radius:34px;
    }

    .flip-inner{
      position:relative;
      width:100%;
      height:100%;
      transform-style:preserve-3d;
      transition:transform .8s ease;
      cursor:pointer;
    }

    .flip-card.is-flipped .flip-inner{
      transform:rotateY(180deg);
    }

    .flip-face{
      position:absolute;
      inset:0;
      overflow:hidden;
      backface-visibility:hidden;
      border-radius:34px;
      filter:drop-shadow(0 20px 26px rgba(6,29,69,.20));
    }

    .flip-face img{
      display:block;
      width:100%;
      height:100%;
      object-fit:contain;
    }

    .flip-back{
      transform:rotateY(180deg);
      background:rgba(255,255,255,.30);
    }

    .hid-flip-hint{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:10px;
      margin-top:14px;
      color:var(--hid-navy);
      font-size:18px;
      line-height:1;
      user-select:none;
    }

    .hid-flip-icon{
      font-size:25px;
      line-height:1;
      transform:translateY(-1px);
    }

    .hid-copy-column{
      min-width:0;
      max-width:520px;
    }

    .hid-eyebrow-line{
      width:min(290px, 60%);
      height:2px;
      margin:20px 0 26px;
      background:linear-gradient(90deg, var(--hid-gold), rgba(220,160,24,0));
      position:relative;
    }

    .hid-eyebrow-line::after{
      content:"";
      position:absolute;
      left:42%;
      top:50%;
      width:9px;
      height:9px;
      border-radius:50%;
      transform:translate(-50%,-50%);
      background:#fff3ad;
      box-shadow:0 0 18px 6px rgba(255,230,116,.72);
    }

    .hid-title{
      margin:0;
      color:var(--hid-navy);
      font-size:clamp(48px, 5.2vw, 74px);
      line-height:.98;
      font-weight:700;
      letter-spacing:-.025em;
      text-shadow:0 2px 0 rgba(255,255,255,.76);
    }

    .hid-copy{
      margin:0 0 22px;
      color:#111722;
      font-family:Arial, Helvetica, sans-serif;
      font-size:clamp(18px, 1.7vw, 24px);
      line-height:1.43;
      letter-spacing:.005em;
    }

    .hid-steps-intro{
      margin-bottom:12px;
    }

    .hid-steps-list{
      margin:0 0 24px;
      padding:0;
      list-style:none;
      display:flex;
      flex-direction:column;
      gap:10px;
      color:#111722;
      font-family:Arial, Helvetica, sans-serif;
      font-size:clamp(17px, 1.45vw, 21px);
      line-height:1.42;
      letter-spacing:.005em;
      counter-reset:hidSteps;
    }

    .hid-steps-list li{
      position:relative;
      padding-left:46px;
      min-height:34px;
    }

    .hid-steps-list li::before{
      counter-increment:hidSteps;
      content:counter(hidSteps);
      position:absolute;
      left:0;
      top:.05em;
      width:30px;
      height:30px;
      border-radius:999px;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      background:linear-gradient(180deg,#ffe6a8,#efbd35);
      color:#071d42;
      border:1px solid rgba(151,101,10,.42);
      font-weight:700;
      line-height:1;
      box-shadow:0 6px 14px rgba(121,79,11,.12);
    }

    .hid-steps-list strong{
      color:var(--hid-navy);
      font-weight:700;
    }

    .hid-actions{
      display:flex;
      flex-wrap:wrap;
      gap:12px;
      margin-top:30px;
    }

    .hid-primary-btn,
    .hid-secondary-btn{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      min-height:48px;
      padding:12px 20px;
      border-radius:999px;
      text-decoration:none;
      font-size:18px;
      line-height:1;
      transition:transform .16s ease, box-shadow .16s ease, filter .16s ease;
    }

    .hid-primary-btn{
      color:#071d42;
      background:linear-gradient(180deg,#ffe6a8,#efbd35);
      border:1px solid rgba(151,101,10,.42);
      box-shadow:0 12px 24px rgba(121,79,11,.14);
      font-weight:700;
    }

    .hid-secondary-btn{
      color:var(--hid-navy);
      background:rgba(255,255,255,.55);
      border:1px solid rgba(6,29,69,.12);
      box-shadow:0 8px 18px rgba(6,29,69,.07);
    }

    .hid-primary-btn:hover,
    .hid-secondary-btn:hover{
      transform:translateY(-1px);
      filter:brightness(1.02);
    }

    .hid-feature-section{
      position:relative;
      z-index:2;
      margin-top:26px;
    }

    .hid-features{
      display:grid;
      grid-template-columns:repeat(3, minmax(0, 1fr));
      gap:20px;
    }

    .hid-feature{
      display:grid;
      grid-template-columns:150px minmax(0, 1fr);
      align-items:center;
      min-height:174px;
      padding:22px 24px 22px 18px;
      border-radius:16px;
      text-decoration:none;
      background:rgba(255,255,255,.82);
      border:1px solid rgba(6,29,69,.06);
      box-shadow:0 12px 30px rgba(6,29,69,.10);
      transition:transform .16s ease, box-shadow .16s ease;
      backdrop-filter:blur(6px);
    }

    .hid-feature:hover{
      transform:translateY(-2px);
      box-shadow:0 18px 38px rgba(6,29,69,.14);
    }

    .hid-feature-icon{
      width:126px;
      height:126px;
      object-fit:contain;
      display:block;
      filter:drop-shadow(0 9px 14px rgba(6,29,69,.12));
    }

    .hid-feature-body{
      min-width:0;
    }

    .hid-feature-title{
      margin:0;
      color:var(--hid-navy);
      font-size:clamp(24px, 2.1vw, 31px);
      line-height:1.02;
      font-weight:700;
      text-transform:uppercase;
      letter-spacing:.01em;
    }

    .hid-feature-line{
      display:block;
      width:48px;
      height:2px;
      margin:12px 0 14px;
      background:var(--hid-gold);
    }

    .hid-feature-text{
      margin:0;
      color:#242a34;
      font-family:Arial, Helvetica, sans-serif;
      font-size:18px;
      line-height:1.43;
    }

    .contactModal,
    #authModal,
    #successModal{
      position:fixed;
      inset:0;
      background:rgba(0,0,0,.58);
      display:none;
      align-items:center;
      justify-content:center;
      padding:18px;
      z-index:3000;
      box-sizing:border-box;
    }

    .contactModal{ z-index:2200; }
    #authModal{ z-index:2400; align-items:flex-start; padding-top:20px; }
    #successModal{ z-index:3000; }

    .contactModalCard,
    .successCard{
      width:min(860px, 96vw);
      max-height:calc(100vh - 36px);
      overflow:auto;
      background:rgba(255,255,255,.96);
      border-radius:22px;
      border:1px solid rgba(0,0,0,.08);
      box-shadow:0 24px 54px rgba(0,0,0,.18);
    }

    .contactModalCard{ padding:22px 22px 18px; }

    .contactModalHead{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:14px;
      margin-bottom:12px;
    }

    .contactModalHead h2{
      margin:0;
      font-size:clamp(28px, 3vw, 42px);
      color:#24416f;
    }

    .contactModalHead p{
      margin:6px 0 0;
      font-size:18px;
      line-height:1.4;
    }

    .contactClose{
      border:none;
      background:rgba(33,55,98,.10);
      color:#213762;
      width:44px;
      height:44px;
      border-radius:999px;
      font-size:28px;
      line-height:1;
      cursor:pointer;
      flex:0 0 auto;
    }

    .contactGrid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:12px;
      margin-top:14px;
    }

    .contactGrid > *{ min-width:0; }

    .field{
      display:flex;
      flex-direction:column;
      gap:6px;
      text-align:left;
    }

    .field label{
      font-size:14px;
      opacity:.9;
    }

    .contactInput,
    .contactTextarea,
    #authModal input,
    #authModal button{
      width:100%;
      box-sizing:border-box;
      font-family:"Minion Pro", Georgia, serif;
    }

    .contactInput,
    .contactTextarea{
      width:100%;
      max-width:100%;
      font-size:16px;
      padding:12px 12px;
      border:1px solid rgba(0,0,0,.14);
      border-radius:12px;
      background:rgba(255,255,255,.90);
      outline:none;
    }

    .contactTextarea{
      resize:vertical;
      min-height:140px;
      grid-column:1 / -1;
    }

    .contactActions{
      grid-column:1 / -1;
      display:flex;
      flex-direction:column;
      gap:10px;
      margin-top:4px;
    }

    .recaptchaRow{
      width:100%;
      max-width:100%;
      box-sizing:border-box;
      display:flex;
      justify-content:center;
      padding:0 6px;
      overflow-x:auto;
    }

    .contactBtn{
      border:none;
      border-radius:12px;
      padding:12px 14px;
      background:#0078d4;
      color:#fff;
      font-size:16px;
      cursor:pointer;
      box-shadow:0 10px 20px rgba(0,0,0,.10);
    }

    .contactBtn:disabled{ opacity:.65; cursor:not-allowed; }

    .contactFine{
      margin:0;
      text-align:center;
      font-size:14px;
      opacity:.95;
      min-height:18px;
    }

    #authModal .modalCard{
      background:#fff;
      width:80vw;
      max-width:600px;
      height:calc(100vh - 40px);
      padding:24px;
      position:relative;
      overflow-y:auto;
      overflow-x:hidden;
      border-radius:12px;
      box-sizing:border-box;
      box-shadow:0 24px 54px rgba(0,0,0,.18);
    }

    #joinTab,
    #signinTab{
      background-image:linear-gradient(blue, navy);
      color:white;
      border:none;
      padding:10px 14px;
      border-radius:10px;
      cursor:pointer;
    }

    #joinForm{
      display:flex;
      flex-direction:column;
      align-items:center;
      gap:14px;
    }

    #joinForm input,
    #signinForm input{
      padding:10px 12px;
      border:1px solid #ddd;
      border-radius:10px;
      font-size:16px;
    }

    #signinForm{
      display:none;
      flex-direction:column;
      gap:10px;
    }

    .successCard{
      width:min(560px, 94vw);
      padding:18px 16px;
      text-align:center;
    }

    .successCard h3{
      margin:0 0 8px;
      font-size:clamp(20px, 2.8vw, 28px);
    }

    .successCard p{
      margin:0 0 14px;
      opacity:.92;
      line-height:1.4;
    }

    .successCard button{
      border:none;
      border-radius:12px;
      padding:10px 14px;
      background:#0078d4;
      color:#fff;
      font-size:16px;
      cursor:pointer;
    }

    @media (max-width: 1240px){
      body .hh-left{ gap:22px !important; }
      body .hh-link{ font-size:18px !important; }
      body .hh-social{ width:40px !important; height:40px !important; }
      body .hh-contact-btn{ min-width:128px !important; padding:10px 18px !important; font-size:18px !important; }

      .hid-feature{
        grid-template-columns:116px minmax(0, 1fr);
        padding:20px 18px;
      }

      .hid-feature-icon{
        width:100px;
        height:100px;
      }
    }

    @media (max-width: 1100px){
      body .hh-nav-inner{
        min-height:66px !important;
      }

      body .hh-spacer{
        height:66px !important;
      }

      .hid-page{
        min-height:calc(100vh - 66px);
      }

      .hid-hero-grid{
        grid-template-columns:1fr;
        justify-items:center;
        text-align:center;
      }

      .hid-copy-column{
        max-width:760px;
      }

      .hid-eyebrow-line{
        margin-left:auto;
        margin-right:auto;
      }

      .hid-actions{
        justify-content:center;
      }

      .hid-features{
        grid-template-columns:1fr;
        max-width:760px;
        margin:0 auto;
      }

      .hid-feature{
        grid-template-columns:134px minmax(0, 1fr);
        text-align:left;
      }

      .hid-feature-icon{
        width:116px;
        height:116px;
      }
    }

    @media (max-width: 900px){
      body .hh-nav-inner{
        min-height:58px !important;
      }

      body .hh-spacer{
        height:58px !important;
      }

      body .hh-burger span{
        background:var(--hid-navy) !important;
      }

      body .hh-nav-logo img,
      body .hh-wordmark{
        width:min(92px, 28vw) !important;
        max-width:min(92px, 28vw) !important;
      }

      body .hh-mobile-user a{
        font-size:17px !important;
      }
    }

    @media (max-width: 720px){
      .hid-hero{
        padding:28px 14px 24px;
        background-position:center top;
      }

      .flip-card{
        border-radius:20px;
      }

      .flip-face{
        border-radius:20px;
      }

      .hid-title{
        font-size:clamp(42px, 12vw, 58px);
      }

      .hid-copy{
        font-size:18px;
      }

      .hid-feature{
        grid-template-columns:92px minmax(0, 1fr);
        min-height:0;
        padding:16px;
        border-radius:14px;
      }

      .hid-feature-icon{
        width:78px;
        height:78px;
      }

      .hid-feature-title{
        font-size:22px;
      }

      .hid-feature-text{
        font-size:16px;
      }

      .contactGrid{
        grid-template-columns:1fr;
      }

      .contactModalCard{
        padding:18px 16px 16px;
      }

      .contactModalHead{
        align-items:flex-start;
      }

      #authModal .modalCard{
        width:92vw;
      }
    }

    @media (max-width: 480px){
      .hid-actions{
        flex-direction:column;
      }

      .hid-primary-btn,
      .hid-secondary-btn{
        width:100%;
      }

      .hid-feature{
        grid-template-columns:1fr;
        justify-items:center;
        text-align:center;
        gap:8px;
      }

      .hid-feature-line{
        margin-left:auto;
        margin-right:auto;
      }
    }
  </style>
</head>
<body>

<?php
$headerContext = [
  'active' => 'home',
  'show_contact' => true,
  'show_socials' => true
];
include __DIR__ . '/header.php';
?>

<main class="hid-page">
  <section class="hid-hero" data-aos="fade-up">
    <div class="hid-shell">
      <div class="hid-hero-grid">
        <div class="hid-card-column">
          <div class="hid-card-stage">
            <div class="flip-card" id="exampleFlip" aria-label="Example Citizen of Heaven ID card. Tap to flip." role="button">
              <div class="flip-inner">
                <div class="flip-face flip-front">
                  <img src="img/heavenly-card-front-john-smith.png" alt="Citizen of Heaven ID card front for John Smith">
                </div>
                <div class="flip-face flip-back">
                  <img src="img/heavenly-card-back.png" alt="Citizen of Heaven ID card back">
                </div>
              </div>
            </div>

            <div class="hid-flip-hint" aria-hidden="true">
              <span class="hid-flip-icon">⟳</span>
              <span>Tap to flip</span>
            </div>
          </div>
        </div>

        <div class="hid-copy-column">
          <h1 class="hid-title">Welcome to<br>heavenlyid.com</h1>
          <div class="hid-eyebrow-line" aria-hidden="true"></div>

          <p class="hid-copy">
            WELCOME to  Heavenlyid.com.  Create an ID card that represents your identity in Christ.
          </p>

          <p class="hid-copy">
            Create and personalize your own wallet-size Citizen of Heaven ID card. This is also a great gift for somebody you know who is walking with the Lord. 
          </p>

          <p class="hid-copy hid-steps-intro">
            It only takes 3 easy steps to start building your card.
          </p>

          <ol class="hid-steps-list">
            <li><strong>Free Signup</strong> - the process is easy, we just need to know where to ship your card.  You can even use your gmail account!</li>
            <li><strong>Start building!</strong> - once you save your card, it saves forever with us.</li>
            <li><strong>Easy checkout with Shopify</strong> - once the process is completed, our printer will receive your design, you'll receive a confirmation with us and then you can check on the status of it anytime with the printer on the &ldquo;My Designs&rdquo; page.</li>
          </ol>

          <div class="hid-actions">
            <a class="hid-primary-btn" id="create" href="cardbuilder.php">Create Your Own Card</a>
            <a class="hid-secondary-btn" href="<?= htmlspecialchars($downloadDesignHref) ?>">Download Design</a>
          </div>
        </div>
      </div>

      <section class="hid-feature-section" aria-label="Heavenly ID features">
        <div class="hid-features">
          <a class="hid-feature" href="cardbuilder.php">
            <img class="hid-feature-icon" src="ChatGPT Image Jun 26, 2026, 09_11_06 PM.png" alt="Create ID card icon">
            <div class="hid-feature-body">
              <h2 class="hid-feature-title">Create A<br>ID Card Now</h2>
              <span class="hid-feature-line" aria-hidden="true"></span>
              <p class="hid-feature-text">Design your own Citizen of Heaven ID card in just a few easy steps.</p>
            </div>
          </a>

          <a class="hid-feature" href="#about-heavenly-id">
            <img class="hid-feature-icon" src="ChatGPT Image Jun 26, 2026, 09_11_14 PM.png" alt="About us icon">
            <div class="hid-feature-body">
              <h2 class="hid-feature-title">About Us</h2>
              <span class="hid-feature-line" aria-hidden="true"></span>
              <p class="hid-feature-text">Learn more about our mission and the heart behind Heavenly ID.</p>
            </div>
          </a>

          <a class="hid-feature" href="/forum/">
            <img class="hid-feature-icon" src="ChatGPT Image Jun 26, 2026, 09_11_21 PM.png" alt="Community icon">
            <div class="hid-feature-body">
              <h2 class="hid-feature-title">Community</h2>
              <span class="hid-feature-line" aria-hidden="true"></span>
              <p class="hid-feature-text">Join a community of believers who encourage and uplift one another.</p>
            </div>
          </a>
        </div>
      </section>
    </div>
  </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>

<div class="contactModal" id="contactModal" role="dialog" aria-modal="true" aria-label="Contact us">
  <div class="contactModalCard">
    <div class="contactModalHead">
      <div>
        <h2>Contact Us</h2>
        <p>Send us a message and we'll get back to you as soon as we can.</p>
      </div>
      <button class="contactClose" id="contactClose" type="button" aria-label="Close contact form">&times;</button>
    </div>

    <form id="contactForm">
      <div class="contactGrid">
        <div class="field">
          <label for="c_name">Name</label>
          <input class="contactInput" id="c_name" name="name" placeholder="Your name" required>
        </div>

        <div class="field">
          <label for="c_email">Email</label>
          <input class="contactInput" id="c_email" name="email" type="email" placeholder="you@example.com" required>
        </div>

        <div class="field" style="grid-column:1 / -1;">
          <label for="c_message">Message</label>
          <textarea class="contactTextarea" id="c_message" name="message" rows="5" placeholder="How can we help?" required></textarea>
        </div>

        <div class="contactActions">
          <div class="recaptchaRow">
            <div class="g-recaptcha" data-sitekey="6LfIljwsAAAAANhosJDdP6SAFG9qMcaNBHnbrRKe"></div>
          </div>

          <button type="submit" class="contactBtn" id="contactSubmit">Send Message</button>
          <p class="contactFine" id="contactFine" aria-live="polite"></p>
        </div>
      </div>
    </form>
  </div>
</div>

<div id="successModal" role="dialog" aria-modal="true" aria-label="Message sent">
  <div class="successCard">
    <h3>Message Sent</h3>
    <p>Thanks for reaching out. We received your message and will reply soon.</p>
    <button id="successClose">Close</button>
  </div>
</div>

<div id="authModal">
  <div class="modalCard">
    <div style="display:flex; justify-content:center; gap:16px; margin-bottom:20px;">
      <button id="joinTab" style="font-weight:600;">Join</button>
      <button id="signinTab">Sign In</button>
    </div>

    <form id="joinForm" autocomplete="off">
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

      <small id="pwHelp" style="font-size:12px;">
        Must be 12+ chars, 1 uppercase, 1 special character
      </small>

      <button type="submit" style="padding:12px; border:none; border-radius:10px; background:#0078d4; color:#fff;">
        Create Account
      </button>

      <div style="text-align:center;">OR</div>

      <button
        type="button"
        id="googleSignup"
        style="padding:12px;background:url(4LSMF.png) no-repeat center center;background-size:contain;width:290px;height:61px;border:0;cursor:pointer;">
      </button>
    </form>

    <form id="signinForm">
      <input name="email" type="email" placeholder="Email" required>
      <input name="password" type="password" placeholder="Password" required>

      <button type="submit" style="background:#0078d4;color:#fff;padding:10px;border:none;border-radius:10px;">
        Sign In
      </button>
    </form>
  </div>
</div>

<script>
  function initAOS(){
    const aosItems = document.querySelectorAll("[data-aos]");
    if (!aosItems.length) return;

    const aosObserver = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const el = entry.target;
        el.style.opacity = 1;
        el.style.transform = "translate(0,0) scale(1)";
      });
    }, { threshold: 0.18 });

    aosItems.forEach(el => {
      const type = el.dataset.aos;
      el.style.opacity = 0;
      el.style.transition = "opacity 0.9s ease, transform 0.9s ease";

      if (type === "fade-up") el.style.transform = "translateY(40px)";
      if (type === "fade-down") el.style.transform = "translateY(-40px)";
      if (type === "fade-left") el.style.transform = "translateX(-40px)";
      if (type === "fade-right") el.style.transform = "translateX(40px)";
      if (type === "fade-in") el.style.transform = "scale(0.98)";

      aosObserver.observe(el);
    });
  }

  function initAuth(){
    const authModal = document.getElementById("authModal");
    const joinTab = document.getElementById("joinTab");
    const signinTab = document.getElementById("signinTab");
    const joinForm = document.getElementById("joinForm");
    const signinForm = document.getElementById("signinForm");

    if (!authModal || !joinTab || !signinTab || !joinForm || !signinForm) return;

    function showJoin(){
      joinForm.style.display = "flex";
      signinForm.style.display = "none";
      joinTab.style.fontWeight = "700";
      signinTab.style.fontWeight = "400";
    }

    function showSignin(){
      joinForm.style.display = "none";
      signinForm.style.display = "flex";
      joinTab.style.fontWeight = "400";
      signinTab.style.fontWeight = "700";
    }

    document.addEventListener("heavenly:open-auth", () => {
      showJoin();
      authModal.style.display = "flex";
    });

    authModal.addEventListener("click", (e) => {
      if (e.target === authModal) authModal.style.display = "none";
    });

    joinTab.addEventListener("click", showJoin);
    signinTab.addEventListener("click", showSignin);

    function validPassword(pw){
      return (pw.length >= 12 && /[A-Z]/.test(pw) && /[^a-zA-Z0-9]/.test(pw));
    }

    joinForm.addEventListener("submit", (e) => {
      e.preventDefault();

      const formData = new FormData(joinForm);
      const firstName = (formData.get("first_name") || "").trim();
      const lastName  = (formData.get("last_name") || "").trim();
      const email     = (formData.get("email") || "").trim();
      const phone     = (formData.get("phone") || "").trim();
      const password  = formData.get("password") || "";
      const confirm   = formData.get("confirm_password") || "";

      if (!firstName || !lastName || !email || !phone){
        alert("Please fill out all required fields.");
        return;
      }
      if (!validPassword(password)){
        alert("Password must be 12+ chars, 1 uppercase, 1 special char.");
        return;
      }
      if (password !== confirm){
        alert("Passwords do not match.");
        return;
      }

      const submitBtn = joinForm.querySelector('button[type="submit"]');
      submitBtn.disabled = true;
      const oldText = submitBtn.textContent;
      submitBtn.textContent = "Creating Account...";

      fetch("register_user.php", {
        method: "POST",
        body: formData,
        credentials: "same-origin"
      })
      .then(res => res.json())
      .then(json => {
        if (!json.success) throw new Error(json.error || "Registration failed");
        authModal.style.display = "none";
        joinForm.reset();
        location.reload();
      })
      .catch(err => alert(err.message))
      .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = oldText;
      });
    });

    signinForm.addEventListener("submit", (e) => {
      e.preventDefault();

      const formData = new FormData(signinForm);

      fetch("login_user.php", {
        method: "POST",
        body: formData,
        credentials: "same-origin"
      })
      .then(res => res.json())
      .then(json => {
        if (json.error) throw new Error(json.error);
        location.reload();
      })
      .catch(err => alert(err.message));
    });
  }

  function initGoogleSignup(){
    const waitForGoogle = setInterval(() => {
      if (window.google && google.accounts && google.accounts.id) {
        clearInterval(waitForGoogle);

        google.accounts.id.initialize({
          client_id: "775296211110-9eo6e45ab96k9g2ggln3d3084bp5dljq.apps.googleusercontent.com",
          callback: handleGoogleSignup
        });

        const btn = document.getElementById("googleSignup");
        if (btn) btn.addEventListener("click", () => google.accounts.id.prompt());
      }
    }, 50);
  }

  function handleGoogleSignup(response){
    const data = JSON.parse(atob(response.credential.split('.')[1]));
    document.querySelector('#joinForm [name="first_name"]').value = data.given_name || "";
    document.querySelector('#joinForm [name="last_name"]').value  = data.family_name || "";
    document.querySelector('#joinForm [name="email"]').value      = data.email || "";
  }

  function initFlip(){
    const exampleFlip = document.getElementById("exampleFlip");
    if (!exampleFlip) return;

    exampleFlip.addEventListener("click", () => {
      exampleFlip.classList.toggle("is-flipped");
    });

    exampleFlip.tabIndex = 0;
    exampleFlip.addEventListener("keydown", (e) => {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        exampleFlip.classList.toggle("is-flipped");
      }
    });
  }

  function initContactModal(){
    const contactModal = document.getElementById("contactModal");
    const closeBtn = document.getElementById("contactClose");

    if (!contactModal) return;

    function openContact(){
      contactModal.style.display = "flex";
    }

    function closeContact(){
      contactModal.style.display = "none";
    }

    document.addEventListener("heavenly:open-contact", openContact);

    if (closeBtn) {
      closeBtn.addEventListener("click", closeContact);
    }

    contactModal.addEventListener("click", (e) => {
      if (e.target === contactModal) closeContact();
    });
  }

  function initContact(){
    const contactForm = document.getElementById("contactForm");
    const contactFine = document.getElementById("contactFine");
    const contactSubmit = document.getElementById("contactSubmit");
    const contactModal = document.getElementById("contactModal");

    const successModal = document.getElementById("successModal");
    const successClose = document.getElementById("successClose");

    function openSuccess(){
      if (successModal) successModal.style.display = "flex";
    }

    function closeSuccess(){
      if (successModal) successModal.style.display = "none";
    }

    if (successClose) successClose.addEventListener("click", closeSuccess);
    if (successModal) {
      successModal.addEventListener("click", (e) => {
        if (e.target === successModal) closeSuccess();
      });
    }

    if (!contactForm) return;

    contactForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      contactFine.textContent = "";

      const token = (window.grecaptcha && grecaptcha.getResponse) ? grecaptcha.getResponse() : "";
      if (!token) {
        contactFine.textContent = "Please complete the reCAPTCHA.";
        return;
      }

      const fd = new FormData(contactForm);
      fd.append("g-recaptcha-response", token);

      contactSubmit.disabled = true;
      const oldText = contactSubmit.textContent;
      contactSubmit.textContent = "Sending...";

      try {
        const res = await fetch("contact_send.php", {
          method: "POST",
          body: fd,
          credentials: "same-origin"
        });

        const json = await res.json().catch(() => ({}));
        if (!res.ok || !json.success) throw new Error(json.error || "Message failed to send. Please try again.");

        contactForm.reset();
        if (window.grecaptcha && grecaptcha.reset) grecaptcha.reset();
        if (contactModal) contactModal.style.display = "none";
        openSuccess();
      } catch (err) {
        contactFine.textContent = err.message || "Something went wrong.";
      } finally {
        contactSubmit.disabled = false;
        contactSubmit.textContent = oldText;
      }
    });
  }

  window.addEventListener("DOMContentLoaded", () => {
    initAOS();
    initAuth();
    initGoogleSignup();
    initFlip();
    initContactModal();
    initContact();
  });
</script>
</body>
</html>
