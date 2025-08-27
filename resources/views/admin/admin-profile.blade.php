<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/profile.css') }}" />
    <title>Admin Profile - MJ'S PHARMACY</title>
</head>
<body>
    @include('admin.admin-header')
    
    <!-- Profile Content -->
    <div class="profile-container">
        @if(Auth::check() && Auth::user()->isAdmin())
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
                                <div class="info-value">{{ $admin->isAdmin() ? 'System Administrator' : 'Staff Member' }}</div>
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
                                <div class="info-value">{{ $admin->email_verified_at ? $admin->email_verified_at->format('F j, Y - g:i A') : 'Not verified' }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Account Created</div>
                                <div class="info-value">{{ $admin->created_at ? $admin->created_at->format('F j, Y') : 'N/A' }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Last Updated</div>
                                <div class="info-value">{{ $admin->updated_at ? $admin->updated_at->format('F j, Y - g:i A') : 'N/A' }}</div>
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
                    <a href="{{ route('login') }}" class="edit-button" style="display: inline-block; text-decoration: none; margin-top: 1rem;">
                        Admin Login
                    </a>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function switchTab(event, tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(tab => tab.classList.remove('active'));
            
            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(button => button.classList.remove('active'));
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }

        function editProfile() {
            // Redirect to edit profile page or show modal
            window.location.href = "{{ url('/admin/profile/edit') }}";
        }

        function changePassword() {
            // Redirect to change password page or show modal
            window.location.href = "{{ url('/admin/profile/change-password') }}";
        }

        function managePermissions() {
            // Redirect to permissions management page or show modal
            window.location.href = "{{ url('/admin/settings/permissions') }}";
        }
    </script>
    @endpush

    @stack('scripts')
</body>
</html>