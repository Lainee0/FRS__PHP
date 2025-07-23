<?php
session_start();
require_once 'config.php';

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$barangay_filter = isset($_GET['barangay']) ? trim($_GET['barangay']) : '';

// Build the query
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(last_name LIKE :search OR first_name LIKE :search OR remarks LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($barangay_filter)) {
    $where[] = "barangay = :barangay";
    $params[':barangay'] = $barangay_filter;
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get data (limit to 100 results for performance)
$sql = "SELECT * FROM families $where_clause ORDER BY barangay, household_number, is_head DESC, last_name LIMIT 100";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$families = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate and return table rows
foreach ($families as $family) {
    echo '<tr>
        <td>'.htmlspecialchars($family['household_number']).'</td>
        <td>'.htmlspecialchars($family['barangay']).'</td>
        <td>'.htmlspecialchars($family['last_name']).'</td>
        <td>'.htmlspecialchars($family['first_name']).'</td>
        <td>'.htmlspecialchars($family['middle_name']).'</td>
        <td>'.htmlspecialchars($family['relationship']).'</td>
        <td>'.htmlspecialchars($family['age']).'</td>
        <td>'.htmlspecialchars($family['sex']).'</td>
        <td>'.htmlspecialchars($family['civil_status']).'</td>
        <td class="remarks-cell">';
    
    if (!empty($family['remarks'])) {
        $remarks = array_filter(explode("\n", $family['remarks']), function($r) {
            return trim($r) !== '';
        });
        if (!empty($remarks)) {
            echo '<ul style="margin-bottom: 0; padding-left: 1rem;">';
            foreach ($remarks as $remark) {
                echo '<li>'.htmlspecialchars(trim($remark)).'</li>';
            }
            echo '</ul>';
        } else {
            echo 'No remarks';
        }
    } else {
        echo 'No remarks';
    }
    
    echo '</td>
        <td>
            <div class="btn-group btn-group-sm" role="group">
                <a href="view_household.php?household_number='.$family['household_number'].'" class="btn btn-info" title="View Household">
                    <i class="bi bi-house-door"></i>
                </a>
                <a href="#" class="btn btn-warning edit-member" title="Edit" data-id="'.$family['id'].'">
                    <i class="bi bi-pencil"></i>
                </a>
                <a href="delete.php?id='.$family['id'].'" class="btn btn-danger" title="Delete" onclick="return confirm(\'Are you sure?\')">
                    <i class="bi bi-trash"></i>
                </a>
            </div>
        </td>
    </tr>';
}

// If no results found
if (empty($families)) {
    echo '<tr><td colspan="12" class="text-center">No matching records found</td></tr>';
}
?>