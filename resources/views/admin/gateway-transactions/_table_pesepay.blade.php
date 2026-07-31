{{-- Pesepay filter form + lazy-load container --}}
<div class="filters-card">
    <form id="filter-form-pesepay" onsubmit="applyFilter('pesepay');return false;">
        <div class="filters-grid">
            <div class="filter-group">
                <label>Search</label>
                <input type="text" name="search" placeholder="Reference, reason, app name…">
            </div>
            <div class="filter-group">
                <label>Status</label>
                <select name="status">
                    <option value="">All statuses</option>
                    <option value="SUCCESS">Success</option>
                    <option value="FAILED">Failed</option>
                    <option value="PENDING">Pending</option>
                    <option value="CANCELLED">Cancelled</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Currency</label>
                <select name="currency">
                    <option value="">All currencies</option>
                    <option value="USD">USD</option>
                    <option value="ZAR">ZAR</option>
                    <option value="ZMW">ZMW</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Date From</label>
                <input type="date" name="date_from">
            </div>
            <div class="filter-group">
                <label>Date To</label>
                <input type="date" name="date_to">
            </div>
            <div class="filter-group" style="display:flex;flex-direction:row;align-items:flex-end;gap:6px;padding-top:2px">
                <button type="submit" class="btn-filter apply"><i class="bi bi-funnel"></i> Filter</button>
                <button type="button" class="btn-filter clear" onclick="clearFilter('pesepay')"><i class="bi bi-x-circle"></i> Clear</button>
            </div>
        </div>
    </form>
</div>

<div class="table-card">
    <div class="tab-inner-content">
        <div class="no-results"><i class="bi bi-hourglass-split"></i>Loading…</div>
    </div>
</div>

