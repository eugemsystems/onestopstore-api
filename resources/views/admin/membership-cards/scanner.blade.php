@extends('admin.layout')

@section('title', 'Membership Card Scanner')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-credit-card-2-front"></i> Membership Card Scanner</h2>
</div>

<div class="row">
    <!-- Hardware Scanner Section -->
    <div class="col-lg-8 mb-4">
        <!-- Main Scanner Card -->
        <div class="card border-primary" style="border-width: 3px;">
            <div class="card-header text-white" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
                <h4 class="mb-0">
                    <i class="bi bi-upc-scan"></i> Scan Membership Card
                    <span class="badge bg-light text-primary ms-2">Ready!</span>
                </h4>
                <small>Scan the 1D barcode on the membership card</small>
            </div>
            <div class="card-body bg-light">
                <div class="mb-3">
                    <label for="card-input" class="form-label fw-bold">
                        <i class="bi bi-credit-card"></i> Scan Card Number:
                    </label>
                    <input
                        type="text"
                        id="card-input"
                        class="form-control form-control-lg"
                        placeholder="Click here and scan membership card..."
                        autocomplete="off"
                        style="border: 3px solid #0d6efd; font-size: 1.3rem; padding: 20px;">
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i>
                        Scanner will automatically detect the card number
                    </small>
                </div>

                <!-- Scan Result Display -->
                <div id="scan-result" class="mt-3">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No card scanned yet. Scan a membership card to view user details.
                    </div>
                </div>
            </div>
        </div>

        <!-- User Details Card (Initially Hidden) -->
        <div id="user-details-card" class="card mt-3" style="display: none;">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-person-circle"></i> Member Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Name:</strong> <span id="user-name"></span></p>
                        <p><strong>Email:</strong> <span id="user-email"></span></p>
                        <p><strong>Phone:</strong> <span id="user-phone"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Card Number:</strong> <span id="user-card-number" class="badge bg-primary"></span></p>
                        <p><strong>Card Assigned:</strong> <span id="user-card-assigned"></span></p>
                        <p><strong>Member Since:</strong> <span id="user-created-at"></span></p>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h3 class="text-primary mb-0"><i class="bi bi-coin"></i> <span id="user-points">0</span></h3>
                                <small class="text-muted">Points Balance</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h3 class="text-success mb-0"><i class="bi bi-wallet2"></i> $<span id="user-wallet">0.00</span></h3>
                                <small class="text-muted">Wallet Balance</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders Card (Initially Hidden) -->
        <div id="orders-card" class="card mt-3" style="display: none;">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-cart-check"></i> Recent Orders</h5>
            </div>
            <div class="card-body">
                <div id="orders-list">
                    <div class="text-center text-muted">
                        <div class="spinner-border spinner-border-sm"></div> Loading orders...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions Sidebar -->
    <div class="col-lg-4">
        <!-- Add Points Card -->
        <div id="add-points-card" class="card mb-3" style="display: none;">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Add Points</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="points-amount" class="form-label">Points Amount:</label>
                    <input type="number" id="points-amount" class="form-control" placeholder="Enter points" min="1">
                </div>
                <div class="mb-3">
                    <label for="points-reason" class="form-label">Reason:</label>
                    <input type="text" id="points-reason" class="form-control" placeholder="Optional">
                </div>
                <button onclick="addPoints()" class="btn btn-warning w-100">
                    <i class="bi bi-plus-circle"></i> Add Points
                </button>
            </div>
        </div>

        <!-- Add Wallet Card -->
        <div id="add-wallet-card" class="card mb-3" style="display: none;">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-wallet2"></i> Add to Wallet</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="wallet-amount" class="form-label">Amount ($):</label>
                    <input type="number" id="wallet-amount" class="form-control" placeholder="0.00" min="0" step="0.01">
                </div>
                <div class="mb-3">
                    <label for="wallet-reason" class="form-label">Reason:</label>
                    <input type="text" id="wallet-reason" class="form-control" placeholder="Optional">
                </div>
                <button onclick="addWallet()" class="btn btn-success w-100">
                    <i class="bi bi-wallet2"></i> Add to Wallet
                </button>
            </div>
        </div>

        <!-- Assign Card Card -->
        <div id="assign-card-section" class="card" style="display: none;">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-credit-card-fill"></i> Assign Card</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Card Number: <strong id="unassigned-card"></strong></p>
                <p>This card is not assigned. Search for a user to assign it:</p>

                <div class="mb-3">
                    <label for="user-search" class="form-label">Search User:</label>
                    <input type="text" id="user-search" class="form-control" placeholder="Name, email, or phone" onkeyup="searchUsers()">
                </div>

                <div id="user-search-results" class="list-group" style="max-height: 300px; overflow-y: auto;">
                    <!-- Search results will appear here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>

