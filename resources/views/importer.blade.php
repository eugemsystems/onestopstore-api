<html>
    <head>
        <title>Import Excel File in Laravel</title>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
    </head>
    <body>
        <div class="container">
            <h2>Import Excel File in Laravel</h2>
            <form action="{{ route('importer.post') }}" method="POST" enctype="multipart/form-data">
                {{ csrf_field() }}
                <input type="file" name="file" class="form-control">
                <br>
                <button class="btn btn-success">Import User Data</button>
            </form>
        </div>
    </body>
</html>
