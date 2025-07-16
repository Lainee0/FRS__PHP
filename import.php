<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    require_once 'vendor/autoload.php';
    
    $file = $_FILES['excel_file']['tmp_name'];
    $filename = $_FILES['excel_file']['name'];
    $barangay = pathinfo($filename, PATHINFO_FILENAME);
    
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // Remove header row
        array_shift($rows);
        
        $pdo->beginTransaction();
        
        // Track current household and head ID
        $current_household = null;
        $current_head_id = null;
        
        foreach ($rows as $row) {
            if (empty($row[0])) continue;
            
            // Determine if this is a family head (Row Indicator = "Head")
            $is_head = (trim($row[1]) === 'Head');
            $household_number = $row[11] ?? null;
            
            // If this is a new household or we found a head, update tracking
            if ($household_number !== $current_household || $is_head) {
                $current_household = $household_number;
                $current_head_id = null; // Reset until we find the head
            }
            
            // Prepare data for insertion
            $data = [
                'sn' => $row[0] ?? null,
                'row_indicator' => $row[1] ?? null,
                'last_name' => $row[2] ?? null,
                'first_name' => $row[3] ?? null,
                'middle_name' => $row[4] ?? null,
                'ext' => $row[5] ?? null,
                'relationship' => $row[6] ?? null,
                'birthday' => $row[7] ? date('Y-m-d', strtotime($row[7])) : null,
                'age' => $row[8] ?? null,
                'sex' => $row[9] ?? null,
                'civil_status' => $row[10] ?? null,
                'household_number' => $household_number,
                'barangay' => $barangay,
                'is_head' => $is_head ? 1 : 0,
                'head_id' => $is_head ? null : $current_head_id,
                'is_leader' => !empty($row[12]) ? 1 : 0
            ];
            
            $sql = "INSERT INTO families (
                sn, row_indicator, last_name, first_name, middle_name, ext, relationship, 
                birthday, age, sex, civil_status, household_number, barangay, 
                is_head, head_id, is_leader
            ) VALUES (
                :sn, :row_indicator, :last_name, :first_name, :middle_name, :ext, :relationship, 
                :birthday, :age, :sex, :civil_status, :household_number, :barangay, 
                :is_head, :head_id, :is_leader
            )";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            
            // If this is the head, store the ID for family members
            if ($is_head) {
                $current_head_id = $pdo->lastInsertId();
                
                // Update the head's own head_id to point to themselves
                $pdo->prepare("UPDATE families SET head_id = ? WHERE id = ?")
                    ->execute([$current_head_id, $current_head_id]);
            }
        }
        
        $pdo->commit();
        $_SESSION['success_message'] = "Excel file imported successfully! Barangay: $barangay. Household relationships established.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Error importing file: " . $e->getMessage();
    }
    
    // Redirect back to index.php after import
    header('Location: index.php');
    exit;
}

// If not a POST request, show the standalone page (for backward compatibility)
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Import Excel File</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .instructions { background-color: #f8f9fa; padding: 15px; border-radius: 5px; }
        .format-table { width: 100%; border-collapse: collapse; }
        .format-table th, .format-table td { border: 1px solid #dee2e6; padding: 8px; }
        .format-table th { background-color: #e9ecef; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2 class="mb-4">Import Family Data</h2>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php elseif (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5>Import Instructions</h5>
            </div>
            <div class="card-body">
                <div class="instructions mb-3">
                    <strong>Important:</strong>
                    <ul>
                        <li>File name must match the barangay name (e.g., "Candulao.xlsx")</li>
                        <li>The <strong>Row Indicator</strong> column must contain "Head" for family heads</li>
                        <li>All members of a household must have the same Household Number</li>
                    </ul>
                </div>
                
                <form method="POST" enctype="multipart/form-data" class="mb-4">
                    <div class="mb-3">
                        <label for="excel_file" class="form-label">Select Excel File</label>
                        <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx, .xls" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Import Data</button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>