let currentUserId = null;
let currentCardNumber = null;
let cardTimeout = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    setupCardScanner();
    setupUserSearch();

    // Auto-focus card input
    const cardInput = document.getElementById('card-input');
    if (cardInput) {
        cardInput.focus();
    }
});

// Setup user search input
function setupUserSearch() {
    const searchInput = document.getElementById('user-search');

    if (searchInput) {
        // Prevent card input from stealing focus when typing in search
        searchInput.addEventListener('focus', function() {
            console.log('User search focused');
        });

        searchInput.addEventListener('click', function(e) {
            e.stopPropagation();
            searchInput.focus();
        });
    }
}

// Setup card scanner
function setupCardScanner() {
    const cardInput = document.getElementById('card-input');

    if (cardInput) {
        // Handle Enter key (barcode scanners send Enter)
        cardInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const cardNumber = cardInput.value.trim();

                if (cardNumber) {
                    processCard(cardNumber);
                    cardInput.value = ''; // Clear for next scan
                }
            }
        });

        // Auto-process after brief pause
        cardInput.addEventListener('input', function(e) {
            clearTimeout(cardTimeout);

            cardTimeout = setTimeout(() => {
                const cardNumber = cardInput.value.trim();

                if (cardNumber.length > 3) {
                    processCard(cardNumber);
                    cardInput.value = ''; // Clear for next scan
                }
            }, 500);
        });

        // Keep focus on input ONLY if assign card section is not visible
        // This allows typing in the search field when assigning cards
        cardInput.addEventListener('blur', function(e) {
            // Check if user is clicking in the assign card section
            const assignSection = document.getElementById('assign-card-section');
            const isAssignSectionVisible = assignSection && assignSection.style.display !== 'none';

            // Don't auto-focus if assign section is visible or if user clicked in search area
            if (!isAssignSectionVisible) {
                setTimeout(() => {
                    // Only refocus if not typing in another input
                    if (!document.activeElement || document.activeElement.tagName !== 'INPUT' || document.activeElement.id === 'card-input') {
                        cardInput.focus();
                    }
                }, 100);
            }
        });

    }
}

// Process scanned card
async function processCard(cardNumber) {
    const resultDiv = document.getElementById('scan-result');
    resultDiv.innerHTML = '<div class="spinner-border spinner-border-sm"></div> Processing...';

    // Hide all action cards
    document.getElementById('user-details-card').style.display = 'none';
    document.getElementById('add-points-card').style.display = 'none';
    document.getElementById('add-wallet-card').style.display = 'none';
    document.getElementById('assign-card-section').style.display = 'none';
    document.getElementById('orders-card').style.display = 'none';

    currentCardNumber = cardNumber;

    try {
        const requestBody = { card_number: cardNumber };
        const response = await fetch('{{ route("admin.membership-cards.scan") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(requestBody)
        });

        const data = await response.json();

        if (data.success && data.user) {
            displayUserDetails(data.user);
            playSound('success');
        } else {
            // Card not assigned
            displayUnassignedCard(cardNumber);
            playSound('warning');
        }

    } catch (error) {
        resultDiv.innerHTML = `
            <div class="alert alert-danger">
                <h5><i class="bi bi-x-circle"></i> Error</h5>
                <p class="mb-0">Failed to process card. ${error.message}</p>
            </div>
        `;
        playSound('error');
    }
}

