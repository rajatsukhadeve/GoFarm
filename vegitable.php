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
        <h1 class="text-2xl font-bold text-gray-800">Fruits</h1>
    </div>

    <!-- Product Container -->
    <div id="product-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 px-6 mt-20 animate-fadeIn w-full max-w-6xl"></div>

    <!-- Floating Add Button -->
    <button onclick="openForm()" class="fixed bottom-6 right-6 bg-blue-900 text-white w-16 h-16 rounded-full flex items-center justify-center shadow-xl animate-bounce hover-scale text-2xl font-bold">
        +
    </button>

    <!-- Add Product Form -->
    <div id="add-product-modal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-96 animate-fadeIn">
            <h2 class="text-xl font-bold mb-4">Add Fruit</h2>
            <input type="text" id="product-name" placeholder="Product Name" class="w-full border p-2 mb-2 rounded">
            <input type="text" id="product-price" placeholder="Price (₹)" class="w-full border p-2 mb-2 rounded">
            <input type="text" id="product-quantity" placeholder="Quantity" class="w-full border p-2 mb-2 rounded">
            <input type="text" id="product-unit" placeholder="Unit (e.g., per KG)" class="w-full border p-2 mb-2 rounded">
            <input type="text" id="product-image" placeholder="Image URL" class="w-full border p-2 mb-4 rounded">
            
            <button onclick="addProduct()" class="w-full bg-blue-600 text-white p-2 rounded transition-all duration-300 hover:bg-blue-700">Add Product</button>
            <button onclick="closeForm()" class="w-full mt-2 bg-gray-400 text-white p-2 rounded transition-all duration-300 hover:bg-gray-500">Cancel</button>
        </div>
    </div>

    <script>
        let products = [];

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
                    </div>
                `;
            });
        }

        function openForm() {
            document.getElementById("add-product-modal").classList.remove("hidden");
        }

        function closeForm() {
            document.getElementById("add-product-modal").classList.add("hidden");
        }

        function addProduct() {
            const name = document.getElementById("product-name").value;
            const price = document.getElementById("product-price").value;
            const quantity = document.getElementById("product-quantity").value;
            const unit = document.getElementById("product-unit").value;
            const image = document.getElementById("product-image").value;

            if (name && price && quantity && unit && image) {
                fetch('add_product.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'name': name,
                        'price': price,
                        'quantity': quantity,
                        'unit': unit,
                        'image': image
                    })
                })
                .then(response => response.text())
                .then(data => {
                    console.log(data); // Log the response for debugging
                    alert(data); // Show success message
                    closeForm(); // Close the modal
                    fetchProducts(); // Refresh the product list
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("Failed to add product.");
                });
            } else {
                alert("Please fill all fields.");
            }
        }

        function removeProduct(index) {
            products.splice(index, 1);
            renderProducts();
        }

        function fetchProducts() {
            fetch('fetch_products.php') // This PHP file should return the list of products in JSON format
                .then(response => response.json())
                .then(data => {
                    products = data;
                    renderProducts();
                })
                .catch(error => console.error('Error fetching products:', error));
        }

        // Initial fetch of products
        fetchProducts();
    </script>
</body>
</html>