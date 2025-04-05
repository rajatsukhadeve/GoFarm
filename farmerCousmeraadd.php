<?php
// Database configuration
$servername = "localhost";
$username = "root"; // Change to your MySQL username
$password = ""; // Change to your MySQL password
$dbname = "farmer";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) !== TRUE) {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($dbname);

// Create products table if not exists
$sql = "CREATE TABLE IF NOT EXISTS products (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    image VARCHAR(255) DEFAULT 'image/default.jpeg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating table: " . $conn->error);
}

// Check if table is empty, add default products if it is
$result = $conn->query("SELECT COUNT(*) as count FROM products");
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    // Default products to insert
    $defaultProducts = [
        ['Tomato', 40, 'Vegetables', 'image/tomato.jpeg'],
        ['Carrot', 50, 'Vegetables', 'image/carrot.jpeg'],
        ['Onion', 40, 'Vegetables', 'image/onin.jpeg'],
        ['Capsicum', 40, 'Vegetables', 'image/cpasicum.jpeg'],
        ['Potato', 40, 'Vegetables', 'image/potato.jpeg'],
        ['Spinach', 40, 'Vegetables', 'image/spinach.jpeg'],
        ['Cabbage', 30, 'Vegetables', 'image/cabbage.jpeg'],
        ['Broccoli', 80, 'Vegetables', 'image/Broccoli.jpg'],
        ['Cauliflower', 50, 'Vegetables', 'image/cauliflower.jpeg'],
        ['Garlic', 150, 'Vegetables', 'image/garlic.jpeg'],
        ['Apple', 100, 'Fruits', 'image/apple.jpeg'],
        ['Banana', 60, 'Fruits', 'image/banan.jpeg'],
        ['Kiwi', 120, 'Fruits', 'image/kivi.jpeg'],
        ['Guava', 120, 'Fruits', 'image/gauva.jpeg'],
        ['Mango', 90, 'Fruits', 'image/mango.jpeg'],
        ['Ginger', 200, 'Spices', 'image/ginger.jpeg'],
        ['Cumin', 80, 'Spices', 'image/cumin.jpeg'],
        ['Almonds', 700, 'Dry Fruits', 'image/almonds.jpeg'],
        ['Cashew', 900, 'Dry Fruits', 'image/cashew.jpeg']
    ];
    
    $stmt = $conn->prepare("INSERT INTO products (name, price, category, image) VALUES (?, ?, ?, ?)");
    
    foreach ($defaultProducts as $product) {
        $stmt->bind_param("sdss", $product[0], $product[1], $product[2], $product[3]);
        $stmt->execute();
    }
    
    $stmt->close();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['action'])) {
        switch ($data['action']) {
            case 'getProducts':
                $category = $data['category'] ?? '';
                getProducts($conn, $category);
                break;
                
            case 'searchProducts':
                $query = $data['query'] ?? '';
                searchProducts($conn, $query);
                break;
                
            case 'addProduct':
                addProduct($conn, $data);
                break;
                
            case 'removeProduct':
                removeProduct($conn, $data['id']);
                break;
                
            default:
                echo json_encode(['error' => 'Invalid action']);
        }
        exit;
    }
}

// Function to get products by category
function getProducts($conn, $category) {
    if (!empty($category)) {
        $stmt = $conn->prepare("SELECT * FROM products WHERE category = ?");
        $stmt->bind_param("s", $category);
    } else {
        $stmt = $conn->prepare("SELECT * FROM products");
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    echo json_encode(['products' => $products]);
    $stmt->close();
}

// Function to search products
function searchProducts($conn, $query) {
    $search = "%$query%";
    $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE ?");
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    echo json_encode(['products' => $products]);
    $stmt->close();
}

// Function to add a new product
function addProduct($conn, $data) {
    $stmt = $conn->prepare("INSERT INTO products (name, price, category, image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdss", $data['name'], $data['price'], $data['category'], $data['image']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Product added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add product']);
    }
    
    $stmt->close();
}

// Function to remove a product
function removeProduct($conn, $id) {
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Product removed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove product']);
    }
    
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html { scroll-behavior: smooth; }
        .glass { backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.2); }
    </style>
