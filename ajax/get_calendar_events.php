<?php
require_once '../config/database.php';
require_once '../app/core/Auth.php';

header('Content-Type: application/json');

if(!Auth::isLoggedIn()) {
    echo json_encode([]);
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// FullCalendar usually sends start/end ISO dates
$start = isset($_GET['start']) ? $_GET['start'] : null;
$end = isset($_GET['end']) ? $_GET['end'] : null;

$where = [];
$params = [];

$isAdmin = Auth::hasRole('admin') || Auth::hasRole('sales_manager');
if(!$isAdmin) {
    $where[] = 'a.assigned_to = ?';
    $params[] = Auth::getUserID();
}

$where[] = "a.status = 'scheduled'";
if ($start) { $where[] = 'a.activity_date >= ?'; $params[] = $start . ' 00:00:00'; }
if ($end) { $where[] = 'a.activity_date <= ?'; $params[] = $end . ' 23:59:59'; }

$sql = 'SELECT a.* FROM activities a';
if(!empty($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY a.activity_date ASC';

$stmt = $conn->prepare($sql);
foreach($params as $i => $p) { $stmt->bindValue($i+1, $p); }
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

function typeColor($t) {
    switch($t) {
        case 'call': return '#0d6efd'; // blue
        case 'email': return '#6c757d'; // gray
        case 'meeting': return '#198754'; // green
        case 'demo': return '#6610f2'; // purple
        case 'proposal': return '#fd7e14'; // orange
        case 'followup': return '#dc3545'; // red
        default: return '#20c997'; // teal
    }
}

$events = [];
foreach($rows as $a) {
    $events[] = [
        'id' => (int)$a['id'],
        'title' => $a['subject'],
        'start' => $a['activity_date'],
        'end' => null,
        'color' => typeColor($a['activity_type']),
        'extendedProps' => [
            'type' => $a['activity_type'],
            'related_to' => $a['related_to'],
            'related_id' => $a['related_id'],
        ]
    ];
}

echo json_encode($events);
?>
