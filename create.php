<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
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

    $sql = "INSERT INTO families (sn, row_indicator, last_name, first_name, middle_name, ext, relationship, 
            birthday, age, sex, civil_status, household_number, barangay, is_leader) 
            VALUES (:sn, :row_indicator, :last_name, :first_name, :middle_name, :ext, :relationship, 
            :birthday, :age, :sex, :civil_status, :household_number, :barangay, :is_leader)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    
    header("Location: index.php");
    exit;
}

// Get distinct barangays for dropdown
$stmt = $pdo->query("SELECT DISTINCT barangay FROM families ORDER BY barangay");
$barangays = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Family Member</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Add New Family Member</h2>
        <form method="POST">
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
                            <option value="Dead">Dead</option>
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
            <button type="submit" class="btn btn-primary">Submit</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>