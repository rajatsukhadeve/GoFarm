<?php
$servername = "localhost"; // Change if necessary
$username = "root"; // Change if necessary
$password = ""; // Change if necessary
$dbname = "farmer"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query to select all records from the 'vegitablee' table
$sql = "SELECT * FROM vegitablee"; // Adjust the table name and fields as necessary
$result = $conn->query($sql);

// Fetch products into an array
$products = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
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
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade {
            animation: fadeIn 1s ease-in-out;
        }
    </style>
    <script>
        function searchFarmers() {
            let input = document.getElementById("searchInput").value.toLowerCase();
            let cards = document.querySelectorAll(".text-center.bg-white");
    
            cards.forEach(card => {
                let name = card.querySelector("h3").innerText.toLowerCase();
                if (name.includes(input)) {
                    card.style.display = "block";
                } else {
                    card.style.display = "none";
                }
            });
        }
    </script>
    
</head>
<body class="bg-gradient-to-br from-green-100 to-green-300 h-screen flex animate-fade">
    <!-- Sidebar Filter -->
    <div class="w-1/4 bg-white p-6 shadow-lg rounded-lg m-4">
        <h2 class="text-2xl font-semibold text-green-700 mb-6 text-center">Filters</h2>
        <div class="space-y-4">
            <label class="block">
                <input type="checkbox" class="mr-2 accent-green-500" id="verifiedFilter"> Verified Farmers
            </label>
            <label class="block">
                <input type="checkbox" class="mr-2 accent-green-500" id="organicFilter"> Organic Produce
            </label>
            <label class="block">
                <input type="checkbox" class="mr-2 accent-green-500" id="lowToHigh"> Price: Low to High
            </label>
            <label class="block">
                <input type="checkbox" class="mr-2 accent-green-500" id="highToLow"> Price: High to Low
            </label>
        </div>
        <button onclick="applyFilters()"
            class="bg-green-500 text-white px-4 py-2 mt-4 w-full rounded shadow-md hover:bg-green-700">
            Apply Filters
        </button>
    </div>
    <!-- Main Content -->
    <div class="w-3/4 flex flex-col">
        <!-- Header -->
        <div class="bg-green-600 p-6 flex items-center justify-between text-white shadow-lg">
            <h1 class="text-2xl font-bold">Farmers & Their vegitablee</h1>
            <input type="text" placeholder="Search Farmers..." 
                class="p-3 rounded-lg text-black focus:outline-none focus:ring-2 focus:ring-green-500 transition duration-300 shadow-md"
                id="searchInput" onkeyup="searchFarmers()">
        </div>
        
        <!-- Farmer Profiles Grid -->
        <div class="grid grid-cols-2 gap-6 p-6 flex-grow overflow-auto">
            <!-- Farmer Cards -->
            <?php foreach ($products as $product): ?>
                <div class="text-center bg-white p-8 rounded-lg shadow-lg transform transition hover:scale-105">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Farmer" class="w-28 h-28 rounded-full mx-auto shadow-md">
                    <h3 class="mt-4 text-xl font-semibold text-green-700"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="text-green-600 font-semibold">Apples - ₹<?php echo htmlspecialchars($product['price']); ?> per kg</p>
                    <p class="text-green-600 font-semibold">Stock - <?php echo htmlspecialchars($product['quantity']); ?> kg</p>
                    <p class="text-sm text-gray-700"><?php echo htmlspecialchars($product['info']); ?></p>
                    <div class="mt-4 space-x-4">
                        <button class="bg-green-500 text-white px-4 py-2 rounded shadow-md hover:bg-green-700">Call</button>
                        <button class="bg-blue-500 text-white px-4 py-2 rounded shadow-md hover:bg-blue-700"><a href="Ratilerchat.html">Chat</a></button>
                        <button class="bg-red-500 text-white px-4 py-2 rounded shadow-md hover:bg-red-700">
                            <a href="<?php echo htmlspecialchars($product['videopath']); ?>" target="_blank">View Video</a>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>