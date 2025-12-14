<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['admin_id'])) {
    header("Location: /emp_dtr/adminPage/adminMain.php");
    exit(); 
}
if (isset($_SESSION['employee_id'])) {
    header("Location: /emp_dtr/employeePage/mainpage.php");
    exit(); 
}

?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
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
  .app-header .brand img {
      height: 30px;
      width: auto;
  }
  .app-header .nav-links a { 
      color: white; 
      text-decoration: none; 
      font-weight: 600;
      padding: 8px 12px;
      border: 1px solid var(--sys-yellow);
      border-radius: 4px;
      transition: background-color 0.3s, color 0.3s, border-color 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
  }
  .app-header .nav-links a:hover {
      background-color: var(--sys-yellow);
      color: var(--sys-red); 
      border-color: var(--sys-yellow);
  }
  
  .icon-sm {
    width: 20px;
    height: 20px;
    stroke: currentColor;
    stroke-width: 2.5;
    fill: none;
    vertical-align: middle;
  }
  
  @media (max-width: 576px) {
      .app-header {
          flex-direction: column;
          align-items: flex-start;
          padding: 10px 15px;
      }
      .app-header .brand {
          margin-bottom: 10px;
      }
      .app-header .nav-links a {
          width: 100%;
          justify-content: center;
      }
  }
</style>

<header class="app-header">
  <a href="/emp_dtr/index.php" class="brand">
    <img src="/emp_dtr/logo.png" alt="DTR Logo">
    E-DTR System
  </a>
  <nav class="nav-links">
    <a href="/emp_dtr/user/login.php">
        <svg class="icon-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        Back to Login
    </a>
  </nav>
</header>
