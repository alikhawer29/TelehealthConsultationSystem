<!DOCTYPE html>
<html>

<head>
    <title>{{ $title }}</title>
    <style>
        @page {
            size: landscape;
            /* Ensures the page is printed in landscape orientation */
            margin: 20mm;
        }

        body {
            font-family: "Courier Prime", monospace;
            font-size: 12px;
            margin: 0;
            padding: 0;
            line-height: normal;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            /* Ensures fixed column widths */
        }

        th,
        td {
            padding: 5px;
            word-wrap: break-word;
            /* Allows long text to wrap */
            text-align: left;
        }

        .header-table {
            width: 100%;
            margin-bottom: 5px;
            padding-bottom: 5px;
        }

        .header-table td {
            vertical-align: top;
        }

        .business-info {
            text-align: left;
        }

        .report-info {
            text-align: right;
        }

        /* Add border to the second table only */
        .bordered-table {
            border: 1px solid #ddd;
        }

        .bordered-table th,
        .bordered-table td {
            border: 1px solid #ddd;
        }

        .thead tr th {
            text-align: left;
        }

        /* Set specific column widths */
        .bordered-table th:nth-child(1),
        .bordered-table td:nth-child(1) {
            width: 15%;
            /* Account */
        }

        .bordered-table th:nth-child(2),
        .bordered-table td:nth-child(2) {
            width: 20%;
            /* Company */
        }

        .bordered-table th:nth-child(3),
        .bordered-table td:nth-child(3) {
            width: 15%;
            /* Classification */
        }

        .bordered-table th:nth-child(4),
        .bordered-table td:nth-child(4) {
            width: 10%;
            /* Tel. */
        }

        .bordered-table th:nth-child(5),
        .bordered-table td:nth-child(5) {
            width: 10%;
            /* Country */
        }

        .bordered-table th:nth-child(6),
        .bordered-table td:nth-child(6) {
            width: 10%;
            /* Mobile No. */
        }

        .bordered-table th:nth-child(7),
        .bordered-table td:nth-child(7) {
            width: 10%;
            /* Status */
        }
    </style>
</head>

<body>
    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td class="business-info">
                <h2>{{ $business_name }}</h2>
                <p>{{ $address . ', ' . $country }}</p>
                <p>{{ $phone }}</p>
            </td>
            <td class="report-info">
                <h2>{{ $title }}</h2>
                <p>Run date: {{ $date }}</p>
            </td>
        </tr>
    </table>

    <table class="bordered-table">
        <thead>
            <tr>
                <th>Grp</th>
                <th>Type</th>
                <th>Description</th>
                <th>Number</th>
                <th>Issue Date</th>
                <th>Due Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $record)
                <tr>
                    <td>{{ $record?->group?->type }}</td>
                    <td>{{ $record?->classification?->description }}</td>
                    <td>{{ $record->description }}</td>
                    <td>{{ $record->number }}</td>
                    <td>{{ $record->issue_date }}</td>
                    {{-- <td>{{ $record?->classifications?->classification }}</td> --}}
                    {{-- <td>{{ $record->country_code . ' ' . $record->telephone_number }}</td> --}}
                    {{-- <td>{{ $record?->nationalities?->name }}</td> --}}
                    {{-- <td>{{ $record->mobile_country_code . ' ' . $record->mobile_number }}</td> --}}
                    <td>{{ $record->due_date }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>
