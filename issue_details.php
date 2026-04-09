<?php
/**
 * SUSTAIN-U - Issue Details Page
 * Detailed view of a single issue with admin controls
 */
require_once 'config.php';
requireLogin();

$issue_id = intval($_GET['id'] ?? 0);
if (!$issue_id) {
    header('Location: my_works.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Details - Sustain-U</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="app">
    <?php include __DIR__ . '/inc/header.php'; ?>

    <main class="container" style="max-width: 800px; margin: 2rem auto;">
        <div id="loadingContent" class="loading" style="text-align: center; padding: 3rem;">
            <div class="spinner" style="margin: 0 auto;"></div>
            <p>Loading issue details...</p>
        </div>

        <div id="issueContent" style="display: none;">
            <button onclick="history.back()" class="btn btn-secondary" style="margin-bottom: 1.5rem;">← Back</button>

            <div class="card">
                <div class="card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                        <div>
                            <h2 id="issueTitle" style="margin: 0;">Issue Title</h2>
                            <p id="issueDate" style="margin: 0.5rem 0 0; color: #666;">Date</p>
                        </div>
                        <div style="text-align: right;">
                            <div id="statusBadgeContainer"></div>
                            <div id="downloadReportContainer" style="margin-top: 0.75rem; display: none;">
                                <button onclick="downloadPDF()" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem; margin-left: auto;">
                                    <span>📄</span> Download Audit Report
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Issue Image -->
                    <div id="imageContainer" style="margin-bottom: 2rem; display: none;">
                        <img id="issueImage" alt="Issue" style="max-width: 100%; height: auto; border-radius: var(--radius); border: 1px solid var(--border-color);">
                    </div>

                    <!-- Resolved Image (appears inline, no separate 'Resolution Proof' block) -->
                    <div id="resolvedImageContainer" style="margin-bottom: 2rem; display: none;">
                        <h4 style="margin:0.25rem 0 0.5rem; color: var(--primary-color);">Resolved Image</h4>
                        <img id="resolvedImage" alt="Resolved" style="max-width: 100%; height: auto; border-radius: var(--radius); border: 1px solid var(--border-color);">
                    </div>

                    <!-- Issue Details -->
                    <div style="background: var(--light-color); padding: 1.5rem; border-radius: var(--radius); margin-bottom: 2rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div>
                                <label style="color: var(--primary-color); font-weight: 600;">Category</label>
                                <p id="issueCategory" style="margin: 0.5rem 0 0;">--</p>
                            </div>

                            <div>
                                <label style="color: var(--primary-color); font-weight: 600;">Urgency Level</label>
                                <p id="issueUrgency" style="margin: 0.5rem 0 0;">--</p>
                            </div>

                            <div>
                                <label style="color: var(--primary-color); font-weight: 600;">Location</label>
                                <p id="issueLocation" style="margin: 0.5rem 0 0;">--</p>
                            </div>

                            <div>
                                <label style="color: var(--primary-color); font-weight: 600;">Status</label>
                                <p id="issueStatus" style="margin: 0.5rem 0 0;">--</p>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div style="margin-bottom: 2rem;">
                        <h3 style="color: var(--primary-color); margin-bottom: 1rem;">Description</h3>
                        <p id="issueDescription" style="line-height: 1.8; color: #555;">--</p>
                    </div>

                    <!-- Reported By -->
                    <div style="background: var(--light-color); padding: 1.5rem; border-radius: var(--radius); margin-bottom: 2rem;">
                        <h4 style="color: var(--primary-color); margin-top: 0;">Reported By</h4>
                        <p id="reporterName" style="margin: 0.5rem 0 0; font-weight: 500;">--</p>
                        <p id="reporterEmail" style="margin: 0.5rem 0 0; color: #666;">--</p>
                        <p id="reporterRegNum" style="margin: 0.25rem 0 0; color: #666; font-size: 0.9rem;">--</p>
                    </div>

                </div>

                <!-- Admin Actions -->
                <div id="adminActions" class="card-footer" style="display: none; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                    <h4 style="margin-top: 0; color: var(--primary-color);">Admin Actions</h4>

                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <button id="markInProgressBtn" class="btn btn-info" onclick="updateIssueStatus('in_progress')">Mark as In Progress</button>
                        <button id="markResolvedBtn" class="btn btn-success" onclick="document.getElementById('resolutionSection').style.display='block'; document.getElementById('resolutionNotes').focus();">Mark as Resolved</button>

                    </div>

                    <!-- Resolution Update -->
                    <div id="resolutionSection" style="margin-top: 1.5rem; display: none;">
                        <h5>Add Resolution Notes & Image</h5>
                        <div class="form-group">
                            <label for="resolutionImageInput">Upload Resolution Image (required to mark resolved)</label>
                            <input type="file" id="resolutionImageInput" accept="image/*">
                        </div>
                        <div class="form-group">
                            <textarea id="resolutionNotes" placeholder="Describe the resolution steps taken..." rows="4" required></textarea>
                        </div>
                        <button class="btn btn-primary" onclick="submitResolution()">Save Resolution & Mark Resolved</button>
                    </div>

                    <!-- Resolution Display -->
                    <div id="resolutionDisplay" style="margin-top: 1.5rem; background: rgba(76, 175, 80, 0.1); padding: 1rem; border-radius: var(--radius); display: none;">
                        <h5 style="margin-top: 0;">Resolution Notes</h5>
                        <p id="resolutionText" style="margin: 0; color: #555;"></p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="js/main.js"></script>
    <script>
        const issueId = <?php echo $issue_id; ?>;
        const isAdmin = <?php echo isAdmin() ? 'true' : 'false'; ?>;

        // Load issue on page load
        loadIssueDetails();

        async function loadIssueDetails() {
            try {
                const response = await fetch(`api/get_issue_details.php?id=${issueId}`, { credentials: 'same-origin' });
                const data = await response.json();

                if (!data.success) {
                    document.getElementById('loadingContent').innerHTML = 
                        `<div class="alert alert-danger">Error: ${data.message || 'Failed to load issue'}</div>`;
                    return;
                }

                const issue = data.data;
                displayIssue(issue);
                document.getElementById('loadingContent').style.display = 'none';
                document.getElementById('issueContent').style.display = 'block';
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('loadingContent').innerHTML = 
                    `<div class="alert alert-danger">Error loading issue. Please try again.</div>`;
            }
        }

        function displayIssue(issue) {
            // Set title and date
            const catLabel = getCategoryBadge(issue.category);
            document.getElementById('issueTitle').textContent = sanitize(catLabel);
            document.getElementById('issueDate').textContent = 'Submitted ' + new Date(issue.created_at).toLocaleString();

            // Set status badge and conditional download button
            document.getElementById('statusBadgeContainer').innerHTML = getStatusBadge(issue.status) + ' ' + getUrgencyBadge(issue.urgency);
            
            if (issue.status === 'resolved') {
                document.getElementById('downloadReportContainer').style.display = 'block';
            } else {
                document.getElementById('downloadReportContainer').style.display = 'none';
            }

            // Set details
            document.getElementById('issueCategory').textContent = catLabel;
            document.getElementById('issueUrgency').textContent = '⚠️ ' + issue.urgency.toUpperCase();
            document.getElementById('issueLocation').textContent = sanitize(issue.location);
            document.getElementById('issueStatus').textContent = issue.status.toUpperCase();
            document.getElementById('issueDescription').textContent = sanitize(issue.description);

            // Set reporter
            document.getElementById('reporterName').textContent = sanitize(issue.user_name);
            document.getElementById('reporterEmail').textContent = sanitize(issue.email);
            if (issue.register_number) {
                document.getElementById('reporterRegNum').textContent = 'Reg: ' + sanitize(issue.register_number) + (issue.section ? ' (' + sanitize(issue.section) + ')' : '');
                document.getElementById('reporterRegNum').style.display = 'block';
            } else {
                document.getElementById('reporterRegNum').style.display = 'none';
            }

            // Set original image if available
            if (issue.image_path) {
                document.getElementById('issueImage').src = sanitize(issue.image_path);
                document.getElementById('imageContainer').style.display = 'block';
            }

            // Show resolved image inline (if present)
            if (issue.resolved_image) {
                document.getElementById('resolvedImage').src = sanitize(issue.resolved_image);
                document.getElementById('resolvedImageContainer').style.display = 'block';
            } else {
                document.getElementById('resolvedImageContainer').style.display = 'none';
            }

            // Show admin actions if admin
            if (isAdmin) {
                document.getElementById('adminActions').style.display = 'block';
                
                // Show/hide buttons based on status
                document.getElementById('markInProgressBtn').style.display = issue.status === 'submitted' ? 'block' : 'none';
                document.getElementById('markResolvedBtn').style.display = issue.status !== 'resolved' ? 'block' : 'none';

                // Show resolution section if in progress OR when admin explicitly clicks Mark Resolved
                if (issue.status === 'in_progress') {
                    document.getElementById('resolutionSection').style.display = 'block';
                } else if (issue.status === 'resolved') {
                     document.getElementById('resolutionSection').style.display = 'none';
                } else {
                    document.getElementById('resolutionSection').style.display = 'none';
                }

                // Show resolution notes if available
                if (issue.resolution_notes) {
                    document.getElementById('resolutionText').textContent = sanitize(issue.resolution_notes);
                    document.getElementById('resolutionDisplay').style.display = 'block';
                } else {
                    document.getElementById('resolutionDisplay').style.display = 'none';
                }
            }
        }

        function getStatusBadge(status) {
            const badges = {
                'submitted': '<span class="badge badge-warning">📝 Submitted</span>',
                'in_progress': '<span class="badge badge-info">⏳ In Progress</span>',
                'resolved': '<span class="badge badge-success">✓ Resolved</span>'
            };
            return badges[status] || '<span class="badge badge-secondary">Unknown</span>';
        }

        function getUrgencyBadge(urgency) {
            const badges = {
                'can_wait': '<span class="badge badge-success">🟢 Can Wait</span>',
                'needs_attention': '<span class="badge badge-warning">🟡 Needs Attention</span>',
                'emergency': '<span class="badge badge-danger">🔴 Emergency</span>'
            };
            return badges[urgency] || '';
        }

        async function updateIssueStatus(newStatus) {
            if (!confirm(`Update issue status to ${newStatus.replace('_', ' ').toUpperCase()}?`)) return;

            try {
                // server expects form-encoded POST (not JSON) so use FormData
                const formData = new FormData();
                formData.append('issue_id', issueId);
                formData.append('status', newStatus);

                const response = await fetch('api/update_status.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    loadIssueDetails();
                } else {
                    alert(data.message || 'Failed to update status');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error updating status');
            }
        }

        async function submitResolution() {
            const notes = document.getElementById('resolutionNotes').value.trim();
            const fileInput = document.getElementById('resolutionImageInput');

            if (!notes) {
                alert('Please enter resolution notes');
                return;
            }

            if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                alert('Please attach a resolution image before saving');
                return;
            }

            const formData = new FormData();
            formData.append('issue_id', issueId);
            formData.append('status', 'resolved');
            formData.append('resolution_notes', notes);
            formData.append('resolution_image', fileInput.files[0]);

            try {
                const response = await fetch('api/update_status.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    alert('Resolution saved and issue marked resolved');
                    loadIssueDetails();
                } else {
                    alert(data.message || 'Failed to save resolution');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error saving resolution');
            }
        }

        function downloadPDF() {
            window.location.href = `api/generate_pdf.php?id=${issueId}`;
        }

        function sanitize(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
    <?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
