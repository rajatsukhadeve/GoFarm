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

// Handle adding a product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];
    $image = $_POST['image'];

    $stmt = $conn->prepare("INSERT INTO wastee (name, price, quantity, unit, image) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sssss", $name, $price, $quantity, $unit, $image);
        if ($stmt->execute()) {
            echo "Product added successfully.";
        } else {
            echo "Error adding product: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Prepare failed: " . $conn->error;
    }
    exit; // Exit after handling the request
}

// Handle deleting a product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM wastee WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo "Product deleted successfully.";
        } else {
            echo "Error deleting product: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Prepare failed: " . $conn->error;
    }
    exit; // Exit after handling the request
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
    <title>Waste Products</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeIn {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body class="bg-green-100 min-h-screen flex flex-col items-center">

    <!-- Header with Back Button -->
    <div class="w-full flex items-center justify-between px-6 py-4 bg-white shadow-md">
        <a href="./farmerWeb.html">
            <button class="w-10 h-10 transition-all duration-300 hover:scale-110">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="black"> 
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
        </a>
        <h1 class="text-2xl font-bold">Waste Products</h1>
        <div></div>
    </div>

    <!-- Products Grid -->
   <!-- Products Grid -->
<!-- Products Grid -->
<div id="product-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 p-6 w-full max-w-6xl animate-fadeIn">
    <?php if (!empty($products)): ?>
        <?php foreach ($products as $product): ?>
            <div class="bg-white rounded-lg shadow-lg p-4 flex flex-col items-center relative transition-all duration-300 hover:scale-105 hover:shadow-xl animate-fadeIn">
                
                <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-32 h-32 object-cover rounded-md mb-2 hover:scale-110 transition-all duration-300">
                <p class="text-green-600 font-semibold"><?= htmlspecialchars($product['price']) ?></p>
                <p class="text-gray-500"><?= htmlspecialchars($product['quantity']) ?> - <?= htmlspecialchars($product['unit']) ?></p>
                <h3 class="text-lg font-bold mt-2"><?= htmlspecialchars($product['name']) ?></h3>
                <button class="mt-3 bg-blue-500 text-white px-4 py-2 rounded-md transition-all duration-300 hover:bg-blue-700">View Details</button>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-gray-500">No products available.</p>
    <?php endif; ?>
</div>

    <!-- Floating Add Button -->
    <button onclick="openForm()" class="fixed bottom-6 right-6 bg-blue-900 text-white w-16 h-16 rounded-full flex items-center justify-center shadow-lg animate-bounce transition-all duration-300 hover:scale-110">
        +
    </button>

    <!-- Add Product Form -->
    <div id="add-product-modal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-96 animate-fadeIn">
            <h2 class="text-xl font-bold mb-4">Add Waste Product</h2>
            <input type="text" id="product-name" placeholder="Product Name" class="w-full border p-2 mb-2 rounded">
            <input type="text" id="product-price" placeholder="Price (₹)" class="w-full border p-2 mb-2 rounded">
            <input type="text" id="product-quantity" placeholder="Quantity" class="w-full border p-2 mb-2 rounded">
            <input type="text" id="product-unit" placeholder="Unit (e.g., per KG, per Item)" class="w-full border p-2 mb-2 rounded">
            <input type="text" id="product-image" placeholder="Image URL" class="w-full border p-2 mb-4 rounded">
            
            <button onclick="addProduct()" class="w-full bg-blue-600 text-white p-2 rounded transition-all duration-300 hover:bg-blue-700">Add Product</button>
            <button onclick="closeForm()" class="w-full mt-2 bg-gray-400 text-white p-2 rounded transition-all duration-300 hover:bg-gray-500">Cancel</button>
        </div>
    </div>

    <script>
        function openForm() {
            document.getElementById("add-product-modal").classList.remove("hidden");
        }

        function closeForm() {
            document.getElementById("add-product-modal").classList.add("hidden");
        }

        function addProduct() {
            let name = document.getElementById("product-name").value;
            let price = "₹ " + document.getElementById("product-price").value;
            let quantity = document.getElementById("product-quantity").value;
            let unit = document.getElementById("product-unit").value;
            let image = document.getElementById("product-image").value;

            if (name && price && quantity && unit && image) {
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'action': 'add',
                        'name': name,
                        'price': price,
                        'quantity': quantity,
                        'unit': unit,
                        'image': image
                    })
                })
                .then(response => response.text())
                .then(data => {
                    console.log(data);
                    location.reload();
                })
                .catch(error => console.error('Error:', error));
            } else {
                alert("Please fill all fields.");
            }
        }

        function removeProduct(id) {
            if (confirm("Are you sure you want to delete this product?")) {
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'action': 'delete',
                        'id': id
                    })
                })
                .then(response => response.text())
                .then(data => {
                    console.log(data);
                    location.reload();
                })
                .catch(error => console.error('Error:', error));
            }
        }
    </script>

</body>
</html>