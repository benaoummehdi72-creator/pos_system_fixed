// Shared script. The cart code only runs on the cashier POS screen
// (the admin pages load this file too, but have no cart).

let cartIsEmpty = true;
const CHECKOUT_LABEL = 'Checkout';

document.addEventListener('DOMContentLoaded', function() {
    // No cart on this page (admin pages): nothing to set up
    if (!document.getElementById('cart-items')) return;

    loadCart();

    // One delegated click handler for all "Add to Cart" buttons. It keeps working
    // when a button changes state (e.g. a product is restocked) after a stock refresh.
    const grid = document.querySelector('.product-grid');
    if (grid) {
        grid.addEventListener('click', function(e) {
            const button = e.target.closest('.add-to-cart');
            if (!button || button.disabled) return;
            e.preventDefault();
            const card = button.closest('.product-card');
            addToCart(card.getAttribute('data-id'));
        });
    }

    // Checkout button
    const checkoutBtn = document.getElementById('checkout-btn');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', checkout);
    }

    // Keep stock numbers fresh: other cashiers sell and the admin restocks
    setInterval(refreshStock, 15000);
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) refreshStock();
    });
});

// POST helper: sends form fields and returns the parsed JSON
function postForm(url, params) {
    return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(params).toString()
    }).then(response => response.json());
}

