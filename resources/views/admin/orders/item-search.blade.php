@extends('admin.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Search Order Items</h1>
            <p class="text-muted">Find products within orders quickly</p>
        </div>
    </div>

    <!-- Search Card -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="mb-4">
                        <label for="orderItemSearch" class="form-label fw-semibold">
                            <i class="ri-search-line me-2"></i>Search by Product Name, Variation, or Order Number
                        </label>
                        <input
                            type="text"
                            class="form-control form-control-lg"
                            id="orderItemSearch"
                            placeholder="Type to search (e.g., 'iPhone', 'Blue Shirt', order number...)"
                            autocomplete="off"
                        >
                        <div class="form-text">
                            Start typing to see suggestions. Recent non-pending/processing orders are prioritized.
                        </div>
                    </div>

                    <!-- Search Results Dropdown -->
                    <div id="searchResults" class="list-group" style="display: none;"></div>

                    <!-- Loading Indicator -->
                    <div id="searchLoading" class="text-center py-3" style="display: none;">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Searching...</span>
                        </div>
                        <span class="ms-2 text-muted">Searching...</span>
                    </div>

                    <!-- No Results Message -->
                    <div id="noResults" class="alert alert-info" style="display: none;">
                        <i class="ri-information-line me-2"></i>No items found matching your search.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Searches (Optional) -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="ri-history-line me-2"></i>How to Use</h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li>Type at least 2 characters to start searching</li>
                        <li>Search supports partial matches - no need to type the exact name</li>
                        <li>Results show up to 20 most relevant items</li>
                        <li>Click on any result to view the full order details</li>
                        <li>Recent orders with active statuses appear first</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #searchResults .list-group-item {
        cursor: pointer;
        transition: all 0.2s;
        border-left: 3px solid transparent;
    }

    #searchResults .list-group-item:hover {
        background-color: #f8f9fa;
        border-left-color: #0d6efd;
        transform: translateX(2px);
    }

    .product-image-small {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
    }

    .status-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('orderItemSearch');
    const searchResults = document.getElementById('searchResults');
    const searchLoading = document.getElementById('searchLoading');
    const noResults = document.getElementById('noResults');
    let searchTimeout = null;

    searchInput.addEventListener('input', function() {
        const query = this.value.trim();

        // Clear previous timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        // Hide all
        searchResults.style.display = 'none';
        searchLoading.style.display = 'none';
        noResults.style.display = 'none';
        searchResults.innerHTML = '';

        // Don't search if query is too short
        if (query.length < 2) {
            return;
        }

        // Show loading
        searchLoading.style.display = 'block';

        // Debounce search
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });

    function performSearch(query) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        fetch(`{{ route('admin.orders.item-search.search') }}?q=${encodeURIComponent(query)}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            searchLoading.style.display = 'none';

            if (!data.success) {
                noResults.style.display = 'block';
                noResults.textContent = data.message || 'Search failed';
                return;
            }

            if (data.data.length === 0) {
                noResults.style.display = 'block';
                return;
            }

            displayResults(data.data);
        })
        .catch(error => {
            console.error('Search error:', error);
            searchLoading.style.display = 'none';
            noResults.style.display = 'block';
            noResults.textContent = 'An error occurred while searching';
        });
    }

    function displayResults(results) {
        searchResults.innerHTML = '';
        searchResults.style.display = 'block';

        results.forEach(item => {
            const resultItem = createResultItem(item);
            searchResults.appendChild(resultItem);
        });
    }

    function createResultItem(item) {
        const div = document.createElement('div');
        div.className = 'list-group-item list-group-item-action';

        const orderUrl = `{{ url('/admin/orders') }}/${item.order_number}`;

        // Status badge color
        let statusClass = 'bg-secondary';
        switch(item.order_status_slug) {
            case 'pending': statusClass = 'bg-warning'; break;
            case 'processing': statusClass = 'bg-info'; break;
            case 'delivered': statusClass = 'bg-success'; break;
            case 'cancelled': statusClass = 'bg-danger'; break;
        }

        div.innerHTML = `
            <div class="d-flex align-items-center">
                ${item.product_image ?
                    `<img src="${item.product_image}" class="product-image-small me-3" alt="Product">` :
                    `<div class="product-image-small me-3 bg-light d-flex align-items-center justify-content-center">
                        <i class="ri-image-line text-muted"></i>
                    </div>`
                }
                <div class="flex-grow-1">
                    <h6 class="mb-1">${escapeHtml(item.product_name)}</h6>
                    ${item.variation_display_name ?
                        `<small class="text-muted d-block"><i class="ri-paint-line me-1"></i>${escapeHtml(item.variation_display_name)}</small>` :
                        ''
                    }
                    <div class="mt-1">
                        <span class="badge bg-primary me-2">
                            <i class="ri-shopping-cart-line me-1"></i>Order #${item.order_number}
                        </span>
                        <span class="badge ${statusClass} me-2">
                            ${escapeHtml(item.order_status)}
                        </span>
                        <small class="text-muted">
                            <i class="ri-calendar-line me-1"></i>${formatDate(item.order_date)}
                        </small>
                    </div>
                </div>
                <div class="text-end">
                    <div class="fw-semibold">$${parseFloat(item.subtotal).toFixed(2)}</div>
                    <small class="text-muted">Qty: ${item.quantity}</small>
                    <div class="mt-1">
                        <i class="ri-external-link-line text-muted" title="Opens in new tab"></i>
                    </div>
                </div>
            </div>
        `;

        div.addEventListener('click', function() {
            window.open(orderUrl, '_blank');
        });

        return div;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Focus on search input when page loads
    searchInput.focus();

    // Clear results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchResults.contains(e.target) && e.target !== searchInput) {
            searchResults.style.display = 'none';
        }
    });

    // Show results again when clicking on input (if there are results)
    searchInput.addEventListener('focus', function() {
        if (searchResults.children.length > 0) {
            searchResults.style.display = 'block';
        }
    });
});
</script>
@endsection

