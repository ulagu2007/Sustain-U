// ============================================
// UTILITY FUNCTIONS
// ============================================

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Email validation
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Password validation
function validatePassword(password) {
    // At least 8 chars, 1 uppercase, 1 lowercase, 1 number
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumbers = /\d/.test(password);
    return password.length >= 8 && hasUpperCase && hasLowerCase && hasNumbers;
}
// Campus Care: 4-step wizard behaviour
document.addEventListener('DOMContentLoaded', () => {
    const steps = Array.from(document.querySelectorAll('#wizard .step'));
    let current = 0;
    let selectedUrgency = null;
    let selectedFile = null;

    const showStep = (i) => {
        steps.forEach((s, idx) => s.style.display = idx === i ? '' : 'none');
        current = i;
    };

    // Elements
    const imageInput = document.getElementById('imageInput');
    const imagePreview = document.getElementById('imagePreview');
    const nextToLocation = document.getElementById('nextToLocation');
    const backToImage = document.getElementById('backToImage');
    const nextToUrgency = document.getElementById('nextToUrgency');
    const backToLocation = document.getElementById('backToLocation');
    const nextToReview = document.getElementById('nextToReview');
    const backToUrgency = document.getElementById('backToUrgency');
    const submitIssue = document.getElementById('submitIssue');
    const reviewCard = document.getElementById('reviewCard');
    const summary = document.getElementById('summary');

    // Image preview
    if (imageInput) {
        imageInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;
            if (!file.type.startsWith('image/')) { alert('Please select an image'); imageInput.value = ''; return; }
            if (file.size > 5 * 1024 * 1024) { alert('File too large (max 5MB)'); imageInput.value = ''; return; }
            selectedFile = file;
            const url = URL.createObjectURL(file);
            imagePreview.innerHTML = `<img src="${url}" alt="preview" style="width:100%;height:auto;border-radius:12px;" />`;
        });
    }

    // Step buttons
    nextToLocation && nextToLocation.addEventListener('click', () => {
        if (!selectedFile) { alert('Please select an image to continue'); return; }
        showStep(1);
    });
    backToImage && backToImage.addEventListener('click', () => showStep(0));
    nextToUrgency && nextToUrgency.addEventListener('click', () => {
        const building = document.getElementById('building').value.trim();
        const floor = document.getElementById('floor').value.trim();
        const room = document.getElementById('room').value.trim();
        if (!building || !floor || !room) { alert('Please fill building, floor and room'); return; }
        showStep(2);
    });
    backToLocation && backToLocation.addEventListener('click', () => showStep(1));

    // Urgency selection
    document.querySelectorAll('.urgency').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.urgency').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            selectedUrgency = btn.dataset.value;
        });
    });

    nextToReview && nextToReview.addEventListener('click', () => {
        if (!selectedUrgency) { alert('Please select an urgency'); return; }
        // Populate review
        const building = document.getElementById('building').value.trim();
        const floor = document.getElementById('floor').value.trim();
        const room = document.getElementById('room').value.trim();
        reviewCard.innerHTML = imagePreview.innerHTML;
        summary.innerHTML = `<p><strong>Building:</strong> ${escapeHtml(building)}</p><p><strong>Floor:</strong> ${escapeHtml(floor)}</p><p><strong>Room:</strong> ${escapeHtml(room)}</p><p><strong>Urgency:</strong> ${escapeHtml(selectedUrgency)}</p>`;
        showStep(3);
    });

    backToUrgency && backToUrgency.addEventListener('click', () => showStep(2));

    // Submission: upload (geofence removed)
    submitIssue && submitIssue.addEventListener('click', async () => {
        if (!selectedFile) { alert('Please select an image to continue'); return; }
        submitIssue.disabled = true;
        submitIssue.textContent = 'Uploading...';

        try {
            const fd = new FormData();
            fd.append('image', selectedFile);
            fd.append('building', document.getElementById('building').value.trim());
            fd.append('floor', document.getElementById('floor').value.trim());
            fd.append('room', document.getElementById('room').value.trim());
            fd.append('urgency', selectedUrgency);

            const upload = await fetch('/Sustain-U/api/submit_issue.php', { method: 'POST', credentials: 'same-origin', body: fd });
            const result = await upload.json();
            if (result.success) {
                alert('Issue submitted successfully');
                window.location.href = '/Sustain-U/test.php';
            } else {
                alert(result.message || 'Submission failed');
            }
        } catch (err) {
            console.error(err);
            alert('Submission error');
        } finally {
            submitIssue.disabled = false;
            submitIssue.textContent = 'Submit';
        }
    });

    // Small escapeHtml util
    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/[&<>"']/g, (m) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#039;"})[m]);
    }

    showStep(0);
});

