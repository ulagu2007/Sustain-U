<?php
/**
 * SUSTAIN-U - My Works / My Issues Page
 * Lists all issues submitted by the current student with color-coded status badges
 */
require_once 'config.php';
requireLogin();

if (isAdmin()) {
    header('Location: /Sustain-U/admin_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Issues - Sustain-U</title>
    <link rel="stylesheet" href="/Sustain-U/css/style.css">
</head>
<body class="app">
    <?php include __DIR__ . '/inc/header.php'; ?>

    <main class="container" style="margin: 2rem auto;">
        <div class="card">
            <div class="card-header">
                <h2>My Submitted Issues</h2>
                <p style="margin: 0.5rem 0 0; color: #666;">Track all the environmental issues you've reported</p>
            </div>

            <div class="card-body">
                <!-- Filter Controls -->
                <div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
                    <select id="statusFilter" style="flex: 1; min-width: 150px;">
                        <option value="">All Status</option>
                        <option value="submitted">📝 Submitted</option>
                        <option value="in_progress">⏳ In Progress</option>
                        <option value="resolved">✓ Resolved</option>
                    </select>

                    <select id="categoryFilter" style="flex: 1; min-width: 150px;">
                        <option value="">All Categories</option>
                        <option value="water_waste">💧 Water Wastage</option>
                        <option value="plastic_pollution">♻️ Plastic Pollution</option>
                        <option value="air_quality">💨 Air Quality</option>
                        <option value="energy_waste">⚡ Energy Waste</option>
                        <option value="littering">🗑️ Littering</option>
                        <option value="tree_damage">🌳 Tree Damage</option>
                        <option value="other">📋 Other</option>
                    </select>

                    <button id="resetFilters" class="btn btn-secondary">Reset</button>
                </div>

                <!-- Issues List -->
                <div id="issuesList">
                    <div class="loading" style="text-align: center; padding: 3rem;">
                        <div class="spinner" style="margin: 0 auto;"></div>
                        <p>Loading your issues...</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bottom Navigation for Mobile -->
    <nav class="bottom-nav">
        <div class="bottom-nav-items">
            <a href="/Sustain-U/my_works.php" class="bottom-nav-item">📊 Dashboard</a>
            <a href="/Sustain-U/report_issue.php" class="bottom-nav-item">📝 Report</a>
            <a href="/Sustain-U/my_works.php" class="bottom-nav-item active">📋 My Issues</a>
            <a href="/Sustain-U/profile.php" class="bottom-nav-item">👤 Profile</a>
        </div>
    </nav>

    <script src="/Sustain-U/js/main.js"></script>
    <script>
        let allIssues = [];

        // Load issues on page load
        loadIssues();

        // Filter event listeners
        document.getElementById('statusFilter').addEventListener('change', applyFilters);
        document.getElementById('categoryFilter').addEventListener('change', applyFilters);
        document.getElementById('resetFilters').addEventListener('click', () => {
            document.getElementById('statusFilter').value = '';
            document.getElementById('categoryFilter').value = '';
            applyFilters();
        });

        async function loadIssues() {
            try {
                const response = await fetch('/Sustain-U/api/get_student_issues.php', { credentials: 'same-origin' });
                const data = await response.json();

                if (!data.success) {
                    document.getElementById('issuesList').innerHTML = 
                        `<div class="alert alert-danger">Error: ${data.message || 'Failed to load issues'}</div>`;
                    return;
                }

                allIssues = data.data || [];

                if (allIssues.length === 0) {
                    document.getElementById('issuesList').innerHTML = 
                        `<div class="alert alert-info">
                            <strong>No issues submitted yet</strong><br>
                            <a href="/Sustain-U/report_issue.php" class="btn btn-primary" style="margin-top: 1rem;">Submit Your First Report</a>
                        </div>`;
                    return;
                }

                displayIssues(allIssues);
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('issuesList').innerHTML = 
                    `<div class="alert alert-danger">Error loading issues. Please try again.</div>`;
            }
        }

        function applyFilters() {
            const statusFilter = document.getElementById('statusFilter').value;
            const categoryFilter = document.getElementById('categoryFilter').value;

            const filtered = allIssues.filter(issue => {
                const statusMatch = !statusFilter || issue.status === statusFilter;
                const categoryMatch = !categoryFilter || issue.category === categoryFilter;
                return statusMatch && categoryMatch;
            });

            displayIssues(filtered);
        }

        function displayIssues(issues) {
            const issuesList = document.getElementById('issuesList');

            if (issues.length === 0) {
                issuesList.innerHTML = '<div class="alert alert-info">No issues match your filter criteria</div>';
                return;
            }

            issuesList.innerHTML = issues.map(issue => `
                <div class="card" style="margin-bottom: 1.5rem; cursor: pointer;" onclick="location.href='/Sustain-U/issue_details.php?id=${issue.id}'">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                        <div>
                            <h3 style="margin: 0; margin-bottom: 0.5rem;">${sanitize(issue.category.replace(/_/g, ' ').toUpperCase())}</h3>
                            <small style="color: #666;">${new Date(issue.created_at).toLocaleString()}</small>
                        </div>
                        <div style="text-align: right;">
                            ${getStatusBadge(issue.status)}
                            ${getUrgencyBadge(issue.urgency)}
                        </div>
                    </div>

                    <p style="margin-bottom: 1rem; color: #555;">${sanitize(issue.description.substring(0, 150))}...</p>

                    <div style="display: flex; gap: 2rem; flex-wrap: wrap; font-size: 0.9rem; color: #666;">
                        <div>📍 ${sanitize(issue.location)}</div>
                        <div>⚠️ ${issue.urgency.toUpperCase()}</div>
                        <div>📅 ${issue.status === 'resolved' ? 'Resolved' : 'In Progress'}</div>
                    </div>

                    ${issue.image_path ? `
                        <div style="margin-top: 1rem;">
                            <img src="${sanitize(issue.image_path)}" alt="Issue image" style="max-height: 200px; border-radius: var(--radius);">
                        </div>
                    ` : ''}
                </div>
            `).join('');
        }

        function getStatusBadge(status) {
            const badges = {
                'submitted': '<span class="badge badge-info">📝 Submitted</span>',
                'in_progress': '<span class="badge badge-warning">⏳ In Progress</span>',
                'resolved': '<span class="badge badge-success">✓ Resolved</span>'
            };
            return badges[status] || '<span class="badge badge-secondary">Unknown</span>';
        }

        function getUrgencyBadge(urgency) {
            const badges = {
                'low': '<span class="badge badge-success">🟢 Low</span>',
                'medium': '<span class="badge badge-warning">🟡 Medium</span>',
                'high': '<span class="badge badge-danger">🔴 High</span>'
            };
            return badges[urgency] || '';
        }

        function sanitize(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
