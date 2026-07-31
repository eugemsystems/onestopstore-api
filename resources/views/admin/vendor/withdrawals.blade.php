@extends('admin.layout')

@section('title', 'My Withdrawals')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0">
                <i class="bi bi-piggy-bank"></i> My Withdrawals
            </h2>
            <p class="text-muted">Request and track withdrawal requests</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title text-white-50">Wallet Balance</h6>
                    <h2 class="mb-0">${{ number_format($stats['wallet_balance'], 2) }}</h2>
                    <small>Available to withdraw</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title text-white-50">Pending</h6>
                    <h2 class="mb-0">${{ number_format($stats['pending_amount'], 2) }}</h2>
                    <small>Awaiting approval</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title text-white-50">Approved</h6>
                    <h2 class="mb-0">${{ number_format($stats['approved_amount'], 2) }}</h2>
                    <small>All time</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6 class="card-title text-white-50">Rejected</h6>
                    <h2 class="mb-0">${{ number_format($stats['rejected_amount'], 2) }}</h2>
                    <small>All time</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Withdrawal Card -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="bi bi-wallet2"></i> Request Withdrawal
            </h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>Minimum withdrawal amount: ${{ number_format($minWithdrawAmount, 2) }}</strong><br>
                <small>Your current wallet balance: ${{ number_format($stats['wallet_balance'], 2) }}</small>
            </div>

            <form method="POST" action="{{ route('admin.vendor.withdrawals.create') }}" id="withdrawalForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="amount" class="form-control" step="0.01" min="{{ $minWithdrawAmount }}" max="{{ $stats['wallet_balance'] }}" required placeholder="{{ $minWithdrawAmount }}" value="{{ old('amount') }}">
                        </div>
                        <small class="text-muted">Min: ${{ number_format($minWithdrawAmount, 2) }} | Max: ${{ number_format($stats['wallet_balance'], 2) }}</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_type" id="paymentType" class="form-select" required>
                            <option value="">Select payment method</option>
                            <option value="Bank" {{ old('payment_type') == 'Bank' ? 'selected' : '' }}>Bank Transfer</option>
                            <option value="Mobile Money" {{ old('payment_type') == 'Mobile Money' ? 'selected' : '' }}>Mobile Money</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Message (Optional)</label>
                        <input type="text" name="message" class="form-control" placeholder="Any notes..." value="{{ old('message') }}">
                    </div>
                </div>

                <!-- Bank Transfer Details -->
                <div id="bankTransferSection" class="payment-details-section" style="display: none;">
                    <hr class="my-4">
                    <h6 class="mb-3"><i class="bi bi-bank"></i> Bank Transfer Details</h6>

                    @if($paymentAccounts->where('bank_account_no', '!=', null)->count() > 0)
                    <div class="mb-3">
                        <label class="form-label">Select Saved Account</label>
                        <select name="payment_account_id" id="bankAccountSelect" class="form-select">
                            <option value="">-- Or enter new bank details below --</option>
                            @foreach($paymentAccounts->where('bank_account_no', '!=', null) as $account)
                            <option value="{{ $account->id }}"
                                    data-bank-name="{{ $account->bank_name }}"
                                    data-holder-name="{{ $account->bank_holder_name }}"
                                    data-account-no="{{ $account->bank_account_no }}"
                                    data-swift="{{ $account->swift }}"
                                    data-ifsc="{{ $account->ifsc }}"
                                    {{ old('payment_account_id') == $account->id ? 'selected' : '' }}>
                                {{ $account->bank_name }} - {{ substr($account->bank_account_no, -4) }} ({{ $account->bank_holder_name }})
                                @if($account->is_default) <span class="badge bg-success">Default</span> @endif
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div id="newBankFields">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Bank Name <span class="text-danger bank-required">*</span></label>
                                <input type="text" name="bank_name" id="bankName" class="form-control" placeholder="e.g., Standard Bank" value="{{ old('bank_name') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Account Holder Name <span class="text-danger bank-required">*</span></label>
                                <input type="text" name="bank_holder_name" id="bankHolderName" class="form-control" placeholder="Full name as per bank" value="{{ old('bank_holder_name') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Account Number <span class="text-danger bank-required">*</span></label>
                                <input type="text" name="bank_account_no" id="bankAccountNo" class="form-control" placeholder="Account number" value="{{ old('bank_account_no') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">SWIFT Code</label>
                                <input type="text" name="swift" id="bankSwift" class="form-control" placeholder="Optional" value="{{ old('swift') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">IFSC Code</label>
                                <input type="text" name="ifsc" id="bankIfsc" class="form-control" placeholder="Optional" value="{{ old('ifsc') }}">
                            </div>
                        </div>
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" name="save_payment_details" id="saveBankDetails" value="1" {{ old('save_payment_details') ? 'checked' : '' }}>
                            <label class="form-check-label" for="saveBankDetails">
                                <i class="bi bi-save"></i> Save these bank details for future withdrawals
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Mobile Money Details -->
                <div id="mobileMoneySection" class="payment-details-section" style="display: none;">
                    <hr class="my-4">
                    <h6 class="mb-3"><i class="bi bi-phone"></i> Mobile Money Details</h6>

                    @if($paymentAccounts->where('bank_account_no', '!=', null)->count() > 0)
                    <div class="mb-3">
                        <label class="form-label">Select Saved Mobile Money Account</label>
                        <select name="payment_account_id" id="mobileAccountSelect" class="form-select">
                            <option value="">-- Or enter new mobile money details below --</option>
                            @foreach($paymentAccounts as $account)
                            <option value="{{ $account->id }}"
                                    data-provider="{{ $account->bank_name }}"
                                    data-name="{{ $account->bank_holder_name }}"
                                    data-number="{{ $account->bank_account_no }}"
                                    {{ old('payment_account_id') == $account->id ? 'selected' : '' }}>
                                {{ $account->bank_name }} - {{ $account->bank_account_no }} ({{ $account->bank_holder_name }})
                                @if($account->is_default) <span class="badge bg-success">Default</span> @endif
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div id="newMobileFields">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Mobile Money Provider <span class="text-danger mobile-required">*</span></label>
                                <select name="mobile_money_provider" id="mobileProvider" class="form-select">
                                    <option value="">Select provider</option>
                                    <option value="EcoCash" {{ old('mobile_money_provider') == 'EcoCash' ? 'selected' : '' }}>EcoCash</option>
                                    <option value="OneMoney" {{ old('mobile_money_provider') == 'OneMoney' ? 'selected' : '' }}>OneMoney</option>
                                    <option value="Telecash" {{ old('mobile_money_provider') == 'Telecash' ? 'selected' : '' }}>Telecash</option>
                                    <option value="M-Pesa" {{ old('mobile_money_provider') == 'M-Pesa' ? 'selected' : '' }}>M-Pesa</option>
                                    <option value="Airtel Money" {{ old('mobile_money_provider') == 'Airtel Money' ? 'selected' : '' }}>Airtel Money</option>
                                    <option value="MTN Mobile Money" {{ old('mobile_money_provider') == 'MTN Mobile Money' ? 'selected' : '' }}>MTN Mobile Money</option>
                                    <option value="Other" {{ old('mobile_money_provider') == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mobile Money Number <span class="text-danger mobile-required">*</span></label>
                                <input type="text" name="mobile_money_number" id="mobileNumber" class="form-control" placeholder="e.g., 0771234567" value="{{ old('mobile_money_number') }}">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Account Name <span class="text-danger mobile-required">*</span></label>
                                <input type="text" name="mobile_money_name" id="mobileName" class="form-control" placeholder="Name registered on mobile money account" value="{{ old('mobile_money_name') }}">
                            </div>
                        </div>
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" name="save_payment_details" id="saveMobileDetails" value="1" {{ old('save_payment_details') ? 'checked' : '' }}>
                            <label class="form-check-label" for="saveMobileDetails">
                                <i class="bi bi-save"></i> Save these mobile money details for future withdrawals
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary" {{ $stats['wallet_balance'] < $minWithdrawAmount ? 'disabled' : '' }}>
                        <i class="bi bi-send"></i> Submit Withdrawal Request
                    </button>
                    @if($stats['wallet_balance'] < $minWithdrawAmount)
                        <small class="text-danger ms-2">Insufficient balance for withdrawal</small>
                    @endif
                </div>
            </form>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentType = document.getElementById('paymentType');
    const bankSection = document.getElementById('bankTransferSection');
    const mobileSection = document.getElementById('mobileMoneySection');
    const bankAccountSelect = document.getElementById('bankAccountSelect');
    const mobileAccountSelect = document.getElementById('mobileAccountSelect');

    // Payment type change
    paymentType.addEventListener('change', function() {
        // Hide all sections
        document.querySelectorAll('.payment-details-section').forEach(section => {
            section.style.display = 'none';
        });

        // Clear previous selections
        if (bankAccountSelect) bankAccountSelect.value = '';
        if (mobileAccountSelect) mobileAccountSelect.value = '';
        clearBankFields();
        clearMobileFields();

        // Show relevant section
        if (this.value === 'Bank') {
            bankSection.style.display = 'block';
            setFieldsRequired('bank', true);
            setFieldsRequired('mobile', false);
        } else if (this.value === 'Mobile Money') {
            mobileSection.style.display = 'block';
            setFieldsRequired('mobile', true);
            setFieldsRequired('bank', false);
        }
    });

    // Bank account selection
    if (bankAccountSelect) {
        bankAccountSelect.addEventListener('change', function() {
            if (this.value) {
                const option = this.options[this.selectedIndex];
                document.getElementById('bankName').value = option.dataset.bankName || '';
                document.getElementById('bankHolderName').value = option.dataset.holderName || '';
                document.getElementById('bankAccountNo').value = option.dataset.accountNo || '';
                document.getElementById('bankSwift').value = option.dataset.swift || '';
                document.getElementById('bankIfsc').value = option.dataset.ifsc || '';

                // Use readonly instead of disabled so values still get submitted
                setFieldsReadonly('bank', true);
                document.getElementById('saveBankDetails').checked = false;
                document.getElementById('saveBankDetails').disabled = true;
                // Don't require fields when using saved account (they're pre-filled)
                setFieldsRequired('bank', false);
            } else {
                clearBankFields();
                setFieldsReadonly('bank', false);
                document.getElementById('saveBankDetails').disabled = false;
                setFieldsRequired('bank', true);
            }
        });
    }

    // Mobile account selection
    if (mobileAccountSelect) {
        mobileAccountSelect.addEventListener('change', function() {
            if (this.value) {
                const option = this.options[this.selectedIndex];
                document.getElementById('mobileProvider').value = option.dataset.provider || '';
                document.getElementById('mobileNumber').value = option.dataset.number || '';
                document.getElementById('mobileName').value = option.dataset.name || '';

                // Use readonly instead of disabled so values still get submitted
                setFieldsReadonly('mobile', true);
                document.getElementById('saveMobileDetails').checked = false;
                document.getElementById('saveMobileDetails').disabled = true;
                // Don't require fields when using saved account
                setFieldsRequired('mobile', false);
            } else {
                clearMobileFields();
                setFieldsReadonly('mobile', false);
                document.getElementById('saveMobileDetails').disabled = false;
                setFieldsRequired('mobile', true);
            }
        });
    }

    function clearBankFields() {
        document.getElementById('bankName').value = '';
        document.getElementById('bankHolderName').value = '';
        document.getElementById('bankAccountNo').value = '';
        document.getElementById('bankSwift').value = '';
        document.getElementById('bankIfsc').value = '';
    }

    function clearMobileFields() {
        document.getElementById('mobileProvider').value = '';
        document.getElementById('mobileNumber').value = '';
        document.getElementById('mobileName').value = '';
    }

    function setFieldsReadonly(type, readonly) {
        if (type === 'bank') {
            document.getElementById('bankName').readOnly = readonly;
            document.getElementById('bankHolderName').readOnly = readonly;
            document.getElementById('bankAccountNo').readOnly = readonly;
            document.getElementById('bankSwift').readOnly = readonly;
            document.getElementById('bankIfsc').readOnly = readonly;

            // Add visual feedback
            const fields = ['bankName', 'bankHolderName', 'bankAccountNo', 'bankSwift', 'bankIfsc'];
            fields.forEach(id => {
                const field = document.getElementById(id);
                if (readonly) {
                    field.style.backgroundColor = '#e9ecef';
                    field.style.cursor = 'not-allowed';
                } else {
                    field.style.backgroundColor = '';
                    field.style.cursor = '';
                }
            });
        } else if (type === 'mobile') {
            document.getElementById('mobileProvider').disabled = readonly; // Keep disabled for select
            document.getElementById('mobileNumber').readOnly = readonly;
            document.getElementById('mobileName').readOnly = readonly;

            // Add visual feedback
            if (readonly) {
                document.getElementById('mobileProvider').style.backgroundColor = '#e9ecef';
                document.getElementById('mobileNumber').style.backgroundColor = '#e9ecef';
                document.getElementById('mobileName').style.backgroundColor = '#e9ecef';
                document.getElementById('mobileNumber').style.cursor = 'not-allowed';
                document.getElementById('mobileName').style.cursor = 'not-allowed';
            } else {
                document.getElementById('mobileProvider').style.backgroundColor = '';
                document.getElementById('mobileNumber').style.backgroundColor = '';
                document.getElementById('mobileName').style.backgroundColor = '';
                document.getElementById('mobileNumber').style.cursor = '';
                document.getElementById('mobileName').style.cursor = '';
            }
        }
    }

    function setFieldsRequired(type, required) {
        if (type === 'bank') {
            document.getElementById('bankName').required = required;
            document.getElementById('bankHolderName').required = required;
            document.getElementById('bankAccountNo').required = required;

            // Update visual indicators
            document.querySelectorAll('.bank-required').forEach(el => {
                el.style.display = required ? 'inline' : 'none';
            });
        } else if (type === 'mobile') {
            document.getElementById('mobileProvider').required = required;
            document.getElementById('mobileNumber').required = required;
            document.getElementById('mobileName').required = required;

            // Update visual indicators
            document.querySelectorAll('.mobile-required').forEach(el => {
                el.style.display = required ? 'inline' : 'none';
            });
        }
    }

    // Trigger on page load if payment type is already selected (from old() data)
    if (paymentType.value) {
        paymentType.dispatchEvent(new Event('change'));
    }
});
</script>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.vendor.withdrawals.index') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                            <a href="{{ route('admin.vendor.withdrawals.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Withdrawals Table -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0">Withdrawal History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th>Message</th>
                            <th>Requested Date</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($withdrawals as $withdrawal)
                        <tr>
                            <td><strong>#{{ $withdrawal->id }}</strong></td>
                            <td><strong>${{ number_format($withdrawal->amount, 2) }}</strong></td>
                            <td>{{ $withdrawal->payment_type ?? 'N/A' }}</td>
                            <td>
                                @if($withdrawal->status == 'pending')
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-hourglass-split"></i> Pending
                                    </span>
                                @elseif($withdrawal->status == 'approved')
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle"></i> Approved
                                    </span>
                                @elseif($withdrawal->status == 'rejected')
                                    <span class="badge bg-danger">
                                        <i class="bi bi-x-circle"></i> Rejected
                                    </span>
                                @endif
                            </td>
                            <td>
                                <small>{{ $withdrawal->message ?: '-' }}</small>
                            </td>
                            <td>{{ $withdrawal->created_at->format('M d, Y H:i') }}</td>
                            <td>
                                @if($withdrawal->status == 'approved')
                                    <small class="text-success">
                                        Approved on {{ $withdrawal->approved_at ? $withdrawal->approved_at->format('M d, Y') : 'N/A' }}
                                        @if($withdrawal->payment_reference)
                                            <br>Ref: {{ $withdrawal->payment_reference }}
                                        @endif
                                    </small>
                                @elseif($withdrawal->status == 'rejected')
                                    <small class="text-danger">
                                        Rejected on {{ $withdrawal->rejected_at ? $withdrawal->rejected_at->format('M d, Y') : 'N/A' }}
                                        @if($withdrawal->rejection_reason)
                                            <br><strong>Reason:</strong> {{ $withdrawal->rejection_reason }}
                                        @endif
                                    </small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                <p class="text-muted mt-2">No withdrawal requests yet</p>
                                <small class="text-muted">Request your first withdrawal using the form above</small>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $withdrawals->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