</head>
<body class="bg-gradient-to-br from-green-50 to-green-200 min-h-screen">
    <nav class="bg-white shadow-lg sticky top-0 z-50 p-6 flex justify-between items-center">
        <h2 class="text-3xl font-bold text-red-500">🌾GO<span class="text-blue-600">FaRm</span></h2>
        <input type="text" id="searchBar" class="border p-2 rounded-lg" placeholder="Search products...">
        <div class="flex gap-4">
            <button id="addItemBtn" class="bg-blue-600 text-white px-6 py-2 rounded-full">
                ➕ Add Item
            </button>
        </div>
    </nav>

    <div class="text-center py-16">
        <h1 class="text-6xl font-extrabold text-green-700">Fresh from Farmer 🥦🍎</h1>
    </div>

    <div class="flex gap-4 justify-center mb-8">
        <button onclick="loadProducts('Vegetables')" class="bg-green-600 text-white px-6 py-2 rounded-full">Vegetables</button>
        <button onclick="loadProducts('Fruits')" class="bg-green-600 text-white px-6 py-2 rounded-full">Fruits</button>
        <button onclick="loadProducts('Spices')" class="bg-green-600 text-white px-6 py-2 rounded-full">Spices</button>
        <button onclick="loadProducts('Dry Fruits')" class="bg-green-600 text-white px-6 py-2 rounded-full">Dry Fruits</button>
    </div>

    <div id="productsGrid" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-8 p-4"></div>

    <!-- Add Item Modal -->
    <div id="addItemModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md">
            <h2 class="text-2xl font-bold mb-4 text-green-700">Add New Product</h2>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Product Name</label>
                <input type="text" id="newProductName" class="w-full border p-2 rounded" placeholder="Enter product name">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Price (₹)</label>
                <input type="number" id="newProductPrice" class="w-full border p-2 rounded" placeholder="Enter price">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Category</label>
                <select id="newProductCategory" class="w-full border p-2 rounded">
                    <option value="Vegetables">Vegetables</option>
                    <option value="Fruits">Fruits</option>
                    <option value="Spices">Spices</option>
                    <option value="Dry Fruits">Dry Fruits</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Image URL</label>
                <input type="text" id="newProductImage" class="w-full border p-2 rounded" placeholder="Enter image URL" value="image/default.jpeg">
            </div>
            
            <div class="flex justify-end gap-4 mt-6">
                <button onclick="closeAddItemModal()" class="bg-gray-300 text-gray-800 px-6 py-2 rounded">Cancel</button>
                <button onclick="saveNewProduct()" class="bg-green-600 text-white px-6 py-2 rounded">Save Product</button>
            </div>
        </div>
    </div>

    <script>
        // Global error handler
        function handleError(error) {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        }
        
        // Function to make API calls
        async function apiCall(action, data = {}) {
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ action, ...data }),
                });
                
                return await response.json();
            } catch (error) {
                handleError(error);
                return { error: 'Request failed' };
            }
        }
        
        // Load products from server
        async function loadProducts(category = 'Vegetables') {
            try {
                const data = await apiCall('getProducts', { category });
                displayProducts(data.products);
            } catch (error) {
                handleError(error);
            }
        }
        
        // Display products in the grid
        function displayProducts(products) {
            const productsGrid = document.getElementById('productsGrid');
            productsGrid.innerHTML = '';
            
            if (!products || products.length === 0) {
                productsGrid.innerHTML = "<p class='text-center col-span-full'>No products in this category</p>";
                return;
            }
            
            products.forEach(product => {
                const productHTML = `
                    <div class='bg-white p-6 shadow-xl rounded-lg text-center'>
                        <img src='${product.image}' class='h-40 w-40 mx-auto rounded-lg object-cover' onerror="this.src='image/default.jpeg'">
                        <h2 class='text-2xl font-bold mt-4'>${product.name}</h2>
                        <p class='text-green-700 font-semibold'>₹${product.price} / Kg</p>
                        <button class='bg-red-500 text-white px-6 py-2 rounded-full mt-4' onclick='removeProduct(${product.id})'>Remove</button>
                    </div>`;
                productsGrid.innerHTML += productHTML;
            });
        }
        
        // Search products
        async function searchProducts() {
            const query = document.getElementById('searchBar').value.trim();
            
            if (!query) {
                return loadProducts('Vegetables');
            }
            
            try {
                const data = await apiCall('searchProducts', { query });
                displayProducts(data.products);
            } catch (error) {
                handleError(error);
            }
        }
        
        // Remove product
        async function removeProduct(id) {
            if (confirm('Are you sure you want to remove this product?')) {
                try {
                    const response = await apiCall('removeProduct', { id });
                    
                    if (response.success) {
                        alert(response.message);
                        // Reload current category
                        const activeCategory = document.querySelector('.bg-green-700')?.textContent || 'Vegetables';
                        loadProducts(activeCategory);
                    } else {
                        alert('Failed to remove product.');
                    }
                } catch (error) {
                    handleError(error);
                }
            }
        }
        
        // Add Item Modal Functions
        function openAddItemModal() {
            document.getElementById('addItemModal').classList.remove('hidden');
        }
        
        function closeAddItemModal() {
            document.getElementById('addItemModal').classList.add('hidden');
        }
        
        // Save new product
        async function saveNewProduct() {
            const name = document.getElementById('newProductName').value.trim();
            const price = parseFloat(document.getElementById('newProductPrice').value);
            const category = document.getElementById('newProductCategory').value;
            const image = document.getElementById('newProductImage').value.trim() || 'image/default.jpeg';
            
            if (!name || !price) {
                alert('Please enter product name and price');
                return;
            }
            
            try {
                const response = await apiCall('addProduct', {
                    name,
                    price,
                    category,
                    image
                });
                
                if (response.success) {
                    alert(response.message);
                    
                    // Reset form
                    document.getElementById('newProductName').value = '';
                    document.getElementById('newProductPrice').value = '';
                    document.getElementById('newProductImage').value = 'image/default.jpeg';
                    
                    // Close modal
                    closeAddItemModal();
                    
                    // Reload products
                    loadProducts(category);
                } else {
                    alert('Failed to add product.');
                }
            } catch (error) {
                handleError(error);
            }
        }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', () => {
            // Initial load
            loadProducts('Vegetables');
            
            // Search on keyup
            document.getElementById('searchBar').addEventListener('keyup', searchProducts);
            
            // Add item button
            document.getElementById('addItemBtn').addEventListener('click', openAddItemModal);
            
            // Add category active state
            const categoryButtons = document.querySelectorAll('button');
            categoryButtons.forEach(button => {
                button.addEventListener('click', function() {
                    categoryButtons.forEach(btn => {
                        btn.classList.remove('bg-green-700');
                        btn.classList.add('bg-green-600');
                    });
                    if (this.textContent === 'Vegetables' || 
                        this.textContent === 'Fruits' || 
                        this.textContent === 'Spices' || 
                        this.textContent === 'Dry Fruits') {
                        this.classList.remove('bg-green-600');
                        this.classList.add('bg-green-700');
                    }
                });
            });
        });
    </script>
</body>
</html>