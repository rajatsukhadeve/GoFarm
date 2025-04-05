<?php
// Database configuration
$host = "localhost";
$username = "root"; // Change to your MySQL username
$password = ""; // Change to your MySQL password
$database = "farmer"; // Change to your database name

// Establish database connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create users and otp_verification tables if they don't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    mobile VARCHAR(15) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($sql)) {
    echo "Error creating users table: " . $conn->error;
}

$sql = "CREATE TABLE IF NOT EXISTS otp_verification (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    mobile VARCHAR(15) NOT NULL,
    otp VARCHAR(6) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    verified TINYINT(1) DEFAULT 0
)";

if (!$conn->query($sql)) {
    echo "Error creating otp_verification table: " . $conn->error;
}

// Handle API requests
$response = ['status' => 'error', 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'send_otp':
                $response = sendOTP($conn, $data);
                break;
            case 'verify_otp':
                $response = verifyOTP($conn, $data);
                break;
            case 'register':
                $response = registerUser($conn, $data);
                break;
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Function to send OTP
function sendOTP($conn, $data) {
    if (!isset($data['mobile']) || empty($data['mobile'])) {
        return ['status' => 'error', 'message' => 'Mobile number is required'];
    }
    
    $mobile = $conn->real_escape_string($data['mobile']);
    
    // Generate a random 6-digit OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Set expiry time (15 minutes from now)
    $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Delete any existing OTP for this mobile number
    $sql = "DELETE FROM otp_verification WHERE mobile = '$mobile'";
    $conn->query($sql);
    
    // Insert new OTP
    $sql = "INSERT INTO otp_verification (mobile, otp, expires_at) VALUES ('$mobile', '$otp', '$expires_at')";
    
    if ($conn->query($sql)) {
        // In production, you would integrate with an SMS API here
        // For testing, we'll just return the OTP in the response
        return [
            'status' => 'success', 
            'message' => 'OTP sent successfully',
            'otp' => $otp // Remove this in production
        ];
    } else {
        return ['status' => 'error', 'message' => 'Failed to send OTP: ' . $conn->error];
    }
}

// Function to verify OTP
function verifyOTP($conn, $data) {
    if (!isset($data['mobile']) || empty($data['mobile']) || !isset($data['otp']) || empty($data['otp'])) {
        return ['status' => 'error', 'message' => 'Mobile number and OTP are required'];
    }
    
    $mobile = $conn->real_escape_string($data['mobile']);
    $otp = $conn->real_escape_string($data['otp']);
    $current_time = date('Y-m-d H:i:s');
    
    $sql = "SELECT * FROM otp_verification WHERE mobile = '$mobile' AND otp = '$otp' AND expires_at > '$current_time' AND verified = 0";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        // Mark OTP as verified
        $sql = "UPDATE otp_verification SET verified = 1 WHERE mobile = '$mobile' AND otp = '$otp'";
        $conn->query($sql);
        
        return ['status' => 'success', 'message' => 'OTP verified successfully'];
    } else {
        return ['status' => 'error', 'message' => 'Invalid or expired OTP'];
    }
}

// Function to register a new user
function registerUser($conn, $data) {
    if (!isset($data['name']) || empty($data['name']) || !isset($data['mobile']) || empty($data['mobile'])) {
        return ['status' => 'error', 'message' => 'Name and mobile number are required'];
    }
    
    $name = $conn->real_escape_string($data['name']);
    $mobile = $conn->real_escape_string($data['mobile']);
    
    // Check if mobile is verified
    $sql = "SELECT * FROM otp_verification WHERE mobile = '$mobile' AND verified = 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows === 0) {
        return ['status' => 'error', 'message' => 'Mobile number not verified'];
    }
    
    // Check if user already exists
    $sql = "SELECT * FROM users WHERE mobile = '$mobile'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        return ['status' => 'success', 'message' => 'User already exists'];
    }
    
    // Insert new user
    $sql = "INSERT INTO users (name, mobile) VALUES ('$name', '$mobile')";
    
    if ($conn->query($sql)) {
        return ['status' => 'success', 'message' => 'User registered successfully'];
    } else {
        return ['status' => 'error', 'message' => 'Failed to register user: ' . $conn->error];
    }
}

