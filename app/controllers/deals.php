<?php
// deals.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../models/Deal.php';

Auth::requireLogin();

$db = new Database();
$conn = $db->getConnection();
$dealModel = new Deal($conn);

// Get deals by funnel
$isAdmin = Auth::hasRole('admin') || Auth::hasRole('sales_manager');
$ownerFilter = $isAdmin ? null : Auth::getUserID();
$yellowDeals = $dealModel->getDealsByFunnel('yellow', $ownerFilter);
$pinkDeals = $dealModel->getDealsByFunnel('pink', $ownerFilter);
$greenDeals = $dealModel->getDealsByFunnel('green', $ownerFilter);
$blueDeals = $dealModel->getDealsByFunnel('blue', $ownerFilter);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Funnel - Technical CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .funnel-column {
            min-height: 600px;
            border-radius: 10px;
            padding: 15px;
        }
        .yellow-funnel { background-color: #fff3cd; border: 1px solid #ffeaa7; }
        .pink-funnel { background-color: #f8d7da; border: 1px solid #fab1a0; }
        .green-funnel { background-color: #d1e7dd; border: 1px solid #a3e4d7; }
        .blue-funnel { background-color: #cfe2ff; border: 1px solid #74b9ff; }
        .deal-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            cursor: move;
        }
        .deal-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .deal-value {
            font-weight: bold;
            color: #2d3436;
        }
        .probability-bar {
            height: 5px;
            background: #dfe6e9;
            border-radius: 3px;
            margin-top: 5px;
            overflow: hidden;
        }
        .probability-fill {
            height: 100%;
            background: #00b894;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../views/sidebar.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Sales Funnel</h2>
            <a href="/add_deal" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Deal
            </a>
        </div>
        
        <!-- Funnel Legend -->
        <div class="row mb-4">
            <div class="col-3 text-center">
                <div class="p-3 bg-warning rounded">
                    <h5>Closable This Month</h5>
                    <small>High probability, closing soon</small>
                </div>
            </div>
            <div class="col-3 text-center">
                <div class="p-3 bg-danger text-white rounded">
                    <h5>Newly Quoted</h5>
                    <small>Recently sent quotes</small>
                </div>
            </div>
            <div class="col-3 text-center">
                <div class="p-3 bg-success text-white rounded">
                    <h5>Project Based</h5>
                    <small>Long-term projects</small>
                </div>
            </div>
            <div class="col-3 text-center">
                <div class="p-3 bg-primary text-white rounded">
                    <h5>Services Offered</h5>
                    <small>Service agreements</small>
                </div>
            </div>
        </div>
        
        <!-- Funnel Columns -->
        <div class="row">
            <!-- Yellow Funnel -->
            <div class="col-md-3">
                <div class="funnel-column yellow-funnel">
                    <h4 class="text-center mb-3">
                        <span class="badge bg-warning"><?php echo count($yellowDeals); ?></span><br>
                        Closable This Month
                    </h4>
                    <?php foreach($yellowDeals as $deal): ?>
                        <div class="deal-card" data-id="<?php echo $deal['id']; ?>">
                            <div class="d-flex justify-content-between">
                                <h6><?php echo $deal['deal_name']; ?></h6>
                                <span class="deal-value"><?php echo Helpers::formatCurrency($deal['deal_value']); ?></span>
                            </div>
                            <p class="mb-1 small"><?php echo $deal['company_name']; ?></p>
                            <p class="mb-1 small text-muted">Close: <?php echo Helpers::formatDate($deal['expected_close']); ?></p>
                            <div class="probability-bar">
                                <div class="probability-fill" style="width: <?php echo $deal['probability']; ?>%"></div>
                            </div>
                            <div class="mt-2">
                                <a href="deal_detail.php?id=<?php echo $deal['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <span class="badge bg-info"><?php echo $deal['probability']; ?>%</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Pink Funnel -->
            <div class="col-md-3">
                <div class="funnel-column pink-funnel">
                    <h4 class="text-center mb-3">
                        <span class="badge bg-danger"><?php echo count($pinkDeals); ?></span><br>
                        Newly Quoted
                    </h4>
                    <?php foreach($pinkDeals as $deal): ?>
                        <div class="deal-card" data-id="<?php echo $deal['id']; ?>">
                            <div class="d-flex justify-content-between">
                                <h6><?php echo $deal['deal_name']; ?></h6>
                                <span class="deal-value"><?php echo Helpers::formatCurrency($deal['deal_value']); ?></span>
                            </div>
                            <p class="mb-1 small"><?php echo $deal['company_name']; ?></p>
                            <p class="mb-1 small text-muted">Quoted: <?php echo Helpers::formatDate($deal['quote_date']); ?></p>
                            <div class="probability-bar">
                                <div class="probability-fill" style="width: <?php echo $deal['probability']; ?>%"></div>
                            </div>
                            <div class="mt-2">
                                <a href="deal_detail.php?id=<?php echo $deal['id']; ?>" class="btn btn-sm btn-danger">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <span class="badge bg-info"><?php echo $deal['probability']; ?>%</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Green Funnel -->
            <div class="col-md-3">
                <div class="funnel-column green-funnel">
                    <h4 class="text-center mb-3">
                        <span class="badge bg-success"><?php echo count($greenDeals); ?></span><br>
                        Project Based
                    </h4>
                    <?php foreach($greenDeals as $deal): ?>
                        <div class="deal-card" data-id="<?php echo $deal['id']; ?>">
                            <div class="d-flex justify-content-between">
                                <h6><?php echo $deal['deal_name']; ?></h6>
                                <span class="deal-value"><?php echo Helpers::formatCurrency($deal['deal_value']); ?></span>
                            </div>
                            <p class="mb-1 small"><?php echo $deal['company_name']; ?></p>
                            <p class="mb-1 small text-muted">Type: <?php echo $deal['deal_type']; ?></p>
                            <div class="probability-bar">
                                <div class="probability-fill" style="width: <?php echo $deal['probability']; ?>%"></div>
                            </div>
                            <div class="mt-2">
                                <a href="deal_detail.php?id=<?php echo $deal['id']; ?>" class="btn btn-sm btn-success">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <span class="badge bg-info"><?php echo $deal['probability']; ?>%</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Blue Funnel -->
            <div class="col-md-3">
                <div class="funnel-column blue-funnel">
                    <h4 class="text-center mb-3">
                        <span class="badge bg-primary"><?php echo count($blueDeals); ?></span><br>
                        Services Offered
                    </h4>
                    <?php foreach($blueDeals as $deal): ?>
                        <div class="deal-card" data-id="<?php echo $deal['id']; ?>">
                            <div class="d-flex justify-content-between">
                                <h6><?php echo $deal['deal_name']; ?></h6>
                                <span class="deal-value"><?php echo Helpers::formatCurrency($deal['deal_value']); ?></span>
                            </div>
                            <p class="mb-1 small"><?php echo $deal['company_name']; ?></p>
                            <p class="mb-1 small text-muted">Type: <?php echo $deal['deal_type']; ?></p>
                            <div class="probability-bar">
                                <div class="probability-fill" style="width: <?php echo $deal['probability']; ?>%"></div>
                            </div>
                            <div class="mt-2">
                                <a href="deal_detail.php?id=<?php echo $deal['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <span class="badge bg-info"><?php echo $deal['probability']; ?>%</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../views/footer.php'; ?>
    
    <!-- Drag and Drop Functionality -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
    <script>
        // Make each funnel column sortable
        const funnelColumns = document.querySelectorAll('.funnel-column');
        
        funnelColumns.forEach(column => {
            new Sortable(column, {
                group: 'funnel',
                animation: 150,
                onEnd: function(evt) {
                    const dealId = evt.item.getAttribute('data-id');
                    const newFunnel = evt.to.parentElement.classList[1].split('-')[0]; // Get color from class
                    
                    // Send AJAX request to update funnel
                    fetch('update_funnel.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            deal_id: dealId,
                            funnel_category: newFunnel
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            showNotification('Deal moved to ' + newFunnel + ' funnel', 'success');
                        }
                    });
                }
            });
        });
        
        function showNotification(message, type) {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alert.style.top = '20px';
            alert.style.right = '20px';
            alert.style.zIndex = '9999';
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 3000);
        }
    </script>
</body>
</html>