<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale | MJ's Pharmacy</title>
    <link rel="stylesheet" href="{{ asset('css/pos.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>
    @include('admin.admin-header')
    <header class="header-bar">
        <h2 class="page-title">Walk Ins</h2>
    </header>

    <main class="pos-main">

        <div class="pos-content">
            <section class="left-panel">
                <div class="card product-search">
                    <h2>Products</h2>
                    <hr>

                    <div class="search-container">
                        <input type="text" id="productSearch" placeholder="Search products..." class="search-input">
                        <select id="categoryFilter" class="filter-select">
                            <option value="">All Categories</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="product-list" id="productList">
                        <table class="product-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($products as $product)
                                    <tr data-id="{{ $product->id }}">
                                        <td>
                                            <div class="product-name">{{ $product->product_name }}</div>
                                            <div class="product-brand">{{ $product->brand_name }}</div>
                                            @if ($product->batches->first() && $product->batches->first()->expiration_date <= now()->addDays(30))
                                                <div class="product-expiry">Expires:
                                                    {{ $product->batches->first()->expiration_date->format('M d, Y') }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="product-category">
                                                {{ $product->category->name ?? 'Uncategorized' }}</div>
                                        </td>
                                        <td>
                                            <div class="product-price">
                                                ₱{{ number_format($product->batches->first()->sale_price ?? 0, 2) }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="product-stock">{{ $product->stock_quantity ?? 0 }}</div>
                                        </td>
                                        <td class="action-cell">
                                            <button type="button" class="btn-add-to-cart"
                                                onclick="addToCart({{ $product->id }})">
                                                Add to Cart
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination-container">
                        {{ $products->links() }}
                    </div>
                </div>
            </section>

            <section class="right-panel">
                <div class="card cart-section">
                    <h2>Cart</h2>
                    <hr>

                    <div class="cart-items" id="cartItems">
                        <div class="empty-cart">
                            <p>No items in cart</p>
                        </div>
                    </div>

                    <div class="cart-summary" id="cartSummary" style="display: none;">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span id="subtotal">₱0.00</span>
                        </div>
                        <div class="summary-row">
                            <span>Discount:</span>
                            <span>
                                <input type="number" id="discountAmount" placeholder="0.00" min="0"
                                    step="0.01" onchange="calculateTotal()" class="discount-input">
                            </span>
                        </div>
                        <div class="summary-row total">
                            <span><strong>Total:</strong></span>
                            <span id="total"><strong>₱0.00</strong></span>
                        </div>
                    </div>

                    <div class="payment-section" id="paymentSection" style="display: none;">
                        <h3>Payment</h3>

                        <label for="customerName">Customer Name (Optional)</label>
                        <input type="text" id="customerName" placeholder="Enter customer name">

                        <label for="paymentMethod">Payment Method</label>
                        <select id="paymentMethod">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="gcash">GCash</option>
                        </select>

                        <label for="amountPaid">Amount Paid</label>
                        <input type="number" id="amountPaid" placeholder="0.00" min="0" step="0.01"
                            onchange="calculateChange()">

                        <div class="change-display" id="changeDisplay" style="display: none;">
                            <span>Change: </span>
                            <span id="changeAmount">₱0.00</span>
                        </div>

                        <label for="notes">Notes (Optional)</label>
                        <textarea id="notes" rows="2" placeholder="Additional notes..."></textarea>
                    </div>

                    <div class="action-buttons">
                        <button type="button" class="btn-cancel" onclick="clearCartWithConfirmation()">Clear
                            Cart</button>
                        <button type="button" class="btn-submit" id="submitBtn" onclick="processTransaction()"
                            disabled>
                            <span class="btn-text">Complete Sale</span>
                            <span class="btn-loading" style="display: none;">Processing...</span>
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <div id="receiptModal" class="modal-overlay">
        <div class="modal-content receipt-modal">
            <div class="receipt-header">
                <h2>Transaction Complete</h2>
                <button class="modal-close" onclick="hideReceiptModal()">&times;</button>
            </div>
            <div id="receiptContent">
            </div>
            <div class="receipt-actions">
                <button class="btn-print" onclick="printReceipt()">Print Receipt</button>
                <button class="btn-new-sale" onclick="startNewSale()">New Sale</button>
            </div>
        </div>
    </div>

    <div id="confirmModal" class="modal-overlay">
        <div class="modal-content">
            <h2 id="confirmTitle">Confirm Action</h2>
            <p id="confirmMessage">Are you sure you want to proceed?</p>
            <div class="modal-buttons">
                <button class="btn btn-confirm" id="confirmBtn">Confirm</button>
                <button class="btn btn-cancel" onclick="hideConfirmModal()">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        let cart = [];
        let searchTimeout;

        function saveCartToStorage() {
            try {
                localStorage.setItem('pos_cart', JSON.stringify(cart));
                localStorage.setItem('pos_cart_timestamp', Date.now().toString());
            } catch (error) {
                console.error('Failed to save cart to storage:', error);
            }
        }

        function loadCartFromStorage() {
            try {
                const savedCart = localStorage.getItem('pos_cart');
                const timestamp = localStorage.getItem('pos_cart_timestamp');

                if (savedCart && timestamp) {
                    const cartAge = Date.now() - parseInt(timestamp);
                    const maxAge = 24 * 60 * 60 * 1000;

                    if (cartAge < maxAge) {
                        cart = JSON.parse(savedCart);
                        updateCartDisplay();
                        validateCartItems();

                        if (cart.length > 0) {
                            showCartRecoveryNotification(cart.length);
                        }
                    } else {
                        clearStoredCart();
                    }
                }
            } catch (error) {
                console.error('Failed to load cart from storage:', error);
                clearStoredCart();
            }
        }

        function clearStoredCart() {
            try {
                localStorage.removeItem('pos_cart');
                localStorage.removeItem('pos_cart_timestamp');
            } catch (error) {
                console.error('Failed to clear cart storage:', error);
            }
        }

        async function validateCartItems() {
            const invalidItems = [];

            for (let i = cart.length - 1; i >= 0; i--) {
                const cartItem = cart[i];

                try {
                    const response = await fetch(`/pos/product/${cartItem.id}`);
                    const data = await response.json();

                    if (!data.success) {
                        invalidItems.push(cartItem.name);
                        cart.splice(i, 1);
                    } else {
                        const product = data.product;
                        cartItem.maxStock = product.total_stock;
                        cartItem.price = parseFloat(product.unit_price);

                        if (cartItem.quantity > product.total_stock) {
                            if (product.total_stock > 0) {
                                cartItem.quantity = product.total_stock;
                            } else {
                                invalidItems.push(cartItem.name);
                                cart.splice(i, 1);
                            }
                        }
                    }
                } catch (error) {
                    console.error(`Failed to validate item ${cartItem.name}:`, error);
                }
            }

            if (invalidItems.length > 0) {
                alert(
                    `The following items were removed from your cart due to stock changes:\n${invalidItems.join(', ')}`
                );
            }

            updateCartDisplay();
        }

        function showCartRecoveryNotification(itemCount) {
            const notification = document.createElement('div');
            notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: var(--color-success);
        color: white;
        padding: 10px 15px;
        border-radius: var(--border-radius);
        z-index: 1000;
        font-size: 14px;
        box-shadow: var(--shadow-medium);
    `;
            notification.textContent = `Cart recovered with ${itemCount} items`;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadCartFromStorage();

            document.getElementById('productSearch').addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const searchTerm = this.value.trim();

                const delay = searchTerm.length < 3 ? 100 : 200;

                searchTimeout = setTimeout(() => {
                    searchProducts();
                }, delay);
            });

            document.getElementById('categoryFilter').addEventListener('change', function() {
                searchProducts();
            });

            window.addEventListener('beforeunload', function() {
                if (cart.length > 0) {
                    saveCartToStorage();
                }
            });
        });

        function searchProducts() {
            const search = document.getElementById('productSearch').value;
            const category = document.getElementById('categoryFilter').value;

            fetch('/pos/search', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        search,
                        category,
                        fuzzy: true
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const filteredProducts = search ? filterProductsFuzzy(data.products, search) : data.products;
                        displayProducts(filteredProducts);
                    }
                })
                .catch(error => {
                    console.error('Search error:', error);
                });
        }

        function filterProductsFuzzy(products, searchTerm) {
            if (!searchTerm || searchTerm.length < 2) {
                return products;
            }

            const normalizedSearch = searchTerm.toLowerCase().trim();

            return products.filter(product => {
                const searchableText = [
                    product.product_name || '',
                    product.brand_name || '',
                    product.category?.name || ''
                ].join(' ').toLowerCase();

                const words = [
                    product.product_name || '',
                    product.brand_name || '',
                    product.category?.name || ''
                ];

                return (
                    // Exact substring match (highest priority)
                    searchableText.includes(normalizedSearch) ||

                    // Partial word matching
                    containsPartialWords(searchableText, normalizedSearch) ||

                    // Character sequence matching (original order)
                    containsCharacterSequence(searchableText, normalizedSearch) ||

                    // Scrambled letters matching (any order)
                    containsScrambledLetters(searchableText, normalizedSearch) ||

                    // Word similarity matching
                    words.some(word => calculateWordSimilarity(word, normalizedSearch) >= 0.5) ||

                    // Traditional fuzzy matching
                    (normalizedSearch.split(' ').some(word =>
                        word.length > 2 && fuzzyMatch(searchableText, word, 0.6)
                    ))
                );
            }).sort((a, b) => {
                const aText = [a.product_name || '', a.brand_name || ''].join(' ').toLowerCase();
                const bText = [b.product_name || '', b.brand_name || ''].join(' ').toLowerCase();

                // Calculate match scores for sorting
                const aExact = aText.includes(normalizedSearch) ? 100 : 0;
                const bExact = bText.includes(normalizedSearch) ? 100 : 0;

                const aScrambled = containsScrambledLetters(aText, normalizedSearch) ? 80 : 0;
                const bScrambled = containsScrambledLetters(bText, normalizedSearch) ? 80 : 0;

                const aWords = [a.product_name || '', a.brand_name || ''];
                const bWords = [b.product_name || '', b.brand_name || ''];

                const aSimilarity = Math.max(...aWords.map(word => calculateWordSimilarity(word,
                    normalizedSearch))) * 60;
                const bSimilarity = Math.max(...bWords.map(word => calculateWordSimilarity(word,
                    normalizedSearch))) * 60;

                const aScore = Math.max(aExact, aScrambled, aSimilarity);
                const bScore = Math.max(bExact, bScrambled, bSimilarity);

                if (aScore !== bScore) {
                    return bScore - aScore; // Higher score first
                }

                // Then by product name alphabetically
                return (a.product_name || '').localeCompare(b.product_name || '');
            });
        }

        function containsPartialWords(text, search) {
            const words = search.split(' ').filter(word => word.length > 1);
            return words.every(word =>
                text.split(' ').some(textWord =>
                    textWord.startsWith(word) || textWord.includes(word)
                )
            );
        }

        function containsCharacterSequence(text, search) {
            if (search.length < 3) return false;

            let textIndex = 0;
            let searchIndex = 0;
            let matches = 0;

            while (textIndex < text.length && searchIndex < search.length) {
                if (text[textIndex] === search[searchIndex]) {
                    matches++;
                    searchIndex++;
                }
                textIndex++;
            }

            return matches >= Math.ceil(search.length * 0.7);
        }

        function fuzzyMatch(text, pattern, threshold = 0.7) {
            if (pattern.length < 3) return false;

            const words = text.split(' ');

            return words.some(word => {
                if (word.length < pattern.length - 2) return false;

                let matches = 0;
                let patternIndex = 0;

                for (let i = 0; i < word.length && patternIndex < pattern.length; i++) {
                    if (word[i] === pattern[patternIndex]) {
                        matches++;
                        patternIndex++;
                    }
                }

                const score = matches / pattern.length;
                return score >= threshold;
            });
        }

        function highlightMatch(text, searchTerm) {
            if (!text || !searchTerm || searchTerm.length < 2) {
                return text || '';
            }

            const regex = new RegExp(`(${escapeRegex(searchTerm)})`, 'gi');
            return text.replace(regex, '<span style="background-color: yellow; font-weight: bold;">$1</span>');
        }

        function escapeRegex(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        function displayProducts(products) {
            const productList = document.getElementById('productList');
            const searchTerm = document.getElementById('productSearch').value.toLowerCase().trim();

            if (products.length === 0) {
                productList.innerHTML =
                    '<table class="product-table"><tbody><tr><td colspan="5" class="no-products-row">No products found</td></tr></tbody></table>';
                return;
            }

            const tableHTML = `
        <table class="product-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                ${products.map(product => `
                                    <tr data-id="${product.id}">
                                        <td>
                                            <div class="product-name">${highlightMatch(product.product_name, searchTerm)}</div>
                                            <div class="product-brand">${highlightMatch(product.brand_name, searchTerm)}</div>
                                            ${product.batches && product.batches[0] && new Date(product.batches[0].expiration_date) <= new Date(Date.now() + 30*24*60*60*1000) ?
                                                `<div class="product-expiry">Expires: ${new Date(product.batches[0].expiration_date).toLocaleDateString()}</div>` : ''}
                                        </td>
                                        <td>
                                            <div class="product-category">${highlightMatch(product.category ? product.category.name : 'Uncategorized', searchTerm)}</div>
                                        </td>
                                        <td>
                                            <div class="product-price">₱${parseFloat(product.unit_price || 0).toFixed(2)}</div>
                                        </td>
                                        <td>
                                            <div class="product-stock">${product.total_stock || 0}</div>
                                        </td>
                                        <td class="action-cell">
                                            <button type="button" class="btn-add-to-cart" onclick="addToCart(${product.id})">
                                                Add to Cart
                                            </button>
                                        </td>
                                    </tr>
                                `).join('')}
            </tbody>
        </table>
    `;

            productList.innerHTML = tableHTML;
        }

        function addToCart(productId) {
            fetch(`/pos/product/${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const product = data.product;
                        const existingItem = cart.find(item => item.id === productId);

                        if (existingItem) {
                            if (existingItem.quantity < product.total_stock) {
                                existingItem.quantity++;
                                updateCartDisplay();
                                saveCartToStorage();
                            } else {
                                alert('Cannot add more items. Insufficient stock.');
                            }
                        } else {
                            cart.push({
                                id: productId,
                                name: product.product_name,
                                brand: product.brand_name,
                                price: parseFloat(product.unit_price),
                                quantity: 1,
                                maxStock: product.total_stock
                            });
                            updateCartDisplay();
                            saveCartToStorage();
                        }
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error adding item to cart');
                });
        }

        function removeFromCart(productId) {
            cart = cart.filter(item => item.id !== productId);
            updateCartDisplay();
            saveCartToStorage();
        }

        function updateQuantity(productId, newQuantity) {
            const item = cart.find(item => item.id === productId);
            if (item) {
                if (newQuantity > 0 && newQuantity <= item.maxStock) {
                    item.quantity = newQuantity;
                } else if (newQuantity <= 0) {
                    removeFromCart(productId);
                    return;
                } else {
                    alert(`Maximum available quantity is ${item.maxStock}`);
                    document.querySelector(`input[data-id="${productId}"]`).value = item.quantity;
                    return;
                }
                updateCartDisplay();
                saveCartToStorage();
            }
        }

        function updateCartDisplay() {
            const cartItemsContainer = document.getElementById('cartItems');
            const cartSummary = document.getElementById('cartSummary');
            const paymentSection = document.getElementById('paymentSection');

            if (cart.length === 0) {
                cartItemsContainer.innerHTML = '<div class="empty-cart"><p>No items in cart</p></div>';
                cartSummary.style.display = 'none';
                paymentSection.style.display = 'none';
                document.getElementById('submitBtn').disabled = true;
                return;
            }

            cartItemsContainer.innerHTML = cart.map(item => `
        <div class="cart-item">
            <div class="item-info">
                <h5>${item.name}</h5>
                <p class="brand">${item.brand}</p>
                <p class="price">₱${item.price.toFixed(2)} each</p>
            </div>
            <div class="item-controls">
                <button class="btn-quantity" onclick="updateQuantity(${item.id}, ${item.quantity - 1})">-</button>
                <input type="number" value="${item.quantity}" min="1" max="${item.maxStock}"
                       data-id="${item.id}" onchange="updateQuantity(${item.id}, parseInt(this.value))">
                <button class="btn-quantity" onclick="updateQuantity(${item.id}, ${item.quantity + 1})">+</button>
                <button class="btn-remove" onclick="removeFromCart(${item.id})">✕</button>
            </div>
            <div class="item-total">₱${(item.price * item.quantity).toFixed(2)}</div>
        </div>
    `).join('');
            cartSummary.style.display = 'block';
            paymentSection.style.display = 'block';

            calculateTotal();
        }

        function calculateTotal() {
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const discountAmount = parseFloat(document.getElementById('discountAmount').value) || 0;
            const total = subtotal - discountAmount;

            document.getElementById('subtotal').textContent = `₱${subtotal.toFixed(2)}`;
            document.getElementById('total').textContent = `₱${total.toFixed(2)}`;

            calculateChange();

            document.getElementById('submitBtn').disabled = cart.length === 0;
        }

        function calculateChange() {
            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0) -
                (parseFloat(document.getElementById('discountAmount').value) || 0);
            const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
            const change = amountPaid - total;

            const changeDisplay = document.getElementById('changeDisplay');
            const changeAmount = document.getElementById('changeAmount');

            if (amountPaid > 0) {
                changeDisplay.style.display = 'block';
                changeAmount.textContent = `₱${change.toFixed(2)}`;

                if (change < 0) {
                    changeAmount.style.color = 'var(--color-danger)';
                } else {
                    changeAmount.style.color = 'var(--color-success)';
                }
            } else {
                changeDisplay.style.display = 'none';
            }
        }

        function processTransaction() {
            if (cart.length === 0) {
                alert('Cart is empty');
                return;
            }

            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const discountAmount = parseFloat(document.getElementById('discountAmount').value) || 0;
            const total = subtotal - discountAmount;
            const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;

            if (amountPaid < total) {
                alert('Insufficient payment amount');
                return;
            }

            const btnText = document.querySelector('.btn-text');
            const btnLoading = document.querySelector('.btn-loading');
            const submitBtn = document.getElementById('submitBtn');

            btnText.style.display = 'none';
            btnLoading.style.display = 'inline';
            submitBtn.disabled = true;

            const transactionData = {
                items: cart.map(item => ({
                    product_id: item.id,
                    quantity: item.quantity
                })),
                amount_paid: amountPaid,
                payment_method: document.getElementById('paymentMethod').value,
                customer_name: document.getElementById('customerName').value,
                discount_amount: discountAmount,
                notes: document.getElementById('notes').value
            };

            fetch('/pos/process-transaction', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(transactionData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showReceipt(data.transaction);
                        clearCart();
                    } else {
                        alert(data.message || 'Error processing transaction');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Network error occurred. Please try again.');
                })
                .finally(() => {
                    btnText.style.display = 'inline';
                    btnLoading.style.display = 'none';
                    submitBtn.disabled = false;
                });
        }

        function clearCart() {
            cart = [];
            document.getElementById('customerName').value = '';
            document.getElementById('discountAmount').value = '';
            document.getElementById('amountPaid').value = '';
            document.getElementById('notes').value = '';
            updateCartDisplay();
            clearStoredCart();
        }

        function showReceipt(transaction) {
            const receiptContent = document.getElementById('receiptContent');

            receiptContent.innerHTML = `
        <div class="receipt">
            <div class="receipt-business-info">
                <h3>MJ's Pharmacy</h3>
                <p>Your Trusted Healthcare Partner</p>
            </div>

            <div class="receipt-transaction-info">
                <p><strong>Transaction ID:</strong> ${transaction.transaction_id}</p>
                <p><strong>Date:</strong> ${new Date().toLocaleString()}</p>
                ${transaction.customer_name ? `<p><strong>Customer:</strong> ${transaction.customer_name}</p>` : ''}
                <p><strong>Payment:</strong> ${transaction.payment_method.toUpperCase()}</p>
            </div>

            <div class="receipt-items">
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${transaction.items.map(item => `
                                            <tr>
                                                <td>
                                                    <div class="item-name">${item.product_name}</div>
                                                    <div class="item-brand">${item.brand_name}</div>
                                                </td>
                                                <td>${item.quantity}</td>
                                                <td>₱${parseFloat(item.unit_price).toFixed(2)}</td>
                                                <td>₱${parseFloat(item.total_price).toFixed(2)}</td>
                                            </tr>
                                        `).join('')}
                    </tbody>
                </table>
            </div>

            <div class="receipt-summary">
                <div class="summary-line">
                    <span>Subtotal:</span>
                    <span>₱${parseFloat(transaction.subtotal).toFixed(2)}</span>
                </div>
                ${parseFloat(transaction.discount_amount) > 0 ? `
                                    <div class="summary-line">
                                        <span>Discount:</span>
                                        <span>-₱${parseFloat(transaction.discount_amount).toFixed(2)}</span>
                                    </div>
                                ` : ''}
                <div class="summary-line total-line">
                    <span><strong>Total:</strong></span>
                    <span><strong>₱${parseFloat(transaction.total_amount).toFixed(2)}</strong></span>
                </div>
                <div class="summary-line">
                    <span>Amount Paid:</span>
                    <span>₱${parseFloat(transaction.amount_paid).toFixed(2)}</span>
                </div>
                <div class="summary-line">
                    <span>Change:</span>
                    <span>₱${parseFloat(transaction.change_amount).toFixed(2)}</span>
                </div>
            </div>

            <div class="receipt-footer">
                <p>Thank you for choosing MJ's Pharmacy!</p>
                <p>Please keep this receipt for your records</p>
            </div>
        </div>
    `;

            document.getElementById('receiptModal').classList.add('active');
        }

        function hideReceiptModal() {
            document.getElementById('receiptModal').classList.remove('active');
        }

        function printReceipt() {
            const receiptContent = document.getElementById('receiptContent').innerHTML;
            const printWindow = window.open('', '_blank');

            printWindow.document.write(`
        <html>
        <head>
            <title>Receipt - MJ's Pharmacy</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 300px; margin: 0 auto;}
                .receipt { padding: 20px; }
                .receipt-business-info { text-align: center; margin-bottom: 20px; }
                .receipt-business-info h3 { margin: 0; font-size: 18px; }
                .receipt-transaction-info p { margin: 5px 0; font-size: 12px; }
                .receipt-items table { width: 100%; border-collapse: collapse; font-size: 12px; }
                .receipt-items th, .receipt-items td { padding: 5px; border-bottom: 1px solid #ddd; }
                .receipt-items th { text-align: left; background: #f5f5f5; }
                .item-brand { font-size: 10px; color: #666; }
                .receipt-summary { margin-top: 15px; font-size: 12px; }
                .summary-line { display: flex; justify-content: space-between; margin: 3px 0; }
                .total-line { border-top: 2px solid #000; padding-top: 5px; font-weight: bold; }
                .receipt-footer { text-align: center; margin-top: 20px; font-size: 10px; }
            </style>
        </head>
        <body>${receiptContent}</body>
        </html>
    `);

            printWindow.document.close();
            printWindow.print();
        }

        function startNewSale() {
            hideReceiptModal();
            clearCart();
        }

        function showConfirmModal(title, message, callback) {
            document.getElementById('confirmTitle').textContent = title;
            document.getElementById('confirmMessage').textContent = message;
            document.getElementById('confirmBtn').onclick = function() {
                callback();
                hideConfirmModal();
            };
            document.getElementById('confirmModal').classList.add('active');
        }

        function hideConfirmModal() {
            document.getElementById('confirmModal').classList.remove('active');
        }

        const originalClearCart = clearCart;

        function clearCartWithConfirmation() {
            if (cart.length > 0) {
                showConfirmModal(
                    'Clear Cart',
                    'Are you sure you want to clear all items from the cart?',
                    originalClearCart
                );
            }
        }
    </script>
    @stack('scripts')

</body>

</html>
