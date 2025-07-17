<?php
require_once 'config.php';

if (!isset($_GET['household_number'])) {
    header("Location: index.php");
    exit;
}

$household_number = $_GET['household_number'];

// Handle form submission for remarks
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_remarks'])) {
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
    elseif (isset($_POST['update_member'])) {
        $member_id = $_POST['member_id'];
        $data = [
            'last_name' => $_POST['last_name'],
            'first_name' => $_POST['first_name'],
            'middle_name' => $_POST['middle_name'],
            'ext' => $_POST['ext'],
            'relationship' => $_POST['relationship'],
            'birthday' => $_POST['birthday'],
            'age' => $_POST['age'],
            'sex' => $_POST['sex'],
            'civil_status' => $_POST['civil_status'],
            'is_leader' => isset($_POST['is_leader']) ? 1 : 0,
            'id' => $member_id
        ];
        
        try {
            $stmt = $pdo->prepare("UPDATE families SET 
                last_name = :last_name,
                first_name = :first_name,
                middle_name = :middle_name,
                ext = :ext,
                relationship = :relationship,
                birthday = :birthday,
                age = :age,
                sex = :sex,
                civil_status = :civil_status,
                is_leader = :is_leader
                WHERE id = :id");
            
            $stmt->execute($data);
            
            $_SESSION['success_message'] = "Member updated successfully!";
            header("Location: view_household.php?household_number=" . $household_number);
            exit;
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error updating member: " . $e->getMessage();
            header("Location: view_household.php?household_number=" . $household_number);
            exit;
        }
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
        .member-form .form-group {
            margin-bottom: 15px;
        }
        .form-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
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
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Barangay:</strong> <?= htmlspecialchars($head['barangay'] ?? '') ?></p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Family Members</h5>
                <button class="btn btn-light btn-sm" id="addMemberBtn">
                    <i class="bi bi-plus"></i> Add Member
                </button>
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
                                        <button class="btn btn-outline-secondary edit-member" 
                                                data-member-id="<?= $member['id'] ?>"
                                                data-first-name="<?= htmlspecialchars($member['first_name']) ?>"
                                                data-last-name="<?= htmlspecialchars($member['last_name']) ?>"
                                                data-middle-name="<?= htmlspecialchars($member['middle_name']) ?>"
                                                data-ext="<?= htmlspecialchars($member['ext']) ?>"
                                                data-relationship="<?= htmlspecialchars($member['relationship']) ?>"
                                                data-birthday="<?= htmlspecialchars($member['birthday']) ?>"
                                                data-age="<?= htmlspecialchars($member['age']) ?>"
                                                data-sex="<?= htmlspecialchars($member['sex']) ?>"
                                                data-civil-status="<?= htmlspecialchars($member['civil_status']) ?>"
                                                data-is-leader="<?= $member['is_leader'] ?>"
                                                title="Edit Member">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="delete.php?id=<?= $member['id'] ?>" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this member?')">
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
                            <!-- <textarea class="form-control" id="remarksNotes" name="remarks" rows="5"></textarea> -->
                            <input type="text" class="form-control" name="remarks" id="remarksNotes">
                            <button class="btn btn-primary btn-sm" id="addRemarksBtn">
                                <i class="bi bi-plus"></i>
                            </button>
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

    <!-- Edit Member Modal -->
    <div class="modal fade" id="editMemberModal" tabindex="-1" aria-labelledby="editMemberModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="view_household.php?household_number=<?= $household_number ?>">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="editMemberModalLabel">Edit Family Member</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body member-form">
                        <input type="hidden" name="member_id" id="editMemberId">
                        
                        <div class="form-section">
                            <h6>Personal Information</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" name="first_name" id="editFirstName" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label">Middle Name</label>
                                        <input type="text" class="form-control" name="middle_name" id="editMiddleName">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control" name="last_name" id="editLastName" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label">Extension</label>
                                        <input type="text" class="form-control" name="ext" id="editExt">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label">Sex</label>
                                        <select class="form-select" name="sex" id="editSex" required>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label">Age</label>
                                        <input type="number" class="form-control" name="age" id="editAge" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label">Birthday</label>
                                        <input type="date" class="form-control" name="birthday" id="editBirthday" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h6>Family Information</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Relationship</label>
                                        <select class="form-select" name="relationship" id="editRelationship" required>
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
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Civil Status</label>
                                        <select class="form-select" name="civil_status" id="editCivilStatus" required>
                                            <option value="Single">Single</option>
                                            <option value="Married">Married</option>
                                            <option value="Widow">Widow</option>
                                            <option value="Widower">Widower</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <!-- <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_leader" id="editIsLeader">
                                <label class="form-check-label" for="editIsLeader">
                                    Is Leader
                                </label>
                            </div> -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_member" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Member Modal -->
    <div class="modal fade" id="addMemberModal" tabindex="-1" aria-labelledby="addMemberModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="create.php">
                    <input type="hidden" name="household_number" value="<?= $household_number ?>">
                    <input type="hidden" name="barangay" value="<?= htmlspecialchars($head['barangay'] ?? '') ?>">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="addMemberModalLabel">Add New Family Member</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body member-form">
                        <div class="form-section">
                            <h6>Personal Information</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" name="first_name" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label">Middle Name</label>
                                        <input type="text" class="form-control" name="middle_name">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control" name="last_name" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label">Extension</label>
                                        <input type="text" class="form-control" name="ext">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label">Sex</label>
                                        <select class="form-select" name="sex" required>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label">Age</label>
                                        <input type="number" class="form-control" name="age" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label">Birthday</label>
                                        <input type="date" class="form-control" name="birthday" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h6>Family Information</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Relationship</label>
                                        <select class="form-select" name="relationship" required>
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
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Civil Status</label>
                                        <select class="form-select" name="civil_status" required>
                                            <option value="Single">Single</option>
                                            <option value="Married">Married</option>
                                            <option value="Widow">Widow</option>
                                            <option value="Widower">Widower</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <!-- <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_leader" id="is_leader">
                                <label class="form-check-label" for="is_leader">
                                    Is Leader
                                </label>
                            </div> -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Member</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize modals
        const remarksModal = new bootstrap.Modal(document.getElementById('remarksModal'));
        const editMemberModal = new bootstrap.Modal(document.getElementById('editMemberModal'));
        const addMemberModal = new bootstrap.Modal(document.getElementById('addMemberModal'));
        
        // Remarks modal handling
        document.querySelectorAll('.edit-remarks').forEach(button => {
            button.addEventListener('click', function () {
                const memberId = this.getAttribute('data-member-id');
                const remarks = this.getAttribute('data-remarks');
                
                document.getElementById('modalMemberId').value = memberId;
                document.getElementById('remarksNotes').value = remarks || '';
                
                remarksModal.show();
            });
        });
        
        // Edit member modal handling
        document.querySelectorAll('.edit-member').forEach(button => {
            button.addEventListener('click', function () {
                const memberId = this.getAttribute('data-member-id');
                
                // Populate form fields
                document.getElementById('editMemberId').value = memberId;
                document.getElementById('editFirstName').value = this.getAttribute('data-first-name');
                document.getElementById('editMiddleName').value = this.getAttribute('data-middle-name');
                document.getElementById('editLastName').value = this.getAttribute('data-last-name');
                document.getElementById('editExt').value = this.getAttribute('data-ext');
                document.getElementById('editRelationship').value = this.getAttribute('data-relationship');
                document.getElementById('editBirthday').value = this.getAttribute('data-birthday');
                document.getElementById('editAge').value = this.getAttribute('data-age');
                document.getElementById('editSex').value = this.getAttribute('data-sex');
                document.getElementById('editCivilStatus').value = this.getAttribute('data-civil-status');
                // document.getElementById('editIsLeader').checked = this.getAttribute('data-is-leader') === '1';
                
                editMemberModal.show();
            });
        });
        
        // Add member button
        document.getElementById('addMemberBtn').addEventListener('click', function() {
            addMemberModal.show();
        });
        
        // Auto-calculate age from birthday
        document.getElementById('editBirthday').addEventListener('change', function() {
            const birthday = new Date(this.value);
            const ageDiff = Date.now() - birthday.getTime();
            const ageDate = new Date(ageDiff);
            const calculatedAge = Math.abs(ageDate.getUTCFullYear() - 1970);
            document.getElementById('editAge').value = calculatedAge;
        });
    });
    </script>
</body>
</html>