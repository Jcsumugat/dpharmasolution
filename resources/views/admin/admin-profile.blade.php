<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/profile.css') }}" />
    <title>Admin Profile - MJ'S PHARMACY</title>
    <style>
        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .profile-modal {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #1f2937;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            color: #6b7280;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .modal-close:hover {
            background: #f3f4f6;
            color: #1f2937;
        }

        .modal-body {
            padding: 24px;
            max-height: 60vh;
            overflow-y: auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.2s;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group input[readonly] {
            background: #f9fafb;
            color: #6b7280;
            cursor: not-allowed;
        }

        .form-group small {
            display: block;
            margin-top: 6px;
            color: #6b7280;
            font-size: 12px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 16px 24px;
            border-top: 1px solid #e5e7eb;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            font-size: 14px;
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a67d8;
        }

        .btn-primary:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
    </style>

</head>

<body>
    @include('admin.admin-header')

    <!-- Profile Content -->
    <div class="profile-container">
        @if (Auth::check() && Auth::user()->isAdmin())
            @php
                $admin = Auth::user();
                $nameParts = explode(' ', $admin->name ?? 'Admin User');
                $firstName = $nameParts[0] ?? 'Admin';
                $lastName = end($nameParts);
                $initials = strtoupper(substr($firstName, 0, 1)) . strtoupper(substr($lastName, 0, 1));
            @endphp

            <div class="profile-card">
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <span>{{ $initials }}</span>
                    </div>
                    <h1 class="profile-name">{{ $admin->name ?? 'Admin User' }}</h1>
                    <p class="profile-email">{{ $admin->email ?? 'admin@mjspharmacy.com' }}</p>
                </div>

                <!-- Profile Body -->
                <div class="profile-body">
                    <!-- Tabs -->
                    <div class="profile-tabs">
                        <button class="tab-button active" onclick="switchTab(event, 'personal')">Personal Info</button>
                        <button class="tab-button" onclick="switchTab(event, 'security')">Account Security</button>
                        <button class="tab-button" onclick="switchTab(event, 'permissions')">Permissions</button>
                    </div>

                    <!-- Personal Info Tab -->
                    <div id="personal" class="tab-content active">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Full Name</div>
                                <div class="info-value">{{ $admin->name ?? 'Admin User' }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Email Address</div>
                                <div class="info-value">{{ $admin->email ?? 'admin@mjspharmacy.com' }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Role</div>
                                <div class="info-value">{{ ucfirst($admin->role ?? 'admin') }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Account Type</div>
                                <div class="info-value">
                                    {{ $admin->isAdmin() ? 'System Administrator' : 'Staff Member' }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">User ID</div>
                                <div class="info-value">USR{{ str_pad($admin->id ?? 1, 4, '0', STR_PAD_LEFT) }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Email Verified</div>
                                <div class="info-value">{{ $admin->email_verified_at ? 'Yes' : 'No' }}</div>
                            </div>
                        </div>
                        <button class="edit-button" onclick="editProfile()">Edit Profile</button>
                    </div>

                    <!-- Security Tab -->
                    <div id="security" class="tab-content">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Account Status</div>
                                <div class="info-value">
                                    <span class="status-badge status-active">Active</span>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Account Type</div>
                                <div class="info-value">
                                    <span class="status-badge status-admin">{{ ucfirst($admin->role) }}</span>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Email Verified</div>
                                <div class="info-value">
                                    {{ $admin->email_verified_at ? $admin->email_verified_at->format('F j, Y - g:i A') : 'Not verified' }}
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Account Created</div>
                                <div class="info-value">
                                    {{ $admin->created_at ? $admin->created_at->format('F j, Y') : 'N/A' }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Last Updated</div>
                                <div class="info-value">
                                    {{ $admin->updated_at ? $admin->updated_at->format('F j, Y - g:i A') : 'N/A' }}
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Remember Token</div>
                                <div class="info-value">{{ $admin->remember_token ? 'Active' : 'None' }}</div>
                            </div>
                        </div>
                        <button class="edit-button" onclick="changePassword()">Change Password</button>
                    </div>

                    <!-- Permissions Tab -->
                    <div id="permissions" class="tab-content">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Dashboard Access</div>
                                <div class="info-value">✅ Granted</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Product Management</div>
                                <div class="info-value">✅ Full Access</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Supplier Management</div>
                                <div class="info-value">✅ Full Access</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Inventory Control</div>
                                <div class="info-value">✅ Full Access</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Order Management</div>
                                <div class="info-value">✅ Full Access</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Sales Reports</div>
                                <div class="info-value">✅ Full Access</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Customer Management</div>
                                <div class="info-value">✅ Full Access</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">System Reports</div>
                                <div class="info-value">✅ Full Access</div>
                            </div>
                        </div>
                        <button class="edit-button" onclick="managePermissions()">Manage Permissions</button>
                    </div>
                </div>
            </div>
        @else
            <div class="profile-card">
                <div class="no-data">
                    <div class="no-data-icon">🔒</div>
                    <h3>Access Denied</h3>
                    <p>You need to be logged in as an administrator to view this profile.</p>
                    <a href="{{ route('login') }}" class="edit-button"
                        style="display: inline-block; text-decoration: none; margin-top: 1rem;">
                        Admin Login
                    </a>
                </div>
            </div>
        @endif
    </div>
    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal-overlay">
        <div class="modal-content profile-modal">
            <div class="modal-header">
                <h2>Edit Profile</h2>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editProfileForm" method="POST" action="{{ route('admin.profile.update') }}">
                    @csrf
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="{{ $admin->name ?? '' }}"
                            required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="{{ $admin->email ?? '' }}"
                            required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role</label>
                        <input type="text" id="role" name="role" value="{{ $admin->role ?? 'admin' }}"
                            readonly>
                        <small>Contact system administrator to change role</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitProfileForm()">Save Changes</button>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            function switchTab(event, tabName) {
                const tabContents = document.querySelectorAll('.tab-content');
                tabContents.forEach(tab => tab.classList.remove('active'));

                const tabButtons = document.querySelectorAll('.tab-button');
                tabButtons.forEach(button => button.classList.remove('active'));

                document.getElementById(tabName).classList.add('active');
                event.target.classList.add('active');
            }

            function editProfile() {
                document.getElementById('editProfileModal').classList.add('active');
            }

            function closeEditModal() {
                document.getElementById('editProfileModal').classList.remove('active');
            }

            function submitProfileForm() {
                const form = document.getElementById('editProfileForm');
                const formData = new FormData(form);
                const submitBtn = event.target;

                submitBtn.textContent = 'Saving...';
                submitBtn.disabled = true;

                fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Profile updated successfully!');
                            window.location.reload();
                        } else {
                            alert('Error: ' + (data.message || 'Failed to update profile'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to update profile. Please try again.');
                    })
                    .finally(() => {
                        submitBtn.textContent = 'Save Changes';
                        submitBtn.disabled = false;
                    });
            }

            function changePassword() {
                window.location.href = "{{ url('/admin/profile/change-password') }}";
            }

            function managePermissions() {
                window.location.href = "{{ url('/admin/settings/permissions') }}";
            }

            // Close modal on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeEditModal();
                }
            });

            // Close modal on outside click
            document.getElementById('editProfileModal')?.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeEditModal();
                }
            });
        </script>
    @endpush
    @stack('scripts')
</body>
@include('admin.admin-footer')

</html>
