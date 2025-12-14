<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if an Admin is already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: /emp_dtr/adminPage/adminMain.php");
    exit(); // Only one exit needed
}

// Redirect if an Employee is already logged in
if (isset($_SESSION['employee_id'])) {
    header("Location: /emp_dtr/employeePage/mainpage.php");
    exit(); // Only one exit needed
}
?>

<link rel="stylesheet" href="/emp_dtr/assets/css/app.css" />
<style>
    /* Standard modern colors/styles */
    :root {
        --sys-red: #8b0000;
        --sys-red-light: #dc3545;
        --sys-yellow: #ffc107;
    }
    /* Basic Public Header Styles */
    .app-header-public {
        background-color: var(--sys-red);
        color: white;
        padding: 12px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 4px 6px rgba(0,0,0,0.08);
        position: relative; /* For mobile dropdown positioning */
    }
    .app-header-public .brand { 
        font-weight: 700; 
        font-size: 1.2rem; 
        text-decoration: none; 
        color: white;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .app-header-public .brand img {
        height: 30px;
        width: auto;
    }
    .app-header-public .nav-links {
        display: flex;
        gap: 12px;
        align-items: center;
    }
    .app-header-public .nav-links a { 
        color: white; 
        text-decoration: none; 
        font-weight: 600;
        transition: color 0.3s;
        padding: 5px 10px;
        border: 1px solid transparent;
        border-radius: 4px;
        display: inline-flex; /* Enable icon alignment */
        align-items: center;
        gap: 4px;
    }
    .app-header-public .nav-links a:hover {
        color: var(--sys-yellow);
        border-color: var(--sys-yellow);
    }
    
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
        
        .app-header-public .nav-links {
            /* Mobile menu container setup */
            flex-direction: column;
            align-items: flex-start;
            position: absolute;
            top: 100%;
            right: 0;
            background-color: #7a0000;
            border-top: 1px solid var(--sys-yellow);
            width: 100%;
            display: flex; 

            /* Animation properties */
            max-height: 0; 
            opacity: 0;
            padding: 0 20px; 
            overflow: hidden;
            transition: max-height 0.3s ease-out, opacity 0.3s ease-out, padding 0.3s ease-out;
            z-index: 100;
        }
        .app-header-public .nav-links.show {
            max-height: 300px; 
            opacity: 1;
            padding-top: 10px;
            padding-bottom: 10px;
        }
        .app-header-public .nav-links a {
            padding: 8px 0;
            width: 100%;
            border-bottom: 1px solid #9f2a2a;
            justify-content: flex-start;
        }
        .app-header-public .nav-links a:last-child {
            border-bottom: none;
        }
    }
</style>

<header class="app-header-public">
    <a href="/emp_dtr/index.php" class="brand">
        <img src="/emp_dtr/logo.png" alt="DTR Logo" />
        E-DTR SYSTEM
    </a>
    <button class="nav-toggle" aria-expanded="false" aria-controls="main-nav" id="navToggle">
        <svg class="icon-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <nav class="nav-links" id="main-nav">
        <a href="/emp_dtr/user/login.php">
            <svg class="icon-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
            Login
        </a>
        <a href="/emp_dtr/user/request_account.php">
            <svg class="icon-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
            Request Account
        </a>
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