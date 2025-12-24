<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            font-weight: bold;
        }
        .section-title {
            background: #000;
            color: #fff;
            padding: 5px;
            margin-top: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            padding: 4px;
            vertical-align: top;
        }
    </style>
</head>
<body>

<img src="{{ public_path('img/idplay_logo.png') }}" width="120">

<div class="header">
    FORMULIR AKTIVASI BERLANGGANAN
</div>

<p>
    <strong>Tanggal:</strong> {{ $createdDate }} <br>
    <strong>Reg No:</strong> {{ $data->PO_No }}
</p>

<div class="section-title">DATA PELANGGAN</div>

<table>
    <tr>
        <td>CID / No. Pelanggan</td>
        <td>: {{ $data->Task_ID }}</td>
    </tr>
    <tr>
        <td>Nama Pelanggan</td>
        <td>: {{ $data->Customer_Sub_Name }}</td>
    </tr>
    <tr>
        <td>Alamat</td>
        <td>: {{ $data->Customer_Sub_Address }}</td>
    </tr>
    <tr>
        <td>Telepon</td>
        <td>: {{ $data->Handphone }}</td>
    </tr>
</table>

<div class="section-title">JENIS LAYANAN</div>

<p>
    {{ $data->Sub_Product }} <br>
    <strong>Rp {{ number_format($data->Monthly_Price, 0, ',', '.') }}</strong>
</p>

<div class="section-title">INFORMASI TAGIHAN</div>

<table>
    <tr>
        <td>Biaya Layanan</td>
        <td align="right">Rp {{ number_format($data->Monthly_Price, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td>PPN (11%)</td>
        <td align="right">Rp {{ number_format($ppn, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td><strong>Total</strong></td>
        <td align="right"><strong>Rp {{ number_format($total, 0, ',', '.') }}</strong></td>
    </tr>
</table>

<br><br>

<table width="100%">
    <tr>
        <td width="50%">
            Nama: {{ $data->Customer_Sub_Name }} <br>
            Tanggal: {{ $createdDate }}
        </td>
        <td width="50%">
            Nama: {{ $sales->name ?? '-' }} <br>
            Tanggal: {{ now()->translatedFormat('dddd, DD MMMM YYYY') }}
        </td>
    </tr>
</table>

</body>
</html>
