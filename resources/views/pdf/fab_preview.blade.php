{{-- resources/views/pdf/fab_preview.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview FAB</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .content {
            padding: 20px;
        }
        .signature {
            margin-top: 20px;
        }
        .signature img {
            width: 100px;
        }
    </style>
</head>
<body>
    <div class="content">
        <h1>Preview FAB</h1>

        <p><strong>Date:</strong> {{ \Carbon\Carbon::now()->format('d-m-Y') }}</p>

        <p><strong>Customer Name:</strong> {{ $fields['name'][0] }}</p>
        <p><strong>KTP:</strong> {{ $fields['ktp_id'][0] }}</p>
        <p><strong>Address:</strong> {{ $fields['address'][0] }}</p>
        <p><strong>Phone:</strong> {{ $fields['phone'][0] }}</p>
        <p><strong>Email:</strong> {{ $fields['email'][0] }}</p>

        <div class="signature">
            <p><strong>Sales Signature:</strong></p>
            @if(isset($session['payload']['user_id']))
                <img src="{{ $base_url . $session['payload']['user_id'] }}" alt="Signature">
            @endif
        </div>

        <div class="signature">
            <p><strong>Customer Signature:</strong> {{ $fields['name'][0] }}</p>
        </div>

    </div>
</body>
</html>
