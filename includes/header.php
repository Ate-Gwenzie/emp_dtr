<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../classes/notification.php';
?>
<link rel="stylesheet" href="/emp_dtr/assets/css/app.css" />
<style>
  /* Standard modern colors/styles */
  :root {
      --sys-red: #8b0000;
      --sys-red-light: #dc3545;
      --sys-yellow: #ffc107;
  }
  .app-header {
    background-color: var(--sys-red);
    color: white;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 6px rgba(0,0,0,0.08);
    position: relative; /* For mobile dropdown positioning */
  }
  .app-header .brand { 
      font-weight: 700; 
      font-size: 1.2rem; 
      text-decoration: none;
      color: white;
      display: flex;
      align-items: center;
      gap: 10px;
  }
  .app-header .brand-logo { height: 30px; margin-right: 0; }
  .app-header .nav-links { display:flex; gap:12px; align-items:center }
  .app-header .nav-links a { color: white; text-decoration: none; font-weight:600; display: inline-flex; align-items: center; }
  
  /* SVG Icon styles */
  .icon-sm {
    width: 20px;
    height: 20px;
    stroke: currentColor;
    stroke-width: 2.5;
    fill: none;
    vertical-align: middle;
  }
  
  .nav-toggle { 
      display: none; /* Hide on desktop */
      align-items: center;
      justify-content: center;
      background:none; 
      border:0; 
      color:white; 
      padding: 0;
  }
  .nav-toggle svg {
      width: 24px;
      height: 24px;
      stroke: white;
      stroke-width: 2.5;
      fill: none;
  }

  /* Mobile Styles with Soft Dropdown */
  @media (max-width: 768px) {
    .nav-toggle { display:flex } /* Show toggle button */
    
    .app-header .nav-links {
        /* Mobile menu container setup */
        flex-direction: column;
        align-items: flex-start;
        position: absolute;
        top: 100%;
        right: 0;
        background-color: #7a0000;
        border-top: 1px solid var(--sys-yellow);
        width: 100%;
        display: flex; /* Required for max-height transition */

        /* Animation properties */
        max-height: 0; 
        opacity: 0;
        padding: 0 20px; 
        overflow: hidden;
        transition: max-height 0.3s ease-out, opacity 0.3s ease-out, padding 0.3s ease-out;
        z-index: 100;
    }
    .app-header .nav-links.show {
        max-height: 300px; /* Max height large enough for content */
        opacity: 1;
        padding-top: 10px;
        padding-bottom: 10px;
    }
    .app-header .nav-links a {
        padding: 8px 0;
        width: 100%;
        border-bottom: 1px solid #9f2a2a;
    }
    .app-header .nav-links a:last-child {
        border-bottom: none;
    }
  }
</style>

<header class="app-header">
  <div class="brand">
     <img src="/emp_dtr/logo.png" alt="DTR Logo" class="brand-logo" />
     <span>E-DTR SYSTEM</span>
   </div>
  <button class="nav-toggle" aria-expanded="false" aria-controls="main-nav" id="navToggle">
    <svg class="icon-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
  </button>
  <nav class="nav-links" id="main-nav">
    <a href="/emp_dtr/index.php">
        <svg class="icon-sm" style="margin-right: 4px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>
        Home
    </a>
    <?php if (isset($_SESSION['admin_id'])): ?>
      <?php
        try {
            $nm = new Notification();
            $unread = count(array_filter($nm->getNotifications('admin', $_SESSION['admin_id']), function($n){ return ($n['is_read']==0); }));
        } catch (Exception $e) {
            $unread = 0;
        }
      ?>
      <a href="/emp_dtr/user/logout.php">
        <svg class="icon-sm" style="margin-right: 4px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
        Back
      </a>
    <?php elseif (isset($_SESSION['employee_id'])): ?>
      <?php
        try {
            $nm = new Notification();
            $unread = count(array_filter($nm->getNotifications('employee', $_SESSION['employee_id']), function($n){ return ($n['is_read']==0); }));
        } catch (Exception $e) {
            $unread = 0;
        }
      ?>
    <?php else: ?>
      <a href="/emp_dtr/user/request_account.php">
        <svg class="icon-sm" style="margin-right: 4px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
        Request Account
      </a>
    <?php endif; ?>
  </nav>
</header>
<script>
  (function(){
    var btn = document.getElementById('navToggle');
    var nav = document.getElementById('main-nav');
    if (!btn || !nav) return;
    btn.addEventListener('click', function(){
      var expanded = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', !expanded);
      nav.classList.toggle('show');
    });
    // click outside to close
    document.addEventListener('click', function(e){
      if (!nav.contains(e.target) && !btn.contains(e.target)) {
        nav.classList.remove('show');
        btn.setAttribute('aria-expanded', 'false');
      }
    });
  })();
</script>