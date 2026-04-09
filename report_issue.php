<?php
/**
 * SUSTAIN-U - Report Issue Page
 * 4-Step wizard: Category → Location → Urgency → Review & Submit
 */
require_once 'config.php';
require_once 'api/db.php';
requireLogin();

if (isAdmin()) {
    header('Location: admin_dashboard.php');
    exit;
}

// require complete profile before reporting
if (!check_profile_completion($conn, $_SESSION['user_id'])) {
    header('Location: complete_profile.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Issue - Sustain-U</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<style>
        .selection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 1rem;
            margin-top: 0.5rem;
        }
        .selection-card {
            background: #fff;
            border: 2px solid #eee;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-color);
        }
        .selection-card:hover {
            border-color: var(--primary-color);
            background: #f8fbff;
        }
        .selection-card.selected {
            border-color: var(--primary-color);
            background: #eef4ff;
            color: var(--primary-color);
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(26, 115, 232, 0.15);
        }
        .selection-card .icon {
            font-size: 1.5rem;
        }
        .selection-card .label {
            font-size: 0.9rem;
        }
        
        /* AI Validation Styles */
        .ai-status-container {
            margin-top: 10px;
            padding: 12px;
            border-radius: 10px;
            background: rgba(26, 115, 232, 0.05);
            border: 1px solid rgba(26, 115, 232, 0.1);
            display: none;
            position: relative;
            overflow: hidden;
        }
        .ai-status-container.active {
            display: block;
        }
        .ai-loader {
            width: 100%;
            height: 4px;
            background: #eee;
            border-radius: 2px;
            position: relative;
            margin-bottom: 8px;
        }
        .ai-loader-bar {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            background: var(--primary-color);
            border-radius: 2px;
            width: 0%;
            transition: width 0.3s ease;
        }
        .ai-status-text {
            font-size: 0.85rem;
            color: #555;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .ai-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: auto;
        }
        .ai-badge-success { background: #e6f4ea; color: #1e8e3e; }
        .ai-badge-warn { background: #fef7e0; color: #b06000; }
        .ai-badge-error { background: #fce8e6; color: #d93025; }
        
        /* Scanning Animation */
        .scanning-container {
            position: relative;
            overflow: hidden;
            display: inline-block;
            border-radius: 12px;
        }
        .scanning-container::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--primary-color);
            box-shadow: 0 0 10px var(--primary-color);
            animation: scanning 2s infinite;
            display: none;
            z-index: 10;
        }
        .scanning-container.active::after {
            display: block;
        }
        @keyframes scanning {
            0% { top: 0; }
            100% { top: 100%; }
        }
    </style>
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
                            <label>Issue Category *</label>
                            <div class="selection-grid" id="categoryGrid">
                                <button type="button" class="selection-card" data-category="air">
                                    <span class="label">Air Issue</span>
                                </button>
                                <button type="button" class="selection-card" data-category="water">
                                    <span class="label">Water Issue</span>
                                </button>
                                <button type="button" class="selection-card" data-category="waste">
                                    <span class="label">Waste</span>
                                </button>
                            </div>
                            <input type="hidden" id="categorySelect" required>
                        </div>

                        <div class="form-group" id="typeGroup" style="display: none;">
                            <label>Select Type *</label>
                            <div class="selection-grid" id="typeGrid">
                                <!-- Type buttons injected via JS -->
                            </div>
                            <input type="hidden" id="typeSelect" required>
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
                            <div class="image-wrapper" style="position: relative; margin-top: 0.75rem; max-width: 220px;">
                                <div id="categoryImagePreview" class="image-preview" style="min-height: 100px; display: flex; align-items: center; justify-content: center; background: #f9f9f9; border-radius: 12px; border: 2px dashed #ddd; overflow: hidden; position: relative;">
                                    <span style="color: #999;">No image selected</span>
                                </div>
                            </div>
                            <!-- AI Status UI -->
                            <div id="aiStatusContainer" class="ai-status-container">
                                <div class="ai-loader">
                                    <div id="aiProgressBar" class="ai-loader-bar"></div>
                                </div>
                                <div class="ai-status-text">
                                    <span id="aiStatusIcon">🔍</span>
                                    <span id="aiStatusLabel">Analyzing image relevance...</span>
                                    <span id="aiBadge" class="ai-badge" style="display: none;">AI Verified</span>
                                </div>
                                <div id="aiDetails" style="font-size: 0.75rem; color: #777; margin-top: 5px; display: none;"></div>
                            </div>
                        </div>

                        <button type="button" id="step1NextBtn" class="btn btn-primary btn-block" style="margin-top: 1.5rem; opacity: 0.5;" disabled>Next: Location</button>
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
                            <button type="button" id="step2BackBtn" class="btn btn-secondary btn-block">Back</button>
                            <button type="button" id="step2NextBtn" class="btn btn-primary btn-block" style="opacity: 0.5;" disabled>Next: Urgency</button>
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
                                    <div class="urgency-icon"></div>
                                    <div>
                                        <h4 style="margin: 0;">Can Wait</h4>
                                        <p style="margin: 0.25rem 0 0; font-size: 0.9rem; color: #666;">Minor issue, not affecting daily activities</p>
                                    </div>
                                </div>
                            </label>

                            <label class="urgency-card" data-urgency="needs_attention">
                                <input type="radio" name="urgency" value="needs_attention" hidden>
                                <div class="urgency-content">
                                    <div class="urgency-icon"></div>
                                    <div>
                                        <h4 style="margin: 0;">Needs Attention</h4>
                                        <p style="margin: 0.25rem 0 0; font-size: 0.9rem; color: #666;">Should be fixed soon</p>
                                    </div>
                                </div>
                            </label>

                            <label class="urgency-card" data-urgency="emergency">
                                <input type="radio" name="urgency" value="emergency" hidden>
                                <div class="urgency-content">
                                    <div class="urgency-icon"></div>
                                    <div>
                                        <h4 style="margin: 0;">Emergency</h4>
                                        <p style="margin: 0.25rem 0 0; font-size: 0.9rem; color: #666;">Requires immediate action</p>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <small id="urgencyError" class="error-message" style="display: block; margin-top: 1rem;"></small>

                        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                            <button type="button" id="step3BackBtn" class="btn btn-secondary btn-block">Back</button>
                            <button type="button" id="step3NextBtn" class="btn btn-primary btn-block" style="opacity: 0.5;" disabled>Next: Review</button>
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

                        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                            <button type="button" id="step4BackBtn" class="btn btn-secondary btn-block">Back</button>
                            <button type="submit" class="btn btn-primary btn-block" id="submitBtn">Submit Report</button>
                        </div>
                    </div>
                </div>
            </div>

        </form>
    </main>



    <script src="js/main.js"></script>
    <script>
        // Ensure stepData is initialized early
        window.stepData = {
            category: '',
            type: '',
            customDescription: '',
            building: '',
            floor: '',
            room: '',
            urgency: 'can_wait',
            imageFile: null,
            isAiVerified: false
        };

        document.addEventListener('DOMContentLoaded', () => {
            const categorySelect = document.getElementById('categorySelect');
            const typeInput = document.getElementById('typeSelect');
            const typeGroup = document.getElementById('typeGroup');
            const typeGrid = document.getElementById('typeGrid');
            const customDescriptionGroup = document.getElementById('customDescriptionGroup');
            const customDescription = document.getElementById('customDescription');
            const step1NextBtn = document.getElementById('step1NextBtn');

            const imagePreview = document.getElementById('imagePreview');
            const categoryImagePreview = document.getElementById('categoryImagePreview');

            async function submitIssue() {
                const submitBtn = document.getElementById('submitBtn');
                if (!submitBtn) return;
                
                submitBtn.disabled = true;
                submitBtn.textContent = 'Submitting...';

                try {
                    const data = window.stepData;

                    const formData = new FormData();
                    formData.append('category', data.category);
                    formData.append('type', data.type);
                    formData.append('custom_description', data.customDescription || '');
                    formData.append('building', data.building);
                    formData.append('floor', data.floor);
                    formData.append('room', data.room);
                    formData.append('urgency', data.urgency);

                    if (!data.imageFile) {
                        alert('Please attach a photo of the issue.');
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Submit Report';
                        return;
                    }
                    formData.append('image', data.imageFile, data.imageFile.name);

                    const response = await fetch('api/submit_issue.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: formData
                    });
                    
                    const result = await response.json();

                    if (result.success) {
                        alert('Issue submitted successfully!');
                        window.location.href = 'my_works.php';
                        return;
                    }

                    alert(result.message || 'Submission failed');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Report';
                } catch (err) {
                    console.error('Submission error:', err);
                    alert('An error occurred during submission. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Report';
                }
            }

            async function runDeepAudit(imgElement) {
                console.log("🛡️ Starting AI Audit...");
                const feedbackContainer = document.getElementById('categoryImagePreview').parentElement;
                let aiFeedback = document.getElementById('aiFeedback');
                if (!aiFeedback) {
                    aiFeedback = document.createElement('div');
                    aiFeedback.id = 'aiFeedback';
                    aiFeedback.style.marginTop = '10px';
                    aiFeedback.style.fontSize = '0.9rem';
                    aiFeedback.style.fontWeight = '600';
                    feedbackContainer.appendChild(aiFeedback);
                }

                const scanBoxes = document.querySelectorAll('.scanning-container');
                scanBoxes.forEach(box => box.classList.add('active'));

                // [1/1] Server-Side Safety & Relevance Audit (The Unified Source of Truth)
                try {
                    aiFeedback.innerHTML = "🛡️ [1/1] Secure Server-Side Audit: Scanning for Human, NSFW, and Category Relevance...";
                    aiFeedback.style.color = "#1a73e8";
                    
                    const formData = new FormData();
                    formData.append('image', window.stepData.imageFile);
                    
                    const response = await fetch('api/validate_image.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    scanBoxes.forEach(box => box.classList.remove('active'));

                    if (response.ok) {
                        const result = await response.json();
                        console.log("AI Server Response:", result);
                        
                        // Handle Rejection
                        if (result && result.status === 'REJECTED') {
                            return rejectAudit(`❌ REJECTED: ${result.reason || 'Safety check failed'}`);
                        }

                        // Success Case
                        if (result && (result.prediction || result.status === 'APPROVED' || result.status === 'success' || result.status === 'BYPASS')) {
                            const finalClass = result.prediction || 'verified';
                            const confidence = result.confidence ? Math.round(result.confidence * 100) : null;
                            const userCategory = window.stepData.category;

                            // Validate vs User Selection if prediction is available
                            if (result.prediction && userCategory && result.prediction !== userCategory && !(result.prediction === 'waste' && userCategory === 'waste')) {
                                let msg = `❌ REJECTED: You selected ${userCategory.toUpperCase()}, but our AI identified this as ${result.prediction.toUpperCase()}.`;
                                return rejectAudit(msg);
                            }

                             const reasonText = (result.reason && result.reason !== 'Image verified') ? ` - ${result.reason}` : "";
                             aiFeedback.innerHTML = `✅ VERIFIED: Server AI confirmed ${finalClass.toUpperCase()}${confidence ? ' ('+confidence+'% confidence)' : ''}${reasonText}`;
                            aiFeedback.style.color = "#1e8e3e";
                            window.stepData.isAiVerified = true;
                            validateStep1();
                        }
                    } else {
                        throw new Error("Validation service unreachable");
                    }
                } catch (err) {
                    console.error("Audit Error", err);
                    scanBoxes.forEach(box => box.classList.remove('active'));
                    // Provide detailed error feedback for better mobile debugging
                    aiFeedback.innerHTML = `⚠️ Audit Bypass: ${err.message || "Unknown error"}. Proceeding with manual review.`;
                    aiFeedback.style.color = "#FF9800";
                    window.stepData.isAiVerified = true; // Allow bypass for UX
                    validateStep1();
                }
            }

            function rejectAudit(msg) {
                const aiFeedback = document.getElementById('aiFeedback');
                const scanBoxes = document.querySelectorAll('.scanning-container');
                scanBoxes.forEach(box => box.classList.remove('active'));
                
                // Show rejection message
                aiFeedback.innerHTML = msg;
                aiFeedback.style.color = "#d93025";
                
                window.stepData.imageFile = null;
                window.stepData.isAiVerified = false;
                
                // Disable button
                const step1NextBtn = document.getElementById('step1NextBtn');
                if (step1NextBtn) {
                    step1NextBtn.disabled = true;
                    step1NextBtn.style.opacity = '0.5';
                }
                validateStep1();
            }

            function setSelectedImage(file) {
                if (!file) return false;
                if (!file.type || !file.type.startsWith('image/')) { 
                    alert('Please select an image file'); 
                    return false; 
                }
                if (file.size > 20 * 1024 * 1024) { 
                    alert('File too large (max 20MB). Please use a smaller photo.'); 
                    return false; 
                }

                window.stepData.imageFile = file;
                window.stepData.isAiVerified = false; // Reset on new image selection
                const url = URL.createObjectURL(file);

                // Update Previews
                if (imagePreview) {
                    imagePreview.innerHTML = `
                        <div class="scanning-container" id="reviewScanBox">
                            <img id="aiTarget" src="${url}" style="max-width:100%; height:auto;">
                        </div>`;
                }
                if (categoryImagePreview) {
                    categoryImagePreview.innerHTML = `
                        <div class="scanning-container" id="categoryScanBox">
                            <img id="categoryAiTarget" src="${url}" style="max-width:200px; height:auto;">
                        </div>`;
                }

                // Trigger Audit
                const aiImg = document.getElementById('categoryAiTarget') || document.getElementById('aiTarget');
                if (aiImg) {
                    if (aiImg.complete) {
                        runDeepAudit(aiImg);
                    } else {
                        aiImg.onload = () => runDeepAudit(aiImg);
                    }
                }

                updateReviewSummary();
                validateStep1();
                return true;
            }

            const reviewImageInput = document.getElementById('imageInput');
            if (reviewImageInput) {
                reviewImageInput.addEventListener('change', (e) => {
                    if (e.target.files && e.target.files[0]) {
                        setSelectedImage(e.target.files[0]);
                    }
                });
            }

            const categoryImageInput = document.getElementById('categoryImageInput');
            if (categoryImageInput) {
                categoryImageInput.addEventListener('change', (e) => {
                    if (e.target.files && e.target.files[0]) {
                        setSelectedImage(e.target.files[0]);
                    }
                });
            }
    
            // Category type options
            const typeOptions = {
                air: [
                    { value: 'emission', label: 'Emission' },
                    { value: 'odour', label: 'Odour' },
                    { value: 'others', label: 'Others' }
                ],
                water: [
                    { value: 'leak', label: 'Leak' },
                    { value: 'stagnation', label: 'Stagnation' },
                    { value: 'quality', label: 'Quality' },
                    { value: 'drainage', label: 'Drainage' },
                    { value: 'others', label: 'Others' }
                ],
                waste: [
                    { value: 'spillage', label: 'Spillage' },
                    { value: 'others', label: 'Others' }
                ]
            };

            // Helper function to validate Step 1
            function validateStep1() {
                const category = categorySelect.value;
                const type = typeInput.value;
                const customDesc = customDescription.value.trim();
                const isOthers = (type === 'others');
                const hasImage = !!window.stepData.imageFile;
                const isAiVerified = !!window.stepData.isAiVerified;

                const isValid = (category && type && (!isOthers || customDesc) && hasImage && isAiVerified);

                // Update image error state
                const imgErr = document.getElementById('categoryImageError');
                if (!hasImage) {
                    if (imgErr) imgErr.textContent = 'Please attach a photo to continue';
                } else {
                    if (imgErr) imgErr.textContent = '';
                }

                // Update button state
                if (step1NextBtn) {
                    step1NextBtn.disabled = !isValid;
                    step1NextBtn.style.opacity = isValid ? '1' : '0.5';
                    step1NextBtn.style.cursor = isValid ? 'pointer' : 'not-allowed';
                }

                return isValid;
            }

            // Handle Category Selection
            document.querySelectorAll('#categoryGrid .selection-card').forEach(card => {
                card.addEventListener('click', () => {
                    document.querySelectorAll('#categoryGrid .selection-card').forEach(c => c.classList.remove('selected'));
                    card.classList.add('selected');

                    const category = card.dataset.category;
                    categorySelect.value = category;
                    window.stepData.category = category;

                    // Reset type
                    typeInput.value = '';
                    window.stepData.type = '';
                    customDescriptionGroup.style.display = 'none';
                    customDescription.value = '';
                    window.stepData.customDescription = '';

                    // Reset AI verification on category change
                    window.stepData.isAiVerified = false;
                    const aiStatus = document.getElementById('aiFeedback');
                    if (aiStatus) {
                        aiStatus.innerHTML = '⚠️ Category changed. Re-verifying image...';
                        aiStatus.style.color = '#777';
                    }

                    // Automatically re-run AI audit if an image is already selected
                    const aiImg = document.getElementById('categoryAiTarget') || document.getElementById('aiTarget');
                    if (aiImg && window.stepData.imageFile) {
                        runDeepAudit(aiImg);
                    }

                    // Populate Type Grid
                    typeGrid.innerHTML = '';
                    if (category && typeOptions[category]) {
                        typeOptions[category].forEach(opt => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'selection-card';
                            btn.dataset.type = opt.value;
                            btn.innerHTML = `<span class="label">${opt.label}</span>`;
                            
                            btn.addEventListener('click', () => {
                                document.querySelectorAll('#typeGrid .selection-card').forEach(c => c.classList.remove('selected'));
                                btn.classList.add('selected');
                                typeInput.value = opt.value;
                                window.stepData.type = opt.value;
                                
                                const isOthers = (opt.value === 'others');
                                customDescriptionGroup.style.display = isOthers ? 'block' : 'none';
                                if (!isOthers) {
                                    customDescription.value = '';
                                    window.stepData.customDescription = '';
                                }
                                
                                validateStep1();
                            });
                            
                            typeGrid.appendChild(btn);
                        });
                        typeGroup.style.display = 'block';
                    } else {
                        typeGroup.style.display = 'none';
                    }

                    validateStep1();
                });
            });

            // Custom description input event
            customDescription.addEventListener('input', () => {
                window.stepData.customDescription = customDescription.value;
                validateStep1();
            });

            // Urgency card selection
            document.querySelectorAll('.urgency-card').forEach(card => {
                card.addEventListener('click', () => {
                    document.querySelectorAll('.urgency-card').forEach(c => c.classList.remove('selected'));
                    card.classList.add('selected');
                    const radio = card.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.checked = true;
                        window.stepData.urgency = radio.value;
                    }
                    validateStep3();
                });
            });

            // Location listeners
            const locationInputs = ['building', 'floor', 'room'];
            locationInputs.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', () => {
                        window.stepData[id] = el.value.trim();
                        validateStep2();
                    });
                }
            });

            function validateStep2() {
                const building = document.getElementById('building').value.trim();
                const floor = document.getElementById('floor').value.trim();
                const room = document.getElementById('room').value.trim();
                const isValid = (building && floor && room);
                const btn = document.getElementById('step2NextBtn');
                if (btn) {
                    btn.disabled = !isValid;
                    btn.style.opacity = isValid ? '1' : '0.5';
                    btn.style.cursor = isValid ? 'pointer' : 'not-allowed';
                }
                return isValid;
            }

            function validateStep3() {
                const checkedUrgency = document.querySelector('input[name="urgency"]:checked');
                const isValid = !!checkedUrgency;
                const btn = document.getElementById('step3NextBtn');
                if (btn) {
                    btn.disabled = !isValid;
                    btn.style.opacity = isValid ? '1' : '0.5';
                    btn.style.cursor = isValid ? 'pointer' : 'not-allowed';
                }
                return isValid;
            }

            // Handle Next/Back button clicks
            document.getElementById('step1NextBtn').addEventListener('click', () => nextStep(1));
            
            const step2BackBtn = document.getElementById('step2BackBtn');
            if (step2BackBtn) step2BackBtn.addEventListener('click', () => prevStep(2));
            const step2NextBtn = document.getElementById('step2NextBtn');
            if (step2NextBtn) step2NextBtn.addEventListener('click', () => nextStep(2));
            
            const step3BackBtn = document.getElementById('step3BackBtn');
            if (step3BackBtn) step3BackBtn.addEventListener('click', () => prevStep(3));
            const step3NextBtn = document.getElementById('step3NextBtn');
            if (step3NextBtn) step3NextBtn.addEventListener('click', () => nextStep(3));
            
            const step4BackBtn = document.getElementById('step4BackBtn');
            if (step4BackBtn) step4BackBtn.addEventListener('click', () => prevStep(4));

            function getStepData(step) {
                const data = {};
                switch(step) {
                    case 1:
                        data.category = document.getElementById('categorySelect').value;
                        data.type = document.getElementById('typeSelect').value;
                        data.customDescription = document.getElementById('customDescription').value;
                        break;
                    case 2:
                        data.building = document.getElementById('building').value.trim();
                        data.floor = document.getElementById('floor').value.trim();
                        data.room = document.getElementById('room').value.trim();
                        break;
                    case 3:
                        const checkedUrgency = document.querySelector('input[name="urgency"]:checked');
                        data.urgency = checkedUrgency ? checkedUrgency.value : 'can_wait';
                        break;
                }
                return data;
            }

            function validateStep(step) {
                if (step === 1) return validateStep1();
                if (step === 2) return validateStep2();
                if (step === 3) return validateStep3();
                return true;
            }

            function nextStep(currentStep) {
                if (!validateStep(currentStep)) {
                    return;
                }

                const data = getStepData(currentStep);

                if (currentStep === 1) {
                    window.stepData.category = data.category;
                    window.stepData.type = data.type;
                    window.stepData.customDescription = data.customDescription;
                } else if (currentStep === 2) {
                    window.stepData.building = data.building;
                    window.stepData.floor = data.floor;
                    window.stepData.room = data.room;
                } else if (currentStep === 3) {
                    window.stepData.urgency = data.urgency;
                }

                if (currentStep === 3) {
                    updateReviewSummary();
                }

                const steps = document.querySelectorAll('.progress-step');
                steps.forEach((step, idx) => {
                    const stepNum = parseInt(step.dataset.step);
                    if (stepNum <= currentStep) step.classList.add('completed');
                    if (stepNum === currentStep + 1) step.classList.add('active');
                    else step.classList.remove('active');
                });

                document.querySelectorAll('.wizard-step').forEach(s => s.style.display = 'none');
                const nextStepEl = document.getElementById(`step-${currentStep + 1}`);
                if (nextStepEl) nextStepEl.style.display = 'block';
                
                window.scrollTo(0, 0);
            }

            function prevStep(currentStep) {
                document.querySelectorAll('.wizard-step').forEach(s => s.style.display = 'none');
                const prevStepEl = document.getElementById(`step-${currentStep - 1}`);
                if (prevStepEl) prevStepEl.style.display = 'block';

                const steps = document.querySelectorAll('.progress-step');
                steps.forEach((step, idx) => {
                    const stepNum = parseInt(step.dataset.step);
                    if (stepNum < currentStep - 1) step.classList.add('completed');
                    else step.classList.remove('completed');
                    
                    if (stepNum === currentStep - 1) step.classList.add('active');
                    else step.classList.remove('active');
                });

                window.scrollTo(0, 0);
            }

            function updateReviewSummary() {
                const data = window.stepData;
                let categoryText = data.category ? (data.category.charAt(0).toUpperCase() + data.category.slice(1)) : 'Not selected';
                let typeText = data.type ? (data.type.charAt(0).toUpperCase() + data.type.slice(1).replace(/_/g, ' ')) : '';
                
                const summaryCat = document.getElementById('summaryCategory');
                if (summaryCat) {
                    summaryCat.textContent = typeText ? `${categoryText} – ${typeText}` : categoryText;
                }

                const urgencyLabels = {
                    'can_wait': 'Can Wait',
                    'needs_attention': 'Needs Attention',
                    'emergency': 'Emergency'
                };
                const summaryUrg = document.getElementById('summaryUrgency');
                if (summaryUrg) {
                    summaryUrg.textContent = urgencyLabels[data.urgency] || data.urgency || 'Not selected';
                }

                const summaryLoc = document.getElementById('summaryLocation');
                if (summaryLoc) {
                    summaryLoc.textContent = (data.building || data.floor || data.room) 
                        ? `${data.building || ''}, ${data.floor || ''}, ${data.room || ''}`.replace(/^,\s*/, '')
                        : 'Not provided';
                }

                const summaryImgContainer = document.getElementById('summaryImage');
                if (summaryImgContainer) {
                    if (data.imageFile) {
                        const url = URL.createObjectURL(data.imageFile);
                        summaryImgContainer.innerHTML = `<img src="${url}" alt="attached" style="max-width:200px; height:auto; border-radius:8px; border:1px solid #ddd">`;
                    } else {
                        summaryImgContainer.textContent = 'No image attached';
                    }
                }
            }

            // Form submission
            const form = document.getElementById('issueWizard');
            if (form) {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    await submitIssue();
                });
            }
        });





    </script>
    <?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
