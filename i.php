<?php
// Database configuration - same as your admin page
$servername = "localhost";
$username = "root"; // Change to your MySQL username
$password = ""; // Change to your MySQL password
$dbname = "farmer";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle AJAX requests for product data
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
                
            default:
                echo json_encode(['error' => 'Invalid action']);
        }
        exit;
    }
}

// Function to get products by category - same as your admin page
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

// Function to search products - same as your admin page
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

// Close database connection when done processing
// We'll do this after the HTML to allow for initial products to be loaded
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
        <button onclick="toggleCart()" class="bg-green-600 text-white px-6 py-2 rounded-full">
            🛒 Cart (<span id="cartCount">0</span>)
        </button>
    </nav>

    <div class="text-center py-16">
        <h1 class="text-6xl font-extrabold text-green-700">Fresh from Consumer 🥦🍎</h1>
    </div>

    <div class="flex gap-4 justify-center mb-8">
        <button onclick="loadProducts('Vegetables')" class="bg-green-700 text-white px-6 py-2 rounded-full category-btn">Vegetables</button>
        <button onclick="loadProducts('Fruits')" class="bg-green-600 text-white px-6 py-2 rounded-full category-btn">Fruits</button>
        <button onclick="loadProducts('Spices')" class="bg-green-600 text-white px-6 py-2 rounded-full category-btn">Spices</button>
        <button onclick="loadProducts('Dry Fruits')" class="bg-green-600 text-white px-6 py-2 rounded-full category-btn">Dry Fruits</button>
    </div>

    <div id="productsGrid" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-8 p-4">
        <!-- Products will be loaded here -->
        <?php
        // Load initial vegetable products for page load
        $stmt = $conn->prepare("SELECT * FROM products WHERE category = 'Vegetables'");
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($product = $result->fetch_assoc()) {
                echo "
                <div class='bg-white p-6 shadow-xl rounded-lg text-center'>
                    <img src='{$product['image']}' class='h-40 w-40 mx-auto rounded-lg object-cover' onerror=\"this.src='image/default.jpeg'\">
                    <h2 class='text-2xl font-bold mt-4'>{$product['name']}</h2>
                    <p class='text-green-700 font-semibold'>₹{$product['price']} / Kg</p>
                    <button class='bg-green-600 text-white px-6 py-2 rounded-full mt-4' onclick='addToCart(\"{$product['name']}\", {$product['price']})'>Add to Cart</button>
                </div>";
            }
        } else {
            echo "<p class='text-center col-span-full text-xl text-gray-600'>No vegetable products found</p>";
        }
        $stmt->close();
        ?>
    </div>

    <!-- Cart Sidebar -->
    <div id="cartSidebar" class="fixed top-0 right-0 w-full z-50 md:w-1/3 bg-white h-full shadow-2xl p-6 transform translate-x-full transition-transform duration-300">
        <h2 class="text-3xl font-bold mb-6 text-green-700">🛒 Your Cart</h2>
        <div id="cartItems" class="text-gray-700 max-h-[60vh] overflow-y-auto"></div>
        <p id="totalAmount" class="text-2xl font-bold mt-6 text-green-700">Total: ₹0</p>
        <button class="bg-green-600 text-white px-6 py-3 rounded-full mt-4 w-full" onclick="payment()">Pay Now 💳</button>
        <button class="bg-red-500 text-white px-6 py-2 rounded-full mt-4 w-full" onclick="toggleCart()">Close ❌</button>
    </div>

    <script>
        // Global cart variables
        let cart = JSON.parse(localStorage.getItem('cart')) || {};
        let total = Object.values(cart).reduce((sum, item) => sum + (item.price * item.quantity), 0);
        
        // Function to handle API errors
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
                return { error: 'Request failed', products: [] };
            }
        }
        
        // Load products from server
        async function loadProducts(category = 'Vegetables') {
            try {
                // Set active category button
                setActiveCategory(category);
                
                // Fetch products from the API
                const data = await apiCall('getProducts', { category });
                displayProducts(data.products || []);
            } catch (error) {
                handleError(error);
                displayProducts([]);
            }
        }
        
        // Display products in the grid
        function displayProducts(products) {
            const productsGrid = document.getElementById('productsGrid');
            productsGrid.innerHTML = '';
            
            if (!products || products.length === 0) {
                productsGrid.innerHTML = "<p class='text-center col-span-full text-xl text-gray-600'>No products found in this category</p>";
                return;
            }
            
            products.forEach(product => {
                const productHTML = `
                    <div class='bg-white p-6 shadow-xl rounded-lg text-center'>
                        <img src='${product.image}' class='h-40 w-40 mx-auto rounded-lg object-cover' onerror="this.src='image/default.jpeg'">
                        <h2 class='text-2xl font-bold mt-4'>${product.name}</h2>
                        <p class='text-green-700 font-semibold'>₹${product.price} / Kg</p>
                        <button class='bg-green-600 text-white px-6 py-2 rounded-full mt-4' onclick='addToCart("${product.name}", ${product.price})'>Add to Cart</button>
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
                displayProducts(data.products || []);
                
                // Reset active category
                resetActiveCategories();
            } catch (error) {
                handleError(error);
            }
        }
        
        // Cart Functions
        function addToCart(product, price) {
            if (!cart[product]) {
                cart[product] = { price: price, quantity: 0 };
            }
            cart[product].quantity++;
            total += price;
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartUI();
            
            // Show confirmation feedback
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg';
            toast.innerText = `Added ${product} to cart`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2000);
        }
        
        function removeFromCart(product) {
            if (cart[product]) {
                total -= cart[product].price;
                cart[product].quantity--;
                if (cart[product].quantity <= 0) {
                    delete cart[product];
                }
                localStorage.setItem('cart', JSON.stringify(cart));
                updateCartUI();
            }
        }
        
        function updateCartUI() {
            document.getElementById("cartCount").innerText = Object.values(cart).reduce((acc, item) => acc + item.quantity, 0);
            document.getElementById("cartItems").innerHTML = Object.keys(cart).length === 0 
                ? "<p class='text-gray-500 text-center'>Your cart is empty</p>" 
                : Object.keys(cart)
                    .map(item => `
                        <div class="flex justify-between items-center mb-2 pb-2 border-b">
                            <div>
                                <span class="font-semibold">${item}</span>
                                <div>x${cart[item].quantity} = ₹${cart[item].quantity * cart[item].price}</div>
                            </div>
                            <div class="flex items-center">
                                <button onclick="removeFromCart('${item}')" class="bg-red-500 text-white px-2 py-1 rounded text-sm">-</button>
                                <button onclick="addToCart('${item}', ${cart[item].price})" class="bg-green-500 text-white px-2 py-1 rounded text-sm ml-2">+</button>
                            </div>
                        </div>
                    `)
                    .join('');
            document.getElementById("totalAmount").innerText = `Total: ₹${total}`;
        }
        
        function toggleCart() {
            document.getElementById("cartSidebar").classList.toggle("translate-x-full");
        }
        
        function payment() {
            if (total > 0) {
                alert("Payment Successful ✅ Thank you for shopping with GOFaRm!");
                cart = {};
                total = 0;
                localStorage.removeItem('cart');
                updateCartUI();
                toggleCart();
            } else {
                alert("Your cart is empty!");
            }
        }
        
        // Helper functions for UI
        function setActiveCategory(category) {
            const categoryButtons = document.querySelectorAll('.category-btn');
            categoryButtons.forEach(btn => {
                btn.classList.remove('bg-green-700');
                btn.classList.add('bg-green-600');
                
                if (btn.textContent.trim() === category) {
                    btn.classList.remove('bg-green-600');
                    btn.classList.add('bg-green-700');
                }
            });
        }
        
        function resetActiveCategories() {
            const categoryButtons = document.querySelectorAll('.category-btn');
            categoryButtons.forEach(btn => {
                btn.classList.remove('bg-green-700');
                btn.classList.add('bg-green-600');
            });
        }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', () => {
            // Search on keyup
            document.getElementById('searchBar').addEventListener('keyup', (e) => {
                if (e.key === 'Enter' || e.target.value.length > 2 || e.target.value.length === 0) {
                    searchProducts();
                }
            });
            
            // Update cart UI on load
            updateCartUI();
        });
    </script>
</body>
</html>
<?php
// Close the database connection at the end of the page
$conn->close();
?>