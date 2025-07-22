<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id' => $_GET['id'],
        'sn' => $_POST['sn'],
        'row_indicator' => $_POST['row_indicator'],
        'last_name' => $_POST['last_name'],
        'first_name' => $_POST['first_name'],
        'middle_name' => $_POST['middle_name'],
        'ext' => $_POST['ext'],
        'relationship' => $_POST['relationship'],
        'birthday' => $_POST['birthday'],
        'age' => $_POST['age'],
        'sex' => $_POST['sex'],
        'civil_status' => $_POST['civil_status'],
        'household_number' => $_POST['household_number'],
        'barangay' => $_POST['barangay'],
    ];

    $sql = "UPDATE families SET sn = :sn, row_indicator = :row_indicator, last_name = :last_name, 
            first_name = :first_name, middle_name = :middle_name, ext = :ext, relationship = :relationship, 
            birthday = :birthday, age = :age, sex = :sex, civil_status = :civil_status, 
            household_number = :household_number, barangay = :barangay 
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($data)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update member']);
    }
    exit;
}

if (!isset($_GET['id'])) {
    die('Member ID not provided');
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM families WHERE id = ?");
$stmt->execute([$id]);
$family = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$family) {
    die('Member not found');
}

// Get distinct barangays for dropdown
$stmt = $pdo->query("SELECT DISTINCT barangay FROM families ORDER BY barangay");
$barangays = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<form id="editMemberForm" method="POST" action="edit.php?id=<?= $id ?>">
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Household Number</label>
                <input type="text" class="form-control" name="household_number" value="<?= htmlspecialchars($family['household_number']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Row Indicator</label>
                <select class="form-select" name="row_indicator" required>
                    <option value="Head" <?= $family['row_indicator'] == 'Head' ? 'selected' : '' ?>>Head</option>
                    <option value="Member" <?= $family['row_indicator'] == 'Member' ? 'selected' : '' ?>>Member</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Last Name</label>
                <input type="text" class="form-control" name="last_name" value="<?= htmlspecialchars($family['last_name']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">First Name</label>
                <input type="text" class="form-control" name="first_name" value="<?= htmlspecialchars($family['first_name']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Middle Name</label>
                <input type="text" class="form-control" name="middle_name" value="<?= htmlspecialchars($family['middle_name']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Extension</label>
                <input type="text" class="form-control" name="ext" value="<?= htmlspecialchars($family['ext']) ?>">
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Barangay</label>
                <select class="form-select" name="barangay" required>
                    <option value="">Select Barangay</option>
                    <?php foreach ($barangays as $barangay): ?>
                        <option value="<?= $barangay ?>" <?= $family['barangay'] == $barangay ? 'selected' : '' ?>>
                            <?= $barangay ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Relationship</label>
                <select class="form-select" name="relationship" required>
                    <option value="">Select Relationship</option>
                    <option value="1 - Puno ng Pamilya" <?= $family['relationship'] == '1 - Puno ng Pamilya' ? 'selected' : '' ?>>1 - Puno ng Pamilya</option>
                    <option value="2 - Asawa" <?= $family['relationship'] == '2 - Asawa' ? 'selected' : '' ?>>2 - Asawa</option>
                    <option value="3 - Anak" <?= $family['relationship'] == '3 - Anak' ? 'selected' : '' ?>>3 - Anak</option>
                    <option value="4 - Kapatid" <?= $family['relationship'] == '4 - Kapatid' ? 'selected' : '' ?>>4 - Kapatid</option>
                    <option value="5 - Bayaw o Hipag" <?= $family['relationship'] == '5 - Bayaw o Hipag' ? 'selected' : '' ?>>5 - Bayaw o Hipag</option>
                    <option value="6 - Apo" <?= $family['relationship'] == '6 - Apo' ? 'selected' : '' ?>>6 - Apo</option>
                    <option value="7 - Ama / Ina" <?= $family['relationship'] == '7 - Ama / Ina' ? 'selected' : '' ?>>7 - Ama / Ina</option>
                    <option value="8 - Iba pang Kamag-anak" <?= $family['relationship'] == '8 - Iba pang Kamag-anak' ? 'selected' : '' ?>>8 - Iba pang Kamag-anak</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Birthday</label>
                <input type="date" class="form-control" name="birthday" value="<?= htmlspecialchars($family['birthday']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Age</label>
                <input type="number" class="form-control" name="age" value="<?= htmlspecialchars($family['age']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Sex</label>
                <select class="form-select" name="sex" required>
                    <option value="Male" <?= $family['sex'] == 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= $family['sex'] == 'Female' ? 'selected' : '' ?>>Female</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Civil Status</label>
                <select class="form-select" name="civil_status" required>
                    <option value="Single" <?= $family['civil_status'] == 'Single' ? 'selected' : '' ?>>Single</option>
                    <option value="Married" <?= $family['civil_status'] == 'Married' ? 'selected' : '' ?>>Married</option>
                    <option value="Widow" <?= $family['civil_status'] == 'Widow' ? 'selected' : '' ?>>Widow</option>
                    <option value="Widower" <?= $family['civil_status'] == 'Widower' ? 'selected' : '' ?>>Widower</option>
                </select>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Update</button>
    </div>
</form>