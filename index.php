<?php
// Simple landing page with Login and Request Account
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
            body {
                background-image: url('/emp_dtr/time-dtr.jpg');
                background-size: cover;
                background-position: center;
                background-attachment: fixed;
                font-family: 'Segoe UI', Tahoma, Arial;
                min-height: 100vh;
                margin: 0;
                display: flex;
                flex-direction: column;
        }
            .main-content-wrapper {
                flex-grow: 1; 
                display: flex;
                justify-content: center;
                align-items: center; 
                /* ADJUSTMENT: Reduced vertical padding to pull the box up */
                padding: 10px 20px; 
            }
            .center {
                max-width: 800px;
                margin: 0; 
                text-align: center;
                background: rgba(255, 255, 255, 0.85);
                padding: 40px; 
                border-radius: 12px;
                box-shadow: 0 8px 40px rgba(0, 0, 0, 0.12);
                width: 100%;
            }
            .hero-title { 
                font-size: 1.6rem; 
                color: #8b0000; 
                margin: 0; 
                font-weight: 700; 
            }
            .brand-hero{
                margin-bottom: 24px;
                margin-top: 0;
                max-width: 500px;               
                margin-left: auto;
                margin-right: auto;
            }
            .brand-hero img {
                max-width: 150px;
                height: auto;
                margin-bottom: 10px;
            }
            .btn-main {
                display: inline-block;
                padding: 10px 25px;
                font-size: 1.1rem;
                font-weight: bold;
                border-radius: 50px;
                transition: background-color 0.3s;
            }
    </style>
</head>
<body>
<?php include_once __DIR__ . '/includes/header_public.php'; ?>

<div class="main-content-wrapper">
    <div class="center"> 
        <div class="brand-hero">
            <img src="/emp_dtr/logo.png" alt="DTR Logo" />
            <h1 class="hero-title">Welcome to Employee Daily Time Record!</h1>
            <h1 class="hero-title">E-DTR</h1>
            <p class="mb-4">Manage Daily-Time Records, requests and attendance, simply and securely.</p>
        </div>
        <a href="user/login.php" class="btn btn-outline-danger btn-main mb-3">Login</a>
        <a href="user/request_account.php" class="btn btn-outline-danger btn-main mb-3">Request Account</a>
    </div>
</div>
</body>
</html>