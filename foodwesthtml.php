<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "farmer";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all products
$result = $conn->query("SELECT * FROM wastee");
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom animations */
        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s ease-out, transform 0.5s ease-out;
        }
        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .card {
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .image {
            width: 70px; /* Increased width for images */
            height: 70px; /* Fixed height for images */
        }
    </style>
</head>
<body class="bg-green-40 h-screen flex">
    <!-- Sidebar Filter -->
    <div class="w-1/4 bg-white p-4 shadow-md">
        <h2 class="text-lg font-semibold mb-4">Filters</h2>
        <label class="block mb-2">
            <input type="checkbox" class="mr-2"> Verified Farmers
        </label>
        <label class="block mb-2">
            <input type="checkbox" class="mr-2"> Organic Produce
        </label>
        <label class="block mb-2">
            <input type="checkbox" class="mr-2"> Price: Low to High
        </label>
        <label class="block mb-2">
            <input type="checkbox" class="mr-2"> Price: High to Low
        </label>
    </div>
    <!-- Main Content -->
    <div class="w-3/4 flex flex-col">
        <!-- Header -->
        <div class="bg-green-500 p-4 flex items-center justify-between text-white">
            <h1 class="text-xl font-semibold">Farmers & Their Food Waste</h1>
            <input type="text" placeholder="Search Farmers..." class="p-2 rounded-lg text-black">
        </div>
        <!-- Farmer Profiles Grid -->
        <div class="grid grid-cols-2 gap-4 p-4 flex-grow overflow-auto">
            <!-- Farmer Cards -->
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <div class="text-center bg-white p-6 rounded-lg shadow-md card fade-in">
                        <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="image rounded-full mx-auto">
                        <h3 class="mt-2 font-semibold"><?= htmlspecialchars($product['name']) ?></h3>
                        <p class="text-gray-500">@<?= strtolower(str_replace(' ', '', $product['name'])) ?></p>
                        <p class="text-green-600 font-semibold">Food Waste - <?= htmlspecialchars($product['quantity']) ?> <?= htmlspecialchars($product['unit']) ?> available</p>
                        <p class="text-sm text-gray-700">Description: <?= htmlspecialchars($product['description'] ?? 'No description available.') ?></p>
                        <button class="bg-green-500 text-white px-4 py-2 mt-2 rounded">Call</button>
                        <button class="bg-blue-500 text-white px-4 py-2 mt-2 rounded"><a href="Ratilerchat.html">Chat</a></button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-gray-500">No products available.</p>
            <?php endif; ?>
        </div>
        <!-- Navigation Bar -->
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Fade-in effect for farmer cards
            const cards = document.querySelectorAll('.fade-in');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('visible');
                }, index * 100); // Stagger the fade-in effect
            });
        });
    </script>
</body>
</html>