// Display user details
function displayUserDetails(user) {
    const resultDiv = document.getElementById('scan-result');
    currentUserId = user.id;

    resultDiv.innerHTML = `
        <div class="alert alert-success">
            <h5><i class="bi bi-check-circle"></i> Member Found!</h5>
            <p class="mb-0">Welcome, <strong>${user.name}</strong>!</p>
        </div>
    `;

    // Fill user details
    document.getElementById('user-name').textContent = user.name;
    document.getElementById('user-email').textContent = user.email;
    document.getElementById('user-phone').textContent = user.phone;
    document.getElementById('user-card-number').textContent = user.membership_card_number;
    document.getElementById('user-card-assigned').textContent = user.card_assigned_at ? new Date(user.card_assigned_at).toLocaleDateString() : 'N/A';
    document.getElementById('user-created-at').textContent = new Date(user.created_at).toLocaleDateString();
    document.getElementById('user-points').textContent = user.points_balance;
    document.getElementById('user-wallet').textContent = parseFloat(user.wallet_balance).toFixed(2);

    // Show cards
    document.getElementById('user-details-card').style.display = 'block';
    document.getElementById('add-points-card').style.display = 'block';
    document.getElementById('add-wallet-card').style.display = 'block';
    document.getElementById('orders-card').style.display = 'block';

    // Load user's recent orders
    loadUserOrders(user.id);
}

// Display unassigned card
function displayUnassignedCard(cardNumber) {
    const resultDiv = document.getElementById('scan-result');

    resultDiv.innerHTML = `
        <div class="alert alert-warning">
            <h5><i class="bi bi-exclamation-triangle"></i> Card Not Assigned</h5>
            <p class="mb-0">Card <strong>${cardNumber}</strong> is not assigned to any user.</p>
            <small>You can assign it to a user using the panel on the right.</small>
        </div>
    `;

    document.getElementById('unassigned-card').textContent = cardNumber;
    document.getElementById('assign-card-section').style.display = 'block';

    // Clear and focus the search input
    const searchInput = document.getElementById('user-search');
    searchInput.value = '';

    // Clear search results
    document.getElementById('user-search-results').innerHTML = '<div class="p-3 text-muted text-center">Type at least 2 characters to search</div>';

    // Focus the search input after a brief delay
    setTimeout(() => {
        searchInput.focus();
    }, 300);
}

// Search users
let searchTimeout = null;
async function searchUsers() {
    clearTimeout(searchTimeout);

    searchTimeout = setTimeout(async () => {
        const search = document.getElementById('user-search').value.trim();
        const resultsDiv = document.getElementById('user-search-results');

        if (search.length < 2) {
            resultsDiv.innerHTML = '<div class="p-3 text-muted text-center">Type at least 2 characters to search</div>';
            return;
        }

        resultsDiv.innerHTML = '<div class="p-3 text-center"><div class="spinner-border spinner-border-sm"></div> Searching...</div>';

        try {
            const response = await fetch(`{{ route("admin.membership-cards.search-user") }}?search=${encodeURIComponent(search)}`);
            const data = await response.json();

            if (data.success && data.users.length > 0) {
                resultsDiv.innerHTML = '';
                data.users.forEach(user => {
                    const hasCard = user.has_card ? '<span class="badge bg-secondary ms-2">Has Card</span>' : '';
                    const item = document.createElement('button');
                    item.className = 'list-group-item list-group-item-action';
                    item.innerHTML = `
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="mb-1">${user.name}${hasCard}</h6>
                                <small>${user.email} • ${user.phone}</small>
                            </div>
                        </div>
                    `;
                    item.onclick = () => assignCardToUser(user.id, user.name);
                    resultsDiv.appendChild(item);
                });
            } else {
                resultsDiv.innerHTML = '<div class="p-3 text-muted text-center">No users found</div>';
            }
        } catch (error) {
            resultsDiv.innerHTML = '<div class="p-3 text-danger text-center">Error searching users</div>';
        }
    }, 500);
}

