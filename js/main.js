// ============================================
// UTILITY FUNCTIONS
// ============================================

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function getCategoryLabel(key) {
    if (!key) return 'Unspecified';
    const map = {
        'water_waste': 'Water',
        'plastic_pollution': 'Plastic',
        'air_quality': 'Air',
        'energy_waste': 'Energy',
        'littering': 'Littering',
        'tree_damage': 'Tree',
        'other': 'Other Issue',
        'waste': 'Waste Issue',
        'air': 'Air Issue',
        'water': 'Water Issue'
    };
    return map[key] || String(key).replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function getCategoryBadge(key) {
    // Emojis removed
    const labels = {
        'water_waste': 'Water',
        'plastic_pollution': 'Plastic',
        'air_quality': 'Air',
        'energy_waste': 'Energy',
        'littering': 'Littering',
        'tree_damage': 'Tree',
        'other': 'Other',
        'waste': 'Waste',
        'air': 'Air',
        'water': 'Water'
    };
    return labels[key] || getCategoryLabel(key);
}

function getUrgencyBadge(urgency) {
    // Badges updated (removed emojis if any, kept colors)
    const badges = {
        'can_wait': '<span class="badge badge-success">Can Wait</span>',
        'needs_attention': '<span class="badge badge-warning">Needs Attention</span>',
        'emergency': '<span class="badge badge-danger">Emergency</span>'
    };
    return badges[urgency] || `<span class="badge badge-secondary">${urgency}</span>`;
}

// Email validation
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Password validation
function validatePassword(password) {
    // Relaxed validation: Just check length >= 6
    return password && password.length >= 6;
}
// Sustain-U: 4-step wizard behaviour
// DOMContentLoaded event listener for general page initialization (if needed)
document.addEventListener('DOMContentLoaded', () => {
    // Add any global initialization here
});

