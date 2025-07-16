<?php
require_once 'config.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM families WHERE id = ?");
$stmt->execute([$id]);
$family = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$family) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id' => $id,
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
        'is_leader' => isset($_POST['is_leader']) ? 1 : 0
    ];

    $sql = "UPDATE families SET sn = :sn, row_indicator = :row_indicator, last_name = :last_name, 
            first_name = :first_name, middle_name = :middle_name, ext = :ext, relationship = :relationship, 
            birthday = :birthday, age = :age, sex = :sex, civil_status = :civil_status, 
            household_number = :household_number, barangay = :barangay, is_leader = :is_leader 
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Family Member</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Edit Family Member</h2>
        <form method="POST">
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
                            <option value="Dead" <?= $family['row_indicator'] == 'Dead' ? 'selected' : '' ?>>Dead</option>
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
                                <option value="<?= $barangay ?>" <?= (isset($family) && $family['barangay'] == $barangay) ? 'selected' : '' ?>>
                                    <?= $barangay ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Relationship</label>
                        <input type="text" class="form-control" name="relationship" value="<?= htmlspecialchars($family['relationship']) ?>" required>
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
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="is_leader" id="is_leader" <?= $family['is_leader'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_leader">Leader</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>