// Assign card to user
async function assignCardToUser(userId, userName) {
    const result = await Swal.fire({
        title: 'Assign Card',
        html: `Assign card <strong>${currentCardNumber}</strong> to <strong>${userName}</strong>?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Assign Card',
        cancelButtonText: 'Cancel'
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        const response = await fetch('{{ route("admin.membership-cards.assign") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                user_id: userId,
                card_number: currentCardNumber
            })
        });

        const data = await response.json();

        if (data.success) {
            await Swal.fire({
                title: 'Success!',
                text: data.message,
                icon: 'success',
                confirmButtonColor: '#28a745'
            });
            playSound('success');
            // Scan again to show user details
            processCard(currentCardNumber);
        } else {
            await Swal.fire({
                title: 'Error',
                text: data.message,
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
            playSound('error');
        }
    } catch (error) {
        await Swal.fire({
            title: 'Error',
            text: 'Error assigning card: ' + error.message,
            icon: 'error',
            confirmButtonColor: '#dc3545'
        });
        playSound('error');
    }
}

// Add points
async function addPoints() {
    const points = parseInt(document.getElementById('points-amount').value);
    const reason = document.getElementById('points-reason').value;

    if (!points || points < 1) {
        await Swal.fire({
            title: 'Invalid Input',
            text: 'Please enter a valid points amount',
            icon: 'warning',
            confirmButtonColor: '#ffc107'
        });
        return;
    }

    const result = await Swal.fire({
        title: 'Add Points',
        html: `Add <strong>${points} points</strong> to this user?${reason ? '<br><small>Reason: ' + reason + '</small>' : ''}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Add Points',
        cancelButtonText: 'Cancel'
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        const response = await fetch('{{ route("admin.membership-cards.add-points") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                user_id: currentUserId,
                points: points,
                reason: reason
            })
        });

        const data = await response.json();

        if (data.success) {
            await Swal.fire({
                title: 'Success!',
                text: data.message,
                icon: 'success',
                confirmButtonColor: '#28a745'
            });
            document.getElementById('user-points').textContent = data.user.points_balance;
            document.getElementById('points-amount').value = '';
            document.getElementById('points-reason').value = '';
            playSound('success');
        } else {
            await Swal.fire({
                title: 'Error',
                text: data.message,
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
            playSound('error');
        }
    } catch (error) {
        await Swal.fire({
            title: 'Error',
            text: 'Error adding points: ' + error.message,
            icon: 'error',
            confirmButtonColor: '#dc3545'
        });
        playSound('error');
    }
}

