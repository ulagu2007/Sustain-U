<?php
/**
 * SUSTAIN-U - Admin Dashboard
 * Lists all issues with filters and admin controls
 */
require_once 'config.php';
requireAdmin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sustain-U</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body class="app">
    <?php include __DIR__ . '/inc/header.php'; ?>

    <div class="container">
        <!-- page container follows -->


        <div class="dashboard-layout">
            <aside class="sidebar">
                <div class="sidebar-card">
                    <h3>Welcome Admin</h3>
                    <p>Manage all reports</p>
                    <hr style="margin: 1rem 0; border: 0; border-top: 1px solid #eee;">
                    <a href="auditing.php" class="btn btn-small btn-primary" style="display: block; text-align: center;">Auditing System</a>
                </div>
            </aside>

            <main class="dashboard-main">
                <!-- Dynamic admin summary -->
                <div style="display:flex; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap;">
                    <div class="card" style="flex:1; min-width:160px; text-align:center; padding:1rem;">
                        <div style="font-size:1.25rem; color:var(--primary-color);">Total Issues</div>
                        <div id="totalIssuesCount" style="font-size:1.75rem; font-weight:700; margin-top:0.5rem;">0</div>
                    </div>
                    <div class="card" style="flex:1; min-width:160px; text-align:center; padding:1rem;">
                        <div style="font-size:1.25rem; color:#FFA500;">In Progress</div>
                        <div id="inProgressCount" style="font-size:1.75rem; font-weight:700; margin-top:0.5rem;">0</div>
                    </div>
                    <div class="card" style="flex:1; min-width:160px; text-align:center; padding:1rem;">
                        <div style="font-size:1.25rem; color:#32CD32;">Resolved</div>
                        <div id="resolvedCount" style="font-size:1.75rem; font-weight:700; margin-top:0.5rem;">0</div>
                    </div>
                </div>

                <section class="issues-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                        <h2 style="background: var(--primary-color); color: white; padding: 0.5rem 1rem; border-radius: 8px; display: inline-block; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">All Issues</h2>
                        
                        <!-- Filter Controls -->
                        <div style="display: flex; gap: 0.5rem;">
                            <select id="statusFilter" class="form-control" style="padding: 0.5rem; border-radius: 6px; border: 1px solid #ddd;">
                                <option value="">All Status</option>
                                <option value="submitted">Submitted</option>
                                <option value="in_progress">In Progress</option>
                                <option value="resolved">Resolved</option>
                            </select>

                            <select id="categoryFilter" class="form-control" style="padding: 0.5rem; border-radius: 6px; border: 1px solid #ddd;">
                                <option value="">All Categories</option>
                                <option value="air">Air Issues</option>
                                <option value="water">Water Issues</option>
                                <option value="waste">Waste Issues</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="issuesList" class="issues-grid admin-grid">
                        <p class="loading">Loading all reports...</p>
                    </div>
                </section>
            </main>
        </div>


    </div>

    <!-- Status Modal -->
    <div id="statusModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2>Update Issue Status</h2>
            <form id="statusForm">
                <input type="hidden" id="issueId" name="issue_id">
                <div class="form-group">
                    <label for="newStatus">New Status</label>
                    <select id="newStatus" name="status" required onchange="toggleResolutionImage()">
                        <option value="submitted">Submitted</option>
                        <option value="in_progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                    </select>
                </div>
                <div class="form-group" id="resolutionImageGroup" style="display: none;">
                    <label for="resolutionImage">Upload Resolved Image (Required)</label>
                    <input type="file" id="resolutionImage" name="resolved_image" accept="image/*">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-full">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        // Modal functionality
        const modal = document.getElementById('statusModal');
        const closeBtn = document.querySelector('.modal-close');

        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });

        // Global variable to store issues
        let allAdminIssues = [];

        // Filter event listeners
        document.getElementById('statusFilter').addEventListener('change', applyFilters);
        document.getElementById('categoryFilter').addEventListener('change', applyFilters);

        // Load all issues
        async function loadAllIssues() {
            try {
                // cache busing timestamp to ensure fresh data
                const response = await fetch('api/get_reports.php?type=all&_=' + new Date().getTime(), { credentials: 'same-origin' });
                const data = await response.json();
                
                if (!data.success) {
                    document.getElementById('issuesList').innerHTML = '<p class="error">Failed to load issues.</p>';
                    return;
                }
                
                allAdminIssues = data.data || [];
                
                // compute dynamic counts
                const total = allAdminIssues.length;
                const inProgress = allAdminIssues.filter(i => i.status === 'in_progress').length;
                const resolved = allAdminIssues.filter(i => i.status === 'resolved').length;
                document.getElementById('totalIssuesCount').textContent = total;
                document.getElementById('inProgressCount').textContent = inProgress;
                document.getElementById('resolvedCount').textContent = resolved;

                applyFilters(); // Display issues with current filters

            } catch (error) {
                document.getElementById('issuesList').innerHTML = '<p class="error">Error loading issues.</p>';
            }
        }

        function applyFilters() {
            const statusFilter = document.getElementById('statusFilter').value;
            const categoryFilter = document.getElementById('categoryFilter').value;
            const issuesList = document.getElementById('issuesList');

            const filtered = allAdminIssues.filter(issue => {
                const statusMatch = !statusFilter || issue.status === statusFilter;
                const categoryMatch = !categoryFilter || issue.category === categoryFilter;
                return statusMatch && categoryMatch;
            });

            issuesList.innerHTML = '';

            if (filtered.length === 0) {
                issuesList.innerHTML = '<p class="empty">No issues match the filters.</p>';
                return;
            }

            filtered.forEach(issue => {
                const statusColor = {
                    'submitted': '#FFA500',
                    'in_progress': '#4169E1',
                    'resolved': '#32CD32'
                }[issue.status] || '#666';
                
                const card = document.createElement('div');
                card.className = 'issue-card admin-card';
                card.innerHTML = `
                    <div class="issue-header">
                        <div>
                            <h3>${escapeHtml(getCategoryBadge(issue.category))}</h3>
                            <p class="issue-user">By: ${escapeHtml(issue.name)}</p>
                        </div>
                        <span class="badge" style="background-color: ${statusColor}">
                            ${escapeHtml(issue.status)}
                        </span>
                    </div>
                    <p class="issue-description">${escapeHtml(issue.description)}</p>
                    <div class="issue-meta">
                        <span>📍 ${escapeHtml(issue.location)}</span>
                        <span>${getUrgencyBadge(issue.urgency)}</span>
                        <span>📅 ${new Date(issue.created_at).toLocaleDateString()}</span>
                    </div>
                    ${issue.image_path ? `<img src="${escapeHtml(issue.image_path)}" alt="Issue image" class="issue-image">` : ''}
                    ${issue.resolved_image ? `<div style="margin-top:0.5rem;"><small style="display:block;color:#4CAF50;font-weight:600;">Resolved Image</small><img src="${escapeHtml(issue.resolved_image)}" alt="Resolved image" class="issue-image"></div>` : ''}
                    <div class="admin-actions" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                        <a href="issue_details.php?id=${issue.id}" class="btn btn-small btn-secondary">View Details</a>
                        ${issue.status !== 'resolved' ? `<button onclick="openStatusModal(${issue.id}, '${issue.status}')" class="btn btn-small btn-primary" style="font-size: 0.75rem;">Update Status</button>` : ''}
                        ${issue.status === 'resolved' ? `<a href="api/generate_pdf.php?id=${issue.id}" class="btn btn-small btn-primary" style="font-size: 0.75rem;">Download Report</a>` : ''}
                        <button onclick="deleteIssue(${issue.id})" class="btn btn-small" style="font-size:0.75rem;color:#c0392b;border-color:#c0392b;">Delete</button>
                    </div>
                `;
                issuesList.appendChild(card);
            });
        }

        function openStatusModal(issueId, currentStatus) {
            document.getElementById('issueId').value = issueId;
            document.getElementById('resolutionImage').value = ''; // Clear previous file
            document.getElementById('newStatus').value = currentStatus || 'submitted'; // Default to current status
            toggleResolutionImage();
            document.getElementById('statusModal').style.display = 'flex';
        }

        function toggleResolutionImage() {
            const status = document.getElementById('newStatus').value;
            const group = document.getElementById('resolutionImageGroup');
            group.style.display = status === 'resolved' ? 'block' : 'none';
            document.getElementById('resolutionImage').required = status === 'resolved';
        }

        document.getElementById('statusForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            // Validation
            if (formData.get('status') === 'resolved' && (!document.getElementById('resolutionImage').files.length)) {
                alert('Please upload a resolution image.');
                return;
            }

            try {
                const button = e.target.querySelector('button[type="submit"]');
                button.disabled = true;
                button.textContent = 'Updating...';

                const response = await fetch('api/update_status.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('statusModal').style.display = 'none';
                    loadAllIssues();
                } else {
                    alert('Error: ' + data.message);
                }
                button.disabled = false;
                button.textContent = 'Update Status';
            } catch (error) {
                alert('An error occurred.');
                e.target.querySelector('button[type="submit"]').disabled = false;
            }
        });

        async function deleteIssue(issueId) {
            if (!confirm('Are you sure you want to delete this issue?')) return;
            
            const formData = new FormData();
            formData.append('issue_id', issueId);
            formData.append('action', 'delete');
            
            try {
                const response = await fetch('api/update_status.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();
                
                if (data.success) {
                    loadAllIssues();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                alert('An error occurred.');
            }
        }

        // Load issues on page load
        // Initial load
        loadAllIssues();
        
        // Auto-refresh every 10 seconds
        setInterval(loadAllIssues, 10000);
    </script>
    <?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
