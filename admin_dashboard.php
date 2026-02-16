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
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="navbar">
            <div class="navbar-content">
                <a href="/Sustain-U/admin_dashboard.php" class="brand" style="display:flex;align-items:center;gap:0.5rem;text-decoration:none;color:inherit;">
                    <img src="/Sustain-U/assets/logo.jpeg" alt="Sustain-U" style="height:36px;border-radius:6px;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                    <span style="font-weight:700;font-size:1.1rem;">Sustain-U Admin</span>
                </a>
                <div class="nav-links">
                    <span class="user-info">👤 <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="logout.php" class="nav-link">Logout</a>
                </div>
            </div>
        </div>

        <div class="dashboard-layout">
            <aside class="sidebar">
                <div class="sidebar-card">
                    <h3>Admin Panel</h3>
                    <p>Manage all reports and update their status</p>
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
                    <h2>All Issues</h2>
                    <div id="issuesList" class="issues-grid admin-grid">
                        <p class="loading">Loading all reports...</p>
                    </div>
                </section>
            </main>
        </div>

        <footer class="footer">
            <p>&copy; 2026 Sustain-U. Making a difference together.</p>
        </footer>
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
                    <select id="newStatus" name="status" required>
                        <option value="submitted">Submitted</option>
                        <option value="in_progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                    </select>
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

        // Load all issues
        async function loadAllIssues() {
            try {
                const response = await fetch('api/get_reports.php?type=all', { credentials: 'same-origin' });
                const data = await response.json();
                
                const issuesList = document.getElementById('issuesList');
                issuesList.innerHTML = '';
                
                if (!data.success) {
                    issuesList.innerHTML = '<p class="error">Failed to load issues.</p>';
                    return;
                }
                
                if (data.data.length === 0) {
                    // update counters
                    document.getElementById('totalIssuesCount').textContent = 0;
                    document.getElementById('inProgressCount').textContent = 0;
                    document.getElementById('resolvedCount').textContent = 0;

                    issuesList.innerHTML = '<p class="empty">No issues reported yet.</p>';
                    return;
                }

                // compute dynamic counts
                const total = data.data.length;
                const inProgress = data.data.filter(i => i.status === 'in_progress').length;
                const resolved = data.data.filter(i => i.status === 'resolved').length;
                document.getElementById('totalIssuesCount').textContent = total;
                document.getElementById('inProgressCount').textContent = inProgress;
                document.getElementById('resolvedCount').textContent = resolved;

                data.data.forEach(issue => {
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
                                <h3>${escapeHtml(issue.category.replace(/_/g, ' ').toUpperCase())}</h3>
                                <p class="issue-user">By: ${escapeHtml(issue.name)}</p>
                            </div>
                            <span class="badge" style="background-color: ${statusColor}">
                                ${escapeHtml(issue.status)}
                            </span>
                        </div>
                        <p class="issue-description">${escapeHtml(issue.description)}</p>
                        <div class="issue-meta">
                            <span>📍 ${escapeHtml(issue.location)}</span>
                            <span>⚠️ ${escapeHtml(issue.urgency)}</span>
                            <span>📅 ${new Date(issue.created_at).toLocaleDateString()}</span>
                        </div>
                        ${issue.image_path ? `<img src="${escapeHtml(issue.image_path)}" alt="Issue image" class="issue-image">` : ''}
                        ${issue.resolved_image ? `<div style="margin-top:0.5rem;"><small style="display:block;color:#4CAF50;font-weight:600;">Resolved Image</small><img src="${escapeHtml(issue.resolved_image)}" alt="Resolved image" class="issue-image"></div>` : ''}
                        <div class="admin-actions">
                            <button class="btn btn-small btn-primary" onclick="openStatusModal(${issue.id})">Update Status</button>
                            <button class="btn btn-small btn-danger" onclick="deleteIssue(${issue.id})">Delete</button>
                        </div>
                    `;
                    issuesList.appendChild(card);
                });
            } catch (error) {
                document.getElementById('issuesList').innerHTML = '<p class="error">Error loading issues.</p>';
            }
        }

        function openStatusModal(issueId) {
            document.getElementById('issueId').value = issueId;
            document.getElementById('statusModal').style.display = 'flex';
        }

        document.getElementById('statusForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch('api/update_status.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();
                
                if (data.success) {
                    modal.style.display = 'none';
                    loadAllIssues();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                alert('An error occurred.');
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
        loadAllIssues();
    </script>
</body>
</html>