// Add wallet
async function addWallet() {
    const amount = parseFloat(document.getElementById('wallet-amount').value);
    const reason = document.getElementById('wallet-reason').value;

    if (!amount || amount <= 0) {
        await Swal.fire({
            title: 'Invalid Input',
            text: 'Please enter a valid amount',
            icon: 'warning',
            confirmButtonColor: '#ffc107'
        });
        return;
    }

    const result = await Swal.fire({
        title: 'Add to Wallet',
        html: `Add <strong>$${amount.toFixed(2)}</strong> to this user's wallet?${reason ? '<br><small>Reason: ' + reason + '</small>' : ''}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Add Funds',
        cancelButtonText: 'Cancel'
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        const response = await fetch('{{ route("admin.membership-cards.add-wallet") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                user_id: currentUserId,
                amount: amount,
                reason: reason
            })
        });

        const data = await response.json();

        if (data.success) {
            await Swal.fire({
                title: 'Success!',
                text: data.message,
                icon: 'success',
                confirmButtonColor: '#28a745'
            });
            document.getElementById('user-wallet').textContent = parseFloat(data.user.wallet_balance).toFixed(2);
            document.getElementById('wallet-amount').value = '';
            document.getElementById('wallet-reason').value = '';
            playSound('success');
        } else {
            await Swal.fire({
                title: 'Error',
                text: data.message,
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
            playSound('error');
        }
    } catch (error) {
        await Swal.fire({
            title: 'Error',
            text: 'Error adding to wallet: ' + error.message,
            icon: 'error',
            confirmButtonColor: '#dc3545'
        });
        playSound('error');
    }
}

// Load user's recent orders
async function loadUserOrders(userId) {
    const ordersList = document.getElementById('orders-list');
    ordersList.innerHTML = '<div class="text-center text-muted"><div class="spinner-border spinner-border-sm"></div> Loading orders...</div>';

    try {
        const response = await fetch(`{{ route("admin.membership-cards.user-orders") }}?user_id=${userId}`);
        const data = await response.json();

        if (data.success && data.orders.length > 0) {
            let html = '<div class="table-responsive"><table class="table table-sm table-hover mb-0">';
            html += '<thead class="table-light"><tr>';
            html += '<th>Order #</th>';
            html += '<th>Date</th>';
            html += '<th>Total</th>';
            html += '<th>Status</th>';
            html += '<th>Points (1%)</th>';
            html += '<th>Action</th>';
            html += '</tr></thead><tbody>';

            data.orders.forEach(order => {
                const statusBadge = getStatusBadge(order.status);
                const pointsAwarded = order.points_awarded;
                const actionButton = pointsAwarded
                    ? '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Awarded</span>'
                    : `<button class="btn btn-sm btn-warning" onclick="awardOrderPoints(${order.id}, '${order.order_number}', ${order.calculated_points})">
                         <i class="bi bi-star"></i> Award ${order.calculated_points} pts
                       </button>`;

                html += `<tr>
                    <td><strong>${order.order_number}</strong></td>
                    <td><small>${order.created_at_human}</small></td>
                    <td>$${order.total}</td>
                    <td>${statusBadge}</td>
                    <td><strong>${order.calculated_points}</strong></td>
                    <td>${actionButton}</td>
                </tr>`;
            });

            html += '</tbody></table></div>';

            // Add summary
            html += `<div class="mt-3 p-2 bg-light rounded">
                <small class="text-muted">
                    <i class="bi bi-info-circle"></i> Showing ${data.orders.length} recent orders.
                    Points = 1% of order total.
                </small>
            </div>`;

            ordersList.innerHTML = html;
        } else if (data.success) {
            ordersList.innerHTML = '<div class="alert alert-info mb-0"><i class="bi bi-info-circle"></i> No orders found for this user.</div>';
        } else {
            ordersList.innerHTML = '<div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle"></i> Error loading orders.</div>';
        }
    } catch (error) {
        ordersList.innerHTML = '<div class="alert alert-danger mb-0"><i class="bi bi-x-circle"></i> Error: ' + error.message + '</div>';
    }
}

// Get status badge HTML
function getStatusBadge(status) {
    const statusLower = status.toLowerCase();
    let badgeClass = 'secondary';

    if (statusLower === 'delivered' || statusLower === 'completed' || statusLower === 'collected') {
        badgeClass = 'success';
    } else if (statusLower === 'pending') {
        badgeClass = 'warning';
    } else if (statusLower === 'processing' || statusLower === 'shipped') {
        badgeClass = 'info';
    } else if (statusLower === 'cancelled' || statusLower === 'refunded') {
        badgeClass = 'danger';
    }

    return `<span class="badge bg-${badgeClass}">${status}</span>`;
}

// Award points for a specific order
async function awardOrderPoints(orderId, orderNumber, calculatedPoints) {
    const result = await Swal.fire({
        title: 'Award Order Points',
        html: `Award <strong>${calculatedPoints} points</strong> for Order #${orderNumber}?<br><small class="text-muted">(1% of order total)</small>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Award Points',
        cancelButtonText: 'Cancel'
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        const response = await fetch('{{ route("admin.membership-cards.award-order-points") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                user_id: currentUserId,
                order_id: orderId
            })
        });

        const data = await response.json();

        if (data.success) {
            await Swal.fire({
                title: 'Success!',
                html: `${data.points_awarded} points awarded for Order #${data.order_number}!<br><small>Order Total: $${data.order_total}</small>`,
                icon: 'success',
                confirmButtonColor: '#28a745'
            });

            // Update points balance
            document.getElementById('user-points').textContent = data.new_balance;

            // Reload orders to show updated status
            loadUserOrders(currentUserId);

            playSound('success');
        } else {
            await Swal.fire({
                title: 'Error',
                text: data.message,
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
            playSound('error');
        }
    } catch (error) {
        await Swal.fire({
            title: 'Error',
            text: 'Error awarding points: ' + error.message,
            icon: 'error',
            confirmButtonColor: '#dc3545'
        });
        playSound('error');
    }
}

// Play sound feedback
function playSound(type) {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();

    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);

    if (type === 'success') {
        oscillator.frequency.value = 800;
        gainNode.gain.value = 0.3;
        oscillator.start();
        oscillator.stop(audioContext.currentTime + 0.2);
    } else if (type === 'warning') {
        oscillator.frequency.value = 600;
        gainNode.gain.value = 0.2;
        oscillator.start();
        oscillator.stop(audioContext.currentTime + 0.3);
    } else if (type === 'error') {
        oscillator.frequency.value = 400;
        gainNode.gain.value = 0.3;
        oscillator.start();
        oscillator.stop(audioContext.currentTime + 0.4);
    }
}
</script>

<style>
    #card-input:focus {
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    .list-group-item:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }
</style>
@endsection
