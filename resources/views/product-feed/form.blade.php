<!DOCTYPE html>
<html>
<head>
    <title>Export Product Feed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5">
<div class="container">
    <h1 class="mb-4">Generate XML Feed by Category</h1>

    <form action="{{ route('product-feed.export') }}" method="GET" class="row gx-3 gy-2 align-items-center">
        <div class="col-auto">
            <label class="" for="category">Category</label>
            <select name="category" id="category" class="form-select" required>
                <option value="">-- Pick a category --</option>
                @foreach($categories as $cat)
                    @include('product-feed.partials.category-option', [
                      'category' => $cat,
                      'prefix'   => '',
                    ])
                @endforeach
            </select>
        </div>

        <div class="col-auto">
            <label class="" for="currency">Currency</label>
            <select name="currency" class="form-select" required>
                <option value="">-- Pick a currency --</option>
                @foreach($currencies as $c)
                    <option value="{{ $c['code'] }}">
                        {{ $c['code'] }} ({{ $c['symbol'] }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Export XML</button>
        </div>
    </form>
</div>
</body>
</html>
