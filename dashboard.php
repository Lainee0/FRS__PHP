<?php
session_start();
require_once 'config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}

// Get counts
$stmt = $pdo->query("SELECT COUNT(*) FROM families WHERE is_head = 1");
$head_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM families WHERE relationship = '1 - Puno ng Pamilya'");
$puno_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM families");
$total_members = $stmt->fetchColumn();

// Get count by barangay
$stmt = $pdo->query("SELECT barangay, COUNT(*) as count FROM families WHERE is_head = 1 GROUP BY barangay");
$barangay_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Family Registry System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            height: 100vh;
            position: fixed;
            width: 250px;
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .card {
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card-header {
            font-weight: bold;
        }
        .count {
            font-size: 2.5rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="sidebar p-3">
        <h4 class="mb-4">Family Registry System</h4>
        <ul class="nav nav-pills flex-column">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link active">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="index.php" class="nav-link">
                    <i class="bi bi-people-fill me-2"></i>Family Members
                </a>
            </li>
            <li class="nav-item">
                <a href="create.php" class="nav-link">
                    <i class="bi bi-person-plus me-2"></i>Add Member
                </a>
            </li>
            <li class="nav-item">
                <a href="import.php" class="nav-link">
                    <i class="bi bi-file-earmark-excel me-2"></i>Import Data
                </a>
            </li>
            <li class="nav-item mt-3">
                <a href="logout.php" class="nav-link text-danger">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Dashboard</h2>
            <div>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        Household Heads
                    </div>
                    <div class="card-body text-center">
                        <div class="count text-primary"><?= $head_count ?></div>
                        <p>Total Household Heads</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        Puno ng Pamilya
                    </div>
                    <div class="card-body text-center">
                        <div class="count text-success"><?= $puno_count ?></div>
                        <p>Family Leaders</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        Total Members
                    </div>
                    <div class="card-body text-center">
                        <div class="count text-info"><?= $total_members ?></div>
                        <p>All Family Members</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header bg-secondary text-white">
                Household Heads by Barangay
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Barangay</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($barangay_counts as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['barangay']) ?></td>
                            <td><?= $row['count'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>