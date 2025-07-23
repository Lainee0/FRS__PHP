<?php
session_start();
require_once 'config.php';

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$barangay_filter = isset($_GET['barangay']) ? trim($_GET['barangay']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

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

// Get total records
$count_sql = "SELECT COUNT(*) FROM families $where_clause";
$stmt = $pdo->prepare($count_sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Get data
$sql = "SELECT * FROM families $where_clause ORDER BY barangay, household_number, is_head DESC, last_name LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$families = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate table body
$table_body = '';
foreach ($families as $family) {
    $table_body .= '<tr>
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
            $table_body .= '<ul style="margin-bottom: 0; padding-left: 1rem;">';
            foreach ($remarks as $remark) {
                $table_body .= '<li>'.htmlspecialchars(trim($remark)).'</li>';
            }
            $table_body .= '</ul>';
        } else {
            $table_body .= 'No remarks';
        }
    } else {
        $table_body .= 'No remarks';
    }
    
    $table_body .= '</td>
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

// Generate pagination
$pagination = '<ul class="pagination justify-content-center">';
// Previous Button
$pagination .= '<li class="page-item '.($page <= 1 ? 'disabled' : '').'">
    <a class="page-link" href="#" data-page="'.($page - 1).'">Previous</a>
</li>';

// Page Numbers
$max_visible_pages = 20;
$start_page = max(1, $page - floor($max_visible_pages/2));
$end_page = min($total_pages, $start_page + $max_visible_pages - 1);

// Adjust if we're at the beginning
if ($end_page - $start_page + 1 < $max_visible_pages) {
    $start_page = max(1, $end_page - $max_visible_pages + 1);
}

for ($i = $start_page; $i <= $end_page; $i++) {
    $pagination .= '<li class="page-item '.($i == $page ? 'active' : '').'">
        <a class="page-link" href="#" data-page="'.$i.'">'.$i.'</a>
    </li>';
}

// Next Button
$pagination .= '<li class="page-item '.($page >= $total_pages ? 'disabled' : '').'">
    <a class="page-link" href="#" data-page="'.($page + 1).'">Next</a>
</li>';
$pagination .= '</ul>';

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'table_body' => $table_body,
    'pagination' => $pagination
]);
?>