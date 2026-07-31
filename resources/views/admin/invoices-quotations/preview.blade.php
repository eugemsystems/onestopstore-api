<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Document Preview</title>
    <style>
        /* Reset to prevent parent page CSS from affecting preview */
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: auto;
        }
    </style>
</head>
<body>
@php
$template = match($document->document_type) {
    'invoice' => 'invoice',
    'quotation' => 'quotation',
    'receipt' => 'receipt',
    'proforma' => 'proforma',
    'delivery_note' => 'delivery_note',
    default => 'invoice',
};
@endphp

@include("admin.invoices-quotations.templates.{$template}", ['document' => $document])
</body>
</html>


