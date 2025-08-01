@extends('layouts.app')
@section('title', 'Create Client')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">CREATE CLIENT</h1>
        <p class="text-muted mb-0">Add new client to the system</p>
    </div>
    <div>
        <a href="{{ route('admin.clients.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Clients
        </a>
    </div>
</div>

<!-- Main Form Card -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.clients.store') }}" id="client-form">
            @csrf

            <!-- Basic Company Information -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <h5 class="text-primary mb-3">
                        <i class="fas fa-building"></i> Company Information
                    </h5>
                </div>

                <div class="col-md-6">
                    <label for="company_name" class="form-label fw-bold">
                        Company Name <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           id="company_name"
                           name="company_name"
                           class="form-control @error('company_name') is-invalid @enderror"
                           value="{{ old('company_name') }}"
                           required
                           placeholder="e.g., ABC Company">
                    @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label for="address" class="form-label fw-bold">Address</label>
                    <input type="text"
                           id="address"
                           name="address"
                           class="form-control @error('address') is-invalid @enderror"
                           value="{{ old('address') }}"
                           placeholder="e.g., 123 HV Dela Costa Salcedo Village Makati">
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label for="sec_registration" class="form-label fw-bold">SEC Registration</label>
                    <input type="text"
                           id="sec_registration"
                           name="sec_registration"
                           class="form-control @error('sec_registration') is-invalid @enderror"
                           value="{{ old('sec_registration') }}"
                           placeholder="e.g., ABC-000-0000">
                    @error('sec_registration')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label for="tax_identification" class="form-label fw-bold">Tax Identification Number</label>
                    <input type="text"
                           id="tax_identification"
                           name="tax_identification"
                           class="form-control @error('tax_identification') is-invalid @enderror"
                           value="{{ old('tax_identification') }}"
                           placeholder="e.g., 000-000-001">
                    @error('tax_identification')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <!-- Contact Person 1 Section -->
            <hr class="my-4">
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <h5 class="text-primary mb-3">
                        <i class="fas fa-user"></i> Contact Person 1
                    </h5>
                </div>

                <div class="col-md-4">
                    <label for="contact_person_1" class="form-label fw-bold">
                        Contact Person 1 <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           id="contact_person_1"
                           name="contact_person"
                           class="form-control @error('contact_person') is-invalid @enderror"
                           value="{{ old('contact_person') }}"
                           required
                           placeholder="Full name">
                    @error('contact_person')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label for="contact_number_1" class="form-label fw-bold">Contact Number 1</label>
                    <input type="text"
                           id="contact_number_1"
                           name="phone"
                           class="form-control @error('phone') is-invalid @enderror"
                           value="{{ old('phone') }}"
                           placeholder="e.g., +63 912 345 6789">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label for="email_1" class="form-label fw-bold">
                        Email Address 1 <span class="text-danger">*</span>
                    </label>
                    <input type="email"
                           id="email_1"
                           name="email"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}"
                           required
                           placeholder="contact@company.com">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <!-- Contact Person 2 Section -->
            <hr class="my-4">
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <h5 class="text-primary mb-3">
                        <i class="fas fa-user-plus"></i> Contact Person 2 <small class="text-muted">(Optional)</small>
                    </h5>
                </div>

                <div class="col-md-4">
                    <label for="contact_person_2" class="form-label fw-bold">Contact Person 2</label>
                    <input type="text"
                           id="contact_person_2"
                           name="contact_person_2"
                           class="form-control @error('contact_person_2') is-invalid @enderror"
                           value="{{ old('contact_person_2') }}"
                           placeholder="Full name (optional)">
                    @error('contact_person_2')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label for="contact_number_2" class="form-label fw-bold">Contact Number 2</label>
                    <input type="text"
                           id="contact_number_2"
                           name="contact_number_2"
                           class="form-control @error('contact_number_2') is-invalid @enderror"
                           value="{{ old('contact_number_2') }}"
                           placeholder="e.g., +63 912 345 6789">
                    @error('contact_number_2')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label for="email_2" class="form-label fw-bold">Email Address 2</label>
                    <input type="email"
                           id="email_2"
                           name="email_2"
                           class="form-control @error('email_2') is-invalid @enderror"
                           value="{{ old('email_2') }}"
                           placeholder="contact2@company.com">
                    @error('email_2')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <!-- Login Credentials Section -->
            <hr class="my-4">
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <h5 class="text-primary mb-3">
                        <i class="fas fa-key"></i> Login Credentials
                    </h5>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> These credentials will be used by the client to access the system.
                        The system will create a user account using the primary contact person's name and email.
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="name" class="form-label fw-bold">
                        Full Name <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}"
                           required
                           placeholder="User's full name for login">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">This will be auto-filled from Contact Person 1</div>
                </div>

                <div class="col-md-6">
                    <label for="password" class="form-label fw-bold">
                        Password <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <input type="password"
                               id="password"
                               name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               required
                               minlength="8"
                               placeholder="Enter secure password">
                        <button type="button" class="btn btn-outline-secondary" id="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Minimum 8 characters required</div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label for="password_confirmation" class="form-label fw-bold">
                        Confirm Password <span class="text-danger">*</span>
                    </label>
                    <input type="password"
                           id="password_confirmation"
                           name="password_confirmation"
                           class="form-control @error('password_confirmation') is-invalid @enderror"
                           required
                           placeholder="Confirm password">
                    @error('password_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Account Status</label>
                    <div class="form-control-plaintext">
                        <span class="badge bg-success fs-6">
                            <i class="fas fa-check-circle"></i> Active
                        </span>
                        <small class="text-muted ms-2">Client will be able to login immediately</small>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <hr class="my-4">
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <h5 class="text-primary mb-3">
                        <i class="fas fa-info-circle"></i> Additional Information
                    </h5>
                </div>

                <div class="col-md-12">
                    <label for="notes" class="form-label fw-bold">Notes</label>
                    <textarea id="notes"
                              name="notes"
                              class="form-control @error('notes') is-invalid @enderror"
                              rows="3"
                              placeholder="Optional notes about this client...">{{ old('notes') }}</textarea>
                    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <!-- Action Buttons -->
            <hr class="my-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    <i class="fas fa-shield-alt"></i>
                    All client data is securely stored and encrypted
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.clients.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary" id="save-btn">
                        <i class="fas fa-save"></i> Save Client
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@section('styles')
<style>
/* Form styling improvements */
.form-label.fw-bold {
    color: #495057;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.form-control, .form-select {
    border-radius: 0.375rem;
    border: 1px solid #ced4da;
    padding: 0.75rem;
}

.form-control:focus, .form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Card enhancements */
.card {
    border-radius: 0.5rem;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
}

.card-body {
    padding: 2rem;
}

/* Section headers */
h5.text-primary {
    color: #007bff !important;
    font-weight: 600;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 0.5rem;
}

h5.text-primary .fas {
    width: 20px;
    text-align: center;
    margin-right: 0.5rem;
}

/* Alert styling */
.alert.alert-info {
    background-color: #e3f2fd;
    border-color: #bbdefb;
    color: #1976d2;
    border-radius: 0.375rem;
}

/* Button enhancements */
.btn {
    font-weight: 500;
    border-radius: 0.375rem;
    padding: 0.75rem 1.5rem;
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.btn-primary {
    background-color: #007bff;
    border-color: #007bff;
}

.btn-outline-secondary {
    border-color: #6c757d;
    color: #6c757d;
}

.btn-outline-secondary:hover {
    background-color: #6c757d;
    border-color: #6c757d;
}

/* Input group styling */
.input-group .btn {
    border-left: none;
    padding: 0.75rem;
}

.input-group .form-control:focus {
    z-index: 2;
}

/* Form text styling */
.form-text {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

/* Badge styling */
.badge.fs-6 {
    font-size: 0.9rem !important;
    padding: 0.5rem 0.75rem;
}

/* Required asterisk */
.text-danger {
    color: #dc3545 !important;
}

/* Validation states */
.is-valid {
    border-color: #28a745;
}

.is-invalid {
    border-color: #dc3545;
}

.valid-feedback {
    color: #28a745;
}

.invalid-feedback {
    color: #dc3545;
    font-size: 0.8rem;
}

/* Row spacing */
.row.g-4 {
    --bs-gutter-x: 1.5rem;
    --bs-gutter-y: 1.5rem;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .card-body {
        padding: 1.5rem;
    }

    .btn {
        padding: 0.5rem 1rem;
    }

    h5.text-primary {
        font-size: 1.1rem;
    }
}

/* Loading state */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

#save-btn.loading {
    background-color: #6c757d;
    border-color: #6c757d;
}

/* Form sections spacing */
hr {
    border-color: #e9ecef;
    opacity: 1;
}

/* Icon alignments */
.fas {
    width: 16px;
    text-align: center;
}

/* Auto-fill styling */
.form-control[readonly] {
    background-color: #f8f9fa;
    border-color: #e9ecef;
}

/* Password strength indicator */
.password-strength {
    height: 4px;
    border-radius: 2px;
    margin-top: 0.25rem;
    transition: all 0.3s ease;
}

.password-strength.weak {
    background-color: #dc3545;
    width: 33%;
}

.password-strength.medium {
    background-color: #ffc107;
    width: 66%;
}

.password-strength.strong {
    background-color: #28a745;
    width: 100%;
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('client-form');
    const saveBtn = document.getElementById('save-btn');
    const togglePasswordBtn = document.getElementById('toggle-password');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('password_confirmation');
    const contactPerson1Input = document.getElementById('contact_person_1');
    const nameInput = document.getElementById('name');
    const email1Input = document.getElementById('email_1');

    // Auto-fill name from contact person 1
    contactPerson1Input.addEventListener('input', function() {
        if (this.value && !nameInput.value) {
            nameInput.value = this.value;
        }
    });

    // Password visibility toggle
    togglePasswordBtn.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);

        const icon = this.querySelector('i');
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });

    // Password strength indicator
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        const strengthBar = document.getElementById('password-strength');

        if (!strengthBar) {
            // Create strength bar if it doesn't exist
            const strengthDiv = document.createElement('div');
            strengthDiv.id = 'password-strength';
            strengthDiv.className = 'password-strength';
            this.parentNode.appendChild(strengthDiv);
        }

        const strength = calculatePasswordStrength(password);
        updatePasswordStrength(strength);
    });

    // Password confirmation validation
    confirmPasswordInput.addEventListener('input', function() {
        const password = passwordInput.value;
        const confirmPassword = this.value;

        if (confirmPassword && password !== confirmPassword) {
            this.classList.add('is-invalid');
            this.classList.remove('is-valid');
        } else if (confirmPassword) {
            this.classList.add('is-valid');
            this.classList.remove('is-invalid');
        }
    });

    // Form validation
    form.addEventListener('submit', function(e) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('input[required]');

        // Check required fields
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            }
        });

        // Check password match
        if (passwordInput.value !== confirmPasswordInput.value) {
            confirmPasswordInput.classList.add('is-invalid');
            isValid = false;
        }

        // Check email format
        const emailFields = form.querySelectorAll('input[type="email"]');
        emailFields.forEach(field => {
            if (field.value && !isValidEmail(field.value)) {
                field.classList.add('is-invalid');
                isValid = false;
            }
        });

        if (!isValid) {
            e.preventDefault();
            showAlert('Please fill in all required fields correctly.', 'danger');
            return false;
        }

        // Add loading state
        saveBtn.disabled = true;
        saveBtn.classList.add('loading');
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Client...';
    });

    // Real-time email validation
    const emailFields = document.querySelectorAll('input[type="email"]');
    emailFields.forEach(field => {
        field.addEventListener('blur', function() {
            if (this.value && !isValidEmail(this.value)) {
                this.classList.add('is-invalid');
            } else if (this.value) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    });

    // Phone number formatting
    const phoneFields = document.querySelectorAll('input[name="phone"], input[name="contact_number_2"]');
    phoneFields.forEach(field => {
        field.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');

            // Format Philippine numbers
            if (value.startsWith('63')) {
                value = '+63 ' + value.substring(2, 5) + ' ' + value.substring(5, 8) + ' ' + value.substring(8, 12);
            } else if (value.startsWith('0')) {
                value = value.substring(0, 4) + ' ' + value.substring(4, 7) + ' ' + value.substring(7, 11);
            }

            this.value = value;
        });
    });

    // Auto-save draft functionality (optional)
    const draftKey = 'client_form_draft';
    let draftTimeout;

    function saveDraft() {
        const formData = new FormData(form);
        const draftData = {};

        for (let [key, value] of formData.entries()) {
            if (key !== 'password' && key !== 'password_confirmation') {
                draftData[key] = value;
            }
        }

        localStorage.setItem(draftKey, JSON.stringify(draftData));
    }

    function loadDraft() {
        const draftData = localStorage.getItem(draftKey);

        if (draftData) {
            try {
                const data = JSON.parse(draftData);

                Object.keys(data).forEach(key => {
                    const field = form.querySelector(`[name="${key}"]`);
                    if (field && field.type !== 'password') {
                        field.value = data[key];
                    }
                });

                showAlert('Draft loaded successfully', 'info');
            } catch (e) {
                console.error('Error loading draft:', e);
            }
        }
    }

    // Save draft on input change
    form.addEventListener('input', function() {
        clearTimeout(draftTimeout);
        draftTimeout = setTimeout(saveDraft, 2000); // Save after 2 seconds of inactivity
    });

    // Clear draft on successful submission
    form.addEventListener('submit', function() {
        localStorage.removeItem(draftKey);
    });

    // Load draft on page load (uncomment if needed)
    // loadDraft();
});

