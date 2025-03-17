<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function amazon_woo_crawler_admin_page()
{
    ?>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <div class="wrap p-6">
        <h1 class="text-2xl font-bold mb-4">Amazon to WooCommerce Crawler</h1>

        <!-- Settings Form -->
        <button class="collapsible w-full bg-gray-200 px-4 py-2 text-left text-lg font-semibold">Settings</button>
        <div class="content p-4 border rounded bg-white" style="display: none;">
            <form id="amazon-crawler-settings" class="space-y-4">
                <label for="proxy_list" class="block text-sm font-medium text-gray-700">Proxy List (one per line):</label>
                <textarea id="proxy_list" name="proxy_list" rows="3" class="w-full border rounded p-2"></textarea>
                <button type="submit" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    Save Settings
                </button>
            </form>
        </div>

        <hr class="my-6">
        <!-- URL Input Form -->
        <div class="content p-4 border rounded bg-white">
            <form id="amazon-crawler-form" class="space-y-4">
                <label for="amazon_urls" class="block text-sm font-medium text-gray-700">Amazon Product URLs (one per
                    line):</label>
                <textarea id="amazon_urls" name="amazon_urls" rows="5"
                    class="w-full border rounded p-2">https://www.amazon.com/dp/B07WC1846W</textarea>
                <button id="start-scraping" type="submit"
                    class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Start
                    Scraping
                </button>
                <button id="clear-data" type="button" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Clear
                </button>
            </form>
        </div>

        <hr class="my-6">



        <!-- Results Section -->
        <button class="collapsible w-full bg-gray-200 px-4 py-2 text-left text-lg font-semibold">Scraped Products</button>
        <div class="content p-4 border rounded bg-white">
            <table class="w-full border-collapse border border-gray-200">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border px-4 py-2">#</th>
                        <th class="border px-4 py-2">SKU</th>
                        <th class="border px-4 py-2">Image</th>
                        <th class="border px-4 py-2">Title</th>
                        <th class="border px-4 py-2">Price</th>
                        <th class="border px-4 py-2 w-48">Actions</th>
                    </tr>
                </thead>
                <tbody id="scraped-products">
                    <tr>
                        <td colspan="6" class="border px-4 py-2 text-center">No results yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        window.products = [];

        // Function to toggle product details
        function toggleDetails(id) {
            const detailsRow = document.getElementById(id);
            detailsRow.classList.toggle("hidden");
        }
        // getMe 
        async function getMe() {
            let response = await fetch("<?php echo rest_url('/wp/v2/users/me'); ?>", {
                method: "GET",
                headers: {
                    "X-WP-Nonce": "<?php echo wp_create_nonce('wp_rest'); ?>",
                    "Content-Type": "application/json"
                }
            });

            let data = await response.json();
            console.log("Me:", data);

        }
        // importProduct
        async function importProduct(event, index) {
            let thisBtn = event.target;
            thisBtn.textContent = "Importing...";
            thisBtn.disabled = true;

            const product = window.products[index];
            console.log("Importing product:", product);

            try {
                let response = await fetch("/wp-json/amazon-crawler/v1/import", {
                    method: "POST",
                    headers: {
                        "X-WP-Nonce": "<?php echo wp_create_nonce('wp_rest'); ?>",
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(product),
                });

                const data = await response.json();

                if (response.ok) {
                    console.log("Import response:", data);
                    alert("Product imported successfully.");
                    window.products[index] = { ...product, status: "imported", product_id: data.product_id };
                } else {
                    console.error("Error importing product:", data);
                    alert(`Error importing product.\n${data.message}`);
                    window.products[index] = { ...product, status: "error", message: data.message };
                }
            } catch (error) {
                console.error("Fetch error:", error);
                alert("An error occurred while importing the product.");
            }

            renderProducts();
            thisBtn.textContent = "Import";
            thisBtn.disabled = false;
        }


        function renderProducts() {
            const tableBody = document.getElementById("scraped-products");
            tableBody.innerHTML = "";

            for (let index = 0; index < window.products.length; index++) {
                const data = window.products[index];

                let rowId = `details-${index}`;

                if (data.status === "imported") {
                    let row = `<tr>
                    <td class='border px-4 py-2'>${index + 1}</td>
                    <td class='border px-4 py-2'>${data.sku}</td>
                    <td class='border px-4 py-2'><img src="${data.images ? data.images[0] : ''}" width="50"></td>
                    <td class='border px-4 py-2'>${data.title}</td>
                    <td class='border px-4 py-2'>${data.sale_price}</td>
                    <td class='border px-4 py-2'><a href="/wp-admin/post.php?post=${data.product_id}&action=edit" target="_blank" class="text-blue-500">Product #${data.product_id}</a></td>
                    </tr>`;
                    tableBody.innerHTML += row;
                    continue;
                }

                if (data.status === "error") {
                    let row = `<tr>
                    <td class='border px-4 py-2'>${index + 1}</td>
                    <td class='border px-4 py-2'>${data.sku}</td>
                    <td class='border px-4 py-2' colspan="4">${data.message}</td>
                    </tr>`;
                    tableBody.innerHTML += row;
                    continue;
                }

                let row = `<tr>
                <td class='border px-4 py-2'>${index + 1}</td>
                <td class='border px-4 py-2'>${data.sku}</td>
                <td class='border px-4 py-2'><img src="${data.images ? data.images[0] : ''}" width="50"></td>
                <td class='border px-4 py-2'>${data.title}</td>
                <td class='border px-4 py-2'>${data.sale_price}</td>

                <td class='border px-4 py-2 w-48'>
                <button class='bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 import-btn' data-index="${index}">Import</button>
                <button class='bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600' onclick="toggleDetails('${rowId}')">View</button>
                </td>
                </tr>

                <tr id="${rowId}" class="hidden">
                <td colspan="6" class="border px-4 py-2 bg-gray-100">
                <div class="product-details">
                <h3 class="text-lg font-bold mb-2">Product Details</h3>
                <p><strong>Description:</strong> ${data.description || "No description available"}</p>
                <p><strong>Price:</strong> ${data.sale_price}</p>

                <div class="mt-3">
                    <p><strong>Categories:</strong></p>
                        <ul class="list-disc ml-5">
                            ${data.categories ?
                    data.categories.map(feat => `<li>${feat}</li>`).join("") :
                    "No categories available"
                }
                        </ul>
                </div>

                <div class="mt-3">
                    <p><strong>Features:</strong></p>
                    <ul class="list-disc ml-5">
                        ${data.feature ? data.feature.map(feat => feat ? `<li>${feat}</li>` : '').join("") : "No features available"}
                    </ul>
                </div>

                <div class="mt-3">
                    <p><strong>Information:</strong></p>
                        <ul class="list-disc ml-5">
                            ${data.information ?
                    data.information.map(feat => `<li>${feat?.name}: ${feat?.value}</li>`).join("") :
                    "No information available"
                }
                        </ul>
                </div>

                <div class="mt-3">
                    <p><strong>Images:</strong></p>
                    <div class="flex flex-wrap">
                        ${data.images.slice(1).map(img => `<img src="${img}" width="100" class="mr-2 mb-2">`).join("")}
                    </div>
                </div>

                <div class="mt-5">
                    <h3 class="text-lg font-bold mb-2">Reviews</h3>
                    <div class="reviews-container">
                        ${data.reviews && data.reviews.length > 0 ?
                    data.reviews.map(review => `
                            <div class="review-item border-b pb-3 mb-3">
                                <div class="flex items-center mb-1">
                                    <span class="font-semibold mr-2">${review.name}</span>
                                    <span class="text-yellow-500">${review.rating} ★</span>
                                    <span class="text-gray-500 text-sm ml-2">${review.date}</span>
                                </div>
                                <p class="mb-1">${review.comment}</p>
                                <span class="text-gray-500 text-xs">${review.location}</span>
                            </div>
                            `).join("")
                    : "No reviews available"}
                    </div>
                </div>
                </div>
                </td>
                </tr>`;
                tableBody.innerHTML += row;
            }

            document.querySelectorAll(".import-btn").forEach((button) => {
                button.addEventListener("click", function (event) {
                    let index = event.target.getAttribute("data-index");
                    console.log("Importing product at index:", index);
                    importProduct(event, index);
                });
            });
        }

        function extractASINs(input) {
            const asinRegex = /\b(B0[A-Z0-9]{8})\b/g;
            const matches = input.match(asinRegex);
            return matches ? [...new Set(matches)] : [];
        }

        document.addEventListener("DOMContentLoaded", function () {
            console.log("Amazon Crawler Admin Page Loaded", getMe());

            document.querySelectorAll(".collapsible").forEach(button => {
                button.addEventListener("click", function () {
                    this.classList.toggle("active");
                    let content = this.nextElementSibling;
                    content.style.display = content.style.display === "block" ? "none" : "block";
                });
            });


            // submit form to scrape Amazon product data
            document.getElementById("amazon-crawler-form").addEventListener("submit", async function (e) {
                e.preventDefault();

                let urls = document.getElementById("amazon_urls").value;
                if (!urls) {
                    alert("Please enter at least one URL.");
                    return;
                }

                urls = extractASINs(urls);
                if (urls.length === 0) {
                    alert("No valid Amazon URLs found.");
                    return;
                }

                console.log("Scraping URLs:", urls);

                const button = document.getElementById("start-scraping");
                button.textContent = "Loading...";
                button.disabled = true;

                for (const url of urls) {
                    try {
                        const apiUrl = `/wp-json/amazon-crawler/v1/scrape?url=${encodeURIComponent(`https://www.amazon.com/dp/${url}`)}`;
                        let response = await fetch(apiUrl);
                        let data = await response.json();

                        if (!response.ok) {
                            console.log("error", data)
                            throw new Error(data?.message || "Unknown error occurred");
                        }

                        window.products.push({ ...data, status: "success", });
                    } catch (error) {
                        console.error(`Error scraping ${url}:`, error.response, error.data);

                        window.products.push({
                            sku: url,
                            status: "error",
                            message: error.message || "Unknown error occurred",
                        });
                    }
                    renderProducts();
                }
                button.textContent = "Start Scraping";
                button.disabled = false;
                document.getElementById("amazon_urls").value = "";


            });

            document.getElementById("amazon-crawler-settings").addEventListener("submit", function (e) {
                e.preventDefault();
                alert("Settings saved (but no backend handler yet).");
            });

            document.getElementById("clear-data").addEventListener("click", function (e) {
                e.preventDefault();
                let yes = confirm("This will clear all scraped data. Are you sure? Make sure to save any important data first.")
                if (yes) {
                    window.products = [];
                    renderProducts();
                    document.getElementById("amazon_urls").value = "";
                }
            });


        });
    </script>
    <?php
}