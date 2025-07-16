<?php
require_once 'config.php';

// Changed from head_id to household_number
if (!isset($_GET['household_number'])) {
    header("Location: index.php");
    exit;
}

$household_number = $_GET['household_number'];

// Handle form submission for remarks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_remarks'])) {
    $member_id = $_POST['member_id'];
    $remarks = $_POST['remarks'];
    
    try {
        $stmt = $pdo->prepare("UPDATE families SET remarks = ? WHERE id = ?");
        $stmt->execute([$remarks, $member_id]);
        
        $_SESSION['success_message'] = "Remarks saved successfully!";
        header("Location: view_household.php?household_number=" . $household_number);
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error saving remarks: " . $e->getMessage();
        header("Location: view_household.php?household_number=" . $household_number);
        exit;
    }
}

// Get household head info (first head found with this household number)
$stmt = $pdo->prepare("SELECT * FROM families WHERE household_number = ? AND is_head = 1 LIMIT 1");
$stmt->execute([$household_number]);
$head = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$head) {
    // If no head found, just get the first member (for display purposes)
    $stmt = $pdo->prepare("SELECT * FROM families WHERE household_number = ? LIMIT 1");
    $stmt->execute([$household_number]);
    $head = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$head) {
        header("Location: index.php");
        exit;
    }
}

// Get all household members with the same household_number
$stmt = $pdo->prepare("
    SELECT * FROM families 
    WHERE household_number = ?
    ORDER BY 
        is_head DESC, -- Head first
        CASE 
            WHEN relationship LIKE '1 - Puno ng Pamilya' THEN 1
            WHEN relationship LIKE '2 - Asawa' THEN 2
            WHEN relationship LIKE '3 - Anak' THEN 3
            ELSE 4
        END,
        age DESC
");
$stmt->execute([$household_number]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Household Members - <?= htmlspecialchars($head['last_name'] ?? '') ?> Family</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .household-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        .table-remarks {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .badge-head {
            background-color: #fd7e14;
        }
        .badge-leader {
            background-color: #198754;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Household Members - <?= htmlspecialchars($head['first_name'] ?? '') ?> <?= htmlspecialchars($head['last_name'] ?? '') ?></h2>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
        
        <div class="household-header mb-4">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Household #:</strong> <?= htmlspecialchars($household_number) ?></p>
                    <p class="mb-1"><strong>Total Members:</strong> <?= count($members) ?></p>
                    <!-- <p class="mb-1"><strong>Address:</strong> <?= htmlspecialchars($head['address'] ?? '') ?></p> -->
                </div>
                <div class="col-md-6">
                    <!-- <p class="mb-1"><strong>Contact Number:</strong> <?= htmlspecialchars($head['contact_number'] ?? '') ?></p> -->
                    <p class="mb-1"><strong>Barangay:</strong> <?= htmlspecialchars($head['barangay'] ?? '') ?></p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Family Members</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Relationship</th>
                                <th>Age</th>
                                <th>Sex</th>
                                <th>Status</th>
                                <th>Type</th>
                                <th>Remarks</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($member['first_name']) . ' ' . htmlspecialchars($member['last_name']) ?>
                                </td>
                                <td><?= htmlspecialchars($member['relationship']) ?></td>
                                <td><?= htmlspecialchars($member['age']) ?></td>
                                <td><?= htmlspecialchars($member['sex']) ?></td>
                                <td><?= htmlspecialchars($member['civil_status']) ?></td>
                                <td>
                                    <?php if ($member['is_head']): ?>
                                        <span class="badge badge-head">Head</span>
                                    <?php endif; ?>
                                    <?php if ($member['is_leader']): ?>
                                        <span class="badge badge-leader">Leader</span>
                                    <?php endif; ?>
                                </td>
                                <td class="table-remarks" title="<?= !empty($member['remarks']) ? htmlspecialchars($member['remarks']) : 'No remarks' ?>">
                                    <?php if (!empty($member['remarks'])): ?>
                                        <a href="#" class="remarks-view text-primary text-decoration-underline" 
                                        data-member-id="<?= $member['id'] ?>"
                                        data-remarks="<?= htmlspecialchars($member['remarks']) ?>">
                                            Remarks
                                        </a>
                                    <?php else: ?>
                                        No Remarks
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary edit-remarks" 
                                                data-member-id="<?= $member['id'] ?>"
                                                data-remarks="<?= htmlspecialchars($member['remarks'] ?? '') ?>"
                                                title="Edit Remarks">
                                            <i class="bi bi-chat-square-text"></i>
                                        </button>
                                        <a href="edit.php?id=<?= $member['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="delete.php?id=<?= $member['id'] ?>" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Are you sure?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Remarks Modal -->
    <div class="modal fade" id="remarksModal" tabindex="-1" aria-labelledby="remarksModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="view_household.php?household_number=<?= $household_number ?>">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="remarksModalLabel">Edit Remarks</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="member_id" id="modalMemberId">
                        <div class="mb-3">
                            <label for="remarksNotes" class="form-label">Remarks</label>
                            <textarea class="form-control" id="remarksNotes" name="remarks" rows="5"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="save_remarks" class="btn btn-primary">Save Remarks</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Remarks Modal -->
    <div class="modal fade" id="viewRemarksModal" tabindex="-1" aria-labelledby="viewRemarksModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title" id="viewRemarksModalLabel">View Remarks</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="viewRemarksText" class="text-wrap"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const remarksModal = new bootstrap.Modal(document.getElementById('remarksModal'));
        const remarksTextarea = document.getElementById('remarksNotes');
        const memberIdInput = document.getElementById('modalMemberId');
        const saveButton = document.querySelector('button[name="save_remarks"]');

        // Edit mode
        document.querySelectorAll('.edit-remarks').forEach(button => {
            button.addEventListener('click', function () {
                const memberId = this.getAttribute('data-member-id');
                const remarks = this.getAttribute('data-remarks');

                memberIdInput.value = memberId;
                remarksTextarea.value = remarks || '';
                remarksTextarea.removeAttribute('readonly');
                saveButton.style.display = 'inline-block';

                remarksModal.show();
            });
        });

        // View-only mode
        document.querySelectorAll('.remarks-view').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();

                const memberId = this.getAttribute('data-member-id');
                const remarks = this.getAttribute('data-remarks');

                memberIdInput.value = memberId;
                remarksTextarea.value = remarks || '';
                remarksTextarea.setAttribute('readonly', true);
                saveButton.style.display = 'none';

                remarksModal.show();
            });
        });
    });
    </script>
</body>
</html>