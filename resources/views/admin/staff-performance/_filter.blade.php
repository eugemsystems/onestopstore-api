{{-- Month / Year period filter. $action = form target URL --}}
<form method="GET" action="{{ $action }}" class="sp-filter">
    <span class="sp-lbl">View by:</span>
    <label class="sp-radio"><input type="radio" name="period" value="month" {{ $period === 'month' ? 'checked' : '' }} onchange="spToggle()"> Month</label>
    <label class="sp-radio"><input type="radio" name="period" value="year" {{ $period === 'year' ? 'checked' : '' }} onchange="spToggle()"> Year</label>

    <input type="month" name="month" id="sp-month" value="{{ $month }}" max="{{ now()->format('Y-m') }}">
    <select name="year" id="sp-year">
        @for($y = now()->year; $y >= max(2020, now()->year - 6); $y--)
            <option value="{{ $y }}" {{ (int) $year === $y ? 'selected' : '' }}>{{ $y }}</option>
        @endfor
    </select>
    <button type="submit" class="sp-btn">Apply</button>
    <span class="sp-current">Showing: <strong>{{ $label }}</strong></span>
</form>
<script>
function spToggle() {
    var yr = document.querySelector('.sp-filter input[name=period]:checked').value === 'year';
    document.getElementById('sp-month').style.display = yr ? 'none' : '';
    document.getElementById('sp-year').style.display  = yr ? '' : 'none';
}
spToggle();
</script>
