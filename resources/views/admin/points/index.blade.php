@extends('admin.layout')

@section('title', 'Points Management - Admin Panel')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-coin"></i> Points Management</h2>
            </div>

            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Select Customer Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Select Customer</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <label for="customerSearch" class="form-label">Search Customer</label>
                            <input type="text"
                                   id="customerSearch"
                                   class="form-control"
                                   placeholder="Type customer name or email..."
                                   autocomplete="off">
                            <div id="customerResults" class="list-group mt-2" style="display: none;"></div>
                            <input type="hidden" id="selectedConsumerId">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Points Balance and Actions Card -->
            <div class="card mb-4" id="pointsCard" style="display: none;">
                <div class="card-header">
                    <h5 class="mb-0">Points Balance</h5>
                </div>
                <div class="card-body">
                    <div class="points-section">
                        <div class="d-flex align-items-center mb-4">
                            <div class="points-icon me-3">
                                <i class="bi bi-coin" style="font-size: 3rem; color: #fbbf24;"></i>
                            </div>
                            <div>
                                <h2 class="mb-0" id="pointsBalance">0</h2>
                                <h6 class="text-muted">Current Points Balance</h6>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="amount" class="form-label">Points Amount</label>
                                <input type="number"
                                       id="amount"
                                       class="form-control"
                                       placeholder="Enter points amount"
                                       step="1"
                                       min="1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="remark" class="form-label">Remark (Optional)</label>
                                <input type="text"
                                       id="remark"
                                       class="form-control"
                                       placeholder="Enter remark">
                            </div>
                        </div>

                        @can('point.edit')
                        <div class="d-flex gap-2">
                            <button type="button"
                                    class="btn btn-success"
                                    id="creditBtn"
                                    onclick="creditPoints()">
                                <i class="bi bi-plus-circle"></i> Add
                            </button>
                            <button type="button"
                                    class="btn btn-danger"
                                    id="debitBtn"
                                    onclick="debitPoints()">
                                <i class="bi bi-dash-circle"></i> Withdraw
                            </button>
                        </div>
                        @else
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle"></i> You can view points balance and transactions, but do not have permission to credit or debit points.
                        </div>
                        @endcan
                    </div>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="card" id="transactionsCard" style="display: none;">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Transaction History</h5>
                    <div class="d-flex gap-2">
                        <input type="date" id="startDate" class="form-control form-control-sm" style="width: auto;">
                        <input type="date" id="endDate" class="form-control form-control-sm" style="width: auto;">
                        <button class="btn btn-sm btn-primary" onclick="filterTransactions()">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Points</th>
                                    <th>Type</th>
                                    <th>Remark</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody id="transactionsBody">
                                <tr>
                                    <td colspan="4" class="text-center">Select a customer to view transactions</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="paginationContainer" class="d-flex justify-content-center mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.points-section {
    background: linear-gradient(135deg, #fff7ed 0%, #fed7aa 100%);
    padding: 2rem;
    border-radius: 12px;
}

.points-icon {
    background: white;
    padding: 1rem;
    border-radius: 50%;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.list-group-item {
    cursor: pointer;
}

.list-group-item:hover {
    background-color: #f8f9fa;
}

.status-credit {
    background-color: #28a745;
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 600;
}

.status-debit {
    background-color: #dc3545;
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 600;
}
</style>

@push('scripts')
<script>
let selectedConsumer = null;
let currentPage = 1;
let searchTimeout = null;

// Customer Search
document.getElementById('customerSearch').addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    const query = e.target.value.trim();

    if (query.length < 2) {
        document.getElementById('customerResults').style.display = 'none';
        return;
    }

    searchTimeout = setTimeout(() => searchCustomers(query), 500);
});

function searchCustomers(query) {
    fetch(`/admin/orders/search/users?search=${encodeURIComponent(query)}&role=consumer`)
        .then(res => res.json())
        .then(response => {
            const resultsDiv = document.getElementById('customerResults');
            resultsDiv.innerHTML = '';

            // Handle the response format { success: true, data: [...] }
            const data = response.data || response || [];

            if (data.length > 0) {
                data.forEach(user => {
                    const item = document.createElement('a');
                    item.href = '#';
                    item.className = 'list-group-item list-group-item-action';
                    item.innerHTML = `
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong>${user.name}</strong>
                                <br><small class="text-muted">${user.email}</small>
                            </div>
                        </div>
                    `;
                    item.onclick = (e) => {
                        e.preventDefault();
                        selectCustomer(user);
                    };
                    resultsDiv.appendChild(item);
                });
                resultsDiv.style.display = 'block';
            } else {
                resultsDiv.innerHTML = '<div class="list-group-item">No customers found</div>';
                resultsDiv.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error searching customers:', error);
            const resultsDiv = document.getElementById('customerResults');
            resultsDiv.innerHTML = '<div class="list-group-item text-danger">Error searching customers</div>';
            resultsDiv.style.display = 'block';
        });
}

function selectCustomer(user) {
    selectedConsumer = user;
    document.getElementById('selectedConsumerId').value = user.id;
    document.getElementById('customerSearch').value = `${user.name} (${user.email})`;
    document.getElementById('customerResults').style.display = 'none';

    loadPointsBalance();
    loadTransactions();
}

function loadPointsBalance() {
    if (!selectedConsumer) return;

    fetch(`/admin/points/user-points?consumer_id=${selectedConsumer.id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('pointsBalance').textContent = parseInt(data.balance).toString();
                document.getElementById('pointsCard').style.display = 'block';

                // Enable/disable debit button based on balance
                const debitBtn = document.getElementById('debitBtn');
                const amount = parseInt(document.getElementById('amount').value) || 0;
                debitBtn.disabled = amount > data.balance;
            }
        });
}

function loadTransactions(page = 1) {
    if (!selectedConsumer) return;

    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    let url = `/admin/points/transactions?consumer_id=${selectedConsumer.id}&page=${page}`;
    if (startDate) url += `&start_date=${startDate}`;
    if (endDate) url += `&end_date=${endDate}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderTransactions(data.transactions);
                document.getElementById('transactionsCard').style.display = 'block';
            }
        });
}

