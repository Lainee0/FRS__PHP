<?php
require_once 'config.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$barangay_filter = isset($_GET['barangay']) ? trim($_GET['barangay']) : '';

$where = [];
$params = [];

if (!empty($search)) {
    // Split search term into parts
    $searchParts = preg_split('/\s+/', $search);
    
    // Build conditions for each searchable column
    $searchColumns = [
        'household_number',
        'barangay', 
        'last_name',
        'first_name',
        'middle_name',
        'relationship',
        'remarks'
    ];
    
    $conditions = [];
    foreach ($searchParts as $i => $part) {
        $columnConditions = [];
        foreach ($searchColumns as $col) {
            $paramName = ":search_{$col}_$i";
            $columnConditions[] = "$col LIKE $paramName";
            $params[$paramName] = "%$part%";
        }
        $conditions[] = "(" . implode(' OR ', $columnConditions) . ")";
    }
    
    $where[] = "(" . implode(' AND ', $conditions) . ")";
}

if (!empty($barangay_filter)) {
    $where[] = "barangay = :barangay";
    $params[':barangay'] = $barangay_filter;
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Data query - Showing all members with search
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
    <td class="remarks-cell">
        <?php if (!empty($family['remarks'])): ?>
            <?php 
            // Split remarks by newline and filter out empty lines
            $remarks = array_filter(explode("\n", $family['remarks']), function($r) {
                return trim($r) !== '';
            });
            ?>
            <?php if (!empty($remarks)): ?>
                <ul style="margin-bottom: 0; padding-left: 1rem;">
                    <?php foreach ($remarks as $remark): ?>
                        <li><?= htmlspecialchars(trim($remark)) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                No remarks
            <?php endif; ?>
        <?php else: ?>
            No remarks
        <?php endif; ?>
    </td>
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