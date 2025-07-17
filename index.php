<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

// Get counts for sidebar
$stmt = $pdo->query("SELECT COUNT(*) FROM families WHERE is_head = 1");
$head_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM families WHERE relationship = '1 - Puno ng Pamilya'");
$puno_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM families");
$total_members = $stmt->fetchColumn();

// Get distinct barangays for dropdown
$stmt = $pdo->query("SELECT DISTINCT barangay FROM families ORDER BY barangay");
$barangays = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total records
$stmt = $pdo->query("SELECT COUNT(*) FROM families");
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$barangay_filter = isset($_GET['barangay']) ? trim($_GET['barangay']) : '';

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(last_name LIKE :search OR first_name LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($barangay_filter)) {
    $where[] = "barangay = :barangay";
    $params[':barangay'] = $barangay_filter;
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count query
$count_sql = "SELECT COUNT(*) FROM families $where_clause";
$stmt = $pdo->prepare($count_sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total_records = $stmt->fetchColumn();

// Data query - Showing all members
$sql = "SELECT * FROM families $where_clause ORDER BY barangay, household_number, is_head DESC, last_name LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$families = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Family Registry System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .sidebar {
            height: 100vh;
            position: fixed;
            width: 250px;
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
            padding: 20px;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .stat-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 15px;
            border: none;
        }
        .stat-count {
            font-size: 1.8rem;
            font-weight: bold;
        }
        .nav-pills .nav-link.active {
            background-color: #0d6efd;
        }
        .table-responsive {
            overflow-x: auto;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
            }
        }
        /* Modal form styling */
        .modal-body .row {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4 class="mb-4">Family Registry System</h4>
        
        <!-- Quick Stats -->
        <div class="card stat-card bg-light mb-3">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Household Heads</h6>
                <div class="stat-count text-primary"><?= $head_count ?></div>
            </div>
        </div>
        
        <div class="card stat-card bg-light mb-3">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Puno ng Pamilya</h6>
                <div class="stat-count text-success"><?= $puno_count ?></div>
            </div>
        </div>
        
        <div class="card stat-card bg-light mb-4">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Total Members</h6>
                <div class="stat-count text-info"><?= $total_members ?></div>
            </div>
        </div>
        
        <!-- Navigation -->
        <ul class="nav nav-pills flex-column">
            <!-- <li class="nav-item">
                <a href="dashboard.php" class="nav-link">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
            </li> -->
            <li class="nav-item">
                <a href="index.php" class="nav-link active">
                    <i class="bi bi-people-fill me-2"></i>Family Members
                </a>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="modal" data-bs-target="#createMemberModal">
                    <i class="bi bi-person-plus me-2"></i>Add Member
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class="bi bi-file-earmark-excel me-2"></i>Import Data
                </button>
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
            <h2>Family Members</h2>
            <div class="text-muted">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></div>
        </div>
        
        <div class="card mb-4">
            <div class="card-body">
                <form id="searchForm" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="search" id="searchInput" class="form-control" placeholder="Search name..." 
                            value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="barangay" id="barangaySelect" class="form-select">
                            <option value="">All Barangays</option>
                            <?php foreach ($barangays as $barangay): ?>
                                <option value="<?= $barangay ?>" <?= (isset($_GET['barangay']) && $_GET['barangay'] == $barangay) ? 'selected' : '' ?>>
                                    <?= $barangay ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive" id="familyTableContainer">
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Household #</th>
                                <th>Barangay</th>
                                <th>Last Name</th>
                                <th>First Name</th>
                                <th>Middle Name</th>
                                <th>Relationship</th>
                                <th>Age</th>
                                <th>Sex</th>
                                <th>Civil Status</th>
                                <!-- <th>Leader</th>
                                <th>Head</th> -->
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="familyTableBody">
                            <?php foreach ($families as $family): ?>
                            <tr>
                                <td><?= htmlspecialchars($family['household_number']) ?></td>
                                <td><?= htmlspecialchars($family['barangay']) ?></td>
                                <td><?= htmlspecialchars($family['last_name']) ?></td>
                                <td><?= htmlspecialchars($family['first_name']) ?></td>
                                <td><?= htmlspecialchars($family['middle_name']) ?></td>
                                <td><?= htmlspecialchars($family['relationship']) ?></td>
                                <td><?= htmlspecialchars($family['age']) ?></td>
                                <td><?= htmlspecialchars($family['sex']) ?></td>
                                <td><?= htmlspecialchars($family['civil_status']) ?></td>
                                <!-- <td><?= $family['is_leader'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                                <td><?= $family['is_head'] ? '<span class="badge bg-primary">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?></td> -->
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="view_household.php?household_number=<?= $family['household_number'] ?>" class="btn btn-info" title="View Household">
                                            <i class="bi bi-house-door"></i>
                                        </a>
                                        <a href="edit.php?id=<?= $family['id'] ?>" class="btn btn-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="delete.php?id=<?= $family['id'] ?>" class="btn btn-danger" title="Delete" onclick="return confirm('Are you sure?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <nav>
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a></li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>">Next</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Member Modal -->
    <div class="modal fade" id="createMemberModal" tabindex="-1" aria-labelledby="createMemberModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="create.php">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="createMemberModalLabel">Add New Family Member</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Household Number</label>
                                    <input type="text" class="form-control" name="household_number" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Row Indicator</label>
                                    <select class="form-select" name="row_indicator" required>
                                        <option value="Head">Head</option>
                                        <option value="Member">Member</option>
                                        <!-- <option value="Dead">Dead</option> -->
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" name="last_name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" name="first_name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" name="middle_name">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Extension</label>
                                    <input type="text" class="form-control" name="ext">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Barangay</label>
                                    <select class="form-select" name="barangay" required>
                                        <option value="">Select Barangay</option>
                                        <?php foreach ($barangays as $barangay): ?>
                                            <option value="<?= $barangay ?>"><?= $barangay ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Relationship</label>
                                    <select class="form-select" name="relationship" required>
                                        <option value="">Select Relationship</option>
                                        <option value="1 - Puno ng Pamilya">1 - Puno ng Pamilya</option>
                                        <option value="2 - Asawa">2 - Asawa</option>
                                        <option value="3 - Anak">3 - Anak</option>
                                        <option value="4 - Kapatid">4 - Kapatid</option>
                                        <option value="5 - Bayaw o Hipag">5 - Bayaw o Hipag</option>
                                        <option value="6 - Apo">6 - Apo</option>
                                        <option value="7 - Ama / Ina">7 - Ama / Ina</option>
                                        <option value="8 - Iba pang Kamag-anak">8 - Iba pang Kamag-anak</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Birthday</label>
                                    <input type="date" class="form-control" name="birthday" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Age</label>
                                    <input type="number" class="form-control" name="age" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Sex</label>
                                    <select class="form-select" name="sex" required>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Civil Status</label>
                                    <select class="form-select" name="civil_status" required>
                                        <option value="Single">Single</option>
                                        <option value="Married">Married</option>
                                        <option value="Widow">Widow</option>
                                        <option value="Widower">Widower</option>
                                    </select>
                                </div>
                                <!-- <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" name="is_leader" id="is_leader">
                                    <label class="form-check-label" for="is_leader">Leader</label>
                                </div> -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Member</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Import Data Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="importModalLabel">Import Family Data</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- <div class="instructions mb-3 p-3 bg-light rounded">
                        <strong>Important:</strong>
                        <ul>
                            <li>File name must match the barangay name (e.g., "Candulao.xlsx")</li>
                            <li>The <strong>Row Indicator</strong> column must contain "Head" for family heads</li>
                            <li>All members of a household must have the same Household Number</li>
                        </ul>
                    </div> -->
                    
                    <form method="POST" enctype="multipart/form-data" action="import.php">
                        <div class="mb-3">
                            <label for="excel_file" class="form-label">Select Excel File</label>
                            <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx, .xls" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Import Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Import Modal
        $('#importModal form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            
            $.ajax({
                url: 'import.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // This will handle the redirect from import.php
                    // The success/error messages will be shown via session messages
                    $('#importModal').modal('hide');
                    window.location.reload();
                },
                error: function(xhr, status, error) {
                    alert('Error during import: ' + error);
                }
            });
        });
        // Real-time search functionality
        $('#searchInput').on('input', function() {
            performSearch();
        });

        // Barangay filter change
        $('#barangaySelect').change(function() {
            performSearch();
        });

        function performSearch() {
            const searchTerm = $('#searchInput').val();
            const barangayFilter = $('#barangaySelect').val();
            
            $.ajax({
                url: 'search_families.php',
                method: 'GET',
                data: {
                    search: searchTerm,
                    barangay: barangayFilter
                },
                success: function(response) {
                    $('#familyTableBody').html(response);
                    // Hide pagination during search
                    $('.pagination').hide();
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                }
            });
        }

        // Handle successful form submission
        <?php if (isset($_SESSION['success_message'])): ?>
            alert('<?= $_SESSION['success_message'] ?>');
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
    });
    </script>
</body>
</html>