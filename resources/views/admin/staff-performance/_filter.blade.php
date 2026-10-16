{{-- Month / Year period filter. $action = form target URL --}}
<form method="GET" action="{{ $action }}" class="sp-filter">
    <span class="sp-lbl">View by:</span>
    <label class="sp-radio"><input type="radio" name="period" value="month" {{ $period === 'month' ? 'checked' : '' }} onchange="spToggle()"> Month</label>
    <label class="sp-radio"><input type="radio" name="period" value="year" {{ $period === 'year' ? 'checked' : '' }} onchange="spToggle()"> Year</label>
    <label class="sp-radio"><input type="radio" name="period" value="range" {{ $period === 'range' ? 'checked' : '' }} onchange="spToggle()"> Dates</label>

    <input type="month" name="month" id="sp-month" value="{{ $month }}" max="{{ now()->format('Y-m') }}">
    <select name="year" id="sp-year">
        @for($y = now()->year; $y >= max(2020, now()->year - 6); $y--)
            <option value="{{ $y }}" {{ (int) $year === $y ? 'selected' : '' }}>{{ $y }}</option>
        @endfor
    </select>
    <span id="sp-range" style="display:none;align-items:center;gap:6px;">
        <input type="date" name="date_from" value="{{ $from->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}">
        <span style="color:#94a3b8;">→</span>
        <input type="date" name="date_to" value="{{ $to->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}">
    </span>
    <button type="submit" class="sp-btn">Apply</button>
    <span class="sp-current">Showing: <strong>{{ $label }}</strong></span>
</form>
<script>
function spToggle() {
    var p = document.querySelector('.sp-filter input[name=period]:checked').value;
    document.getElementById('sp-month').style.display = p === 'month' ? '' : 'none';
    document.getElementById('sp-year').style.display  = p === 'year' ? '' : 'none';
    document.getElementById('sp-range').style.display = p === 'range' ? 'flex' : 'none';
}
spToggle();
</script>