// Helper functions
function calculatePasswordStrength(password) {
    let strength = 0;

    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;

    return strength;
}

function updatePasswordStrength(strength) {
    const strengthBar = document.getElementById('password-strength');
    if (!strengthBar) return;

    strengthBar.className = 'password-strength';

    if (strength <= 2) {
        strengthBar.classList.add('weak');
    } else if (strength <= 4) {
        strengthBar.classList.add('medium');
    } else {
        strengthBar.classList.add('strong');
    }
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(alertDiv);

    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Auto-format inputs
function formatSecRegistration(input) {
    let value = input.value.replace(/[^A-Z0-9-]/g, '');
    input.value = value;
}

function formatTaxId(input) {
    let value = input.value.replace(/[^0-9-]/g, '');
    if (value.length > 3 && value[3] !== '-') {
        value = value.substring(0, 3) + '-' + value.substring(3);
    }
    if (value.length > 7 && value[7] !== '-') {
        value = value.substring(0, 7) + '-' + value.substring(7);
    }
    input.value = value.substring(0, 11); // Limit length
}

// Apply formatting to specific fields
document.getElementById('sec_registration').addEventListener('input', function() {
    formatSecRegistration(this);
});

document.getElementById('tax_identification').addEventListener('input', function() {
    formatTaxId(this);
});
</script>
@endsection
