<!DOCTYPE html>
<html>
    <head>
        <title>Export Product Feed</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="p-5">
        <div class="container">
            <h1 class="mb-4">Generate XML Feed by Category</h1>
            <p style="color:red;">Fix Category Seq on the db: <code>SELECT setval('categories_id_seq', (SELECT MAX(id) FROM categories));</code>
            </p>

            <div class="container">
                <h2>Import Products</h2>
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                <form action="{{ route('products.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="import_file" class="form-label">Choose Excel/CSV File</label>
                        <input type="file" class="form-control" id="import_file" name="import_file" accept=".csv" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Import</button>
                </form>
                <hr>
                <p><strong>Note:</strong> Supported formats: WooCommerce/WordPress export, or custom format. The importer auto-detects the format.</p>
            </div>
        </div>
    </body>
</html>