// Close connection for API requests
if (isset($_GET['action'])) {
    $conn->close();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GOFaRm - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
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
        .loader {
            border: 5px solid #f3f3f3;
            border-radius: 50%;
            border-top: 5px solid #16a34a;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gradient-to-b from-green-100 to-white flex items-center justify-center h-screen">

<div class="bg-white shadow-lg rounded-3xl p-10 w-full max-w-md animate-fade">
    <div class="text-center">
        <h2 class="text-3xl font-bold text-red-500 mt-4">🌾GO<span class="text-blue-600">FaRm</span></h2>
        <p class="text-sm text-gray-500 mb-6">SMART FARMING FOR ALL</p>
    </div>

    <div id="notification" class="hidden p-4 mb-4 rounded-lg text-center"></div>
    <div id="loading" class="hidden my-4">
        <div class="loader"></div>
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
                <button type="button" id="send-otp-btn" class="bg-green-600 text-white px-4 rounded-lg hover:bg-blue-600 transition">Send OTP</button>
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
            <div id="resend-otp" class="text-right mt-2">
                <a href="#" class="text-sm text-blue-600 hover:underline hidden">Resend OTP</a>
            </div>
        </div>

        <button type="button" id="sign-in-btn" class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-blue-600 transition mt-6 shadow-lg">
            Sign in
        </button>
    </form>
</div>

<script>
// DOM elements
const sendOtpBtn = document.getElementById('send-otp-btn');
const signInBtn = document.getElementById('sign-in-btn');
const nameInput = document.getElementById('name');
const mobileInput = document.getElementById('mobile');
const otpInputs = document.querySelectorAll('.otp');
const notificationEl = document.getElementById('notification');
const loadingEl = document.getElementById('loading');
const resendOtpLink = document.querySelector('#resend-otp a');

// Global variables
let otpVerified = false;
let otpSent = false;

// Functions to show/hide loading and notifications
function showLoading() {
    loadingEl.classList.remove('hidden');
}

function hideLoading() {
    loadingEl.classList.add('hidden');
}

function showNotification(message, type) {
    notificationEl.textContent = message;
    notificationEl.classList.remove('hidden', 'bg-red-100', 'text-red-800', 'bg-green-100', 'text-green-800');
    
    if (type === 'error') {
        notificationEl.classList.add('bg-red-100', 'text-red-800');
    } else {
        notificationEl.classList.add('bg-green-100', 'text-green-800');
    }
    
    setTimeout(() => {
        notificationEl.classList.add('hidden');
    }, 5000);
}

// API functions
async function makeRequest(endpoint, data) {
    try {
        showLoading();
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            throw new Error(HTTP error! Status: ${response.status});
        }
        
        return await response.json();
    } catch (error) {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
        return { status: 'error', message: 'Request failed' };
    } finally {
        hideLoading();
    }
}

// Send OTP function
async function sendOTP() {
    const mobile = mobileInput.value.trim();
    
    if (!mobile) {
        showNotification('Please enter a valid mobile number.', 'error');
        return;
    }
    
    const response = await makeRequest('?action=send_otp', { mobile });
    
    if (response.status === 'success') {
        showNotification('OTP sent successfully!', 'success');
        otpSent = true;
        
        // Show resend link after 30 seconds
        resendOtpLink.classList.add('hidden');
        setTimeout(() => {
            resendOtpLink.classList.remove('hidden');
        }, 30000);
        
        // Focus on first OTP input
        otpInputs[0].focus();
    } else {
        showNotification(response.message || 'Failed to send OTP', 'error');
    }
}

// Verify OTP function
async function verifyOTP() {
    const mobile = mobileInput.value.trim();
    const otp = Array.from(otpInputs).map(input => input.value).join('');
    
    if (!mobile || otp.length !== 6) {
        showNotification('Please enter a valid 6-digit OTP.', 'error');
        return;
    }
    
    const response = await makeRequest('?action=verify_otp', { mobile, otp });
    
    if (response.status === 'success') {
        otpVerified = true;
        return true;
    } else {
        showNotification(response.message || 'Invalid OTP', 'error');
        return false;
    }
}

// Register user function
async function registerUser() {
    const name = nameInput.value.trim();
    const mobile = mobileInput.value.trim();
    
    if (!name || !mobile) {
        showNotification('Name and mobile number are required.', 'error');
        return;
    }
    
    const response = await makeRequest('?action=register', { name, mobile });
    
    if (response.status === 'success') {
        showNotification('Login successful!', 'success');
        
        // Redirect to farmer dashboard after successful login
        setTimeout(() => {
            window.location.href = './farmerWeb.html';
        }, 1500);
    } else {
        showNotification(response.message || 'Registration failed', 'error');
    }
}

// Sign in function
async function signIn() {
    const name = nameInput.value.trim();
    const mobile = mobileInput.value.trim();
    
    if (!name || !mobile) {
        showNotification('Name and mobile number are required.', 'error');
        return;
    }
    
    if (!otpSent) {
        showNotification('Please send OTP first.', 'error');
        return;
    }
    
    // Verify OTP if not already verified
    if (!otpVerified) {
        const verified = await verifyOTP();
        if (!verified) return;
    }
    
    // Register/login user
    await registerUser();
}

// Event listeners
sendOtpBtn.addEventListener('click', sendOTP);
signInBtn.addEventListener('click', signIn);
resendOtpLink.addEventListener('click', (e) => {
    e.preventDefault();
    sendOTP();
});

// Handle OTP input focus
otpInputs.forEach((input, index) => {
    input.addEventListener('input', () => {
        if (input.value.length === 1) {
            if (index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }
        }
    });
    
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !input.value && index > 0) {
            otpInputs[index - 1].focus();
        }
    });
});

// Mobile number validation
mobileInput.addEventListener('input', (e) => {
    e.target.value = e.target.value.replace(/[^0-9]/g, '');
});
</script>

</body>
</html>