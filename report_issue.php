<?php
/**
 * SUSTAIN-U - Report Issue Page
 * 4-Step wizard: Category → Location → Urgency → Review & Submit
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
    <title>Report Issue - Sustain-U</title>
    <link rel="stylesheet" href="/Sustain-U/css/style.css">
</head>
<body class="app">
    <?php include __DIR__ . '/inc/header.php'; ?>

    <main class="container" style="max-width: 600px; margin: 2rem auto; padding: 0 1rem;">
        
        <!-- Progress Bar -->
        <div class="progress-steps">
            <div class="progress-step completed" data-step="1">
                <div class="step-circle">1</div>
                <span class="step-label">Category</span>
            </div>
            <div class="progress-line"></div>
            <div class="progress-step" data-step="2">
                <div class="step-circle">2</div>
                <span class="step-label">Location</span>
            </div>
            <div class="progress-line"></div>
            <div class="progress-step" data-step="3">
                <div class="step-circle">3</div>
                <span class="step-label">Urgency</span>
            </div>
            <div class="progress-line"></div>
            <div class="progress-step" data-step="4">
                <div class="step-circle">4</div>
                <span class="step-label">Review</span>
            </div>
        </div>

        <!-- Wizard Form -->
        <form id="issueWizard" style="margin-top: 2rem;">
            
            <!-- STEP 1: CATEGORY & TYPE SELECTION -->
            <div id="step-1" class="wizard-step active">
                <div class="card">
                    <div class="card-header">
                        <h2>Select Issue Category</h2>
                        <p style="margin: 0.5rem 0 0; color: #666;">Choose the category that best describes the environmental issue</p>
                    </div>

                    <div class="card-body">
                        <div class="form-group">
                            <label for="categorySelect">Issue Category *</label>
                            <select id="categorySelect" required style="padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; width: 100%;">
                                <option value="">-- Select Category --</option>
                                <option value="air">Air</option>
                                <option value="water">Water</option>
                                <option value="waste">Waste</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="typeSelect">Select Type *</label>
                            <select id="typeSelect" required style="padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; width: 100%; display: none;">
                                <option value="">-- Select Type --</option>
                            </select>
                            <small id="typeError" class="error-message"></small>
                        </div>

                        <div class="form-group" id="customDescriptionGroup" style="display: none;">
                            <label for="customDescription">Please describe the issue in detail *</label>
                            <textarea id="customDescription" placeholder="Describe the issue..." style="padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; width: 100%; min-height: 120px; font-family: inherit;"></textarea>
                            <small id="customError" class="error-message"></small>
                        </div>

                        <!-- Image upload at Category step -->
                        <div class="form-group" style="margin-top: 1rem;">
                            <label for="categoryImageInput">Attach Photo (add at least one photo)</label>
                            <input type="file" id="categoryImageInput" accept="image/*">
                            <small id="categoryImageHelp" class="text-muted">Max 5MB — attach a clear photo of the issue.</small>
                            <small id="categoryImageError" class="error-message"></small>
                            <div id="categoryImagePreview" class="image-preview" style="margin-top:0.75rem; max-width:220px;">No image selected</div>
                        </div>

                        <button type="button" id="step1NextBtn" class="btn btn-primary btn-block" style="margin-top: 1.5rem; opacity: 0.5;" disabled onclick="nextStep(1)">Next: Location</button>
                    </div>
                </div>
            </div>

            <!-- STEP 2: LOCATION DETAILS -->
            <div id="step-2" class="wizard-step" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <h2>Issue Location</h2>
                        <p style="margin: 0.5rem 0 0; color: #666;">Provide the exact location of the issue</p>
                    </div>

                    <div class="card-body">
                        <div class="form-group">
                            <label for="building">Building Name *</label>
                            <input type="text" id="building" required placeholder="e.g., Tech Park, Main Building, Hostel B" style="padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; width: 100%; box-sizing: border-box;">
                            <small id="buildingError" class="error-message"></small>
                        </div>

                        <div class="form-group">
                            <label for="floor">Floor Number *</label>
                            <input type="text" id="floor" required placeholder="e.g., Ground Floor, 1st Floor, 2nd Floor" style="padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; width: 100%; box-sizing: border-box;">
                            <small id="floorError" class="error-message"></small>
                        </div>

                        <div class="form-group">
                            <label for="room">Room / Area Description *</label>
                            <input type="text" id="room" required placeholder="e.g., Room 501, Near Elevator, Corridor" style="padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; width: 100%; box-sizing: border-box;">
                            <small id="roomError" class="error-message"></small>
                        </div>

                        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                            <button type="button" class="btn btn-secondary btn-block" onclick="prevStep(2)">Back</button>
                            <button type="button" class="btn btn-primary btn-block" onclick="nextStep(2)">Next: Urgency</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 3: URGENCY LEVEL -->
            <div id="step-3" class="wizard-step" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <h2>Select Urgency Level</h2>
                        <p style="margin: 0.5rem 0 0; color: #666;">How urgent is this issue?</p>
                    </div>

                    <div class="card-body">
                        <div class="urgency-options" style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1.5rem;">
                            <label class="urgency-card" data-urgency="can_wait">
                                <input type="radio" name="urgency" value="can_wait" hidden>
                                <div class="urgency-content">
                                    <div class="urgency-icon">🕐</div>
                                    <div>
                                        <h4 style="margin: 0;">Can Wait</h4>
                                        <p style="margin: 0.25rem 0 0; font-size: 0.9rem; color: #666;">Minor issue, not affecting daily activities</p>
                                    </div>
                                </div>
                            </label>

                            <label class="urgency-card" data-urgency="needs_attention">
                                <input type="radio" name="urgency" value="needs_attention" hidden>
                                <div class="urgency-content">
                                    <div class="urgency-icon">⚠️</div>
                                    <div>
                                        <h4 style="margin: 0;">Needs Attention</h4>
                                        <p style="margin: 0.25rem 0 0; font-size: 0.9rem; color: #666;">Should be fixed soon</p>
                                    </div>
                                </div>
                            </label>

                            <label class="urgency-card" data-urgency="emergency">
                                <input type="radio" name="urgency" value="emergency" hidden>
                                <div class="urgency-content">
                                    <div class="urgency-icon">🚨</div>
                                    <div>
                                        <h4 style="margin: 0;">Emergency</h4>
                                        <p style="margin: 0.25rem 0 0; font-size: 0.9rem; color: #666;">Requires immediate action</p>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <small id="urgencyError" class="error-message" style="display: block; margin-top: 1rem;"></small>

                        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                            <button type="button" class="btn btn-secondary btn-block" onclick="prevStep(3)">Back</button>
                            <button type="button" class="btn btn-primary btn-block" onclick="nextStep(3)">Next: Review</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 4: REVIEW & SUBMIT -->
            <div id="step-4" class="wizard-step" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <h2>Review & Submit</h2>
                        <p style="margin: 0.5rem 0 0; color: #666;">Confirm your issue report</p>
                    </div>

                    <div class="card-body">
                        <!-- Summary Cards -->
                        <div class="review-summary" style="display: flex; flex-direction: column; gap: 1rem;">
                            <div class="summary-item" style="background: #f5f5f5; padding: 1rem; border-radius: 8px;">
                                <p style="margin: 0; font-size: 0.9rem; color: #666;">Category & Type</p>
                                <h4 style="margin: 0.5rem 0 0; font-size: 1.1rem;" id="summaryCategory">Water – Stagnation</h4>
                            </div>

                            <div class="summary-item" style="background: #f5f5f5; padding: 1rem; border-radius: 8px;">
                                <p style="margin: 0; font-size: 0.9rem; color: #666;">Urgency Level</p>
                                <h4 style="margin: 0.5rem 0 0; font-size: 1.1rem;" id="summaryUrgency">Emergency</h4>
                            </div>

                            <div class="summary-item" style="background: #f5f5f5; padding: 1rem; border-radius: 8px;">
                                <p style="margin: 0; font-size: 0.9rem; color: #666;">Location</p>
                                <p style="margin: 0.5rem 0 0; font-size: 1rem;" id="summaryLocation">Building, Floor, Room details</p>
                            </div>

                            <div class="summary-item" style="background: #f5f5f5; padding: 1rem; border-radius: 8px;">
                                <p style="margin: 0; font-size: 0.9rem; color: #666;">Photo</p>
                                <div id="summaryImage" style="margin-top: 0.5rem;">No image attached</div>
                            </div>
                        </div>

                        <!-- Image upload (appears in Review step) -->
                        <div class="form-group" style="margin-top: 1rem;">
                            <label for="imageInput">Attach Photo <small style="color: #666; font-weight: 500;">(required)</small></label>
                            <input type="file" id="imageInput" accept="image/*">
                            <small id="imageHelp" class="text-muted">Max 5MB — please attach a clear photo of the issue.</small>
                            <div id="imagePreview" class="image-preview" style="margin-top: 0.75rem;">No image selected</div>
                        </div>

                        <div style="background: #e8f5ff; padding: 1rem; border-radius: 8px; margin-top: 1.5rem; border-left: 4px solid var(--primary-color);">
                            <p style="margin: 0; font-size: 0.9rem; color: #0b3a5a;">
                                <strong>📍 Location Verification:</strong> Geofencing has been removed — submissions are accepted after photo upload and review.
                            </p>
                        </div>

                        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                            <button type="button" class="btn btn-secondary btn-block" onclick="prevStep(4)">Back</button>
                            <button type="submit" class="btn btn-primary btn-block" id="submitBtn">Submit Report</button>
                        </div>
                    </div>
                </div>
            </div>

        </form>
    </main>

    <script src="/Sustain-U/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const categorySelect = document.getElementById('categorySelect');
            const typeSelect = document.getElementById('typeSelect');
            const customDescriptionGroup = document.getElementById('customDescriptionGroup');
            const customDescription = document.getElementById('customDescription');
            const step1NextBtn = document.getElementById('step1NextBtn');

            const imagePreview = document.getElementById('imagePreview');
            const categoryImagePreview = document.getElementById('categoryImagePreview');
            let selectedFile = null;

            function setSelectedImage(file) {
                if (!file) return false;
                if (!file.type || !file.type.startsWith('image/')) { alert('Please select an image'); return false; }
                if (file.size > 5 * 1024 * 1024) { alert('File too large (max 5MB)'); return false; }

                selectedFile = file;
                window.stepData = window.stepData || {};
                window.stepData.imageFile = file;

                const url = URL.createObjectURL(file);
                if (imagePreview) imagePreview.innerHTML = `<img src="${url}" alt="attached image" style="max-width:100%;height:auto;border-radius:12px;border:1px solid var(--border-color)">`;
                if (categoryImagePreview) categoryImagePreview.innerHTML = `<img src="${url}" alt="attached image" style="max-width:200px;height:auto;border-radius:8px;border:1px solid var(--border-color)">`;

                // clear image error if present
                const imgErr = document.getElementById('categoryImageError');
                if (imgErr) imgErr.textContent = '';

                updateReviewSummary();
                validateStep1();
                return true;
            }

            const reviewImageInput = document.getElementById('imageInput');
            if (reviewImageInput) reviewImageInput.addEventListener('change', (e) => setSelectedImage(e.target.files[0]));

            const categoryImageInput = document.getElementById('categoryImageInput');
            if (categoryImageInput) categoryImageInput.addEventListener('change', (e) => setSelectedImage(e.target.files[0]));

            // Category type options
            const typeOptions = {
                air: ['Emission', 'Odour', 'Others'],
                water: ['Leak', 'Stagnation', 'Quality', 'Drainage', 'Others'],
                waste: ['Spillage', 'Others']
            };

            // Helper function to validate Step 1
            function validateStep1() {
                const category = categorySelect.value;
                const type = typeSelect.value;
                const customDesc = customDescription.value.trim();
                const isOthers = type === 'others';

                const hasImage = Boolean(window.stepData && window.stepData.imageFile);

                const isValid = category && type && (!isOthers || customDesc) && hasImage;

                // Update image error state
                const imgErr = document.getElementById('categoryImageError');
                if (!hasImage) {
                    if (imgErr) imgErr.textContent = 'Please attach a photo to continue';
                } else {
                    if (imgErr) imgErr.textContent = '';
                }

                // Update button state
                step1NextBtn.disabled = !isValid;
                step1NextBtn.style.opacity = isValid ? '1' : '0.5';
                step1NextBtn.style.cursor = isValid ? 'pointer' : 'not-allowed';

                return isValid;
            }

            // Category select change event
            categorySelect.addEventListener('change', (e) => {
                const category = e.target.value;
                typeSelect.innerHTML = '<option value="">-- Select Type --</option>';
                customDescriptionGroup.style.display = 'none';
                customDescription.value = '';

                if (category && typeOptions[category]) {
                    typeOptions[category].forEach(type => {
                        const option = document.createElement('option');
                        option.value = type.toLowerCase().replace(/\s+/g, '_');
                        option.textContent = type;
                        typeSelect.appendChild(option);
                    });
                    typeSelect.style.display = 'block';
                } else {
                    typeSelect.style.display = 'none';
                }
                
                validateStep1();
            });

            // Type select change event
            typeSelect.addEventListener('change', (e) => {
                const isOthers = e.target.value === 'others';
                customDescriptionGroup.style.display = isOthers ? 'block' : 'none';
                if (!isOthers) {
                    customDescription.value = '';
                }
                
                validateStep1();
            });

            // Custom description input event
            customDescription.addEventListener('input', validateStep1);

            // Urgency card selection
            document.querySelectorAll('.urgency-card').forEach(card => {
                card.addEventListener('click', () => {
                    document.querySelectorAll('.urgency-card').forEach(c => c.classList.remove('selected'));
                    card.classList.add('selected');
                    card.querySelector('input[type="radio"]').checked = true;
                });
            });

            // Form submission
            document.getElementById('issueWizard').addEventListener('submit', async (e) => {
                e.preventDefault();
                await submitIssue();
            });
        });

        function getStepData(step) {
            const data = {};
            switch(step) {
                case 1:
                    data.category = document.getElementById('categorySelect').value;
                    data.type = document.getElementById('typeSelect').value;
                    data.customDescription = document.getElementById('customDescription').value;
                    return data;
                case 2:
                    data.building = document.getElementById('building').value.trim();
                    data.floor = document.getElementById('floor').value.trim();
                    data.room = document.getElementById('room').value.trim();
                    return data;
                case 3:
                    data.urgency = document.querySelector('input[name="urgency"]:checked')?.value;
                    return data;
            }
            return data;
        }

        function validateStep(step) {
            document.querySelectorAll('.error-message').forEach(e => e.textContent = '');
            const data = getStepData(step);

            switch(step) {
                case 1:
                    if (!data.category) {
                        document.getElementById('typeError').textContent = 'Please select a category';
                        return false;
                    }
                    if (!data.type) {
                        document.getElementById('typeError').textContent = 'Please select a type';
                        return false;
                    }
                    if (data.type === 'others' && !data.customDescription.trim()) {
                        document.getElementById('customError').textContent = 'Please describe the issue';
                        return false;
                    }
                    return true;
                case 2:
                    if (!data.building) {
                        document.getElementById('buildingError').textContent = 'Building name is required';
                        return false;
                    }
                    if (!data.floor) {
                        document.getElementById('floorError').textContent = 'Floor number is required';
                        return false;
                    }
                    if (!data.room) {
                        document.getElementById('roomError').textContent = 'Room/Area description is required';
                        return false;
                    }
                    return true;
                case 3:
                    if (!data.urgency) {
                        document.getElementById('urgencyError').textContent = 'Please select an urgency level';
                        return false;
                    }
                    return true;
            }
            return true;
        }

        function nextStep(currentStep) {
            if (!validateStep(currentStep)) return;

            const data = getStepData(currentStep);

            if (currentStep === 1) {
                window.stepData = window.stepData || {};
                window.stepData.category = data.category;
                window.stepData.type = data.type;
                window.stepData.customDescription = data.customDescription;
            } else if (currentStep === 2) {
                window.stepData.building = data.building;
                window.stepData.floor = data.floor;
                window.stepData.room = data.room;
            } else if (currentStep === 3) {
                window.stepData.urgency = data.urgency;
                updateReviewSummary();
            }

            // Update progress
            document.querySelectorAll('.progress-step').forEach((step, idx) => {
                if (idx < currentStep) step.classList.add('completed');
                if (idx === currentStep) step.classList.add('active');
                if (idx > currentStep) step.classList.remove('active', 'completed');
            });

            // Show next step
            document.querySelectorAll('.wizard-step').forEach(s => s.style.display = 'none');
            document.getElementById(`step-${currentStep + 1}`).style.display = 'block';
            window.scrollTo(0, 0);
        }

        function prevStep(currentStep) {
            document.querySelectorAll('.wizard-step').forEach(s => s.style.display = 'none');
            document.getElementById(`step-${currentStep - 1}`).style.display = 'block';

            // Update progress
            document.querySelectorAll('.progress-step').forEach((step, idx) => {
                if (idx < currentStep - 1) step.classList.add('completed');
                if (idx === currentStep - 2) step.classList.add('active');
                if (idx >= currentStep - 1) step.classList.remove('active');
            });

            window.scrollTo(0, 0);
        }

        function updateReviewSummary() {
            const data = window.stepData;
            
            // Format category and type
            const category = document.getElementById('categorySelect').value.charAt(0).toUpperCase() + 
                           document.getElementById('categorySelect').value.slice(1);
            const typeOption = document.querySelector(`#typeSelect option[value="${data.type}"]`);
            const type = typeOption ? typeOption.textContent : data.type;
            document.getElementById('summaryCategory').textContent = `${category} – ${type}`;

            // Format urgency
            const urgencyLabels = {
                'can_wait': 'Can Wait',
                'needs_attention': 'Needs Attention',
                'emergency': 'Emergency'
            };
            document.getElementById('summaryUrgency').textContent = urgencyLabels[data.urgency] || data.urgency;

            // Format location
            document.getElementById('summaryLocation').textContent = `${data.building}, ${data.floor}, ${data.room}`;

            // Image preview in summary (uses the file chosen in Review step)
            const summaryImage = document.getElementById('summaryImage');
            if (summaryImage) {
                const previewEl = document.getElementById('imagePreview');
                if (window.stepData?.imageFile && previewEl && previewEl.innerHTML.trim()) {
                    summaryImage.innerHTML = previewEl.innerHTML;
                } else {
                    summaryImage.textContent = 'No image attached';
                }
            }
        }

        async function submitIssue() {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            try {
                const data = window.stepData;

                // Build form data (no geofence check)
                const formData = new FormData();
                formData.append('category', data.category);
                formData.append('type', data.type);
                formData.append('custom_description', data.customDescription || '');
                formData.append('building', data.building);
                formData.append('floor', data.floor);
                formData.append('room', data.room);
                formData.append('urgency', data.urgency);

                // Attach image (required by server)
                if (!window.stepData?.imageFile) {
                    alert('Please attach a photo of the issue before submitting.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Report';
                    return;
                }
                formData.append('image', window.stepData.imageFile, window.stepData.imageFile.name);

                // Submit to API
                const submitRes = await fetch('/Sustain-U/api/submit_issue.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });
                const result = await submitRes.json();

                if (result.success) {
                    alert('Issue submitted successfully!');
                    window.location.href = '/Sustain-U/my_works.php';
                    return;
                }

                alert(result.message || 'Submission failed');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Report';
            } catch (err) {
                console.error(err);
                alert('Submission error. Please try again.');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Report';
            }
        }
    </script>
</body>
</html>