function renderTransactions(transactions) {
    const tbody = document.getElementById('transactionsBody');
    tbody.innerHTML = '';

    if (transactions.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">No transactions found</td></tr>';
        return;
    }

    transactions.data.forEach(tx => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${parseInt(tx.amount)}</td>
            <td><span class="status-${tx.type}">${tx.type.toUpperCase()}</span></td>
            <td>${tx.detail || '-'}</td>
            <td>${new Date(tx.created_at).toLocaleString()}</td>
        `;
        tbody.appendChild(row);
    });

    renderPagination(transactions);
}

function renderPagination(transactions) {
    const container = document.getElementById('paginationContainer');
    container.innerHTML = '';

    if (transactions.last_page <= 1) return;

    const nav = document.createElement('nav');
    const ul = document.createElement('ul');
    ul.className = 'pagination';

    for (let i = 1; i <= transactions.last_page; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === transactions.current_page ? 'active' : ''}`;
        const a = document.createElement('a');
        a.className = 'page-link';
        a.href = '#';
        a.textContent = i;
        a.onclick = (e) => {
            e.preventDefault();
            loadTransactions(i);
        };
        li.appendChild(a);
        ul.appendChild(li);
    }

    nav.appendChild(ul);
    container.appendChild(nav);
}

function filterTransactions() {
    loadTransactions(1);
}

function creditPoints() {
    if (!selectedConsumer) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Please select a customer',
            confirmButtonColor: '#667eea'
        });
        return;
    }

    const amount = parseInt(document.getElementById('amount').value);
    const remark = document.getElementById('remark').value;

    if (!amount || amount <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Amount',
            text: 'Please enter a valid points amount',
            confirmButtonColor: '#667eea'
        });
        return;
    }

    Swal.fire({
        title: 'Credit Points',
        text: `Credit ${amount} points to ${selectedConsumer.name}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Credit',
        cancelButtonText: 'Cancel',
        showLoaderOnConfirm: true,
        allowOutsideClick: () => !Swal.isLoading(),
        preConfirm: () => {
            return fetch('/admin/points/credit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    consumer_id: selectedConsumer.id,
                    balance: amount,
                    remark: remark
                })
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to credit points');
                }
                return data;
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.message}`);
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: result.value.message,
                confirmButtonColor: '#667eea'
            });
            document.getElementById('amount').value = '';
            document.getElementById('remark').value = '';
            loadPointsBalance();
            loadTransactions();
        }
    });
}

function debitPoints() {
    if (!selectedConsumer) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Please select a customer',
            confirmButtonColor: '#667eea'
        });
        return;
    }

    const amount = parseInt(document.getElementById('amount').value);
    const remark = document.getElementById('remark').value;

    if (!amount || amount <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Amount',
            text: 'Please enter a valid points amount',
            confirmButtonColor: '#667eea'
        });
        return;
    }

    Swal.fire({
        title: 'Debit Points',
        text: `Debit ${amount} points from ${selectedConsumer.name}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Debit',
        cancelButtonText: 'Cancel',
        showLoaderOnConfirm: true,
        allowOutsideClick: () => !Swal.isLoading(),
        preConfirm: () => {
            return fetch('/admin/points/debit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    consumer_id: selectedConsumer.id,
                    balance: amount,
                    remark: remark
                })
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to debit points');
                }
                return data;
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.message}`);
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: result.value.message,
                confirmButtonColor: '#667eea'
            });
            document.getElementById('amount').value = '';
            document.getElementById('remark').value = '';
            loadPointsBalance();
            loadTransactions();
        }
    });
}

// Update debit button state when amount changes
document.getElementById('amount').addEventListener('input', function() {
    if (selectedConsumer) {
        const balance = parseInt(document.getElementById('pointsBalance').textContent);
        const amount = parseInt(this.value) || 0;
        document.getElementById('debitBtn').disabled = amount > balance;
    }
});
</script>
@endpush
@endsection

