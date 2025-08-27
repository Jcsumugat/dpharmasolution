<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - MJ'S PHARMACY</title>
    <style>
        .profile-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
            text-align: center;
            color: white;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            border: 4px solid rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            font-weight: bold;
        }

        .profile-name {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .profile-email {
            opacity: 0.9;
            font-size: 1rem;
        }

        .profile-body {
            padding: 2rem;
        }

        .profile-tabs {
            display: flex;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 2rem;
        }

        .tab-button {
            background: none;
            border: none;
            padding: 1rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            color: #718096;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .tab-button.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            background: #f7fafc;
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid #667eea;
        }

        .info-label {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
        }

        .info-value {
            font-size: 1.1rem;
            color: #2d3748;
        }

        .edit-button {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .edit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-active {
            background: #c6f6d5;
            color: #22543d;
        }

        .status-restricted {
            background: #fed7d7;
            color: #742a2a;
        }

        .status-deactivated {
            background: #fbb6ce;
            color: #97266d;
        }

        .no-data {
            text-align: center;
            padding: 2rem;
            color: #718096;
        }

        .no-data-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .profile-container {
                margin: 1rem auto;
                padding: 0 1rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    @include('client.client-header')

    <!-- Profile Content -->
    <div class="profile-container">
        @if(Auth::guard('customer')->check())
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
                                @if($user->status == 'active')
                                <span class="status-badge status-active">Active</span>
                                @elseif($user->status == 'restricted')
                                <span class="status-badge status-restricted">Restricted</span>
                                @elseif($user->status == 'deactivated')
                                <span class="status-badge status-deactivated">Deactivated</span>
                                @else
                                <span class="status-badge status-restricted">{{ ucfirst($user->status ?? 'Unknown') }}</span>
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
                <a href="{{ route('login.form') }}" class="edit-button" style="display: inline-block; text-decoration: none; margin-top: 1rem;">
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

</html>