// Add item to cart
function addToCart(productId) {
    postForm('add_to_cart.php', { product_id: productId })
    .then(data => {
        if (data.success) {
            loadCart();
            showMessage('Item added to cart!', 'success');
        } else {
            showMessage(data.error || 'Could not add item', 'error');
            refreshStock(); // the product may have just sold out
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error adding item', 'error');
    });
}

// Update quantity in cart
function updateQuantity(productId, newQuantity) {
    postForm('update_cart.php', { product_id: productId, quantity: newQuantity })
    .then(data => {
        if (!data.success) {
            showMessage(data.error || 'Could not update quantity', 'error');
            refreshStock();
        }
        loadCart();
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Remove item from cart
function removeFromCart(productId) {
    postForm('remove_from_cart.php', { product_id: productId })
    .then(data => {
        if (data.success) {
            loadCart();
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Load cart contents (one request updates both the cart panel and the item counter)
function loadCart() {
    return fetch('get_cart.php')
    .then(response => response.json())
    .then(cart => {
        displayCart(cart);
        updateCartCount(cart);
    })
    .catch(error => {
        console.error('Error loading cart:', error);
        document.getElementById('cart-items').innerHTML = '<p>Error loading cart</p>';
    });
}

// Update the "Items: N" badge
function updateCartCount(cart) {
    const cartCountEl = document.getElementById('cart-count');
    if (!cartCountEl) return;
    let totalItems = 0;
    for (let id in cart) totalItems += cart[id].qty;
    cartCountEl.textContent = totalItems;
}

// Display cart items
function displayCart(cart) {
    const cartContainer = document.getElementById('cart-items');
    const totalElement = document.getElementById('cart-total');

    // Check if cart is empty
    if (!cart || Object.keys(cart).length === 0) {
        cartIsEmpty = true;
        cartContainer.innerHTML = '<p style="text-align:center; color:#999;">Cart is empty</p>';
        totalElement.textContent = '0.00';
        return;
    }

    cartIsEmpty = false;
    let html = '';
    let total = 0;

    // Loop through cart items
    for (let productId in cart) {
        const item = cart[productId];
        const subtotal = item.price * item.qty;
        total += subtotal;

        html += `
            <div class="cart-item">
                <div class="cart-item-info">
                    <span class="cart-item-name">${item.name}</span>
                    <span class="cart-item-price">$${parseFloat(item.price).toFixed(2)} each</span>
                </div>
                <div class="cart-item-controls">
                    <div class="qty-controls">
                        <button onclick="updateQuantity(${productId}, ${item.qty - 1})" class="qty-btn">-</button>
                        <span class="qty-display">${item.qty}</span>
                        <button onclick="updateQuantity(${productId}, ${item.qty + 1})" class="qty-btn">+</button>
                    </div>
                    <span class="item-subtotal">$${subtotal.toFixed(2)}</span>
                    <button onclick="removeFromCart(${productId})" class="remove-btn" title="Remove item">×</button>
                </div>
            </div>
        `;
    }

    cartContainer.innerHTML = html;
    totalElement.textContent = total.toFixed(2);
}

// Refresh the stock shown on every product card (and the Add / Out of Stock buttons)
function refreshStock() {
    fetch('get_stock.php')
    .then(response => response.ok ? response.json() : null)
    .then(stock => {
        if (!stock) return;
        document.querySelectorAll('.product-card').forEach(card => {
            const qty = stock[card.getAttribute('data-id')];

            // Not in the list any more: the admin archived it
            if (qty === undefined) {
                card.classList.add('unavailable');
                return;
            }

            card.classList.remove('unavailable');
            const soldOut = qty <= 0;
            card.classList.toggle('out-of-stock', soldOut);
            card.querySelector('.stock').textContent = 'Stock: ' + qty;

            const button = card.querySelector('button');
            if (button) {
                button.disabled = soldOut;
                button.textContent = soldOut ? 'Out of Stock' : 'Add to Cart';
                button.classList.toggle('add-to-cart', !soldOut);
            }
        });
    })
    .catch(error => console.error('Stock refresh failed:', error));
}

// Checkout
function checkout() {
    if (cartIsEmpty) {
        showMessage('Cart is empty!', 'error');
        return;
    }

    const checkoutBtn = document.getElementById('checkout-btn');
    checkoutBtn.disabled = true;
    checkoutBtn.textContent = 'Processing...';

    const resetButton = () => {
        checkoutBtn.disabled = false;
        checkoutBtn.textContent = CHECKOUT_LABEL;
    };

    fetch('checkout.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.text()) // read as text first so a PHP error page can be shown
    .then(text => {
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e, text);
            showMessage('Server error: ' + text.substring(0, 100), 'error');
            resetButton();
            return;
        }

        if (data.success) {
            showMessage(`Sale completed! Total: $${data.total}`, 'success');
            loadCart();
            refreshStock();
            resetButton();

            // Open receipt in a new window
            if (data.sale_id) {
                window.open(
                    'receipt.php?sale_id=' + data.sale_id,
                    'receipt',
                    'width=400,height=600'
                );
            }
        } else {
            showMessage(data.error || 'Checkout failed', 'error');
            loadCart();
            refreshStock();
            resetButton();
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        showMessage('Network error. Please try again.', 'error');
        resetButton();
    });
}

// Show message
function showMessage(message, type) {
    const messageDiv = document.getElementById('message');
    if (!messageDiv) return;
    messageDiv.innerHTML = `<div class="${type}">${message}</div>`;
    setTimeout(() => {
        messageDiv.innerHTML = '';
    }, 3000);
}

// ---- SEARCH FUNCTIONALITY ----
const searchInput = document.getElementById('product-search');
if (searchInput) {
    // Real-time filtering as you type
    searchInput.addEventListener('input', filterProducts);
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Press '/' to focus the search box (unless already typing in an input)
        if (e.key === '/' && document.activeElement !== searchInput) {
            e.preventDefault();
            searchInput.focus();
        }
        // Escape to clear search and blur
        if (e.key === 'Escape' && document.activeElement === searchInput) {
            searchInput.value = '';
            searchInput.blur();
            filterProducts();
        }
    });
}

function filterProducts() {
    const searchTerm = searchInput.value.toLowerCase().trim();
    const cards = document.querySelectorAll('.product-card');
    let visibleCount = 0;
    
    cards.forEach(card => {
        // Archived products stay hidden whatever is typed
        if (card.classList.contains('unavailable')) return;

        const productName = card.querySelector('h3').textContent.toLowerCase();
        
        // Show card only if it matches the search term
        if (productName.includes(searchTerm)) {
            card.style.display = '';  // show (reset to default)
            visibleCount++;
        } else {
            card.style.display = 'none';  // hide
        }
    });
    
    // Optional: show a "no results" message
    const grid = document.querySelector('.product-grid');
    let noResultsMsg = document.getElementById('no-results');
    if (visibleCount === 0 && searchTerm !== '') {
        if (!noResultsMsg) {
            noResultsMsg = document.createElement('p');
            noResultsMsg.id = 'no-results';
            noResultsMsg.textContent = 'No products found.';
            noResultsMsg.style.textAlign = 'center';
            noResultsMsg.style.gridColumn = '1 / -1';
            noResultsMsg.style.padding = '2rem';
            noResultsMsg.style.color = '#999';
            grid.appendChild(noResultsMsg);
        }
    } else {
        if (noResultsMsg) noResultsMsg.remove();
    }
}
