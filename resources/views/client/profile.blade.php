<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Products - MJ's Pharmacy</title>
  <link rel="stylesheet" href="{{ asset('css/customer/profile.css') }}">
</head>

<body>
    @include('client.client-header')

    <!-- Profile Content -->
    <div class="profile-container">
        @if (Auth::guard('customer')->check())
            @php
                $user = Auth::guard('customer')->user();
                $nameParts = explode(' ', $user->full_name);
                $firstName = $nameParts[0] ?? '';
                $lastName = end($nameParts);
                $initials = strtoupper(substr($firstName, 0, 1)) . strtoupper(substr($lastName, 0, 1));
            @endphp

            <div class="profile-card">
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <span>{{ $initials }}</span>
                    </div>
                    <h1 class="profile-name">{{ $user->full_name }}</h1>
                    <p class="profile-email">{{ $user->email_address }}</p>
                </div>

                <!-- Profile Body -->
                <div class="profile-body">
                    <!-- Tabs -->
                    <div class="profile-tabs">
                        <button class="tab-button active" onclick="switchTab(event, 'personal')">Personal Info</button>
                        <button class="tab-button" onclick="switchTab(event, 'security')">Account Security</button>
                    </div>

                    <!-- Personal Info Tab -->
                    <div id="personal" class="tab-content active">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Full Name</div>
                                <div class="info-value">{{ $user->full_name }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Email Address</div>
                                <div class="info-value">{{ $user->email_address }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Contact Number</div>
                                <div class="info-value">{{ $user->contact_number ?? 'Not provided' }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Address</div>
                                <div class="info-value">{{ $user->address ?? 'Not provided' }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Birth Date</div>
                                <div class="info-value">
                                    {{ $user->birthdate ? \Carbon\Carbon::parse($user->birthdate)->format('F j, Y') : 'Not provided' }}
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Gender</div>
                                <div class="info-value">{{ $user->sex ?? 'Not specified' }}</div>
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
                                    @if ($user->status == 'active')
                                        <span class="status-badge status-active">Active</span>
                                    @elseif($user->status == 'restricted')
                                        <span class="status-badge status-restricted">Restricted</span>
                                    @elseif($user->status == 'deactivated')
                                        <span class="status-badge status-deactivated">Deactivated</span>
                                    @else
                                        <span
                                            class="status-badge status-restricted">{{ ucfirst($user->status ?? 'Unknown') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Member Since</div>
                                <div class="info-value">{{ $user->created_at->format('F j, Y') }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Last Updated</div>
                                <div class="info-value">{{ $user->updated_at->format('F j, Y - g:i A') }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Account ID</div>
                                <div class="info-value">{{ $user->customer_id ?? $user->id }}</div>
                            </div>
                        </div>
                        <button class="edit-button" onclick="changePassword()">Change Password</button>
                    </div>
                </div>
            </div>
        @else
            <div class="profile-card">
                <div class="no-data">
                    <div class="no-data-icon">🔒</div>
                    <h3>Please Log In</h3>
                    <p>You need to be logged in to view your profile.</p>
                    <a href="{{ route('login.form') }}" class="edit-button"
                        style="display: inline-block; text-decoration: none; margin-top: 1rem;">
                        Login Now
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
                window.location.href = "{{ url('/profile/edit') }}";
            }

            function changePassword() {
                // Redirect to change password page or show modal
                window.location.href = "{{ url('/profile/change-password') }}";
            }
        </script>
    @endpush

    @stack('scripts')
</body>
@include('client.client-footer')
</html>
