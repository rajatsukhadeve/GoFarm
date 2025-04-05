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

// Handle form submission to add a new fruit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_fruit'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];
    $image = $_POST['image']; // Assuming image path is provided
    $info = isset($_POST['info']) ? $_POST['info'] : ''; // Default to empty string if not set
    $videopath = isset($_POST['videopath']) ? $_POST['videopath'] : ''; // Default to empty string if not set

    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO vegitablee (name, price, quantity, unit, image, info, videopath) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    // Check if the statement was prepared successfully
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param("sdsssss", $name, $price, $quantity, $unit, $image, $info, $videopath);
    
    // Execute the statement
    if ($stmt->execute()) {
        echo "New record created successfully";
    } else {
        echo "Error: " . $stmt->error; // Output error if execution fails
    }

    $stmt->close();
}

// SQL query to select all records from the 'vegitablee' table
$sql = "SELECT * FROM vegitablee";
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
    <title>Fruits</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeIn {
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .animate-bounce {
            animation: bounce 1.5s infinite;
        }
        .hover-scale:hover {
            transform: scale(1.08);
            transition: transform 0.3s ease-in-out;
        }
    </style>
</head>
<body class="bg-gradient-to-r from-green-100 to-green-400 min-h-screen flex flex-col items-center font-sans">

    <!-- Header -->
    <div class="w-full flex items-center justify-between px-6 py-4 bg-white shadow-lg animate-fadeIn fixed top-0 left-0 right-0 z-10">
        <a href="./inProduct.html">
            <button class="w-10 h-10 transition-all duration-300 hover-scale">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="black"> 
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">vegitable</h1>
        <button class="w-10 h-10">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="black">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M5 12h14M9 20h6" />
            </svg>
        </button>
    </div>

    <button onclick="openForm()" class="fixed bottom-6 right-6 bg-yellow-600 text-white w-16 h-16 rounded-full flex items-center justify-center shadow-lg animate-bounce transition-all duration-300 hover:scale-110">
        +
    </button>

    <!-- Add Product Form -->
    <div id="add-product-modal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-96 animate-fadeIn">
            <h2 class="text-xl font-bold mb-4">Add vegitablee</h2>
            <form id="add-product-form" method="POST" action="">
                <input type="text" name="name" placeholder="vegitablee" class="w-full border p-2 mb-2 rounded" required>
                <input type="text" name="price" placeholder="Price (₹)" class="w-full border p-2 mb-2 rounded" required>
                <input type="text" name="quantity" placeholder="Quantity" class="w-full border p-2 mb-2 rounded" required>
                <input type="text" name="unit" placeholder="Unit (e.g., per Litre, per KG)" class="w-full border p-2 mb-2 rounded" required>
                <input type="text" name="image" placeholder="Image URL" class="w-full border p-2 mb-4 rounded" required>
                <input type="text" name="info" placeholder="Additional Info" class="w-full border p-2 mb-4 rounded"> <!-- Optional field -->
                <input type="text" name="videopath" placeholder="Video Path" class="w-full border p-2 mb-4 rounded"> <!-- Optional field -->
                <input type="hidden" name="add_fruit" value="1"> <!-- Hidden field to identify the form submission -->
                
                <button type="submit" class="w-full bg-yellow-600 text-white p-2 rounded transition-all duration-300 hover:bg-yellow-700">Add Product</button>
                <button type="button" onclick="closeForm()" class="w-full mt-2 bg-gray-400 text-white p-2 rounded transition-all duration-300 hover:bg-gray-500">Cancel</button>
            </form>
        </div>
    </div>

    <div id="product-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 px-6 mt-20 animate-fadeIn w-full max-w-6xl"></div>

    <script>


        let products = <?php echo json_encode($products); ?>; // Use PHP to pass products to JavaScript

        function renderProducts() {
            const container = document.getElementById("product-container");
            container.innerHTML = "";
            products.forEach((product, index) => {
                container.innerHTML += `
                    <div class="bg-white rounded-lg shadow-lg p-6 flex flex-col items-center relative hover-scale animate-fadeIn w-full max-w-sm">
                        <button onclick="removeProduct(${index})" class="absolute top-2 right-2 w-7 h-7 bg-red-500 text-white rounded-full flex items-center justify-center font-bold shadow-md hover:bg-red-600">✖</button>
                        <img src="${product.image}" alt="${product.name}" class="rounded-full w-28 h-28 bg-gray-100 p-3 mb-3 hover-scale shadow-md">
                        <p class="text-green-600 font-semibold text-xl">${product.price}</p>
                        <p class="text-gray-500 text-md">Quantity: ${product.quantity}</p>
                        <p class="font-bold text-lg text-gray-800">${product.name}</p>
                        <p class="text-sm text-gray-500">${product.unit}</p>

                        <button class="mt-3 bg-blue-500 text-white px-6 py-2 rounded-lg hover-scale shadow-md transition-all duration-300 hover:bg-blue-700" onclick="addInformation(${index})">view Information</button>

                        <button class="mt-2 bg-green-500 text-white px-6 py-2 rounded-lg hover-scale shadow-md transition-all duration-300 hover:bg-green-700" onclick="uploadVideo(${index})">view Video</button>

                        <div id="info-container-${index}" class="mt-3"></div>
                        <div id="button-container-${index}" class="mt-3"></div>
                    </div>
                `;
            });
        }

        function removeProduct(index) {
            products.splice(index, 1);
            renderProducts();
        }

        function openForm() {
            document.getElementById("add-product-modal").classList.remove("hidden");
        }

        function closeForm() {
            document.getElementById("add-product-modal").classList.add("hidden");
        }

        renderProducts();
    </script>
</body>
</html>