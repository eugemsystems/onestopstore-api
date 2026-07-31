@extends('admin.layout')

@push('styles')
<style>
/* ── Card header ─────────────────────────────────────────────── */
.ord-card-header {
    background: linear-gradient(135deg, #0a2d6b 0%, #1a5cb8 100%);
    color: #fff;
    padding: 14px 22px;
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .5px;
    text-transform: uppercase;
    border-bottom: none;
    border-radius: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.ord-card-header h4,.ord-card-header h5,.ord-card-header h6 { margin:0; font-size:inherit; font-weight:inherit; letter-spacing:inherit; text-transform:inherit; color:#fff; }

/* Cards */
.card { border:none !important; border-radius:14px !important; box-shadow:0 2px 14px rgba(0,0,0,.08),0 1px 3px rgba(0,0,0,.04) !important; overflow:hidden !important; margin-bottom:18px !important; }
.card:hover { box-shadow:0 8px 28px rgba(0,0,0,.13),0 2px 8px rgba(0,0,0,.06) !important; transition:box-shadow .22s; }

/* Stepper */
.step-counter { transition:all .3s; box-shadow:0 2px 8px rgba(0,0,0,.08); }
.stepper-item.active   .step-counter { background:linear-gradient(135deg,#0a2d6b,#1a5cb8) !important; color:#fff !important; box-shadow:0 4px 14px rgba(15,61,140,.35) !important; transform:scale(1.08) !important; }
.stepper-item.completed .step-counter { background:linear-gradient(135deg,#0f7040,#1db954) !important; color:#fff !important; }
.stepper-item.active   .step-name { color:#0f3d8c !important; font-weight:700 !important; }
.stepper-item.active::after   { border-bottom-color:#0f3d8c !important; }
.stepper-item.completed .step-name { color:#0f7040 !important; font-weight:700 !important; }
.step-content { animation:stepFadeIn .3s ease-in; }
@keyframes stepFadeIn { from{opacity:0;transform:translateY(8px);} to{opacity:1;transform:translateY(0);} }

/* Product results grid */
#productResults { display:grid !important; grid-template-columns:repeat(auto-fill,minmax(155px,1fr)) !important; gap:12px !important; }

/* Product cards */
.product-card { border:1px solid #e2e8f0 !important; border-radius:12px !important; background:#fff !important; box-shadow:0 1px 4px rgba(0,0,0,.05) !important; transition:all .25s !important; position:relative !important; display:flex !important; flex-direction:column !important; cursor:pointer !important; overflow:hidden !important; }
.product-card:hover { box-shadow:0 6px 20px rgba(15,61,140,.14) !important; border-color:#1a5cb8 !important; transform:translateY(-3px) !important; }
.product-card img { width:100% !important; height:155px !important; object-fit:cover !important; display:block !important; transition:transform .3s !important; }
.product-card:hover img { transform:scale(1.04) !important; }

/* Variation options */
.variation-option { border:2px solid #e2e8f0 !important; border-radius:12px !important; background:#fafafa !important; transition:all .25s !important; }
.variation-option:hover   { border-color:#1a5cb8 !important; background:#eff6ff !important; box-shadow:0 2px 10px rgba(15,61,140,.1) !important; }
.variation-option.selected { border-color:#0f3d8c !important; background:#dbeafe !important; box-shadow:0 3px 14px rgba(15,61,140,.15) !important; }

/* Cart items */
.cart-item { border-bottom:1px solid #f1f5f9 !important; padding:12px 0 !important; font-size:.86rem !important; }

/* Buttons */
.btn-primary { background:linear-gradient(135deg,#0a2d6b,#1a5cb8) !important; border:none !important; box-shadow:0 2px 10px rgba(15,61,140,.28) !important; color:#fff !important; }
.btn-primary:hover { box-shadow:0 5px 16px rgba(15,61,140,.42) !important; transform:translateY(-1px) !important; background:linear-gradient(135deg,#0a2d6b,#1a5cb8) !important; }
.btn-success { background:linear-gradient(135deg,#0f7040,#1db954) !important; border:none !important; color:#fff !important; }
.btn-success:hover { box-shadow:0 5px 16px rgba(15,112,64,.4) !important; transform:translateY(-1px) !important; }

/* Modals */
.modal-header[style*="#062a6a"],.modal-header[style*="062a6a"] { background:linear-gradient(135deg,#0a2d6b,#1a5cb8) !important; color:#fff !important; }
.modal-content { border-radius:14px !important; overflow:hidden !important; box-shadow:0 20px 60px rgba(0,0,0,.18) !important; }

/* Cart items */
.cart-item { border-bottom:1px solid #f1f5f9 !important; padding:10px 0 !important; font-size:.86rem !important; }
.cart-item:last-child { border-bottom:none !important; }

/* Quantity controls */
.quantity-control { display:flex !important; align-items:center !important; gap:5px !important; }
.quantity-control button { width:30px !important; height:30px !important; padding:0 !important; }
.quantity-control input { width:60px !important; text-align:center !important; }

/* Customer item */
.customer-item { cursor:pointer !important; transition:background-color .2s !important; }
.customer-item:hover { background-color:#f8fafc !important; }

/* Sticky sidebar */
.sticky-sidebar { position:sticky !important; top:20px !important; max-height:calc(100vh - 40px) !important; overflow-y:auto !important; }

/* Scrollable checkout left column */
#checkoutLeftColumn { max-height:calc(100vh - 100px) !important; overflow-y:auto !important; padding-right:12px !important; }
#checkoutLeftColumn::-webkit-scrollbar { width:6px; }
#checkoutLeftColumn::-webkit-scrollbar-track { background:#f1f5f9; border-radius:10px; }
#checkoutLeftColumn::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:10px; }
#checkoutLeftColumn::-webkit-scrollbar-thumb:hover { background:#94a3b8; }
</style>
@endpush

@section('title', 'Create Order - Admin Panel')

@section('content')
{{-- ═══════════════ CREATE ORDER HERO ═══════════════ --}}
<div style="background:linear-gradient(135deg,#0a2d6b 0%,#1a5cb8 100%);border-radius:16px;padding:20px 28px;margin-bottom:22px;color:#fff;box-shadow:0 8px 32px rgba(10,45,107,.35);">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <div style="font-size:.65rem;font-weight:700;letter-spacing:.8px;text-transform:uppercase;opacity:.6;margin-bottom:4px;">
                <i class="bi bi-plus-circle me-1"></i> Create New Order
            </div>
            <h2 style="font-size:1.45rem;font-weight:800;margin:0 0 10px;color:#fff;letter-spacing:-.4px;">Point of Sale</h2>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                {{-- Currency badge --}}
                <span style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);border-radius:20px;padding:4px 12px;font-size:.78rem;font-weight:700;" id="currencyBadge">
                    <i class="bi bi-currency-exchange me-1"></i>
                    <span id="currentCurrencyCode">{{ $detectedCurrency['code'] }}</span>
                    (<span id="currentCurrencySymbol">{{ $detectedCurrency['symbol'] }}</span>)
                </span>
                {{-- Currency selector --}}
                <div style="position:relative;">
                    <select class="form-select form-select-sm" id="currencySelector"
                        style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);color:#fff;border-radius:20px;padding:4px 28px 4px 12px;font-size:.78rem;font-weight:600;appearance:none;cursor:pointer;min-width:100px;">
                        @php $currencies = \App\Models\Currency::where('status', 1)->get(); @endphp
                        @foreach($currencies as $curr)
                        <option value="{{ $curr->code }}"
                            data-symbol="{{ $curr->symbol }}"
                            data-rate="{{ $curr->exchange_rate }}"
                            {{ $detectedCurrency['code'] === $curr->code ? 'selected' : '' }}
                            style="color:#0f172a;background:#fff;">
                            {{ $curr->code }} ({{ $curr->symbol }})
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Step tabs --}}
                <div style="display:flex;gap:6px;align-items:center;margin-left:8px;">
                    <div id="step1-indicator" class="step-tab active"
                         style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.25);border:1px solid rgba(255,255,255,.4);border-radius:20px;padding:4px 14px;font-size:.75rem;font-weight:700;cursor:default;transition:all .2s;">
                        <span style="width:18px;height:18px;border-radius:50%;background:#fff;color:#0a2d6b;display:inline-flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:800;">1</span>
                        Add Products
                    </div>
                    <i class="bi bi-chevron-right" style="opacity:.5;font-size:.7rem;"></i>
                    <div id="step2-indicator" class="step-tab"
                         style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.2);border-radius:20px;padding:4px 14px;font-size:.75rem;font-weight:600;cursor:default;opacity:.65;transition:all .2s;">
                        <span style="width:18px;height:18px;border-radius:50%;background:rgba(255,255,255,.3);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:800;">2</span>
                        Checkout
                    </div>
                </div>
            </div>
        </div>
        <a href="{{ route('admin.orders.index') }}"
           style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.25);color:rgba(255,255,255,.85);border-radius:10px;padding:7px 16px;font-size:.8rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;transition:background .2s;"
           onmouseover="this.style.background='rgba(255,255,255,.2)'" onmouseout="this.style.background='rgba(255,255,255,.1)'">
            <i class="bi bi-arrow-left"></i> Back to Orders
        </a>
    </div>
</div>

<form id="createOrderForm" method="POST" action="{{ route('admin.orders.store') }}">
    @csrf

    <!-- Currency Hidden Inputs -->
    <input type="hidden" name="currency" id="currency" value="{{ $detectedCurrency['code'] }}">
    <input type="hidden" name="currency_symbol" id="currency_symbol" value="{{ $detectedCurrency['symbol'] }}">
    <input type="hidden" name="exchange_rate" id="exchange_rate" value="{{ $detectedCurrency['exchange_rate'] }}">

    <!-- Step 1: Add Products to Cart -->
    <div id="step1" class="step-content">
        <div style="display:grid;grid-template-columns:1fr 380px;gap:16px;align-items:start;">

            <!-- LEFT: Product Search Panel -->
            <div style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(10,45,107,.1);">
                <!-- Header -->
                <div style="background:linear-gradient(135deg,#0a2d6b,#1a5cb8);padding:14px 20px;display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <i class="bi bi-grid-3x3-gap-fill" style="color:#fff;font-size:1rem;"></i>
                        <span style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#fff;">Search &amp; Add Products</span>
                    </div>
                    <span style="font-size:.68rem;color:rgba(255,255,255,.65);">Click a card to add to cart</span>
                </div>
                <!-- Search bar -->
                <div style="padding:14px 16px 0;">
                    <div style="display:flex;align-items:center;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;overflow:hidden;transition:border-color .18s;"
                         onfocusin="this.style.borderColor='#1a5cb8'" onfocusout="this.style.borderColor='#e2e8f0'">
                        <span style="padding:0 12px;color:#94a3b8;font-size:1rem;flex-shrink:0;"><i class="bi bi-search"></i></span>
                        <input type="text" id="productSearch" class="form-control border-0 shadow-none bg-transparent"
                               placeholder="Search products by name or SKU…" style="padding:10px 0;font-size:.86rem;color:#0f172a;">
                        <button class="btn btn-sm" type="button" id="clearSearch"
                                style="background:none;border:none;color:#94a3b8;padding:0 14px;font-size:1rem;">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
                <!-- Product grid -->
                <div style="padding:14px 16px;">
                    <div id="productResults"
                         style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;max-height:520px;overflow-y:auto;padding-right:2px;">
                    </div>
                </div>
            </div>

            <!-- RIGHT: Cart Panel -->
            <div style="position:sticky;top:20px;">
                <!-- Cart header -->
                <div style="background:linear-gradient(135deg,#0a2d6b,#1a5cb8);border-radius:16px 16px 0 0;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <i class="bi bi-bag-heart-fill" style="color:#fff;font-size:.95rem;"></i>
                        <span style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#fff;">Cart</span>
                    </div>
                    <span id="cartItemCount" style="background:rgba(255,255,255,.22);color:#fff;border-radius:20px;padding:2px 12px;font-size:.7rem;font-weight:800;">0 items</span>
                </div>
                <!-- Cart items area -->
                <div id="cartItems" style="background:#fff;min-height:260px;max-height:400px;overflow-y:auto;padding:10px 14px;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;">
                    <div id="emptyCartMsg" class="text-center py-5">
                        <i class="bi bi-bag-x" style="font-size:2.2rem;color:#cbd5e1;display:block;margin-bottom:8px;"></i>
                        <p style="color:#94a3b8;font-size:.8rem;margin:0;">Cart is empty.<br>Add products from the grid.</p>
                    </div>
                </div>
                <!-- Cart footer: subtotal + proceed -->
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-top:2px solid #e2e8f0;border-radius:0 0 16px 16px;padding:12px 14px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                        <span style="font-size:.78rem;color:#64748b;font-weight:600;">Subtotal</span>
                        <strong style="font-size:1rem;color:#0f3d8c;" id="cartSubtotal">
                            <span id="currencySymbol">{{ $detectedCurrency['symbol'] }}</span>0.00
                        </strong>
                    </div>
                    <button type="button" class="btn w-100" id="proceedToCheckout" disabled
                        style="background:linear-gradient(135deg,#0a2d6b,#1a5cb8);border:none;color:#fff;border-radius:10px;padding:11px;font-weight:700;font-size:.88rem;box-shadow:0 4px 14px rgba(10,45,107,.28);">
                        Proceed to Checkout &nbsp;<i class="bi bi-arrow-right-circle-fill"></i>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- Step 2: Checkout & Complete Order -->
    <div id="step2" class="step-content" style="display:none;">
        <div style="display:grid;grid-template-columns:1fr 360px;gap:16px;align-items:start;">

            <!-- LEFT: Unified Checkout Form -->
            <div style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(10,45,107,.1);" id="checkoutLeftColumn">

                <!-- Panel header -->
                <div style="background:linear-gradient(135deg,#0a2d6b 0%,#1a5cb8 100%);padding:14px 20px;display:flex;align-items:center;gap:10px;">
                    <i class="bi bi-clipboard2-check-fill" style="color:#fff;font-size:1rem;"></i>
                    <span style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#fff;">Checkout Details</span>
                </div>

                <div style="padding:0 20px;">

                    <!-- ① Customer -->
                    <div style="padding:16px 0;border-bottom:1px solid #f1f5f9;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                            <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#0a2d6b,#1a5cb8);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi bi-person-fill" style="color:#fff;font-size:.72rem;"></i>
                            </div>
                            <span style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#0f172a;">Customer</span>
                        </div>
                        <div style="display:flex;align-items:center;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;overflow:hidden;">
                            <span style="padding:0 12px;color:#94a3b8;font-size:1rem;flex-shrink:0;"><i class="bi bi-person-search"></i></span>
                            <input type="text" id="customerSearch" class="form-control border-0 shadow-none bg-transparent"
                                   placeholder="Search by name, email, or phone…" style="padding:10px 0;font-size:.86rem;">
                            <input type="hidden" name="consumer_id" id="consumer_id" required>
                        </div>
                        <div id="customerResults" class="list-group mt-1" style="display:none;"></div>
                        <div id="selectedCustomer" style="display:none;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:9px 14px;margin-top:8px;font-size:.82rem;">
                            <div style="font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#94a3b8;margin-bottom:3px;">
                                <i class="bi bi-check-circle-fill" style="color:#22c55e;"></i> Selected
                            </div>
                            <div id="customerInfo" style="font-weight:700;color:#0f172a;"></div>
                        </div>
                    </div>

                    <!-- ② Shipping Address -->
                    <div style="padding:16px 0;border-bottom:1px solid #f1f5f9;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                            <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#0a2d6b,#1a5cb8);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi bi-geo-alt-fill" style="color:#fff;font-size:.72rem;"></i>
                            </div>
                            <span style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#0f172a;">Shipping Address</span>
                        </div>
                        <select name="shipping_address_id" id="shipping_address_id" class="form-select mb-2" required disabled
                            style="border-radius:10px;border:1.5px solid #e2e8f0;font-size:.86rem;padding:9px 12px;">
                            <option value="">Select customer first</option>
                        </select>
                        <button type="button" class="btn btn-sm w-100" id="addNewAddressBtn" disabled
                            style="background:#f8fafc;border:1.5px dashed #cbd5e1;color:#64748b;border-radius:10px;padding:7px;font-size:.78rem;font-weight:600;">
                            <i class="bi bi-plus-circle me-1"></i> Add New Address
                        </button>
                        <input type="hidden" name="billing_address_id" id="billing_address_id">
                    </div>

                    <!-- ③ Delivery & Collections -->
                    <div style="padding:16px 0;border-bottom:1px solid #f1f5f9;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                            <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#0a2d6b,#1a5cb8);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi bi-truck" style="color:#fff;font-size:.72rem;"></i>
                            </div>
                            <span style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#0f172a;">Delivery &amp; Collections</span>
                        </div>
                        <div id="shippingMethodsContainer">
                            <p style="font-size:.82rem;color:#94a3b8;margin:0;"><i class="bi bi-info-circle me-1"></i>Select address to see shipping options</p>
                        </div>
                    </div>

                    <!-- ④ Payment Method -->
                    <div style="padding:16px 0;border-bottom:1px solid #f1f5f9;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                            <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#0a2d6b,#1a5cb8);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi bi-credit-card-fill" style="color:#fff;font-size:.72rem;"></i>
                            </div>
                            <span style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#0f172a;">Payment Method</span>
                        </div>
                        <select name="payment_method" id="payment_method" class="form-select" required
                            style="border-radius:10px;border:1.5px solid #e2e8f0;font-size:.86rem;padding:9px 12px;">
                            <option value="">Select Payment Method</option>
                            <option value="cod">💵 Cash at the Office</option>
                            <option value="bank_transfer">🏦 Bank Transfer</option>
                        </select>
                    </div>

                    <!-- ⑤ Wallet & Points -->
                    <div style="padding:16px 0;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                            <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#0a2d6b,#1a5cb8);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi bi-wallet2" style="color:#fff;font-size:.72rem;"></i>
                            </div>
                            <span style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#0f172a;">Wallet &amp; Points</span>
                        </div>
                        <p style="font-size:.82rem;color:#94a3b8;margin:0;" id="walletPointsPlaceholder"><i class="bi bi-info-circle me-1"></i>Select customer to see wallet and points</p>
                        <div id="walletPointsContainer" style="display:none;">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                <!-- Wallet pill -->
                                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:12px 14px;">
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                                        <span style="font-size:.72rem;font-weight:700;color:#065f46;"><i class="bi bi-wallet2 me-1"></i>Wallet</span>
                                        <strong id="availableWallet" style="color:#16a34a;font-size:.84rem;">$0.00</strong>
                                    </div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" id="use_wallet" name="wallet_balance">
                                        <label class="form-check-label" for="use_wallet" style="font-size:.78rem;">Use wallet</label>
                                    </div>
                                    <div id="walletAmountContainer" style="display:none;margin-top:6px;">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text" id="walletInputSymbol" style="border-radius:8px 0 0 8px;font-size:.78rem;">{{ $detectedCurrency['symbol'] }}</span>
                                            <input type="number" class="form-control" id="wallet_amount" step="0.01" min="0" placeholder="Auto" style="border-radius:0;font-size:.78rem;">
                                            <button class="btn btn-outline-secondary btn-sm" type="button" id="useMaxWallet" style="border-radius:0 8px 8px 0;font-size:.72rem;">Max</button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Points pill -->
                                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:12px 14px;">
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                                        <span style="font-size:.72rem;font-weight:700;color:#1e40af;"><i class="bi bi-star-fill me-1"></i>Points</span>
                                        <strong id="availablePoints" style="color:#2563eb;font-size:.84rem;">0 pts</strong>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="use_points" name="points_amount">
                                        <label class="form-check-label" for="use_points" style="font-size:.78rem;">Use points</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div><!-- /padding wrapper -->

                <!-- Order Notes – left column, below checkout fields -->
                <div style="padding:0 20px 16px;">
                    <div style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(10,45,107,.1);">
                        <div style="background:linear-gradient(135deg,#0a2d6b,#1a5cb8);padding:10px 16px;display:flex;align-items:center;gap:8px;">
                            <i class="bi bi-sticky-fill" style="color:#fff;font-size:.85rem;"></i>
                            <span style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#fff;">Order Notes <span style="opacity:.55;font-weight:400;text-transform:none;">(optional)</span></span>
                        </div>
                        <div style="padding:10px 14px;">
                            <textarea name="order_note" id="order_note" class="form-control" rows="2"
                                style="border-radius:10px;border:1.5px solid #e2e8f0;font-size:.82rem;resize:vertical;"
                                placeholder="Special instructions…"></textarea>
                        </div>
                    </div>
                </div>

            </div><!-- /left panel -->

            <!-- RIGHT: Sticky Summary Panel -->
            <div style="position:sticky;top:20px;">

                <!-- Order summary (includes items at top) -->
                <div style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(10,45,107,.1);margin-bottom:12px;">
                    <div style="background:linear-gradient(135deg,#0a2d6b,#1a5cb8);padding:12px 16px;display:flex;align-items:center;gap:8px;">
                        <i class="bi bi-receipt-cutoff" style="color:#fff;font-size:.9rem;"></i>
                        <span style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#fff;">Summary</span>
                    </div>
                    <!-- Items section inside the summary card -->
                    <div style="border-bottom:1px solid #f1f5f9;">
                        <div style="padding:8px 14px 4px;display:flex;align-items:center;gap:6px;">
                            <i class="bi bi-bag-check-fill" style="color:#1a5cb8;font-size:.8rem;"></i>
                            <span style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748b;">Order Items</span>
                        </div>
                        <div id="checkoutCartItems" style="max-height:220px;overflow-y:auto;padding:4px 14px 10px;">
                            <p style="color:#94a3b8;text-align:center;padding:12px 0;font-size:.82rem;margin:0;">Cart items will appear here</p>
                        </div>
                    </div>
                    <!-- Totals -->
                    <div style="padding:12px 14px;">
                        @php $sumRow = fn($l,$id,$sym) => "<div style='display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #f1f5f9;font-size:.8rem;'><span style='color:#64748b;'>$l</span><strong id='$id'>$sym 0.00</strong></div>"; @endphp
                        {!! $sumRow('Subtotal','subtotal',$detectedCurrency['symbol']) !!}
                        {!! $sumRow('Delivery Method','deliveryMethod',$detectedCurrency['symbol']) !!}
                        {!! $sumRow('Shipping Fee','shippingFee',$detectedCurrency['symbol']) !!}
                        <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #f1f5f9;font-size:.8rem;" id="fastShippingRow" style="display:none!important;">
                            <span style="color:#d97706;"><i class="bi bi-lightning-fill me-1"></i>Fast Shipping</span>
                            <strong style="color:#d97706;" id="fastShippingTotal">{{ $detectedCurrency['symbol'] }}0.00</strong>
                        </div>
                        <!-- Tax -->
                        <div style="padding:8px 0;border-bottom:1px solid #f1f5f9;">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="apply_tax" name="apply_tax" value="1">
                                <label class="form-check-label" for="apply_tax" style="font-size:.78rem;"><i class="bi bi-calculator me-1"></i>Apply Tax</label>
                            </div>
                            <small style="color:#94a3b8;font-size:.68rem;" id="taxRateInfo">Auto-calculated based on products</small>
                        </div>
                        <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #f1f5f9;font-size:.8rem;" id="taxRow" style="display:none!important;">
                            <span style="color:#64748b;">Tax (<span id="taxRatePercent">0</span>%)</span>
                            <strong id="taxAmount">{{ $detectedCurrency['symbol'] }}0.00</strong>
                        </div>
                        <input type="hidden" id="tax_total" name="tax_total" value="0">
                        <!-- Deductions -->
                        <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #f1f5f9;font-size:.8rem;" id="walletDeductionRow" style="display:none!important;">
                            <span style="color:#16a34a;"><i class="bi bi-wallet2 me-1"></i>Wallet</span>
                            <strong style="color:#16a34a;" id="walletDeduction">-{{ $detectedCurrency['symbol'] }}0.00</strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #f1f5f9;font-size:.8rem;" id="pointsDeductionRow" style="display:none!important;">
                            <span style="color:#2563eb;"><i class="bi bi-star-fill me-1"></i>Points</span>
                            <strong style="color:#2563eb;" id="pointsDeduction">-{{ $detectedCurrency['symbol'] }}0.00</strong>
                        </div>
                        <!-- Grand total -->
                        <div style="display:flex;justify-content:space-between;padding:10px 0 4px;border-top:2px solid #e2e8f0;margin-top:2px;">
                            <span style="font-weight:800;color:#0f172a;font-size:.9rem;">Total</span>
                            <strong style="font-size:1.05rem;color:#0f3d8c;" id="total">{{ $detectedCurrency['symbol'] }}0.00</strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;padding:4px 0;font-size:.86rem;" id="amountToPayRow" style="display:none!important;">
                            <span style="font-weight:700;color:#0f172a;">Amount to Pay</span>
                            <strong style="color:#dc2626;font-size:1rem;" id="amountToPay">{{ $detectedCurrency['symbol'] }}0.00</strong>
                        </div>
                    </div>
                </div>

                <!-- Validation -->
                <div class="alert alert-warning" id="validationMessages" style="display:none;border-radius:12px;font-size:.8rem;padding:10px 14px;margin-bottom:10px;">
                    <h6 class="mb-2" style="font-size:.82rem;"><i class="bi bi-exclamation-triangle me-1"></i>Required:</h6>
                    <ul id="validationList" class="mb-0 small"></ul>
                </div>

                <!-- Buttons -->
                <div style="display:flex;gap:8px;">
                    <button type="button" class="btn flex-fill" id="backToCart"
                        style="background:#f1f5f9;border:1.5px solid #e2e8f0;color:#475569;border-radius:12px;padding:11px;font-weight:700;font-size:.86rem;">
                        <i class="bi bi-arrow-left-circle me-1"></i> Back
                    </button>
                    <button type="submit" class="btn flex-fill" id="submitOrder" disabled
                        style="background:linear-gradient(135deg,#0f7040,#1db954);border:none;color:#fff;border-radius:12px;padding:11px;font-weight:700;font-size:.86rem;box-shadow:0 4px 14px rgba(15,112,64,.28);">
                        <i class="bi bi-check-circle me-1"></i> Create Order
                    </button>
                </div>

            </div><!-- /right sticky -->

        </div>
    </div>

</form>

<!-- Product Variation Modal -->
<div class="modal fade" id="variationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.22);">
            <!-- Gradient header -->
            <div style="background:linear-gradient(135deg,#0a2d6b 0%,#1a5cb8 100%);padding:18px 22px;display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:rgba(255,255,255,.6);margin-bottom:3px;"><i class="bi bi-tags-fill me-1"></i>Select Variation</div>
                    <div id="variationProductName" style="font-size:1rem;font-weight:700;color:#fff;"></div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="opacity:.7;"></button>
            </div>

            <div class="modal-body" style="padding:0;">
                <div style="display:grid;grid-template-columns:260px 1fr;">
                    <!-- Left: main preview image -->
                    <div style="background:#f8fafc;border-right:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;padding:24px;">
                        <div style="width:210px;height:210px;border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,.1);">
                            <img id="variationImage" src="" alt="Product"
                                 style="width:210px;height:210px;object-fit:cover;display:block;transition:all .25s;">
                        </div>
                    </div>

                    <!-- Right: variations list + qty -->
                    <div style="padding:20px;">
                        <!-- Selected price -->  
                        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:10px 14px;margin-bottom:14px;display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:.78rem;font-weight:600;color:#1e40af;"><i class="bi bi-tag-fill me-1"></i>Selected Price</span>
                            <strong id="selectedPrice" style="font-size:1.05rem;color:#0f3d8c;">$0.00</strong>
                        </div>

                        <!-- Variations list (scrollable) -->
                        <div id="variationsList" style="max-height:220px;overflow-y:auto;display:flex;flex-direction:column;gap:8px;"></div>

                        <!-- Quantity -->
                        <div style="margin-top:14px;display:flex;align-items:center;gap:10px;">
                            <label style="font-size:.78rem;font-weight:600;color:#475569;white-space:nowrap;">Quantity</label>
                            <div style="display:flex;align-items:center;border:1.5px solid #e2e8f0;border-radius:10px;overflow:hidden;">
                                <button type="button" onclick="document.getElementById('variationQuantity').stepDown()"
                                    style="width:36px;height:36px;background:#f8fafc;border:none;cursor:pointer;font-size:1rem;color:#475569;">−</button>
                                <input type="number" id="variationQuantity" value="1" min="1"
                                    style="width:52px;height:36px;border:none;text-align:center;font-weight:700;font-size:.9rem;outline:none;">
                                <button type="button" onclick="document.getElementById('variationQuantity').stepUp()"
                                    style="width:36px;height:36px;background:#f8fafc;border:none;cursor:pointer;font-size:1rem;color:#475569;">+</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="padding:14px 20px;border-top:1px solid #e2e8f0;display:flex;gap:10px;justify-content:flex-end;background:#fafafa;">
                <button type="button" class="btn" data-bs-dismiss="modal"
                    style="background:#f1f5f9;border:1.5px solid #e2e8f0;color:#475569;border-radius:10px;padding:8px 22px;font-weight:600;font-size:.86rem;">Cancel</button>
                <button type="button" class="btn" id="addVariationToCart"
                    style="background:linear-gradient(135deg,#0a2d6b,#1a5cb8);border:none;color:#fff;border-radius:10px;padding:8px 24px;font-weight:700;font-size:.86rem;box-shadow:0 3px 12px rgba(10,45,107,.3);">
                    <i class="bi bi-bag-plus-fill me-1"></i>Add to Cart
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Address Modal -->
<div class="modal fade" id="addAddressModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #062a6a; color: white;">
                <h5 class="modal-title">Add New Address</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addAddressForm">
                    <input type="hidden" id="addressUserId" name="user_id">
                    <div class="mb-3">
                        <label for="addressTitle" class="form-label">Address Title</label>
                        <input type="text" id="addressTitle" name="title" class="form-control" placeholder="Home, Work, etc." required>
                    </div>
                    <div class="mb-3">
                        <label for="addressStreet" class="form-label">Street Address</label>
                        <input type="text" id="addressStreet" name="street" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="addressCity" class="form-label">City</label>
                        <input type="text" id="addressCity" name="city" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="addressCountry" class="form-label">Country</label>
                        <select id="addressCountry" name="country_id" class="form-select" required>
                            <option value="">Select Country</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="addressState" class="form-label">State/Province</label>
                        <select id="addressState" name="state_id" class="form-select" required>
                            <option value="">Select State</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="addressPincode" class="form-label">Postal Code</label>
                        <input type="text" id="addressPincode" name="pincode" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="addressPhone" class="form-label">Phone</label>
                        <input type="text" id="addressPhone" name="phone" class="form-control" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveAddress">
                    <i class="bi bi-check-circle"></i> Save Address
                </button>
            </div>
        </div>
    </div>
</div>

@endsection


@push('scripts')
<script>
    let cart = [];
    let selectedCustomer = null;
    let selectedVariation = null;
    let currentProduct = null;
    let lastLoadedProducts = []; // Track displayed products to re-render on currency change

    // Countries and states loaded from server-side
    let countries = @json($countries);
    let allStates = {}; // Store states by country_id for quick lookup

    // Build states lookup object
    countries.forEach(country => {
        if (country.state) {
            allStates[country.id] = country.state;
        }
    });

    // Delivery options from server-side (what user selects)
    const shippingOptionsData = @json($shippingOptions);

    // Shipping rules from database (for calculating shipping fee based on order total)
    const shippingRulesData = @json($shippings);

    // Country-specific VAT rates (configured in config/tax.php via .env)
    // Zimbabwe: 15% | Zambia: 16%
    const DEFAULT_TAX_RATE_ZWL = {{ config('tax.zimbabwe_rate', 15) }};
    const DEFAULT_TAX_RATE_ZMW = {{ config('tax.zambia_rate', 16) }};

    // Currency detection from server (based on admin IP)
    const detectedCurrency = @json($detectedCurrency);
    let currentCurrency = {
        code: detectedCurrency.code,
        symbol: detectedCurrency.symbol,
        exchange_rate: detectedCurrency.exchange_rate
    };

    // Function to format amount with currency symbol and convert USD to display currency
    function formatCurrency(amount) {
        const amountUSD = parseFloat(amount) || 0;
        const converted = amountUSD * (currentCurrency.exchange_rate || 1);
        return currentCurrency.symbol + converted.toFixed(2);
    }

    // Sync currentCurrency from the dropdown's currently-selected option
    // This ensures the correct exchange_rate from the DB is always used,
    // even if the server-side $detectedCurrency has a stale or missing rate.
    function syncCurrencyFromSelector() {
        const selector = document.getElementById('currencySelector');
        if (!selector) return;
        const selected = selector.options[selector.selectedIndex];
        if (!selected || !selected.value) return;
        const rate = parseFloat(selected.dataset.rate);
        if (isNaN(rate) || rate <= 0) return;
        currentCurrency = {
            code: selected.value,
            symbol: selected.dataset.symbol || currentCurrency.symbol,
            exchange_rate: rate
        };
        // Keep hidden form inputs in sync
        const inp = document.getElementById('exchange_rate');
        if (inp) inp.value = rate;
    }

    // Helper function to round to 2 decimal places for consistent calculations
    function roundTo2(num) {
        return Math.round((parseFloat(num) || 0) * 100) / 100;
    }

    // Helper function to update currency from API responses
    function updateCurrency(currencyData) {
        if (currencyData && currencyData.code) {
            currentCurrency = {
                code: currencyData.code,
                symbol: currencyData.symbol,
                exchange_rate: currencyData.exchange_rate
            };

            // Update hidden form inputs
            document.getElementById('currency').value = currencyData.code;
            document.getElementById('currency_symbol').value = currencyData.symbol;
            document.getElementById('exchange_rate').value = currencyData.exchange_rate;

            // Update currency badge if it exists
            const currencyCodeEl = document.getElementById('currentCurrencyCode');
            const currencySymbolEl = document.getElementById('currentCurrencySymbol');
            if (currencyCodeEl) currencyCodeEl.textContent = currencyData.code;
            if (currencySymbolEl) currencySymbolEl.textContent = currencyData.symbol;

            // Update cart currency symbol if it exists
            const cartCurrencySymbol = document.getElementById('currencySymbol');
            if (cartCurrencySymbol) cartCurrencySymbol.textContent = currencyData.symbol;

            // Update wallet input symbol if it exists
            const walletInputSymbol = document.getElementById('walletInputSymbol');
            if (walletInputSymbol) walletInputSymbol.textContent = currencyData.symbol;

            // Re-render cart to update all displayed amounts
            if (typeof renderCart === 'function') {
                renderCart();
            }

            // Re-render product cards so prices convert to the new currency
            if (lastLoadedProducts.length > 0) {
                displayProductsAsCards(lastLoadedProducts);
            }
        }
    }

    // Tax checkbox event listeners - calculate tax automatically from products
    document.addEventListener('DOMContentLoaded', function() {
        const applyTaxCheckbox = document.getElementById('apply_tax');

        if (applyTaxCheckbox) {
            // Update summary when tax checkbox changes
            applyTaxCheckbox.addEventListener('change', function() {
                updateSummary();
            });
        }

        // Currency selector event listener
        const currencySelector = document.getElementById('currencySelector');
        if (currencySelector) {
            currencySelector.addEventListener('change', function() {
                if (this.value) {
                    const selectedOption = this.options[this.selectedIndex];
                    const newCurrency = {
                        code: this.value,
                        symbol: selectedOption.dataset.symbol,
                        exchange_rate: parseFloat(selectedOption.dataset.rate)
                    };

                    // Update current currency
                    currentCurrency = newCurrency;

                    // Update hidden inputs
                    document.getElementById('currency').value = newCurrency.code;
                    document.getElementById('currency_symbol').value = newCurrency.symbol;
                    document.getElementById('exchange_rate').value = newCurrency.exchange_rate;

                    // Update badge display
                    document.getElementById('currentCurrencyCode').textContent = newCurrency.code;
                    document.getElementById('currentCurrencySymbol').textContent = newCurrency.symbol;

                    // Re-render cart, summary, and product cards with new currency
                    renderCart();
                    updateSummary();
                    // Re-render product cards so prices reflect the new exchange rate
                    if (lastLoadedProducts.length > 0) {
                        displayProductsAsCards(lastLoadedProducts);
                    }
                }
            });
        }
    });

    // Function to calculate shipping fee based on shipping rules from database
    function calculateShippingFee(cartSubtotal, countryId) {

        if (!countryId || !shippingRulesData || shippingRulesData.length === 0) {
            return 0;
        }

        // Cart subtotal is ALREADY in USD (products API returns USD)
        // NO conversion needed!
        const cartSubtotalUSD = cartSubtotal;
        // Find shipping rules for the selected country
        const countryShipping = shippingRulesData.find(shipping =>
            shipping.country_id === parseInt(countryId)
        );

        if (!countryShipping || !countryShipping.shipping_rules || countryShipping.shipping_rules.length === 0) {
            return 0;
        }

        // Apply shipping rules (only ONCE per order, not per product)
        let shippingFeeUSD = 0;

        for (const rule of countryShipping.shipping_rules) {
            const min = parseFloat(rule.min || 0);
            const max = parseFloat(rule.max || 999999);
            const amount = parseFloat(rule.amount || 0);

            // Check if cart subtotal (in USD) falls within this rule's range
            if (cartSubtotalUSD >= min && cartSubtotalUSD <= max) {
                if (rule.rule_type === 'base_on_price') {
                    // Price-based shipping
                    if (rule.shipping_type === 'fixed') {
                        shippingFeeUSD = amount;
                        break; // Apply only once
                    } else if (rule.shipping_type === 'percentage') {
                        shippingFeeUSD = (cartSubtotalUSD * amount) / 100;
                        break; // Apply only once
                    }
                }
                // Note: weight-based shipping would require product weights, which we can add later if needed
            }
        }

        return shippingFeeUSD; // Return USD amount, NOT converted
    }

    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        // Sync currentCurrency from the dropdown's selected option first.
        // This reads the data-rate from the <option> that matches the user's
        // preferred_currency, ensuring formatCurrency() uses the correct rate
        // before any products are rendered.
        syncCurrencyFromSelector();

        loadRandomProducts();
        // Countries are already loaded from server-side, just populate dropdown when needed

        // Clear search button
        document.getElementById('clearSearch').addEventListener('click', function() {
            document.getElementById('productSearch').value = '';
            loadRandomProducts();
        });

        // Add new address button
        document.getElementById('addNewAddressBtn').addEventListener('click', function() {
            if (selectedCustomer) {
                document.getElementById('addressUserId').value = selectedCustomer.id;
                // Populate countries dropdown from server-side data
                populateCountriesDropdown();
                new bootstrap.Modal(document.getElementById('addAddressModal')).show();
            }
        });

        // Save address
        document.getElementById('saveAddress').addEventListener('click', saveNewAddress);

        // Country change - load states when country is selected
        document.getElementById('addressCountry').addEventListener('change', function() {
            const stateSelect = document.getElementById('addressState');
            if (this.value) {
                populateStatesDropdown(this.value);
                stateSelect.required = true;
            } else {
                stateSelect.innerHTML = '<option value="">Select Country First</option>';
                stateSelect.required = false;
            }
        });

        // Payment method change - trigger validation
        document.getElementById('payment_method').addEventListener('change', function() {
            updateSummary();
            validateForm();
        });
    });

    // Load random products on page load
    function loadRandomProducts() {
        fetch('{{ route("admin.orders.search.products") }}?paginate=10&status=1')
            .then(res => res.json())
            .then(data => {
                const products = data.data || [];

                // NOTE: Do NOT call updateCurrency(data.currency) here.
                // The API returns the store's default currency (usually USD).
                // We already have the correct currency set from the dropdown
                // selector via syncCurrencyFromSelector() on page load.

                displayProductsAsCards(products);
            })
            .catch(err => console.error('Error loading products:', err));
    }

    // Populate countries dropdown from server-side data
    function populateCountriesDropdown() {
        const select = document.getElementById('addressCountry');
        if (countries.length === 0) {
            select.innerHTML = '<option value="">No countries available</option>';
        } else {
            select.innerHTML = '<option value="">Select Country</option>' +
                countries.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
        }
    }

    // Populate states dropdown from server-side data
    function populateStatesDropdown(countryId) {
        if (!countryId) {
            const stateSelect = document.getElementById('addressState');
            stateSelect.innerHTML = '<option value="">Select Country First</option>';
            stateSelect.disabled = true;
            return;
        }

        const stateSelect = document.getElementById('addressState');
        const states = allStates[countryId] || [];

        if (states.length === 0) {
            stateSelect.innerHTML = '<option value="">No states available</option>';
            stateSelect.required = false;
            stateSelect.disabled = true;
        } else {
            stateSelect.innerHTML = '<option value="">Select State/Province</option>' +
                states.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
            stateSelect.disabled = false;
            stateSelect.required = true;
        }
    }

    // Search customers
    document.getElementById('customerSearch').addEventListener('input', debounce(function() {
        const query = this.value.trim();
        if (query.length < 2) {
            document.getElementById('customerResults').style.display = 'none';
            return;
        }

        fetch(`{{ route('admin.orders.search.users') }}?search=${encodeURIComponent(query)}&role=consumer&paginate=10`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
            .then(res => {
                if (!res.ok) throw new Error('Failed to fetch users');
                return res.json();
            })
            .then(data => {
                const customers = data.data || [];
                displayCustomers(customers);
            })
            .catch(err => {
                document.getElementById('customerResults').innerHTML = '<div class="list-group-item text-danger">Error loading customers. Please try again.</div>';
                document.getElementById('customerResults').style.display = 'block';
            });
    }, 300));

    function displayCustomers(customers) {
        const container = document.getElementById('customerResults');
        if (customers.length === 0) {
            container.innerHTML = '<div class="list-group-item text-muted">No customers found</div>';
            container.style.display = 'block';
            return;
        }

        container.innerHTML = customers.map(customer => {
            // Safely escape values, handling null/undefined
            const name = customer.name || 'N/A';
            const email = customer.email || '';
            const phone = customer.phone || '';

            const escapedName = String(name).replace(/'/g, "\\'").replace(/"/g, '&quot;');
            const escapedEmail = String(email).replace(/'/g, "\\'").replace(/"/g, '&quot;');
            const escapedPhone = String(phone).replace(/'/g, "\\'").replace(/"/g, '&quot;');

            return `
                <button type="button" class="list-group-item list-group-item-action customer-item"
                    onclick="selectCustomer(${customer.id}, '${escapedName}', '${escapedEmail}', '${escapedPhone}')">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${name}</strong><br>
                            <small class="text-muted">${email || 'No email'}</small>
                            ${phone ? `<br><small class="text-muted"><i class="bi bi-telephone"></i> ${phone}</small>` : ''}
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </div>
                </button>
            `;
        }).join('');
        container.style.display = 'block';
    }

    function selectCustomer(id, name, email, phone) {
        selectedCustomer = { id, name, email, phone };
        document.getElementById('consumer_id').value = id;
        document.getElementById('customerResults').style.display = 'none';
        document.getElementById('customerSearch').value = '';
        document.getElementById('selectedCustomer').style.display = 'block';
        document.getElementById('customerInfo').innerHTML = `
            <div class="mt-2">
                <strong><i class="bi bi-person-circle"></i> ${name}</strong><br>
                <small><i class="bi bi-envelope"></i> ${email}</small>
                ${phone ? `<br><small><i class="bi bi-telephone"></i> ${phone}</small>` : ''}
            </div>
        `;

        // Load customer addresses
        loadCustomerAddresses(id);

        // Load wallet and points balance
        loadWalletAndPoints(id);

        // Enable add address button
        document.getElementById('addNewAddressBtn').disabled = false;

        validateForm();
    }

    function loadCustomerAddresses(customerId) {
        fetch(`{{ route('admin.orders.addresses.user') }}?user_id=${customerId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
            .then(res => res.json())
            .then(data => {
                const addresses = data.data || [];
                const select = document.getElementById('shipping_address_id');

                if (addresses.length === 0) {
                    select.innerHTML = '<option value="">No addresses found - Please add one</option>';
                    select.disabled = true;
                    // Show add address modal automatically
                    setTimeout(() => {
                        document.getElementById('addNewAddressBtn').click();
                    }, 500);
                } else {
                    select.innerHTML = '<option value="">Select shipping address</option>' +
                        addresses.map(addr => `
                            <option value="${addr.id}" data-country-id="${addr.country_id}" data-state-id="${addr.state_id}">${addr.title || 'Address'} - ${addr.street}, ${addr.city}</option>
                        `).join('');
                    select.disabled = false;

                    // Set billing address to same as shipping and load shipping methods
                    select.addEventListener('change', function() {
                        document.getElementById('billing_address_id').value = this.value;
                        if (this.value) {
                            // Load shipping methods with cart total calculation
                            loadShippingMethods();
                        } else {
                            // Clear shipping methods if no address selected
                            document.getElementById('shippingMethodsContainer').innerHTML =
                                '<p class="text-muted small mb-0">Select address to see shipping options</p>';
                        }
                        validateForm();
                    });
                }
            })
            .catch(err => console.error('Error loading addresses:', err));
    }

    // Load wallet and points balance
    function loadWalletAndPoints(customerId) {
        // Load wallet balance
        fetch(`{{ route('admin.orders.wallet.user') }}?user_id=${customerId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
            .then(res => res.json())
            .then(data => {
                const walletBalance = data.data?.balance || 0;
                selectedCustomer.walletBalance = walletBalance; // stored as USD

                // NOTE: Do NOT call updateCurrency(data.currency) here.
                // The wallet API returns the store's default currency.
                // We preserve the admin's selected currency.

                document.getElementById('availableWallet').textContent = formatCurrency(walletBalance);
            })
            .catch(err => {
                console.error('Error loading wallet:', err);
                selectedCustomer.walletBalance = 0;
            });

        // Load points balance
        fetch(`{{ route('admin.orders.points.user') }}?user_id=${customerId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
            .then(res => res.json())
            .then(data => {
                const pointsBalance = data.data?.balance || 0;
                selectedCustomer.pointsBalance = pointsBalance;
                document.getElementById('availablePoints').textContent = parseFloat(pointsBalance).toFixed(0) + ' pts';
            })
            .catch(err => {
                console.error('Error loading points:', err);
                selectedCustomer.pointsBalance = 0;
            });

        // Show wallet and points container
        document.getElementById('walletPointsContainer').style.display = 'block';
        document.getElementById('walletPointsPlaceholder').style.display = 'none';

        // Add event listeners for wallet and points checkboxes
        document.getElementById('use_wallet').addEventListener('change', function() {

            // Show/hide the wallet amount input
            const walletAmountContainer = document.getElementById('walletAmountContainer');
            if (this.checked) {
                walletAmountContainer.style.display = 'block';
            } else {
                walletAmountContainer.style.display = 'none';
                document.getElementById('wallet_amount').value = '';
            }

            updateSummary();
        });

        // Add event listener for wallet amount input
        document.getElementById('wallet_amount').addEventListener('input', function() {
            updateSummary();
        });

        // Add event listener for "Use Max" button
        document.getElementById('useMaxWallet').addEventListener('click', function() {
            const walletInput = document.getElementById('wallet_amount');
            if (selectedCustomer && selectedCustomer.walletBalance > 0) {
                // walletBalance is USD; fill input with display-currency equivalent
                // so the admin sees the same amount as the "Available" balance
                const displayBalance = (selectedCustomer.walletBalance * (currentCurrency.exchange_rate || 1));
                walletInput.value = displayBalance.toFixed(2);
                updateSummary();
            }
        });

        document.getElementById('use_points').addEventListener('change', function() {
            updateSummary();
        });
    } // end loadWalletAndPoints

    // Load shipping methods - just display options with their base prices
    function loadShippingMethods() {

        // Use server-side shipping options data (original prices, no rules applied here)
        let shippingOptions = JSON.parse(JSON.stringify(shippingOptionsData)); // Deep clone


        const container = document.getElementById('shippingMethodsContainer');

        // Remember previously selected shipping option
        const previouslySelected = document.querySelector('input[name="shipping_method"]:checked');
        const previousValue = previouslySelected ? previouslySelected.value : null;

        if (shippingOptions.length === 0) {
            container.innerHTML = '<p class="text-muted small mb-0">No shipping options available</p>';
        } else {
            // Remember previously selected shipping option
            const previouslySelected = document.querySelector('input[name="shipping_method"]:checked');
            const previousValue = previouslySelected ? previouslySelected.value : null;

            // Create grid layout - 2 columns
            let html = '<div class="row g-3">';

            shippingOptions.forEach((option, index) => {
                const price = parseFloat(option.price || 0);
                const priceDisplay = formatCurrency(price);

                html += `
                    <div class="col-md-6">
                        <div class="delivery-option border rounded p-3 h-100" style="cursor: pointer; transition: all 0.3s;">
                            <div class="form-check">
                                <input
                                    class="form-check-input shipping-radio"
                                    type="radio"
                                    name="shipping_method"
                                    id="shipping_${index}"
                                    value="${index}"
                                    data-price="${price}"
                                    data-base-price="${price}"
                                    data-title="${option.title.replace(/"/g, '&quot;')}"
                                    data-description="${option.description.replace(/"/g, '&quot;')}"
                                    ${previousValue == index ? 'checked' : ''}
                                    onchange="selectShippingMethod(${index})"
                                >
                                <label class="form-check-label w-100" for="shipping_${index}" style="cursor: pointer;">
                                    <div>
                                        <strong class="text-dark">${option.title}</strong>
                                        <strong class="text-primary float-end">${priceDisplay}</strong>
                                        <br>
                                        <small class="text-muted">${option.description}</small>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            container.innerHTML = html;

            // Add hover and click effects
            document.querySelectorAll('.delivery-option').forEach(option => {
                const radio = option.querySelector('input[type="radio"]');

                // Set initial style for checked option
                if (radio.checked) {
                    option.style.backgroundColor = '#e7f3ff';
                    option.style.borderColor = '#062a6a';
                }

                option.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f8f9fa';
                    this.style.borderColor = '#062a6a';
                });
                option.addEventListener('mouseleave', function() {
                    const radio = this.querySelector('input[type="radio"]');
                    if (!radio.checked) {
                        this.style.backgroundColor = 'white';
                        this.style.borderColor = '#dee2e6';
                    } else {
                        this.style.backgroundColor = '#e7f3ff';
                        this.style.borderColor = '#062a6a';
                    }
                });

                // Click on card to select radio
                option.addEventListener('click', function(e) {
                    if (e.target.type !== 'radio') {
                        const radio = this.querySelector('input[type="radio"]');
                        radio.click();
                    }
                });
            });

            // Update summary after loading shipping options
            updateSummary();
        }

    }

    // Function to handle shipping method selection
    function selectShippingMethod(index) {

        // Highlight selected option
        document.querySelectorAll('.delivery-option').forEach(option => {
            option.style.backgroundColor = 'white';
            option.style.borderColor = '#dee2e6';
        });

        const selectedOption = document.getElementById(`shipping_${index}`).closest('.delivery-option');
        if (selectedOption) {
            selectedOption.style.backgroundColor = '#e7f3ff';
            selectedOption.style.borderColor = '#062a6a';
        }

        // Update summary with shipping price and validate form
        updateSummary();
        validateForm();
    }

    // Save new address
    function saveNewAddress() {
        const form = document.getElementById('addAddressForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        fetch('{{ route("admin.orders.addresses.create") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(response => {
            if (response.success || response.data) {
                bootstrap.Modal.getInstance(document.getElementById('addAddressModal')).hide();
                form.reset();
                // Reload addresses
                loadCustomerAddresses(selectedCustomer.id);
                showSuccess('Success', 'Address added successfully!');
            } else {
                showError('Error', 'Failed to add address. Please try again.');
            }
        })
        .catch(err => {
            console.error('Error saving address:', err);
            showError('Error', 'Failed to add address. Please try again.');
        });
    }

    // Search products
    document.getElementById('productSearch').addEventListener('input', debounce(function() {
        const query = this.value.trim();
        if (query.length < 2) {
            loadRandomProducts();
            return;
        }

        fetch(`{{ route('admin.orders.search.products') }}?search=${encodeURIComponent(query)}&paginate=20&status=1`)
            .then(res => res.json())
            .then(data => {
                const products = data.data || [];

                // Update currency if returned from API
                if (data.currency) {
                    updateCurrency(data.currency);
                }

                displayProductsAsCards(products);
            })
            .catch(err => console.error('Error searching products:', err));
    }, 300));

    function displayProductsAsCards(products) {
        const container = document.getElementById('productResults');
        // Always keep track of the latest product set for currency-switch re-renders
        if (products.length > 0) lastLoadedProducts = products;
        if (products.length === 0) {
            container.innerHTML = '<div class="col-12 text-center text-muted py-4">No products found</div>';
            return;
        }

        container.innerHTML = products.map(product => {
            const originalPrice = product.price || 0;
            const salePrice = product.sale_price;
            const finalPrice = salePrice || originalPrice;
            const hasDiscount = salePrice && salePrice < originalPrice;
            const productSlug = product.slug || product.id;
            const productUrl = `https://raines.africa/en/product/${productSlug}`;

            const thumbnail = product.product_thumbnail?.image_url ||
                            product.product_thumbnail?.original_url ||
                            'https://via.placeholder.com/200x200?text=No+Image';
            const hasVariations = product.variations && product.variations.length > 0;

            return `
                <div class="product-card" style="position:relative;display:flex;flex-direction:column;cursor:pointer;" onclick="handleProductClickFromDataset(this.querySelector('.add-to-cart-btn'))">
                    <div style="position:relative;overflow:hidden;border-radius:10px 10px 0 0;">
                        <a href="${productUrl}" target="_blank" onclick="event.stopPropagation();">
                            <img src="${thumbnail}" alt="${product.name}" style="width:100%;height:140px;object-fit:cover;display:block;transition:transform .3s;" onmouseover="this.style.transform='scale(1.04)'" onmouseout="this.style.transform='scale(1)'">
                        </a>
                        ${hasDiscount ? `<span style="position:absolute;top:7px;left:7px;background:#dc2626;color:#fff;font-size:.6rem;font-weight:800;padding:2px 7px;border-radius:20px;letter-spacing:.3px;">SALE</span>` : ''}
                        ${hasVariations ? `<span style="position:absolute;top:7px;right:7px;background:#0f3d8c;color:#fff;font-size:.6rem;font-weight:700;padding:2px 7px;border-radius:20px;"><i class="bi bi-tags"></i> Vars</span>` : ''}
                    </div>
                    <div style="padding:10px 11px;flex:1;display:flex;flex-direction:column;justify-content:space-between;">
                        <div>
                            <a href="${productUrl}" target="_blank" class="text-decoration-none text-dark" onclick="event.stopPropagation();">
                                <div style="font-size:.78rem;font-weight:700;color:#0f172a;line-height:1.3;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;margin-bottom:3px;">${product.name}</div>
                            </a>
                            <div style="font-size:.65rem;color:#94a3b8;margin-bottom:6px;">${product.sku || 'No SKU'}</div>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <div>
                                ${hasDiscount ? `
                                    <div style="font-size:.65rem;color:#94a3b8;text-decoration:line-through;">${formatCurrency(originalPrice)}</div>
                                    <div style="font-size:.88rem;font-weight:800;color:#dc2626;">${formatCurrency(finalPrice)}</div>
                                ` : `
                                    <div style="font-size:.88rem;font-weight:800;color:#0f3d8c;">${formatCurrency(finalPrice)}</div>
                                `}
                            </div>
                            <button type="button" class="btn btn-sm add-to-cart-btn"
                                style="background:linear-gradient(135deg,#0a2d6b,#1a5cb8);border:none;color:#fff;border-radius:8px;padding:5px 9px;font-size:.78rem;box-shadow:0 2px 8px rgba(10,45,107,.3);flex-shrink:0;"
                                data-product="${encodeURIComponent(JSON.stringify(product))}"
                                onclick="event.stopPropagation(); handleProductClickFromDataset(this)">
                                <i class="bi bi-cart-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }


    // Helper: parse encoded product JSON from button dataset and forward to existing handler
    function handleProductClickFromDataset(btn) {
        if (!btn || !btn.dataset) return;
        const encoded = btn.dataset.product;
        if (!encoded) return;
        try {
            const json = decodeURIComponent(encoded);
            const product = JSON.parse(json);
            handleProductClick(product);
        } catch (err) {
            console.error('Failed to parse product data from dataset', err, encoded);
            showError('Error', 'Failed to add product to cart. Invalid product data.');
        }
    }

    function handleProductClick(product) {
        currentProduct = product;

        // Check if product has variations
        if (product.variations && product.variations.length > 0) {
            showVariationModal(product);
        } else {
            addToCart(product, null);
        }
    }

    function showVariationModal(product) {
        const modal = new bootstrap.Modal(document.getElementById('variationModal'));
        const thumbnail = product.product_thumbnail?.image_url ||
                        product.product_thumbnail?.original_url ||
                        'https://via.placeholder.com/400x400?text=No+Image';

        document.getElementById('variationImage').src = thumbnail;
        document.getElementById('variationProductName').textContent = product.name;
        document.getElementById('variationQuantity').value = 1;

        // Display variations
        const variationsList = document.getElementById('variationsList');
        variationsList.innerHTML = product.variations.map((variation, index) => {
            const originalPrice = variation.price || 0;
            const salePrice = variation.sale_price;
            const finalPrice = salePrice || originalPrice;
            const hasDiscount = salePrice && salePrice < originalPrice;

            const varImage = variation.variation_image?.image_url ||
                           variation.variation_image?.original_url ||
                           thumbnail;
            const attributes = variation.attribute_values?.map(attr => attr.value).join(', ') || variation.name || 'Option';
            const stockStatus = variation.stock_status || 'In Stock';
            const isAvailable = variation.status === 1 && (variation.quantity || 0) > 0;

            return `
                <div class="variation-option ${index === 0 ? 'selected' : ''}"
                     data-variation-index="${index}"
                     data-variation-id="${variation.id}"
                     style="display:flex;align-items:center;gap:12px;padding:10px 12px;border:2px solid ${index === 0 ? '#0f3d8c' : '#e2e8f0'};border-radius:12px;cursor:pointer;background:${index === 0 ? '#eff6ff' : '#fff'};transition:all .18s;">
                    <div style="width:64px;height:64px;border-radius:8px;overflow:hidden;flex-shrink:0;box-shadow:0 2px 6px rgba(0,0,0,.1);">
                        <img src="${varImage}" alt="${attributes}" style="width:64px;height:64px;object-fit:cover;display:block;">
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.84rem;font-weight:700;color:#0f172a;">${attributes}</div>
                        <div style="font-size:.7rem;color:#94a3b8;">SKU: ${variation.sku || 'N/A'}</div>
                        <div style="font-size:.7rem;font-weight:600;color:${isAvailable ? '#16a34a' : '#dc2626'};margin-top:2px;">
                            <i class="bi bi-${isAvailable ? 'check-circle-fill' : 'x-circle-fill'}"></i>
                            ${stockStatus} &nbsp;·&nbsp; ${variation.quantity || 0} left
                        </div>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        ${hasDiscount ? `
                            <div style="font-size:.7rem;color:#94a3b8;text-decoration:line-through;">${formatCurrency(originalPrice)}</div>
                            <div style="font-size:.95rem;font-weight:800;color:#dc2626;">${formatCurrency(finalPrice)}</div>
                        ` : `
                            <div style="font-size:.95rem;font-weight:800;color:#0f3d8c;">${formatCurrency(finalPrice)}</div>
                        `}
                    </div>
                </div>
            `;
        }).join('');

        // Add click event listeners to variation options
        document.querySelectorAll('.variation-option').forEach((element, index) => {
            element.addEventListener('click', function() {
                selectVariation(product.variations[index], this);
            });
        });

        // Select first variation by default
        if (product.variations.length > 0) {
            selectedVariation = product.variations[0];
            updateSelectedPrice();
        }

        modal.show();
    }

    function selectVariation(variation, element) {
        selectedVariation = variation;

        // Reset all variation options to unselected style
        document.querySelectorAll('.variation-option').forEach(el => {
            el.style.border = '2px solid #e2e8f0';
            el.style.background = '#fff';
        });
        // Apply selected style
        element.style.border = '2px solid #0f3d8c';
        element.style.background = '#eff6ff';

        // Update main preview image
        const varImage = variation.variation_image?.image_url ||
                        variation.variation_image?.original_url ||
                        currentProduct.product_thumbnail?.image_url ||
                        'https://via.placeholder.com/400x400?text=No+Image';
        document.getElementById('variationImage').src = varImage;

        updateSelectedPrice();
    }

    function updateSelectedPrice() {
        if (selectedVariation) {
            const price = selectedVariation.sale_price || selectedVariation.price || 0;
            document.getElementById('selectedPrice').textContent = formatCurrency(price);
        }
    }

    // Add variation to cart
    document.getElementById('addVariationToCart').addEventListener('click', function() {
        if (!selectedVariation || !currentProduct) return;

        const quantity = parseInt(document.getElementById('variationQuantity').value) || 1;
        addToCart(currentProduct, selectedVariation, quantity);

        bootstrap.Modal.getInstance(document.getElementById('variationModal')).hide();
    });

    function addToCart(product, variation = null, quantity = 1) {
        const cartItemId = variation ? `${product.id}_${variation.id}` : `${product.id}`;
        const existingItem = cart.find(item => item.cart_id === cartItemId);

        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            // Determine price and original price for sale display
            const originalPrice = variation ? variation.price : product.price || 0;
            const salePrice = variation ? variation.sale_price : product.sale_price;
            const finalPrice = salePrice || originalPrice;
            const hasDiscount = salePrice && salePrice < originalPrice;

            const thumbnail = variation?.variation_image?.image_url ||
                            variation?.variation_image?.original_url ||
                            product.product_thumbnail?.image_url ||
                            product.product_thumbnail?.original_url || '';
            const attributes = variation?.attribute_values?.map(attr => attr.value).join(', ') || '';

            cart.push({
                cart_id: cartItemId,
                product_id: product.id,
                variation_id: variation?.id || null,
                name: product.name,
                variation_name: attributes,
                sku: variation?.sku || product.sku,
                price: finalPrice, // Use sale price for calculations
                original_price: originalPrice, // Store original for display
                sale_price: salePrice, // Store sale price if exists
                has_discount: hasDiscount, // Flag to show strikethrough
                quantity: quantity,
                thumbnail: thumbnail,
                has_fast_shipping: product.has_expedited_shipping || false,
                fast_shipping_cost: parseFloat(product.expedited_shipping_price || 0),
                fast_shipping_enabled: false, // User hasn't selected it yet
                tax_rate: parseFloat(product.tax?.rate || 0) // Tax rate from product
            });
        }

        renderCart();
    }

    function updateQuantity(cartId, quantity) {
        const item = cart.find(item => item.cart_id === cartId);
        if (item) {
            item.quantity = Math.max(1, parseInt(quantity) || 1);
            renderCart();
        }
    }

    function toggleFastShipping(cartId, enabled) {
        const item = cart.find(item => item.cart_id === cartId);
        if (item) {
            item.fast_shipping_enabled = enabled;

            // Re-render cart to update display
            renderCart();

            // Force update summary to recalculate totals
            updateSummary();
        } else {
            console.error('Item not found in cart:', cartId);
        }
    }

    function removeFromCart(cartId) {
        cart = cart.filter(item => item.cart_id !== cartId);
        renderCart();
    }

    function renderCart() {
        const container = document.getElementById('cartItems');
        const itemCount = cart.length;

        const cartTotal = cart.reduce((sum, item) => {
            return sum + (item.price * item.quantity) + (item.fast_shipping_enabled ? item.fast_shipping_cost * item.quantity : 0);
        }, 0);

        // Update badge and subtotal
        const badge = document.getElementById('cartItemCount');
        if (badge) badge.textContent = itemCount + (itemCount === 1 ? ' item' : ' items');
        document.getElementById('cartSubtotal').innerHTML = formatCurrency(cartTotal);
        document.getElementById('proceedToCheckout').disabled = itemCount === 0;

        if (cart.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-bag-x" style="font-size:2.2rem;color:#cbd5e1;display:block;margin-bottom:8px;"></i>
                    <p style="color:#94a3b8;font-size:.8rem;margin:0;">Cart is empty.<br>Add products from the grid.</p>
                </div>`;
            updateSummary();
            return;
        }

        container.innerHTML = cart.map(item => {
            const lineTotal = (item.price * item.quantity) + (item.fast_shipping_enabled ? item.fast_shipping_cost * item.quantity : 0);
            return `
            <div style="display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid #f1f5f9;">
                ${item.thumbnail
                    ? `<img src="${item.thumbnail}" alt="${item.name}" style="width:44px;height:44px;object-fit:cover;border-radius:8px;flex-shrink:0;">`
                    : `<div style="width:44px;height:44px;background:#f1f5f9;border-radius:8px;flex-shrink:0;display:flex;align-items:center;justify-content:center;"><i class="bi bi-box" style="color:#94a3b8;"></i></div>`}
                <div style="flex:1;min-width:0;">
                    <div style="font-size:.78rem;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${item.name}</div>
                    ${item.variation_name ? `<div style="font-size:.65rem;color:#0ea5e9;">${item.variation_name}</div>` : ''}
                    <div style="font-size:.65rem;color:#94a3b8;">${item.sku || ''}</div>
                    ${item.has_fast_shipping ? `
                        <label style="display:flex;align-items:center;gap:4px;font-size:.65rem;color:#d97706;cursor:pointer;margin-top:2px;">
                            <input type="checkbox" ${item.fast_shipping_enabled ? 'checked' : ''} onchange="toggleFastShipping('${item.cart_id}', this.checked)" style="margin:0;">
                            <i class="bi bi-lightning-fill"></i> Fast (+${formatCurrency(item.fast_shipping_cost)})
                        </label>` : ''}
                    <div style="display:flex;align-items:center;gap:4px;margin-top:4px;">
                        <button type="button" onclick="updateQuantity('${item.cart_id}', ${item.quantity - 1})"
                            style="width:22px;height:22px;border:1px solid #e2e8f0;background:#fff;border-radius:6px;font-size:.78rem;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1;">−</button>
                        <span style="font-size:.75rem;font-weight:700;min-width:18px;text-align:center;">${item.quantity}</span>
                        <button type="button" onclick="updateQuantity('${item.cart_id}', ${item.quantity + 1})"
                            style="width:22px;height:22px;border:1px solid #e2e8f0;background:#fff;border-radius:6px;font-size:.78rem;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1;">+</button>
                        <button type="button" onclick="removeFromCart('${item.cart_id}')"
                            style="width:22px;height:22px;border:1px solid #fee2e2;background:#fff;border-radius:6px;font-size:.72rem;cursor:pointer;color:#dc2626;display:flex;align-items:center;justify-content:center;margin-left:2px;">
                            <i class="bi bi-x"></i></button>
                    </div>
                </div>
                <div style="text-align:right;flex-shrink:0;">
                    ${item.has_discount ? `<div style="font-size:.62rem;color:#94a3b8;text-decoration:line-through;">${formatCurrency(item.original_price)}</div>` : ''}
                    <div style="font-size:.82rem;font-weight:800;color:#0f3d8c;">${formatCurrency(lineTotal)}</div>
                    <div style="font-size:.62rem;color:#94a3b8;">×${item.quantity} @ ${formatCurrency(item.price)}</div>
                </div>
            </div>`;
        }).join('');

        updateCheckoutCartDisplay();
        updateSummary();
    }

    // Update cart display in checkout step (Step 2)
    function updateCheckoutCartDisplay() {
        const container = document.getElementById('checkoutCartItems');
        if (cart.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-4">No items in cart</p>';
            return;
        }

        container.innerHTML = cart.map(item => `
            <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                ${item.thumbnail ? `<img src="${item.thumbnail}" alt="${item.name}" style="width: 60px; height: 60px; object-fit: cover; margin-right: 15px; border-radius: 4px;">` : ''}
                <div class="flex-grow-1">
                    <div><strong>${item.name}</strong></div>
                    ${item.variation_name ? `<small class="text-info">${item.variation_name}</small><br>` : ''}
                    ${item.has_discount ? `
                        <small class="text-muted">Qty: ${item.quantity} × <span class="text-decoration-line-through">${formatCurrency(item.original_price)}</span> <span class="text-danger fw-bold">${formatCurrency(item.price)}</span></small>
                    ` : `
                        <small class="text-muted">Qty: ${item.quantity} × ${formatCurrency(item.price)}</small>
                    `}
                    ${item.fast_shipping_enabled ? `
                        <br><small class="text-warning">
                            <i class="bi bi-lightning-fill"></i> Fast Shipping: ${item.quantity} × ${formatCurrency(item.fast_shipping_cost)}
                        </small>
                    ` : ''}
                </div>
                <div class="text-end">
                    <strong>${formatCurrency((item.price * item.quantity) + (item.fast_shipping_enabled ? item.fast_shipping_cost * item.quantity : 0))}</strong>
                </div>
            </div>
        `).join('');
    }

    // Wizard Step Navigation
    document.getElementById('proceedToCheckout').addEventListener('click', function() {
        if (cart.length === 0) {
            showWarning('Cart Empty', 'Please add items to cart before proceeding to checkout.');
            return;
        }

        // Move to step 2
        goToStep(2);
    });

    document.getElementById('backToCart').addEventListener('click', function() {
        // Move back to step 1
        goToStep(1);
    });

    function goToStep(stepNumber) {
        document.getElementById('step1').style.display = 'none';
        document.getElementById('step2').style.display = 'none';

        const tab1 = document.getElementById('step1-indicator');
        const tab2 = document.getElementById('step2-indicator');
        const numStyle = 'width:18px;height:18px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:800;';

        if (stepNumber === 1) {
            document.getElementById('step1').style.display = 'block';
            // Step 1: active
            tab1.style.background = 'rgba(255,255,255,.25)';
            tab1.style.border = '1px solid rgba(255,255,255,.4)';
            tab1.style.opacity = '1';
            tab1.style.fontWeight = '700';
            tab1.querySelector('span').style.cssText = numStyle + 'background:#fff;color:#0a2d6b;';
            // Step 2: inactive
            tab2.style.background = 'rgba(255,255,255,.08)';
            tab2.style.border = '1px solid rgba(255,255,255,.2)';
            tab2.style.opacity = '0.65';
            tab2.style.fontWeight = '600';
            tab2.querySelector('span').style.cssText = numStyle + 'background:rgba(255,255,255,.3);color:#fff;';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else if (stepNumber === 2) {
            document.getElementById('step2').style.display = 'block';
            // Step 1: completed (green tint)
            tab1.style.background = 'rgba(34,197,94,.3)';
            tab1.style.border = '1px solid rgba(34,197,94,.5)';
            tab1.style.opacity = '1';
            tab1.style.fontWeight = '700';
            tab1.querySelector('span').style.cssText = numStyle + 'background:#22c55e;color:#fff;';
            tab1.querySelector('span').textContent = '✓';
            // Step 2: active
            tab2.style.background = 'rgba(255,255,255,.25)';
            tab2.style.border = '1px solid rgba(255,255,255,.4)';
            tab2.style.opacity = '1';
            tab2.style.fontWeight = '700';
            tab2.querySelector('span').style.cssText = numStyle + 'background:#fff;color:#0a2d6b;';
            updateCheckoutCartDisplay();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    // ...existing code...

    function updateSummary() {
        // Calculate subtotal (product prices only, no fast shipping)
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

        // Calculate fast shipping total separately
        const fastShippingTotal = cart.reduce((sum, item) => {
            return sum + (item.fast_shipping_enabled ? (item.fast_shipping_cost * item.quantity) : 0);
        }, 0);

        // Get delivery method and shipping fee BEFORE calculating tax
        // 1. DELIVERY METHOD COST
        let deliveryMethodCost = 0;
        const selectedShippingForTax = document.querySelector('input[name="shipping_method"]:checked');
        if (selectedShippingForTax) {
            deliveryMethodCost = parseFloat(selectedShippingForTax.getAttribute('data-base-price') || 0);
        }

        // 2. SHIPPING FEE from database rules (in USD)
        let shippingFeeUSD = 0;
        const selectedAddressIdForTax = document.getElementById('shipping_address_id')?.value;
        if (selectedAddressIdForTax && subtotal > 0) {
            const selectedOptionForTax = document.querySelector(`#shipping_address_id option[value="${selectedAddressIdForTax}"]`);
            const countryIdForTax = selectedOptionForTax?.getAttribute('data-country-id');
            if (countryIdForTax) {
                // calculateShippingFee now returns USD amount
                shippingFeeUSD = calculateShippingFee(subtotal, countryIdForTax);
            }
        }

        // Calculate tax automatically if checkbox is checked
        const applyTaxCheckbox = document.getElementById('apply_tax');
        const applyTax = applyTaxCheckbox ? applyTaxCheckbox.checked : false;
        let taxAmount = 0;
        let taxRate = 0;

        if (applyTax && cart.length > 0) {
            // Calculate tax based on product tax rates
            // Collect all unique tax rates from products in cart
            const taxRates = cart.map(item => {
                return item.tax_rate || 0;
            }).filter(rate => rate > 0);

            if (taxRates.length > 0) {
                // Use the first tax rate found from products
                taxRate = taxRates[0];
            } else {
                // Use default tax rate based on selected currency (Zambia=16%, Zimbabwe=15%)
                taxRate = currentCurrency.code === 'ZMW' ? DEFAULT_TAX_RATE_ZMW : DEFAULT_TAX_RATE_ZWL;
            }

            // Calculate tax on TOTAL ORDER AMOUNT (subtotal + shipping + delivery + fast shipping) - ALL IN USD
            const taxableAmount = subtotal + deliveryMethodCost + shippingFeeUSD + fastShippingTotal;
            taxAmount = roundTo2((taxableAmount * taxRate) / 100);
        }

        // Base total = subtotal + delivery method + shipping fee + fast shipping + tax (ALL IN USD)
        // Round each component to ensure precision
        const baseTotalUSD = roundTo2(subtotal) + roundTo2(deliveryMethodCost) + roundTo2(shippingFeeUSD) + roundTo2(fastShippingTotal) + roundTo2(taxAmount);

        // Convert to display currency and round
        const baseTotal = roundTo2(baseTotalUSD * currentCurrency.exchange_rate);

        // Calculate wallet deduction — everything in DISPLAY currency (ZMW/ZWL/etc)
        // walletBalance from API is in USD; convert it to display currency first
        let walletDeduction = 0;
        const walletCheckbox = document.getElementById('use_wallet');
        const useWallet = walletCheckbox ? walletCheckbox.checked : false;
        const rate = currentCurrency.exchange_rate || 1;

        if (useWallet && selectedCustomer && selectedCustomer.walletBalance > 0) {
            // Convert USD wallet balance to display currency
            const walletBalanceDisplay = selectedCustomer.walletBalance * rate;

            // Check if custom amount is specified (admin types in display currency)
            const walletAmountInput = document.getElementById('wallet_amount');
            const customAmount = walletAmountInput ? parseFloat(walletAmountInput.value) : 0;

            if (customAmount > 0) {
                // Clamp: cannot exceed available balance (in display currency) or order total
                walletDeduction = Math.min(customAmount, walletBalanceDisplay, baseTotal);
            } else {
                // Use maximum available (default behavior)
                walletDeduction = Math.min(walletBalanceDisplay, baseTotal);
            }
        }

        // Calculate remaining amount after wallet
        let remainingAfterWallet = Math.max(0, baseTotal - walletDeduction);

        // Calculate points deduction (convert points to display currency)
        let pointsDeduction = 0;
        const pointsCheckbox = document.getElementById('use_points');
        const usePoints = pointsCheckbox ? pointsCheckbox.checked : false;

        if (usePoints && selectedCustomer && selectedCustomer.pointsBalance > 0) {
            // 1 point = $0.01 USD; convert to display currency
            const pointsValueDisplay = selectedCustomer.pointsBalance * 0.01 * rate;
            pointsDeduction = Math.min(pointsValueDisplay, remainingAfterWallet);
        }

        // Calculate final total after all deductions (already in display currency)
        const finalTotal = Math.max(0, baseTotal - walletDeduction - pointsDeduction);

        // Update UI - Subtotal (products only, no fast shipping)
        document.getElementById('subtotal').textContent = formatCurrency(subtotal);

        // Update Delivery Method (what user selected)
        document.getElementById('deliveryMethod').textContent = formatCurrency(deliveryMethodCost);

        // Update Shipping Fee (based on order total rules) - convert USD to display
        document.getElementById('shippingFee').textContent = formatCurrency(shippingFeeUSD);

        // Update Fast Shipping Total - show/hide based on whether any items have it
        const fastShippingRow = document.getElementById('fastShippingRow');
        const fastShippingTotalEl = document.getElementById('fastShippingTotal');
        if (fastShippingTotal > 0) {
            fastShippingRow.style.display = 'flex';
            fastShippingTotalEl.textContent = formatCurrency(fastShippingTotal);
        } else {
            fastShippingRow.style.display = 'none';
        }

        // Update Tax Amount display
        const taxRow = document.getElementById('taxRow');
        const taxAmountEl = document.getElementById('taxAmount');
        const taxRatePercentEl = document.getElementById('taxRatePercent');
        const taxTotalInput = document.getElementById('tax_total');


        if (applyTax) {
            // Show tax row when checkbox is checked
            taxRow.style.display = 'flex';
            if (taxAmountEl) {
                taxAmountEl.textContent = formatCurrency(taxAmount);
            }
            if (taxRatePercentEl) {
                taxRatePercentEl.textContent = taxRate.toFixed(2);
            }
            if (taxTotalInput) {
                taxTotalInput.value = taxAmount.toFixed(2);
            }
        } else {
            // Hide tax row when checkbox is unchecked
            taxRow.style.display = 'none';
            if (taxAmountEl) taxAmountEl.textContent = formatCurrency(0);
            if (taxRatePercentEl) taxRatePercentEl.textContent = '0';
            if (taxTotalInput) taxTotalInput.value = '0';
        }

        // Show/hide wallet deduction row ONLY if wallet is being used
        const walletDeductionRow = document.getElementById('walletDeductionRow');
        const walletDeductionEl = document.getElementById('walletDeduction');
        if (useWallet && walletDeduction > 0) {
            walletDeductionRow.style.display = 'flex';
            // walletDeduction is already in display currency
            walletDeductionEl.textContent = '-' + currentCurrency.symbol + walletDeduction.toFixed(2);
        } else {
            walletDeductionRow.style.display = 'none';
            walletDeductionEl.textContent = '-' + currentCurrency.symbol + '0.00';
        }

        // Show/hide points deduction row ONLY if points are being used
        const pointsDeductionRow = document.getElementById('pointsDeductionRow');
        const pointsDeductionEl = document.getElementById('pointsDeduction');
        if (usePoints && pointsDeduction > 0) {
            pointsDeductionRow.style.display = 'flex';
            pointsDeductionEl.textContent = '-' + currentCurrency.symbol + pointsDeduction.toFixed(2);
        } else {
            pointsDeductionRow.style.display = 'none';
            pointsDeductionEl.textContent = '-' + currentCurrency.symbol + '0.00';
        }

        // Update Total - baseTotal is already in display currency, just add symbol
        document.getElementById('total').textContent = currentCurrency.symbol + baseTotal.toFixed(2);

        // Always show "Amount to Pay" if wallet or points are being used
        const amountToPayRow = document.getElementById('amountToPayRow');
        const amountToPayEl = document.getElementById('amountToPay');

        if (useWallet || usePoints) {
            // Show the row and update the amount
            amountToPayRow.style.display = 'flex';

            // All values already in display currency
            const amountRemaining = Math.max(0, baseTotal - walletDeduction - pointsDeduction);
            amountToPayEl.textContent = currentCurrency.symbol + amountRemaining.toFixed(2);

        } else {
            // Hide the row when neither wallet nor points are used
            amountToPayRow.style.display = 'none';
            amountToPayEl.textContent = currentCurrency.symbol + '0.00';
        }

        validateForm();
    }

    // Validate form and show helpful messages
    function validateForm() {
        const validationIssues = [];
        let isValid = true;

        // Check if cart has items
        if (cart.length === 0) {
            validationIssues.push('Add at least one product to the cart');
            isValid = false;
        }

        // Check if customer is selected
        if (!selectedCustomer) {
            validationIssues.push('Select a customer');
            isValid = false;
        }

        // Check if shipping address is selected
        const shippingAddress = document.getElementById('shipping_address_id')?.value;
        if (!shippingAddress) {
            validationIssues.push('Select a shipping address');
            isValid = false;
        }

        // Check if shipping method is selected
        const shippingMethod = document.querySelector('input[name="shipping_method"]:checked');
        if (!shippingMethod) {
            validationIssues.push('Select a delivery/shipping method');
            isValid = false;
        }

        // Check if payment method is selected
        const paymentMethod = document.getElementById('payment_method')?.value;
        if (!paymentMethod) {
            validationIssues.push('Select a payment method');
            isValid = false;
        }

        // Update submit button state
        const submitButton = document.getElementById('submitOrder');
        submitButton.disabled = !isValid;

        // Show/hide validation messages
        const validationMessagesEl = document.getElementById('validationMessages');
        const validationListEl = document.getElementById('validationList');

        if (!isValid && validationIssues.length > 0) {
            validationListEl.innerHTML = validationIssues.map(issue => `<li>${issue}</li>`).join('');
            validationMessagesEl.style.display = 'block';
        } else {
            validationMessagesEl.style.display = 'none';
        }

        return isValid;
    }

    // Form submission handler - add cart data and shipping method before submitting
    document.getElementById('createOrderForm').addEventListener('submit', function(e) {
        e.preventDefault();

        // Validate form one more time
        if (!validateForm()) {
            showWarning('Required Fields', 'Please fill in all required fields before creating the order.');
            return false;
        }

        // Get selected shipping method
        const selectedShipping = document.querySelector('input[name="shipping_method"]:checked');
        if (!selectedShipping) {
            showWarning('Shipping Required', 'Please select a shipping method.');
            return false;
        }

        // Get delivery method details (what user selected)
        const shippingIndex = parseInt(selectedShipping.value);
        const deliveryTitle = selectedShipping.getAttribute('data-title');
        const deliveryPrice = parseFloat(selectedShipping.getAttribute('data-base-price') || 0);
        const deliveryDescription = selectedShipping.getAttribute('data-description');

        // Calculate shipping fee based on database rules (separate from delivery method)
        const cartSubtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

        // Get country ID from selected shipping address
        const selectedAddressId = document.getElementById('shipping_address_id')?.value;
        const selectedOption = document.querySelector(`#shipping_address_id option[value="${selectedAddressId}"]`);
        const countryId = selectedOption?.getAttribute('data-country-id');

        // Calculate shipping fee from database rules (in USD)
        let shippingFeeUSD = 0;
        if (countryId && cartSubtotal > 0) {
            shippingFeeUSD = calculateShippingFee(cartSubtotal, countryId); // Returns USD
        }


        // Remove any existing hidden cart input
        const existingCartInput = document.querySelector('input[name="cart"]');
        if (existingCartInput) {
            existingCartInput.remove();
        }

        // Remove any existing shipping method inputs
        const existingShippingInputs = document.querySelectorAll('input[name^="item_shipping"]');
        existingShippingInputs.forEach(input => input.remove());

        // Prepare cart data with fast shipping information
        const cartDataWithFastShipping = cart.map(item => ({
            product_id: item.product_id,
            variation_id: item.variation_id,
            quantity: item.quantity,
            price: item.price,
            has_fast_shipping: item.fast_shipping_enabled ? 1 : 0,
            fast_shipping_cost: item.fast_shipping_enabled ? item.fast_shipping_cost : 0
        }));

        // Add cart data as JSON
        const cartInput = document.createElement('input');
        cartInput.type = 'hidden';
        cartInput.name = 'cart';
        cartInput.value = JSON.stringify(cartDataWithFastShipping);
        this.appendChild(cartInput);

        // Add delivery method details (what user selected)
        const deliveryMethodInput = document.createElement('input');
        deliveryMethodInput.type = 'hidden';
        deliveryMethodInput.name = 'item_shipping_method';
        deliveryMethodInput.value = deliveryTitle;
        this.appendChild(deliveryMethodInput);

        const deliveryPriceInput = document.createElement('input');
        deliveryPriceInput.type = 'hidden';
        deliveryPriceInput.name = 'delivery_price';
        deliveryPriceInput.value = deliveryPrice;
        this.appendChild(deliveryPriceInput);

        // Add shipping fee (USD amount - based on order total rules)
        const shippingFeeInput = document.createElement('input');
        shippingFeeInput.type = 'hidden';
        shippingFeeInput.name = 'shipping_fee';
        shippingFeeInput.value = shippingFeeUSD; // Send USD amount
        this.appendChild(shippingFeeInput);

        // Add fast shipping total (sum of all items with fast shipping enabled)
        const fastShippingTotal = cart.reduce((sum, item) => {
            return sum + (item.fast_shipping_enabled ? (item.fast_shipping_cost * item.quantity) : 0);
        }, 0);

        const fastShippingInput = document.createElement('input');
        fastShippingInput.type = 'hidden';
        fastShippingInput.name = 'fast_shipping_total';
        fastShippingInput.value = fastShippingTotal;
        this.appendChild(fastShippingInput);

        // Add wallet and points amounts if used (convert back to USD for backend)
        const useWalletCheckbox = document.getElementById('use_wallet');
        if (useWalletCheckbox && useWalletCheckbox.checked && selectedCustomer) {
            // Calculate wallet amount to use (in converted currency)
            const walletAmountInput = document.getElementById('wallet_amount');
            const customWalletAmount = walletAmountInput ? parseFloat(walletAmountInput.value) : 0;

            let walletAmountConverted = 0;
            if (customWalletAmount > 0) {
                walletAmountConverted = Math.min(customWalletAmount, selectedCustomer.walletBalance);
            } else {
                walletAmountConverted = selectedCustomer.walletBalance;
            }

            // Convert back to USD for backend processing
            const walletAmountUSD = walletAmountConverted / currentCurrency.exchange_rate;

            const walletInput = document.createElement('input');
            walletInput.type = 'hidden';
            walletInput.name = 'wallet_balance_amount';
            walletInput.value = walletAmountUSD.toFixed(2);
            this.appendChild(walletInput);

        }

        const usePointsCheckbox = document.getElementById('use_points');
        if (usePointsCheckbox && usePointsCheckbox.checked && selectedCustomer && selectedCustomer.pointsBalance > 0) {
            // Points are not currency-dependent, send as is
            const pointsAmountInput = document.createElement('input');
            pointsAmountInput.type = 'hidden';
            pointsAmountInput.name = 'points_amount_value';
            pointsAmountInput.value = selectedCustomer.pointsBalance;
            this.appendChild(pointsAmountInput);

        }

        // Add order note if present
        const orderNote = document.getElementById('order_note').value.trim();
        if (orderNote) {
            const noteInput = document.createElement('input');
            noteInput.type = 'hidden';
            noteInput.name = 'note';
            noteInput.value = orderNote;
            this.appendChild(noteInput);
        }

        // Disable submit button to prevent double submission
        document.getElementById('submitOrder').disabled = true;
        document.getElementById('submitOrder').innerHTML = '<i class="bi bi-hourglass-split"></i> Creating Order...';

        // Submit the form
        this.submit();
    });
</script>
@endpush
