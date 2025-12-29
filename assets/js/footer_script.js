       
// Ensure Bootstrap dropdowns initialize
            document.addEventListener('DOMContentLoaded', function() {
                var dropdownToggleList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
                dropdownToggleList.forEach(function (dropdownToggleEl) {
                    try { new bootstrap.Dropdown(dropdownToggleEl); } catch (e) {}
                });

// Fallback: ensure toggle works via click even if data attributes fail
                document.querySelectorAll('.dropdown-toggle').forEach(function(btn){
                    btn.addEventListener('click', function(ev){
                        ev.preventDefault();
                        try {
                            var dd = bootstrap.Dropdown.getOrCreateInstance(btn);
                            dd.toggle();
                        } catch (e) {
                            var parent = btn.closest('.dropdown');
                            var menu = parent ? parent.querySelector('.dropdown-menu') : null;
                            if (menu) {
                                var isShown = menu.classList.contains('show');
                                menu.classList.toggle('show', !isShown);
                                btn.setAttribute('aria-expanded', String(!isShown));
                            }
                        }
                    });
                });
                document.addEventListener('click', function(e){
                    document.querySelectorAll('.dropdown-menu.show').forEach(function(menu){
                        var toggle = menu.previousElementSibling;
                        var isToggle = toggle && toggle.classList && toggle.classList.contains('dropdown-toggle');
                        if (!menu.contains(e.target) && !isToggle) {
                            menu.classList.remove('show');
                        }
                    });
                });
            });

