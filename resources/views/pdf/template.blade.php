<!DOCTYPE html>
<html>

<head>
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: "Arial", sans-serif;
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
            font-weight: bold;
        }

        .header-section {
            text-align: center;
            margin-bottom: 20px;
        }

        .footer-section {
            text-align: center;
            margin-top: 10px;
            font-size: 10px;
        }
    </style>
</head>

<body>
    <!-- Header Section -->
    <div class="header-section">
        <h1>{{ $business_name }}</h1>
        <p>{{ $address }}</p>
        <p>{{ $phone }}</p>
    </div>

    <!-- Content Section -->
    <table>
        <thead>
            <tr>
                <th>Account</th>
                <th>Title of Account</th>
                <th>Classification</th>
                <th>Type</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $record)
                <tr>
                    <td>{{ $record['account_code'] }}</td>
                    <td>{{ $record['account_name'] }}</td>
                    <td>{{ $record['classification'] }}</td>
                    <td>{{ $record['account_type'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
