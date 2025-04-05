<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriCentral - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .animate-fade {
            animation: fadeIn 1.2s ease-in-out;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="bg-gradient-to-b from-green-100 to-white flex items-center justify-center h-screen">

<div class="bg-white shadow-lg rounded-3xl p-10 w-full max-w-md animate-fade">
    <div class="text-center">
        <h2 class="text-3xl font-bold text-red-500 mt-4">🌾GO<span class="text-blue-600">FaRm</span></h2>
        <p class="text-sm text-gray-500 mb-6">SMART FARMING FOR ALL</p>
    </div>

    <form id="login-form">
        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">Name<span class="text-red-500">*</span></label>
            <input type="text" placeholder="Enter your name" id="name" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">Mobile Number<span class="text-red-500">*</span></label>
            <div class="flex gap-2">
                <input type="text" id="mobile" placeholder="Enter your mobile number" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                <button type="button" onclick="sendOTP()" class="bg-green-600 text-white px-4 rounded-lg hover:bg-blue-600 transition">Send OTP</button>
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">OTP</label>
            <div class="grid grid-cols-6 gap-3">
                <input type="text" maxlength="1" class="otp w-12 h-12 text-center text-lg font-bold bg-green-100 rounded-lg border focus:ring-green-500 outline-none">
                <input type="text" maxlength="1" class="otp w-12 h-12 text-center text-lg font-bold bg-green-100 rounded-lg border focus:ring-green-500 outline-none">
                <input type="text" maxlength="1" class="otp w-12 h-12 text-center text-lg font-bold bg-green-100 rounded-lg border focus:ring-green-500 outline-none">
                <input type="text" maxlength="1" class="otp w-12 h-12 text-center text-lg font-bold bg-green-100 rounded-lg border focus:ring-green-500 outline-none">
                <input type="text" maxlength="1" class="otp w-12 h-12 text-center text-lg font-bold bg-green-100 rounded-lg border focus:ring-green-500 outline-none">
                <input type="text" maxlength="1" class="otp w-12 h-12 text-center text-lg font-bold bg-green-100 rounded-lg border focus:ring-green-500 outline-none">
            </div>
        </div>

        <button type="button" onclick="signIn()" class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-blue-600 transition mt-6 shadow-lg">
            Sign in 
        </button>
    </form>
</div>

<script>
let generatedOTP = '';

function sendOTP() {
    const mobileNumber = document.getElementById('mobile').value;

    if (!mobileNumber) {
        alert('Please enter a valid mobile number.');
        return;
    }

    // Simulate sending OTP
    generatedOTP = Math.floor(100000 + Math.random() * 900000).toString(); // Generate a random 6-digit OTP
    alert(`OTP sent successfully! (Simulated OTP: ${generatedOTP})`); // Display the OTP for testing purposes
}

function signIn() {
    const mobileNumber = document.getElementById('mobile').value;
    const otpInputs = document.querySelectorAll('.otp');
    const otp = Array.from(otpInputs).map(input => input.value).join('');

    if (!mobileNumber || otp.length !== 6) {
        alert('Enter a valid 6-digit OTP.');
        return;
    }

    // Simulate OTP verification
    if (otp === generatedOTP) {
        alert('OTP Verified Successfully!');
        window.location.href = './cosumerWeb.html'; // Redirect to the consumer web page
    } else {
        alert('Invalid OTP.');
    }
}

// Handle OTP input focus
const otpInputs = document.querySelectorAll('.otp');
otpInputs.forEach((input, index) => {
    input.addEventListener('input', () => {
        if (input.value.length === 1 && index < otpInputs.length - 1) {
            otpInputs[index + 1].focus();
        } else if (input.value.length === 0 && index > 0) {
            otpInputs[index - 1].focus();
        }
    });
});
</script>

</body>
</html>