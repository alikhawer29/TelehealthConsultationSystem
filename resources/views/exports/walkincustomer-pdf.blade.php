<!DOCTYPE html>
<html>

<head>
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        th {
            background-color: #f4f4f4;
        }

        .header-table {
            width: 100%;
            margin-bottom: 10px;
            padding-bottom: 5px;
        }

        .header-table td {
            vertical-align: top;
        }

        .business-info {
            text-align: left;
            /* line-height: 1.5; */
        }

        .report-info {
            text-align: right;
        }
    </style>
</head>

<body>
    <table class="header-table">
        <tr>
            <td class="business-info">
                <h2>{{ $business_name }}</h2>
                <p>{{ $address }}</p>
                <p>{{ $phone }}</p>
            </td>
            <td class="report-info">
                <h2>{{ $title }}</h2>
                <p>Run date: {{ $date }}</p>
            </td>
        </tr>
    </table>
    <table>
        <thead>
            <tr>
                <th>Customer Name</th>
                <th>Mobile Number</th>
                <th>Tel.</th>
                <th>Nationality</th>
                <th>ID Type</th>
                <th>ID Number</th>
                <th>ID Expiry Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $record)
                <tr>
                    <td>{{ $record->customer_name }}</td>
                    <td>{{ $record->mobile_country_code . ' ' . $record->mobile_number }}</td>
                    <td>{{ $record->telephone_country_code . ' ' . $record->telephone_number }}</td>
                    <td>{{ $record?->nationalities?->name ?? 'N/A' }}</td>
                    <td>{{ $record->id_types != null ? $record->id_types?->description : 'N/A' }}</td>
                    <td>{{ $record->id_number }}</td>
                    <td>{{ \Carbon\Carbon::parse($record->expiry_date)->format('d/m/Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
