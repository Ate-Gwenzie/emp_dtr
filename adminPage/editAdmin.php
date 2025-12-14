<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit();
}
require_once '../classes/admin.php';

$admin = new Admin();
$errors = [];
$success = "";

if (isset($_GET['id'])) {
    $adData = $admin->fetchAdmin($_GET['id']);
    if (!$adData) {
        header("Location: viewAdmin.php");
        exit();
    }
    $admin->adid = $adData['adid'];
    $admin->fname_ad = $adData['fname_ad'];
    $admin->lname_ad = $adData['lname_ad'];
    $admin->email_ad = $adData['email_ad'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admin->adid = $_POST['adid'];
    $admin->fname_ad = trim(htmlspecialchars($_POST['fname_ad'] ?? ""));
    $admin->lname_ad = trim(htmlspecialchars($_POST['lname_ad'] ?? ""));
    $admin->email_ad = trim(htmlspecialchars($_POST['email_ad'] ?? ""));
    
    $new_pass = trim(htmlspecialchars($_POST['pass_ad'] ?? ""));
    if (!empty($new_pass)) {
        $admin->pass_ad = password_hash($new_pass, PASSWORD_DEFAULT);
    } else {
        $admin->pass_ad = null;
    }

    if (empty($admin->fname_ad) || empty($admin->lname_ad) || empty($admin->email_ad)) {
        $errors[] = "Required fields are missing.";
    } elseif (!filter_var($admin->email_ad, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif (!empty($new_pass) && strlen($new_pass) < 6) {
        $errors[] = "New password must be at least 6 characters long.";
    } else {
        try {
            $admin->editAdmin();
            $success = "Admin updated successfully.";
            header("location: viewAdmin.php");
            exit();
        } catch (Exception $e) {
            $errors[] = "Error updating admin: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
  
    body {
        background: #f4f7f6;
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        min-height: 100vh;
    }

/* Header styles centralized to assets/css/app.css */

    .logout:hover, .back:hover, .exit:hover {
        background-color: #f40000; 
    }

    h1 {
        text-align: center;
        margin: 30px 0 20px;
        color: #8b0000; 
        font-weight: 300;
    }

    .container {
        max-width: 500px; 
        margin: 0 auto;
        padding: 0 15px;
    }

    .card {
        background: white;
        border: 1px solid #ddd;
        border-radius: 10px;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
        margin-bottom: 30px;
    }

    .card-header {
        background-color: #8b0000; 
        color: white;
        font-size: 1.2rem;
        font-weight: bold;
        padding: 15px 25px;
        border-bottom: none;
        border-top-left-radius: 9px;
        border-top-right-radius: 9px;
    }

    .card-body {
        padding: 25px;
    }

    .form-control, .form-select {
        border-radius: 5px;
        border: 1px solid #ccc;
        padding: 10px;
        height: auto;
        transition: border-color 0.3s;
    }
    .form-control:focus, .form-select:focus {
        border-color: #ffc107;
        box-shadow: 0 0 5px rgba(255, 193, 7, 0.5);
    }

    .form-label {
        font-weight: 600;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 15px;
    }

    thead {
        background-color: #8b0000;
        color: white;
    }

    th, td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    tbody tr:hover {
        background-color: #fff5f5;
    }

    .btn {
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
        transition: background-color 0.3s, transform 0.2s;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary {
        background-color: #ffc107;
        color: black;
        padding: 10px 30px;
        border-radius: 50px;
        font-size: 1.1rem;
        display: block;
        width: 50%;
        margin: 20px auto 0;
    }

    .btn-primary:hover {
        background-color: #e0a800;
        color: white;
        transform: scale(1.02);
    }
    
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        font-weight: 500;
    }
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .error {
        color: #dc3545;
        font-size: 0.875em;
        margin-top: 5px;
    }
  </style>
</head>
<body>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

  <div class="app-container">
    <a href="viewAdmin.php" class="btn btn-secondary mb-3">Back</a>

  <h1>Edit Admin</h1>

  <div class="container">
    <div class="card">
        <div class="card-header">Update Admin Information</div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
              <div class="alert alert-danger">
                <ul><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul>
              </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
              <div class="alert alert-success" id="success-alert"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
              <input type="hidden" name="adid" value="<?php echo htmlspecialchars($admin->adid); ?>">

              <div class="mb-3">
                <label for="fname_ad" class="form-label">First Name</label>
                <input type="text" class="form-control" id="fname_ad" name="fname_ad" value="<?php echo htmlspecialchars($admin->fname_ad); ?>">
              </div>

              <div class="mb-3">
                <label for="lname_ad" class="form-label">Last Name</label>
                <input type="text" class="form-control" id="lname_ad" name="lname_ad" value="<?php echo htmlspecialchars($admin->lname_ad); ?>">
              </div>

              <div class="mb-3">
                <label for="email_ad" class="form-label">Email</label>
                <input type="email" class="form-control" id="email_ad" name="email_ad" value="<?php echo htmlspecialchars($admin->email_ad); ?>">
              </div>

              <div class="mb-3">
                <label for="pass_ad" class="form-label">New Password (leave blank to keep current)</label>
                <input type="password" class="form-control" id="pass_ad" name="pass_ad">
              </div>

              <button type="submit" class="btn btn-primary">Update Admin</button>
            </form>
        </div>
    </div>
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const successAlert = document.getElementById('success-alert');
        if (successAlert) {
            setTimeout(function() {
                successAlert.style.display = 'none';
            }, 3000);
        }
    });
  </script>
</body>
</html>