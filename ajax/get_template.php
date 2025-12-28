<?php
require_once '../config/database.php';
require_once '../app/core/Auth.php';

if(!Auth::isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$template_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare('SELECT subject, content, variables FROM email_templates WHERE id = ? AND is_active = 1');
$stmt->execute([$template_id]);
$template = $stmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
if($template) {
    $variables = [];
    if (!empty($template['variables'])) {
        $vars = json_decode($template['variables'], true);
        if (is_array($vars)) { $variables = $vars; }
    }
    echo json_encode([
        'subject' => $template['subject'],
        'content' => $template['content'],
        'variables' => $variables
    ]);
} else {
    echo json_encode(['error' => 'Template not found']);
}
?>
