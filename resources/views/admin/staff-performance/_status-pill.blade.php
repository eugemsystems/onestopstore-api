@php
    $k = strtolower($name);
    [$bg, $fg] = match (true) {
        str_contains($k, 'cancel')                                   => ['#fef2f2', '#dc2626'],
        str_contains($k, 'refund')                                   => ['#faf5ff', '#8b5cf6'],
        str_contains($k, 'pending')                                  => ['#fff7ed', '#f59e0b'],
        str_contains($k, 'process')                                  => ['#eff6ff', '#2563eb'],
        str_contains($k, 'out for') || str_contains($k, 'out-for')   => ['#fff1e6', '#ea580c'],
        str_contains($k, 'ready')                                    => ['#f5f3ff', '#7c3aed'],
        str_contains($k, 'transit') || str_contains($k, 'ship')      => ['#ecfeff', '#0891b2'],
        str_contains($k, 'depo') || str_contains($k, 'arrived') || str_contains($k, 'branch') => ['#eef2ff', '#4f46e5'],
        str_contains($k, 'collect')                                  => ['#f0fdfa', '#0d9488'],
        str_contains($k, 'deliver') || str_contains($k, 'complet')   => ['#f0fdf4', '#16a34a'],
        default                                                      => ['#f1f5f9', '#64748b'],
    };
@endphp
<span class="pill" style="background:{{ $bg }};color:{{ $fg }};">{{ $name }} <b>{{ $count }}</b></span>
