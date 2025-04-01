window.products = [];

// Function to toggle product details
function toggleDetails(id) {
  const detailsRow = document.getElementById(id);
  detailsRow.classList.toggle("hidden");
}

// getMe
async function getMe() {
  let response = await fetch("/wp-json/wp/v2/users/me", {
    method: "GET",
    headers: {
      "X-WP-Nonce": amazonWooData.nonce,
      "Content-Type": "application/json",
    },
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
        "X-WP-Nonce": amazonWooData.nonce,
        "Content-Type": "application/json",
      },
      body: JSON.stringify(product),
    });

    const data = await response.json();

    if (response.ok) {
      console.log("Import response:", data);

      Toastify({
        text: `Product imported successfully.`,
      }).showToast();

      window.products[index] = {
        ...product,
        status: "imported",
        product_id: data.product_id,
      };
    } else {
      console.error("Error importing product:", data);

      Toastify({
        text: `Error importing product.\n${data.message}`,
      }).showToast();

      window.products[index] = {
        ...product,
        status: "error",
        message: data.message,
      };
    }
  } catch (error) {
    console.error("Fetch error:", error);
    // alert("An error occurred while importing the product.");
    Swal.fire({
      text: "An error occurred while importing the product.",
      icon: "error",
    });
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
                    <td class='border px-4 py-2'><img src="${
                      data.images ? data.images[0] : ""
                    }" width="50"></td>
                    <td class='border px-4 py-2'>${data.title}</td>
                    <td class='border px-4 py-2'>${data.sale_price}</td>
                    <td class='border px-4 py-2'><a href="/wp-admin/post.php?post=${
                      data.product_id
                    }&action=edit" target="_blank" class="text-blue-500">Product #${
        data.product_id
      }</a></td>
                    </tr>`;
      tableBody.innerHTML += row;
      continue;
    }

    if (data.status === "error") {
      let row = `<tr>
                    <td class='border px-4 py-2'>${index + 1}</td>
                    <td class='border px-4 py-2'>${data.sku}</td>
                    <td class='border px-4 py-2' colspan="4">${
                      data.message
                    }</td>
                    </tr>`;
      tableBody.innerHTML += row;
      continue;
    }

    let row = `<tr>
                <td class='border px-4 py-2'>${index + 1}</td>
                <td class='border px-4 py-2'>${data.sku}</td>
                <td class='border px-4 py-2'><img src="${
                  data.images ? data.images[0] : ""
                }" width="50"></td>
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
                <p><strong>Description:</strong> ${
                  data.description || "No description available"
                }</p>
                <p><strong>Price:</strong> ${data.sale_price}</p>

                <div class="mt-3">
                    <p><strong>Categories:</strong></p>
                        <ul class="list-disc ml-5">
                            ${
                              data.categories
                                ? data.categories
                                    .map((feat) => `<li>${feat}</li>`)
                                    .join("")
                                : "No categories available"
                            }
                        </ul>
                </div>

                <div class="mt-3">
                    <p><strong>Features:</strong></p>
                    <ul class="list-disc ml-5">
                        ${
                          data.feature
                            ? data.feature
                                .map((feat) => (feat ? `<li>${feat}</li>` : ""))
                                .join("")
                            : "No features available"
                        }
                    </ul>
                </div>

                <div class="mt-3">
                    <p><strong>Information:</strong></p>
                        <ul class="list-disc ml-5">
                            ${
                              data.information
                                ? data.information
                                    .map(
                                      (feat) =>
                                        `<li>${feat?.name}: ${feat?.value}</li>`
                                    )
                                    .join("")
                                : "No information available"
                            }
                        </ul>
                </div>

                <div class="mt-3">
                    <p><strong>Images:</strong></p>
                    <div class="flex flex-wrap">
                        ${data.images
                          .slice(1)
                          .map(
                            (img) =>
                              `<img src="${img}" width="100" class="mr-2 mb-2">`
                          )
                          .join("")}
                    </div>
                </div>

                <div class="mt-5">
                    <h3 class="text-lg font-bold mb-2">Reviews</h3>
                    <div class="reviews-container">
                        ${
                          data.reviews && data.reviews.length > 0
                            ? data.reviews
                                .map(
                                  (review) => `
                                <div class="review-item border-b pb-3 mb-3">
                                    <div class="flex items-center mb-1">
                                        <span class="font-semibold mr-2">${review.name}</span>
                                        <span class="text-yellow-500">${review.rating} ★</span>
                                        <span class="text-gray-500 text-sm ml-2">${review.date}</span>
                                    </div>
                                    <p class="mb-1">${review.comment}</p>
                                    <span class="text-gray-500 text-xs">${review.location}</span>
                                </div>`
                                )
                                .join("")
                            : "No reviews available"
                        }
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

  document.querySelectorAll(".collapsible").forEach((button) => {
    button.addEventListener("click", function () {
      this.classList.toggle("active");
      let content = this.nextElementSibling;
      content.style.display =
        content.style.display === "block" ? "none" : "block";
    });
  });

  // submit form to scrape Amazon product data
  document
    .getElementById("amazon-crawler-form")
    .addEventListener("submit", async function (e) {
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

      // load proxies
      let proxies = localStorage.getItem("amazon_woo_crawler_proxies");
      if (proxies) {
        proxies = JSON.parse(proxies);
      }

      for (let i = 0; i < urls.length; i++) {
        let url = urls[i];

        try {
          let apiUrl = `/wp-json/amazon-crawler/v1/scrape?url=${encodeURIComponent(
            `https://www.amazon.com/dp/${url}`
          )}`;

          if (proxies && proxies.length > 0) {
            let proxy = proxies[Math.floor(Math.random() * proxies.length)];
            apiUrl += `&proxy_url=${encodeURIComponent(
              proxy.ip + ":" + proxy.port
            )}`;
            if (proxy.username)
              apiUrl += `&proxy_username=${encodeURIComponent(proxy.username)}`;
            if (proxy.password)
              apiUrl += `&proxy_password=${encodeURIComponent(proxy.password)}`;
          }

          let response = await fetch(apiUrl);
          let data = await response.json();

          if (!response.ok) {
            console.log("error", data);
            throw new Error(data?.message || "Unknown error occurred");
          }

          window.products.push({ ...data, status: "success" });
        } catch (error) {
          console.error(`Error scraping ${url}:`, error.response, error.data);

          window.products.push({
            sku: url,
            status: "error",
            message: error.message || "Unknown error occurred",
          });
        }
        renderProducts();
        // sleep for 3 second
        await new Promise((resolve) => setTimeout(resolve, 3000));
      }

      button.textContent = "Start Scraping";
      button.disabled = false;
      document.getElementById("amazon_urls").value = "";
    });

  // load saved proxies
  let savedProxies = localStorage.getItem("amazon_woo_crawler_proxies");
  if (savedProxies) {
    document.getElementById("proxy_list").value = JSON.parse(savedProxies)
      .map((p) => {
        let m = `${p.ip}:${p.port}`;
        if (p.username) m += `:${p.username}`;
        if (p.password) m += `:${p.password}`;
        return m;
      })
      .join("\n");
  }

  document
    .getElementById("amazon-crawler-settings")
    .addEventListener("submit", function (e) {
      e.preventDefault();
      let proxyList = document.getElementById("proxy_list").value;
      let proxies = proxyList
        .split("\n")
        .map((line) => {
          // ip:port:username:password
          let parts = line.trim().split(":");

          if (parts.length < 2) return false;

          return {
            ip: parts[0],
            port: parts[1],
            username: parts[2] || null,
            password: parts[3] || null,
          };
        })
        .filter((m) => m);

      // save to local storage
      localStorage.setItem(
        "amazon_woo_crawler_proxies",
        JSON.stringify(proxies)
      );
      alert("Settings saved successfully.");
    });

  document.getElementById("clear-data").addEventListener("click", function (e) {
    e.preventDefault();
    let yes = confirm(
      "This will clear all scraped data. Are you sure? Make sure to save any important data first."
    );
    if (yes) {
      window.products = [];
      renderProducts();
      document.getElementById("amazon_urls").value = "";
    }
  });
});