// Auto-dismiss alerts
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);

            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Real-time clock
            function updateClock() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: true 
                });
                $('.clock').text(timeString);
            }
            
            // Add clock to footer if needed
            $(document).ready(function() {
                // Add clock element
                $('.footer .text-muted:first').after('<span class="text-muted ms-3"><i class="fas fa-clock"></i> <span class="clock"></span></span>');
                updateClock();
                setInterval(updateClock, 1000);
                
                // Check for pending follow-ups
                checkPendingFollowups();
            });

            // Check for pending follow-ups and show notification
            function checkPendingFollowups() {
                $.ajax({
                    url: '/ajax/check_followups.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if(response && response.count > 0) {
                            showNotification('You have ' + response.count + ' pending follow-ups today!', 'warning');
                        }
                    }
                });
            }

            // Show notification function
            function showNotification(message, type = 'info') {
                // Create notification element
                const notification = $(`
                    <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999">
                        <div class="toast show" role="alert">
                            <div class="toast-header bg-${type} text-white">
                                <strong class="me-auto">Notification</strong>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                            </div>
                            <div class="toast-body">
                                ${message}
                            </div>
                        </div>
                    </div>
                `);
                
                // Add to body and auto-remove after 5 seconds
                $('body').append(notification);
                setTimeout(() => {
                    notification.remove();
                }, 5000);
            }

            // Handle form submissions with loading state
            $('form').submit(function() {
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
                
                // Re-enable button after 10 seconds (in case of error)
                setTimeout(() => {
                    submitBtn.prop('disabled', false).html(originalText);
                }, 10000);
            });

            // Auto-save forms
            let autoSaveTimer;
            $('form input, form textarea, form select').on('input change', function() {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(() => {
                    const form = $(this).closest('form');
                    if(form.attr('id') === 'autoSaveForm') {
                        saveFormData(form);
                    }
                }, 2000);
            });

            function saveFormData(form) {
                const formData = form.serialize();
                $.post('ajax/auto_save.php', formData, function(response) {
                    if(response.success) {
                        showNotification('Form auto-saved successfully!', 'success');
                    }
                });
            }

            // Handle browser back/forward buttons
            window.addEventListener('popstate', function(event) {
                location.reload();
            });

            // Add confirmation for delete actions
            $('a[href*="delete"], button[class*="delete"]').click(function(e) {
                if(!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                    e.preventDefault();
                }
            });

            // Add confirmation for logout
            $('a[href="/logout"]').click(function(e) {
                if(!confirm('Are you sure you want to logout?')) {
                    e.preventDefault();
                }
            });

            // Handle offline/online status
            window.addEventListener('online', function() {
                showNotification('You are back online!', 'success');
                // Sync any pending data
                syncPendingData();
            });

            window.addEventListener('offline', function() {
                showNotification('You are offline. Some features may be limited.', 'warning');
            });

            function syncPendingData() {
                // Check localStorage for pending data
                const pendingData = localStorage.getItem('pendingData');
                if(pendingData) {
                    $.ajax({
                        url: 'ajax/sync_data.php',
                        method: 'POST',
                        data: { data: pendingData },
                        success: function() {
                            localStorage.removeItem('pendingData');
                            showNotification('Data synced successfully!', 'success');
                        }
                    });
                }
            }

            // Keyboard shortcuts
            $(document).keydown(function(e) {
                // Ctrl+S for save
                if(e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    $('form').submit();
                }
                // Ctrl+F for search
                if(e.ctrlKey && e.key === 'f') {
                    e.preventDefault();
                    $('input[type="search"]').focus();
                }
                // Ctrl+N for new
                if(e.ctrlKey && e.key === 'n') {
                    e.preventDefault();
                    window.location.href = '/add_customer';
                }
                // Ctrl+E for email
                if(e.ctrlKey && e.key === 'e') {
                    e.preventDefault();
                    window.location.href = 'email.php';
                }
                // Ctrl+D for dashboard
                if(e.ctrlKey && e.key === 'd') {
                    e.preventDefault();
                    window.location.href = 'dashboard.php';
                }
                // Esc to close modals
                if(e.key === 'Escape') {
                    $('.modal').modal('hide');
                }
            });

            // Print functionality
            function printPage() {
                window.print();
            }

            // Export functionality
            function exportToCSV(tableId, filename) {
                const table = document.getElementById(tableId);
                const rows = table.querySelectorAll('tr');
                const csv = [];
                
                for (let i = 0; i < rows.length; i++) {
                    const row = [], cols = rows[i].querySelectorAll('td, th');
                    
                    for (let j = 0; j < cols.length; j++) {
                        row.push(cols[j].innerText);
                    }
                    
                    csv.push(row.join(','));
                }

            // Load notifications in dropdown
            function loadNotificationDropdown() {
                $.ajax({
                    url: '/ajax/get_recent_notifications.php',
                    method: 'GET',
                    success: function(data) {
                        $('#notificationDropdownContent').html(data);
                        updateNotificationBadge();
                    }
                 });
            }

            // Update notification badge count
            function updateNotificationBadge() {
                $.ajax({
                    url: '/ajax/get_notification_counts.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        const badge = $('#notificationBadge');
                        if(data.total > 0) {
                             badge.text(data.total).show();
                        } else {
                            badge.hide();
                        }
                    }
                });
            }

            // Load notifications on page load
            $(document).ready(function() {
                loadNotificationDropdown();
                updateNotificationBadge();
    
                // Auto-refresh notifications every 30 seconds
                setInterval(loadNotificationDropdown, 30000);
                setInterval(updateNotificationBadge, 30000);
            });

            // Show dropdown when badge is clicked
            $('#notificationDropdown').click(function() {
                loadNotificationDropdown();
            });
                
                // Download CSV file
                const csvContent = 'data:text/csv;charset=utf-8,' + csv.join('\n');
                const encodedUri = encodeURI(csvContent);
                const link = document.createElement('a');
                link.setAttribute('href', encodedUri);
                link.setAttribute('download', filename + '.csv');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }

            // Theme switcher (light/dark mode)
            const themeToggle = document.createElement('button');
            themeToggle.className = 'btn btn-sm btn-outline-secondary ms-2';
            themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
            themeToggle.title = 'Toggle Dark Mode';
            
            themeToggle.addEventListener('click', function() {
                document.body.classList.toggle('dark-mode');
                localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
                this.innerHTML = document.body.classList.contains('dark-mode') ? 
                    '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
            });

            // Apply saved theme
            if(localStorage.getItem('theme') === 'dark') {
                document.body.classList.add('dark-mode');
                themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            }

            // Add theme toggle to top bar
            document.querySelector('.top-bar .d-flex').appendChild(themeToggle);

            // Dark mode styles
            const darkModeStyles = `
                <style>
                    .dark-mode {
                        background-color: #1a1a1a;
                        color: #ffffff;
                    }
                    .dark-mode .card {
                        background-color: #2d2d2d;
                        color: #ffffff;
                        border-color: #404040;
                    }
                    .dark-mode .table {
                        color: #ffffff;
                    }
                    .dark-mode .table-striped tbody tr:nth-of-type(odd) {
                        background-color: rgba(255,255,255,0.05);
                    }
                    .dark-mode .text-muted {
                        color: #aaaaaa !important;
                    }
                    .dark-mode .sidebar {
                        background: linear-gradient(180deg, #1a252f 0%, #0d1117 100%);
                    }
                </style>
            `;
            document.head.insertAdjacentHTML('beforeend', darkModeStyles);
        
 