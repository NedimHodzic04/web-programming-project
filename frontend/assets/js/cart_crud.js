document.addEventListener("DOMContentLoaded", function () {
    const cartTable = document.querySelector("#cartTable tbody");
    const subtotalElement = document.querySelector("#subtotal");
    const totalPriceElement = document.querySelector("#totalPrice");
    const checkoutBtn = document.querySelector("#checkoutBtn");

    // Load cart items from Local Storage or initialize with an empty array
    let cartItems = JSON.parse(localStorage.getItem("cartItems")) || [];

    // Save cart to Local Storage
    function saveToLocalStorage() {
        localStorage.setItem("cartItems", JSON.stringify(cartItems));
    }

    // Load cart items into table
    function loadCart() {
        cartTable.innerHTML = ""; // Clear current table content
        cartItems.forEach((item, index) => {
            const row = `
                <tr data-index="${index}">
                    <td class="d-flex gap-3 align-items-center">
                        <img src="${item.image}" alt="${item.name}" width="75px">
                        <p>${item.name}</p>
                    </td>
                    <td>$${item.price.toFixed(2)}</td>
                    <td>
                        <input type="number" class="form-control quantity-input" value="${item.quantity}" min="1" onchange="updateQuantity(${index}, this.value)">
                    </td>
                    <td class="total-price">$${(item.price * item.quantity).toFixed(2)}</td>
                    <td><button class="btn btn-danger btn-sm remove-btn">Remove</button></td>
                </tr>
            `;
            cartTable.insertAdjacentHTML("beforeend", row);
        });
        updateCartSummary();
    }

    // Update quantity of an item in the cart
    function updateQuantity(index, quantity) {
        cartItems[index].quantity = parseInt(quantity);
        saveToLocalStorage();
        loadCart();
    }

    // Remove item from the cart
    function removeFromCart(index) {
        cartItems.splice(index, 1);
        saveToLocalStorage();
        loadCart();
    }

    // Update cart summary (Subtotal and Total)
    function updateCartSummary() {
        const subtotal = cartItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        subtotalElement.textContent = subtotal.toFixed(2);
        totalPriceElement.textContent = subtotal.toFixed(2); // Assuming no shipping cost
    }

    // Handle checkout
    checkoutBtn.addEventListener("click", function () {
        if (cartItems.length === 0) {
            alert("Your cart is empty. Please add items before checkout.");
            return;
        }

        alert("Proceeding to checkout...");
        cartItems = []; // Clear the cart after checkout
        saveToLocalStorage();
        loadCart();
    });

    // Example of adding products to the cart (this should be triggered from product pages)
    function addToCart(product) {
        const existingProduct = cartItems.find(item => item.name === product.name);
        if (existingProduct) {
            existingProduct.quantity += 1; // Increment quantity if the product already exists
        } else {
            cartItems.push(product); // Otherwise, add new product
        }
        saveToLocalStorage();
        loadCart();
    }

    // Example product objects for testing
    const sampleProduct1 = { name: "Brake Pads", price: 50.00, quantity: 1, image: "/frontend/assets/images/brake-pads.png" };
    const sampleProduct2 = { name: "MOMO Racing Wheel", price: 350.00, quantity: 1, image: "/frontend/assets/images/momo-wheel.png" };

    // For testing, simulate adding products to the cart
    addToCart(sampleProduct1);
    addToCart(sampleProduct2);

    loadCart(); // Load cart items on page load

    cartTable.addEventListener("click", function (e) {
        if (e.target && e.target.classList.contains("remove-btn")) {
            const row = e.target.closest("tr");
            const index = row.getAttribute("data-index");
            removeFromCart(index);
        }
    });

    cartTable.addEventListener("input", function (e) {
        if (e.target && e.target.classList.contains("quantity-input")) {
            const row = e.target.closest("tr");
            const index = row.getAttribute("data-index");
            const quantity = e.target.value;
            updateQuantity(index, quantity);
            const totalPrice = (cartItems[index].price * quantity).toFixed(2);
            row.querySelector(".total-price").textContent = `$${totalPrice}`;
        }
    });
});
