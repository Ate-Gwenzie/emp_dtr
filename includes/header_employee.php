<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../classes/notification.php';

$unread = 0;
if (isset($_SESSION['employee_id'])) {
    try {
        $notification = new Notification();
        $unread = $notification->countUnreadNotifications('employee', $_SESSION['employee_id']);
    } catch (\Exception $e) {
        error_log("Error counting notifications: " . $e->getMessage());
    }
}
?>
<link rel="stylesheet" href="/emp_dtr/assets/css/app.css" />
<style>
  .app-header {
    background-color: #8b0000;
    color: white;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 6px rgba(0,0,0,0.08);
    position: relative;
  }
  .app-header .brand { 
      font-weight: 700; 
      font-size: 1.2rem; 
      text-decoration: none;
      color: white;
      display: flex;
      align-items: center;
  }
  .app-header .brand img {
      height: 30px; 
      margin-right: 10px;
  }
  .app-header .nav-links { display:flex; gap:12px; align-items:center }
  .app-header .nav-links a { color: white; text-decoration: none; font-weight:600; display: inline-flex; align-items: center; } /* Updated to display: inline-flex for alignment */
  .app-header .nav-links a .notif-count { display:inline-block; background:#ffc107; color:#000; font-weight:700; padding:2px 6px; border-radius:999px; font-size:0.8rem; margin-left:6px; }
  
  .app-header .nav-links a svg {
      width: 20px;
      height: 20px;
      stroke: currentColor;
      stroke-width: 2.5;
      fill: none;
      vertical-align: middle;
      margin-right: 4px;
  }

  .nav-toggle { 
      display: flex;
      align-items: center;
      justify-content: center;
      background:none; 
      border:0; 
      color:white; 
      font-size:1.2rem;
      padding: 0;
  }
  .nav-toggle svg {
      width: 24px;
      height: 24px;
      stroke: white;
      stroke-width: 2.5;
      fill: none;
  }
  
  .app-container { max-width: 1100px; margin: 30px auto; padding: 0 15px; }
  .app-container h1 { color: #8b0000; margin-bottom: 12px; font-weight: 600; }
  .btn-main { width:auto }

  @media (max-width: 768px) {
    .nav-toggle { display:flex }
    .app-header { padding: 10px 12px }
    .app-header .brand { font-size:1rem }
    
    .app-header .nav-links {
        flex-direction: column;
        align-items: flex-start;
        position: absolute;
        top: 100%;
        right: 0;
        background-color: #7a0000;
        border-top: 1px solid #ffc107;
        width: 100%;
        display: flex; 
        max-height: 0; 
        opacity: 0;
        padding: 0 20px; 
        overflow: hidden;
        transition: max-height 0.3s ease-out, opacity 0.3s ease-out, padding 0.3s ease-out;
        z-index: 100;
    }
    .app-header .nav-links.show {
        max-height: 300px; 
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
  <a href="/emp_dtr/employeePage/mainpage.php" class="brand">
    <img src="/emp_dtr/logo.png" alt="DTR Logo" />
    E-DTR SYSTEM
  </a>
  <button id="navToggle" class="nav-toggle" aria-expanded="false" aria-controls="main-nav">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
  </button>
  <nav id="main-nav" class="nav-links">
    
    <?php if (isset($_SESSION['employee_id'])): ?>
      <a href="/emp_dtr/employeePage/viewHistory.php">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M9 12h6"/><path d="M9 16h6"/></svg>
        History
      </a>
      <a href="/emp_dtr/employeePage/viewNotification.php" title="Notifications">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9zm-1.87 11a2 2 0 1 1-3.48 0"/></svg>
          <?php echo $unread > 0 ? "<span class=\"notif-count\">" . intval($unread) . "</span>" : ''; ?>
      </a>
      <a href="/emp_dtr/user/logout.php">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Logout
      </a>
    <?php else: ?>
      <a href="/emp_dtr/user/login.php">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
        Login
      </a>
      <a href="/emp_dtr/user/request_account.php">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
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
    document.addEventListener('click', function(e){
      if (!nav.contains(e.target) && !btn.contains(e.target)) {
        nav.classList.remove('show');
        btn.setAttribute('aria-expanded', 'false');
      }
    });
  })();
</script>
