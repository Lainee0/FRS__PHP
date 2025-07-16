<?php
require_once 'config.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$barangay_filter = isset($_GET['barangay']) ? trim($_GET['barangay']) : '';

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(last_name LIKE :search OR middle_name LIKE :search OR first_name LIKE :search OR household_number LIKE :search OR barangay LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($barangay_filter)) {
    $where[] = "barangay = :barangay";
    $params[':barangay'] = $barangay_filter;
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Data query - Showing all members
$sql = "SELECT * FROM families $where_clause ORDER BY barangay, household_number, is_head DESC, last_name LIMIT 100";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$families = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($families as $family): ?>
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
    <!-- <td><?= $family['is_leader'] ? 'Yes' : 'No' ?></td>
    <td><?= $family['is_head'] ? 'Yes' : 'No' ?></td> -->
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
<?php endforeach;