<?php
// email.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';

Auth::requireLogin();

$db = new Database();
$conn = $db->getConnection();

// Get email templates
$query = "SELECT * FROM email_templates WHERE is_active = 1 ORDER BY category, template_name";
$stmt = $conn->prepare($query);
$stmt->execute();
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle template upload
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_template'])) {
    $template_name = Helpers::escape($_POST['template_name']);
    $template_slug = strtolower(str_replace(' ', '-', $template_name));
    $subject = Helpers::escape($_POST['subject']);
    $content = $_POST['content'];
    $category = Helpers::escape($_POST['category']);
    $variables = json_encode(['customer_name', 'deal_name', 'sales_rep_name']);
    
    $query = "INSERT INTO email_templates 
              (template_name, template_slug, subject, content, category, variables, created_by)
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(1, $template_name);
    $stmt->bindParam(2, $template_slug);
    $stmt->bindParam(3, $subject);
    $stmt->bindParam(4, $content);
    $stmt->bindParam(5, $category);
    $stmt->bindParam(6, $variables);
    $stmt->bindParam(7, $_SESSION['user_id']);
    
    if($stmt->execute()) {
        header("Location: email.php?msg=added");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Templates - Technical CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../views/sidebar.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Email Templates</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTemplateModal">
                <i class="fas fa-plus"></i> New Template
            </button>
        </div>
        
        <!-- Template Categories -->
        <div class="row mb-4">
            <div class="col-12">
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#all">All Templates</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#proposal">Proposals</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#followup">Follow-ups</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#demo">Demos</a>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Templates Grid -->
        <div class="tab-content">
            <div class="tab-pane fade show active" id="all">
                <div class="row">
                    <?php foreach($templates as $template): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5><?php echo $template['template_name']; ?></h5>
                                    <span class="badge bg-<?php 
                                        switch($template['category']) {
                                            case 'proposal': echo 'success'; break;
                                            case 'followup': echo 'warning'; break;
                                            case 'demo': echo 'info'; break;
                                            default: echo 'secondary';
                                        }
                                    ?>">
                                        <?php echo ucfirst($template['category']); ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <p><strong>Subject:</strong> <?php echo $template['subject']; ?></p>
                                    <div class="template-preview mb-3">
                                        <?php echo substr(strip_tags($template['content']), 0, 150); ?>...
                                    </div>
                                    <div class="variables">
                                        <small class="text-muted">
                                            <strong>Variables:</strong> 
                                            <?php 
                                                $vars = json_decode($template['variables'], true);
                                                if(is_array($vars)) {
                                                    echo implode(', ', $vars);
                                                }
                                            ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button class="btn btn-sm btn-primary use-template" 
                                            data-id="<?php echo $template['id']; ?>"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#sendEmailModal">
                                        <i class="fas fa-envelope"></i> Use
                                    </button>
                                    <button class="btn btn-sm btn-secondary edit-template"
                                            data-id="<?php echo $template['id']; ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-template"
                                            data-id="<?php echo $template['id']; ?>">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Category Tabs -->
            <?php 
            $categories = ['proposal', 'followup', 'demo', 'quote', 'meeting'];
            foreach($categories as $cat):
                $catTemplates = array_filter($templates, function($t) use ($cat) {
                    return $t['category'] == $cat;
                });
            ?>
                <div class="tab-pane fade" id="<?php echo $cat; ?>">
                    <div class="row">
                        <?php foreach($catTemplates as $template): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5><?php echo $template['template_name']; ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <p><?php echo substr(strip_tags($template['content']), 0, 200); ?>...</p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- New Template Modal -->
    <div class="modal fade" id="newTemplateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Email Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Template Name</label>
                            <input type="text" class="form-control" name="template_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category" required>
                                <option value="proposal">Proposal</option>
                                <option value="followup">Follow-up</option>
                                <option value="demo">Demo Invitation</option>
                                <option value="quote">Quote Follow-up</option>
                                <option value="meeting">Meeting Request</option>
                                <option value="general">General</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" class="form-control" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Content</label>
                            <textarea id="summernote" name="content" class="form-control" rows="10"></textarea>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">
                                <strong>Available Variables:</strong> {customer_name}, {company_name}, {deal_name}, 
                                {contact_person}, {sales_rep_name}, {demo_date}, {demo_time}
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="upload_template" class="btn btn-primary">Save Template</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Send Email Modal -->
    <div class="modal fade" id="sendEmailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="/email" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Select Customer</label>
                            <select class="form-select" id="customerSelect" name="customer_id" required>
                                <option value="">Select Customer</option>
                                <!-- Will be populated by AJAX -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Template</label>
                            <select class="form-select" id="templateSelect" name="template_id" required>
                                <option value="">Select Template</option>
                                <?php foreach($templates as $template): ?>
                                    <option value="<?php echo $template['id']; ?>">
                                        <?php echo $template['template_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Preview</label>
                            <div id="emailPreview" class="border p-3" style="min-height: 200px;">
                                Select a template to preview...
                            </div>
                        </div>
                        <div id="variableFields">
                            <!-- Dynamic variable fields will appear here -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Email</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../views/footer.php'; ?>
    
    <!-- Summernote WYSIWYG Editor -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Summernote
            $('#summernote').summernote({
                height: 200,
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['font', ['strikethrough', 'superscript', 'subscript']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ]
            });
            
            // Load customers for email modal
            $.get('/ajax/get_customers.php', function(data) {
                $('#customerSelect').html(data);
            });
            
            // Preview template when selected
            $('#templateSelect').change(function() {
                const templateId = $(this).val();
                if(templateId) {
                    $.get('/ajax/get_template.php?id=' + templateId, function(data) {
                        $('#emailPreview').html(data.content);
                        showVariableFields(data.variables);
                    }, 'json');
                }
            });
            
            function showVariableFields(variables) {
                $('#variableFields').empty();
                if(variables && variables.length > 0) {
                    let html = '<h6>Fill Variables:</h6>';
                    variables.forEach(function(variable) {
                        html += `
                            <div class="mb-2">
                                <label class="form-label">${variable.replace(/_/g, ' ').toUpperCase()}</label>
                                <input type="text" class="form-control variable-input" 
                                       data-variable="${variable}" 
                                       placeholder="Enter value for ${variable}">
                            </div>
                        `;
                    });
                    $('#variableFields').html(html);
                    
                    // Update preview when variables are filled
                    $('.variable-input').on('input', function() {
                        updatePreview();
                    });
                }
            }
            
            function updatePreview() {
                let preview = $('#emailPreview').html();
                $('.variable-input').each(function() {
                    const variable = $(this).data('variable');
                    const value = $(this).val();
                    const regex = new RegExp(`{${variable}}`, 'g');
                    preview = preview.replace(regex, value);
                });
                $('#emailPreview').html(preview);
            }
        });
    </script>
</body>
</html>
