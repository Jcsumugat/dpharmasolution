<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup - Step 1 | MJ's Pharmacy</title>
    <link rel="stylesheet" href="{{ asset('css/customer/customer.css') }}">
</head>

<body>
    <div class="form-container">
        <div class="form-box">
            
            <h2>Create Account</h2>
            <p class="form-subtitle">Step 1 of 2: Personal Information</p>

            @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form action="{{ route('signup.step_one.submit') }}" method="POST">
                @csrf

                <div class="input-group">
                    <input type="text" name="full_name" placeholder="Full Name" value="{{ old('full_name') }}" required>
                    @error('full_name') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="input-group">
                    <input type="tel" name="contact_number" placeholder="Mobile Number" value="{{ old('contact_number') }}" required pattern="[0-9]{11}" title="Please enter a valid 11-digit phone number">
                    @error('contact_number') <div class="error">{{ $message }}</div> @enderror
                    <div class="input-hint">Example: 09123456789</div>
                </div>

                <div class="input-group">
                    <input type="email" name="email_address" placeholder="Email Address" value="{{ old('email_address') }}" required>
                    @error('email_address') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="input-group">
                    <label for="birthdate">Date of Birth</label>
                    <input type="date" id="birthdate" name="birthdate" value="{{ old('birthdate') }}" required>
                    @error('birthdate') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="input-group">
                    <label for="sex">Sex</label>
                    <div class="gender-group">
                        <input type="radio" id="male" name="sex" value="male" {{ old('sex') == 'male' ? 'checked' : '' }}>
                        <label for="male">Male</label>

                        <input type="radio" id="female" name="sex" value="female" {{ old('sex') == 'female' ? 'checked' : '' }}>
                        <label for="female">Female</label>

                        <input type="radio" id="other" name="sex" value="other" {{ old('sex') == 'other' ? 'checked' : '' }}>
                        <label for="other">Other</label>
                    </div>
                    @error('sex') <div class="error">{{ $message }}</div> @enderror
                </div>


                <div class="input-group">
                    <textarea name="address" placeholder="Complete Address" rows="3" required>{{ old('address') }}</textarea>
                    @error('address') <div class="error">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="submit-btn">Next Step</button>
            </form>

            <div class="login-link">
                Already have an account? <a href="{{ route('login.form') }}">Log in</a>
            </div>
        </div>
    </div>
</body>

</html>