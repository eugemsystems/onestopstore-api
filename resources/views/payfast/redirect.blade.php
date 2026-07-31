<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Redirecting to PayFast</title>
</head>
<body>
<p>Please wait, redirecting to PayFast...</p>
<form id="payfastForm" action="{{ $url }}" method="post">
    @foreach($data as $key => $value)
        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
    @endforeach
</form>
<script type="text/javascript">
    document.getElementById('payfastForm').submit();
</script>
</body>
</html>
