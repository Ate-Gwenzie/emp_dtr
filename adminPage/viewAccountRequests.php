<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../user/login.php');
    exit();
}
require_once __DIR__ . '/../classes/account_request.php';

$req = new AccountRequest();
$requests = $req->getPendingRequests();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body{background:#f4f7f6;font-family:Segoe UI, Tahoma, Arial}
        .container{max-width:1000px;margin:40px auto}
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<div class="app-container">
    <h1>Pending Account Requests (<?php echo count($requests); ?>)</h1>
    <a href="adminMain.php" class="btn btn-secondary mb-3">Back</a>
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['message']); ?></div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
    <div class="card">
        <div class="card-body">
            <?php if (empty($requests)): ?>
                <p>No pending requests.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr><th>ID</th><th>Name</th><th>Email</th><th>Type</th><th>Employee ID</th><th>Details</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($requests as $r): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($r['id']); ?></td>
                                <td><?php echo htmlspecialchars($r['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($r['email']); ?></td>
                                <td><?php echo htmlspecialchars($r['type']); ?></td>
                                <td><?php echo htmlspecialchars($r['employee_id']); ?></td>
                                <td>
                                    <?php
                                        $det = $r['details'];
                                        if (!empty($det)) {
                                            $decoded = json_decode($det, true);
                                            if (is_array($decoded)) {
                                                $parts = [];
                                                if (!empty($decoded['fname']) || !empty($decoded['lname'])) $parts[] = 'Name: ' . htmlspecialchars(trim(($decoded['fname'] ?? '') . ' ' . ($decoded['lname'] ?? '')));
                                                if (!empty($decoded['position'])) $parts[] = 'Position: ' . htmlspecialchars($decoded['position']);
                                                if (!empty($decoded['shift_type'])) $parts[] = 'Shift: ' . htmlspecialchars($decoded['shift_type']);
                                                if (!empty($decoded['timein_am']) || !empty($decoded['timeout_am'])) $parts[] = 'AM: ' . htmlspecialchars(($decoded['timein_am'] ?? '') . ' - ' . ($decoded['timeout_am'] ?? ''));
                                                if (!empty($decoded['timein_pm']) || !empty($decoded['timeout_pm'])) $parts[] = 'PM: ' . htmlspecialchars(($decoded['timein_pm'] ?? '') . ' - ' . ($decoded['timeout_pm'] ?? ''));
                                                if (!empty($decoded['details_text'])) $parts[] = 'Details: ' . htmlspecialchars($decoded['details_text']);
                                                echo implode('<br>', $parts);
                                            } else {
                                                echo htmlspecialchars($det);
                                            }
                                        }
                                    ?>
                                </td>
                                <td>
                                    <form method="POST" action="process_create_request.php" style="display:inline;">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($r['id']); ?>">
                                        <button class="btn btn-success" type="submit" onclick="return confirm('Create account for this request?')">Create & Send Verification</button>
                                    </form>
                                    <form method="POST" action="process_create_request.php" style="display:inline;margin-left:6px;">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($r['id']); ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button class="btn btn-danger" type="submit" onclick="return confirm('Reject this request?')">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
