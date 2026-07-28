<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAdmin = !empty($_SESSION["admin_logged_in"]);

$loggedIn = false;
$userName = '';

if (isset($_SESSION['user_id']) && isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
    $loggedIn = true;
    $userName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
}

$downloadDesignHref = $loggedIn ? '/my_designs.php' : '/guest_download.php';

/*
  Optional page config before include:
  $headerContext = [
    'active' => 'home' | 'build' | 'download' | 'community',
    'show_contact' => true,
    'show_socials' => true
  ];
*/
$headerContext = isset($headerContext) && is_array($headerContext) ? $headerContext : [];
$headerActive = $headerContext['active'] ?? '';
$showContact = array_key_exists('show_contact', $headerContext) ? (bool)$headerContext['show_contact'] : true;
$showSocials = array_key_exists('show_socials', $headerContext) ? (bool)$headerContext['show_socials'] : true;

function hh_active(string $key, string $active): string {
    return $key === $active ? ' hh-link--active' : '';
}
?>

<style id="heavenly-header-styles">
  :root{
    --hh-content-max: 1180px;
    --hh-header-h: 0px;
    --hh-nav-h: 56px;
    --hh-total-h: var(--hh-nav-h);
    --hh-text: #5f452f;
    --hh-title: #1d3468;
    --hh-gold: #d8ae55;
    --hh-shadow-soft: 0 8px 24px rgba(88,60,24,.10);
  }

  .hh-shell,
  .hh-shell *{
    box-sizing:border-box;
    font-family:"Minion Pro", serif !important;
  }

  .hh-header{
    position:fixed;
    top:0;
    left:0;
    width:100%;
    z-index:999;
    transition:background .35s ease, box-shadow .35s ease, backdrop-filter .35s ease;
    background:#FBF7F0;
  }

  .hh-header.hh-scrolled{
    background:rgba(248, 242, 232, .92);
    box-shadow:0 14px 34px rgba(76,52,24,.12);
    backdrop-filter:blur(10px);
  }

  .hh-top,
  .hh-top-inner{
    display:none !important;
  }

  .hh-logo{
    display:block;
    width:auto;
    height:auto;
    max-width:min(78px, 12vw);
    max-height:calc(var(--hh-header-h) - 18px);
    filter:drop-shadow(0 8px 18px rgba(109,82,27,.10));
  }

  .hh-nav{
    position:relative;
    border-top:1px solid rgba(255,255,255,.45);
    border-bottom:1px solid rgba(208,176,117,.28);
    background:rgba(251,247,240,.86);
    box-shadow:0 10px 28px rgba(92,68,30,.06);
  }

  .hh-nav-inner{
    max-width:none;
    min-height:var(--hh-nav-h);
    margin:0;
    padding:0 24px;
    display:grid;
    grid-template-columns:minmax(0, 1fr) auto minmax(0, 1fr);
    align-items:center;
    gap:16px;
  }

  .hh-left,
  .hh-right{
    display:flex;
    align-items:center;
    min-width:0;
  }

  .hh-left{
    justify-content:flex-start;
    gap:18px;
    flex-wrap:nowrap;
  }

  .hh-nav-logo{
    display:flex;
    align-items:center;
    justify-content:center;
    flex:0 0 auto;
    width:64px;
    margin-right:14px;
    text-decoration:none;
  }

  .hh-nav-logo img{
    display:block;
    width:54px;
    max-width:54px;
    max-height:54px;
    height:auto;
    filter:drop-shadow(0 8px 18px rgba(109,82,27,.10));
  }

  .hh-right{
    justify-content:flex-end;
    gap:10px;
  }

  .hh-center{
    display:flex;
    justify-content:center;
    align-items:center;
    min-width:0;
  }

  .hh-wordmark{
    display:none;
    width:min(250px, 28vw);
    max-width:100%;
  }

  .hh-link,
  .hh-utility,
  .hh-contact-btn{
    position:relative;
    text-decoration:none;
    color:var(--hh-text);
    font-size:16px;
    line-height:1;
    padding:7px 0;
    transition:color .18s ease, opacity .18s ease;
    white-space:nowrap;
    background:none;
  }

  .hh-link:hover,
  .hh-utility:hover,
  .hh-contact-btn:hover{
    color:#a7771f;
  }

  .hh-link--active{
    color:#b98526;
    font-weight:700;
  }

  .hh-link--active::after{
    content:"";
    position:absolute;
    left:0;
    right:0;
    bottom:-13px;
    height:3px;
    border-radius:999px;
    background:linear-gradient(90deg, #e9c46a, #d59d34);
    box-shadow:0 4px 10px rgba(213,157,52,.24);
  }

  .hh-socials{
    display:flex;
    align-items:center;
    gap:10px;
  }

  .hh-social{
    width:34px;
    height:34px;
    border-radius:999px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    background:#2c4374;
    color:#fff;
    box-shadow:0 10px 20px rgba(34,54,98,.16);
    text-decoration:none;
    transition:transform .16s ease, filter .16s ease;
  }

  .hh-social:hover{
    transform:translateY(-1px);
    filter:brightness(1.06);
  }

  .hh-contact-btn{
    border:none;
    cursor:pointer;
    border-radius:999px;
    padding:8px 22px;
    background:linear-gradient(180deg, rgba(247,226,183,.95), rgba(232,202,145,.92));
    border:1px solid rgba(185,138,57,.45);
    box-shadow:0 10px 22px rgba(138,98,36,.12);
    font-size:17px;
    color:#7c5620;
  }


  .hh-right-stack{
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    gap:4px;
  }

  .hh-right-top{
    display:flex;
    align-items:center;
    justify-content:center;
  }

  .hh-desktop-user{
    display:flex;
    justify-content:center;
    align-items:center;
    line-height:1;
  }

  .hh-desktop-user a{
    text-decoration:none;
    color:var(--hh-text);
    font-size:15px;
    line-height:1;
    white-space:nowrap;
  }

  .hh-desktop-user a:hover{
    color:#a7771f;
  }

  .hh-mobile-user{
    display:none;
    align-items:center;
    justify-content:center;
    min-width:0;
    text-align:center;
  }

  .hh-mobile-user a{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    max-width:100%;
    padding:8px 10px;
    text-decoration:none;
    color:var(--hh-text);
    font-size:18px;
    line-height:1.05;
    white-space:normal;
  }

  .hh-mobile-user a:hover{
    color:#a7771f;
  }

  .hh-burger{
    display:none;
    flex-direction:column;
    gap:5px;
    cursor:pointer;
    padding:10px;
    border-radius:12px;
    background:rgba(255,255,255,.62);
    border:1px solid rgba(0,0,0,.06);
    box-shadow:var(--hh-shadow-soft);
  }

  .hh-burger span{
    width:26px;
    height:3px;
    background:#6b4f2d;
    border-radius:2px;
  }

  .hh-mobile{
    display:none;
    flex-direction:column;
    gap:0;
    background:rgba(251,247,240,.98);
    border-top:1px solid rgba(0,0,0,.08);
    box-shadow:0 14px 28px rgba(0,0,0,.10);
    padding:8px 0 10px;
  }

  .hh-mobile a,
  .hh-mobile button{
    display:flex;
    align-items:center;
    justify-content:space-between;
    width:100%;
    background:none;
    border:none;
    padding:14px 18px;
    color:#5f452f;
    text-decoration:none;
    font-size:18px;
    border-bottom:1px solid rgba(0,0,0,.06);
    text-align:left;
  }

  .hh-mobile a:last-child,
  .hh-mobile button:last-child{
    border-bottom:none;
  }

  .hh-mobile-socials{
    display:flex;
    gap:10px;
    padding:14px 18px 4px;
  }

  .hh-mobile-socials .hh-social{
    width:38px;
    height:38px;
  }

  .hh-spacer{
    height:var(--hh-total-h);
  }

  @media (max-width: 1100px){
    :root{
      --hh-header-h: 0px;
      --hh-nav-h: 58px;
    }

    .hh-nav-inner{
      grid-template-columns:minmax(0, 1fr) auto;
    }

    .hh-center{ display:none; }
    .hh-wordmark{ display:block; }
  }

  @media (max-width: 900px){
    :root{
      --hh-header-h: 0px;
      --hh-nav-h: 58px;
    }

    .hh-nav-inner{
      display:grid;
      grid-template-columns:minmax(88px, 1fr) minmax(0, auto) minmax(64px, 1fr);
      justify-content:stretch;
      align-items:center;
      gap:10px;
      padding:0 18px;
    }

    .hh-left,
    .hh-right{
      display:none;
    }

    .hh-desktop-user{
      display:none;
    }

    .hh-center{
      display:flex;
      justify-content:flex-start;
      grid-column:1;
    }

    .hh-right-stack{
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    gap:4px;
  }

  .hh-right-top{
    display:flex;
    align-items:center;
    justify-content:center;
  }

  .hh-desktop-user{
    display:flex;
    justify-content:center;
    align-items:center;
    line-height:1;
  }

  .hh-desktop-user a{
    text-decoration:none;
    color:var(--hh-text);
    font-size:15px;
    line-height:1;
    white-space:nowrap;
  }

  .hh-desktop-user a:hover{
    color:#a7771f;
  }

  .hh-mobile-user{
      display:flex;
      grid-column:2;
    }

    .hh-burger{
      grid-column:3;
      justify-self:end;
    }

    .hh-wordmark{
      display:block;
      width:min(220px, 56vw);
    }

    .hh-burger{ display:flex; }

    .hh-nav-logo{
      width:auto;
      margin-right:0;
    }

    .hh-nav-logo img{
      width:54px;
      max-width:54px;
      max-height:54px;
    }
  }

  @media (max-width: 520px){
    :root{
      --hh-header-h: 0px;
      --hh-nav-h: 58px;
    }

    .hh-logo,
    .hh-wordmark{
      max-width:54px;
      max-height:54px;
    }

    .hh-nav-logo img{
      width:54px;
      max-width:54px;
      max-height:54px;
    }
  }

  /* Shared header normalization moved into header.php.
     This matches the homepage header styling so cardbuilder.php and all other pages
     no longer need page-specific header overrides for the header to look correct. */
  :root{
    --hh-nav-h: 76px;
    --hh-total-h: var(--hh-nav-h);
  }

  .hh-header{
    background:rgba(255,253,247,.96);
    box-shadow:0 8px 28px rgba(6,29,69,.07);
  }

  .hh-nav{
    border-top:0;
    border-bottom:2px solid rgba(220,160,24,.86);
    background:rgba(255,253,247,.96);
    box-shadow:none;
  }

  .hh-nav-inner{
    min-height:76px;
    padding:0 clamp(18px, 3.2vw, 44px);
    gap:22px;
  }

  .hh-nav-logo{
    width:118px;
    margin-right:18px;
  }

  .hh-nav-logo img{
    width:92px;
    max-width:92px;
    max-height:92px;
    filter:drop-shadow(0 7px 14px rgba(6,29,69,.14));
  }

  .hh-left{
    gap:32px;
  }

  .hh-link,
  .hh-utility,
  .hh-desktop-user a,
  .hh-mobile-user a{
    color:#061d45;
    font-size:20px;
    line-height:1;
    letter-spacing:.01em;
  }

  .hh-link--active{
    color:#061d45;
    font-weight:700;
  }

  .hh-link--active::after{
    bottom:-22px;
    height:2px;
    background:linear-gradient(90deg, #dca018, #e9c46a);
  }

  .hh-right{
    gap:18px;
  }

  .hh-right-stack{
    flex-direction:row;
    gap:20px;
  }

  .hh-social{
    width:46px;
    height:46px;
    background:linear-gradient(180deg,#3e8df5,#1557cc);
    box-shadow:0 9px 18px rgba(6,29,69,.15);
  }

  .hh-social[aria-label="Instagram"],
  .hh-socials a:nth-child(2),
  .hh-mobile-socials a:nth-child(2){
    background:
      radial-gradient(circle at 28% 100%, #ffd36b 0 18%, transparent 19%),
      linear-gradient(135deg,#6b51e6,#e53b91 45%,#ff9c34);
  }

  .hh-contact-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    min-width:150px;
    padding:11px 22px;
    border-radius:999px;
    border:1px solid rgba(151,101,10,.45);
    background:linear-gradient(180deg,#ffe5a5 0%, #efbd35 100%);
    color:#071d42;
    font-size:20px;
    box-shadow:0 10px 20px rgba(96,61,8,.16), inset 0 1px 0 rgba(255,255,255,.65);
  }

  .hh-contact-btn::before{
    content:"✉";
    font-size:18px;
    line-height:1;
  }

  .hh-spacer{
    height:76px;
  }

  @media (max-width:1240px){
    .hh-left{ gap:22px; }
    .hh-link{ font-size:18px; }
    .hh-social{ width:40px; height:40px; }
    .hh-contact-btn{ min-width:128px; padding:10px 18px; font-size:18px; }
  }

  @media (max-width:1100px){
    :root{ --hh-nav-h:66px; }
    .hh-nav-inner{ min-height:66px; }
    .hh-spacer{ height:66px; }
  }

  @media (max-width:900px){
    :root{ --hh-nav-h:58px; }
    .hh-nav-inner{ min-height:58px; }
    .hh-spacer{ height:58px; }
    .hh-burger span{ background:#061d45; }
    .hh-nav-logo img,
    .hh-wordmark{
      width:min(92px, 28vw);
      max-width:min(92px, 28vw);
      max-height:min(92px, 28vw);
    }
    .hh-mobile-user a{ font-size:17px; }
  }

</style>

<div class="hh-shell">
  <header class="hh-header" id="hhHeader">
    <div class="hh-nav">
      <div class="hh-nav-inner">
        <div class="hh-left">
          <a href="/index.php" class="hh-nav-logo" aria-label="Heavenly ID Home">
            <img src="/img/heavenly-id-logo.png" alt="Heavenly ID">
          </a>
          <a href="/index.php" class="hh-link<?= hh_active('home', $headerActive) ?>">Home</a>
          <a href="/cardbuilder.php" class="hh-link<?= hh_active('build', $headerActive) ?>">Build Card</a>
          <?php if ($loggedIn): ?>
          <a href="<?= htmlspecialchars($downloadDesignHref) ?>" class="hh-link<?= hh_active('download', $headerActive) ?>">Download Design</a>
          <?php endif; ?>
          

          <?php if ($isAdmin): ?>
            <a href="/admin/logout.php" class="hh-utility" style="color:#b00020; font-weight:700;">Admin Logout</a>
          <?php endif; ?>
        </div>

        <div class="hh-center">
          <img class="hh-wordmark" src="/img/heavenly-id-logo.png" alt="Heavenly ID">
        </div>

        <div class="hh-mobile-user">
          <?php if ($loggedIn): ?>
            <a href="/logout.php"><?= htmlspecialchars($userName) ?> / Logout</a>
          <?php else: ?>
            <a href="#" data-hh-auth-open>Join / Sign In</a>
          <?php endif; ?>
        </div>

        <div class="hh-right">
          <?php if ($showSocials): ?>
            <div class="hh-socials" aria-label="Social media links">
              <a class="hh-social" href="https://www.facebook.com/" target="_blank" rel="noopener" aria-label="Facebook">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.5 22v-8h2.7l.4-3h-3.1V9.1c0-.9.3-1.6 1.7-1.6H16.8V4.8c-.3 0-1.3-.1-2.4-.1-2.4 0-4.1 1.5-4.1 4.2V11H7.5v3h2.8v8h3.2z"/></svg>
              </a>
              <a class="hh-social" href="https://www.instagram.com/" target="_blank" rel="noopener" aria-label="Instagram">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                  <path d="M7.75 2h8.5A5.75 5.75 0 0 1 22 7.75v8.5A5.75 5.75 0 0 1 16.25 22h-8.5A5.75 5.75 0 0 1 2 16.25v-8.5A5.75 5.75 0 0 1 7.75 2zm0 1.8A3.95 3.95 0 0 0 3.8 7.75v8.5a3.95 3.95 0 0 0 3.95 3.95h8.5a3.95 3.95 0 0 0 3.95-3.95v-8.5a3.95 3.95 0 0 0-3.95-3.95h-8.5zm8.9 1.35a1.1 1.1 0 1 1 0 2.2 1.1 1.1 0 0 1 0-2.2zM12 6.4A5.6 5.6 0 1 1 6.4 12 5.6 5.6 0 0 1 12 6.4zm0 1.8A3.8 3.8 0 1 0 15.8 12 3.8 3.8 0 0 0 12 8.2z"/>
                </svg>
              </a>
            </div>
          <?php endif; ?>

          <div class="hh-right-stack">
            <div class="hh-right-top">
              <?php if ($showContact): ?>
                <button class="hh-contact-btn" type="button" data-hh-contact-open>Contact</button>
              <?php endif; ?>
            </div>
            <div class="hh-desktop-user">
              <?php if ($loggedIn): ?>
                <a href="/logout.php"><?= htmlspecialchars($userName) ?> / Logout</a>
              <?php else: ?>
                <a href="#" data-hh-auth-open>Join / Sign In</a>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="hh-burger" id="hhBurger" aria-label="Open menu" role="button" tabindex="0">
          <span></span><span></span><span></span>
        </div>
      </div>

      <div class="hh-mobile" id="hhMobileMenu">
        <a href="/index.php">Home</a>
        <a href="/cardbuilder.php">Build Card</a>
        <?php if ($loggedIn): ?>
        <a href="<?= htmlspecialchars($downloadDesignHref) ?>">Download Design</a>
        <?php endif; ?>
        

        <?php if ($loggedIn): ?>
          <a href="/logout.php"><?= htmlspecialchars($userName) ?> / Logout</a>
        <?php else: ?>
          <a href="#" data-hh-auth-open>Join / Sign In</a>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
          <a href="/admin/logout.php" style="color:#b00020; font-weight:700;">Admin Logout</a>
        <?php endif; ?>

        <?php if ($showContact): ?>
          <button type="button" data-hh-contact-open>Contact</button>
        <?php endif; ?>

        <?php if ($showSocials): ?>
          <div class="hh-mobile-socials" aria-label="Social media links">
            <a class="hh-social" href="https://www.facebook.com/" target="_blank" rel="noopener" aria-label="Facebook">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.5 22v-8h2.7l.4-3h-3.1V9.1c0-.9.3-1.6 1.7-1.6H16.8V4.8c-.3 0-1.3-.1-2.4-.1-2.4 0-4.1 1.5-4.1 4.2V11H7.5v3h2.8v8h3.2z"/></svg>
            </a>
            <a class="hh-social" href="https://www.instagram.com/" target="_blank" rel="noopener" aria-label="Instagram">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M7.75 2h8.5A5.75 5.75 0 0 1 22 7.75v8.5A5.75 5.75 0 0 1 16.25 22h-8.5A5.75 5.75 0 0 1 2 16.25v-8.5A5.75 5.75 0 0 1 7.75 2zm0 1.8A3.95 3.95 0 0 0 3.8 7.75v8.5a3.95 3.95 0 0 0 3.95 3.95h8.5a3.95 3.95 0 0 0 3.95-3.95v-8.5a3.95 3.95 0 0 0-3.95-3.95h-8.5zm8.9 1.35a1.1 1.1 0 1 1 0 2.2 1.1 1.1 0 0 1 0-2.2zM12 6.4A5.6 5.6 0 1 1 6.4 12 5.6 5.6 0 0 1 12 6.4zm0 1.8A3.8 3.8 0 1 0 15.8 12 3.8 3.8 0 0 0 12 8.2z"/>
              </svg>
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <div class="hh-spacer" aria-hidden="true"></div>
</div>

<script id="heavenly-header-script">
(function(){
  if (window.__HEAVENLY_HEADER_INIT__) return;
  window.__HEAVENLY_HEADER_INIT__ = true;

  const header = document.getElementById('hhHeader');
  const burger = document.getElementById('hhBurger');
  const mobileMenu = document.getElementById('hhMobileMenu');

  function onScroll(){
    if (!header) return;
    const currentScroll = window.pageYOffset || document.documentElement.scrollTop || 0;
    header.classList.toggle('hh-scrolled', currentScroll > 50);
    header.style.top = '0px';
  }

  function handleResize(){
    if (window.innerWidth > 900 && mobileMenu) {
      mobileMenu.style.display = 'none';
    }
  }

  function toggleMobileMenu(){
    if (!mobileMenu) return;
    mobileMenu.style.display = (mobileMenu.style.display === 'flex') ? 'none' : 'flex';
  }

  if (burger) {
    burger.addEventListener('click', toggleMobileMenu);
    burger.addEventListener('keydown', function(e){
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        toggleMobileMenu();
      }
    });
  }

  document.addEventListener('click', function(e){
    const authBtn = e.target.closest('[data-hh-auth-open]');
    const contactBtn = e.target.closest('[data-hh-contact-open]');

    if (authBtn) {
      e.preventDefault();
      if (mobileMenu && window.innerWidth <= 900) mobileMenu.style.display = 'none';
      document.dispatchEvent(new CustomEvent('heavenly:open-auth'));
    }

    if (contactBtn) {
      e.preventDefault();
      if (mobileMenu && window.innerWidth <= 900) mobileMenu.style.display = 'none';
      document.dispatchEvent(new CustomEvent('heavenly:open-contact'));
    }
  });

  window.addEventListener('resize', handleResize);
  window.addEventListener('scroll', onScroll, { passive:true });

  handleResize();
  onScroll();
})();
</script>