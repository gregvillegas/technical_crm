<?php
// ajax/get_template.php
require_once '../config/database.php';
require_once '../app/core/Auth.php';

if(!Auth::isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$template_id = $_GET['id'] ?? 0;

$query = "SELECT * FROM email_templates WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bindParam(1, $template_id);
$stmt->execute();
$template = $stmt->fetch(PDO::FETCH_ASSOC);

if($template) {
    $variables = json_decode($template['variables'], true) ?: [];
    echo json_encode([
        'content' => $template['content'],
        'variables' => $variables,
        'subject' => $template['subject']
    ]);
} else {
    echo json_encode(['error' => 'Template not found']);
}
?>