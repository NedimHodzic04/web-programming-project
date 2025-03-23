document.addEventListener("DOMContentLoaded", function () {
    const saveProductBtn = document.querySelector("#saveProductBtn");
    const productForm = document.querySelector("#addProductModal form");
    const productsTable = document.querySelector("#adminProductsTB");
    let editIndex = null;

    // Load products from Local Storage or initialize with an empty array
    let products = JSON.parse(localStorage.getItem("products")) || [];

    function saveToLocalStorage() {
        localStorage.setItem("products", JSON.stringify(products));
    }

    function loadProducts() {
        productsTable.innerHTML = "";
        products.forEach((product, index) => {
            const row = `
                <tr data-index="${index}">
                    <td>${product.id}</td>
                    <td>${product.name}</td>
                    <td>$${product.price.toFixed(2)}</td>
                    <td>${product.stock}</td>
                    <td>${product.category}</td>
                    <td><img src="${product.image}" alt="${product.name}" style="width: 50px; height: 50px; object-fit: cover;"></td>
                    <td>
                        <button class="btn btn-warning btn-sm edit-btn">Edit</button>
                        <button class="btn btn-danger btn-sm delete-btn">Delete</button>
                    </td>
                </tr>
            `;
            productsTable.insertAdjacentHTML("beforeend", row);
        });
    }

    saveProductBtn.addEventListener("click", function (e) {
        e.preventDefault();

        const name = document.querySelector("#productNameInput").value;
        const price = parseFloat(document.querySelector("#priceInput").value);
        const stock = parseInt(document.querySelector("#stockQuantityInput").value);
        const category = document.querySelector("#selectionInput").value;
        const image = "/frontend/assets/images/default.webp"; // Placeholder for file upload

        if (!name || isNaN(price) || isNaN(stock) || category === "Select Category") {
            alert("Please fill out all fields correctly.");
            return;
        }

        if (editIndex !== null) {
            products[editIndex] = { id: products[editIndex].id, name, price, stock, category, image };
            editIndex = null;
        } else {
            const newProduct = { id: (products.length + 1).toString().padStart(3, "0"), name, price, stock, category, image };
            products.push(newProduct);
        }

        saveToLocalStorage(); // Save changes to Local Storage
        productForm.reset();
        document.querySelector("#addProductModal .btn-close").click();
        loadProducts();
    });

    productsTable.addEventListener("click", function (e) {
        const row = e.target.closest("tr");
        const index = row.getAttribute("data-index");

        if (e.target.classList.contains("edit-btn")) {
            const product = products[index];
            document.querySelector("#productNameInput").value = product.name;
            document.querySelector("#priceInput").value = product.price;
            document.querySelector("#stockQuantityInput").value = product.stock;
            document.querySelector("#selectionInput").value = product.category;
            editIndex = index;
            new bootstrap.Modal(document.querySelector("#addProductModal")).show();
        }

        if (e.target.classList.contains("delete-btn")) {
            if (confirm("Are you sure you want to delete this product?")) {
                products.splice(index, 1);
                saveToLocalStorage(); // Save after deleting
                loadProducts();
            }
        }
    });

    loadProducts(); // Load products on page load
});