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
            text-align: right;
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
            width: 5%;
            /* S.no */
        }

        .bordered-table th:nth-child(2),
        .bordered-table td:nth-child(2) {
            width: 15%;
            /* Ledger */
        }

        .bordered-table th:nth-child(3),
        .bordered-table td:nth-child(3) {
            width: 15%;
            /* Account */
        }

        .bordered-table th:nth-child(4),
        .bordered-table td:nth-child(4) {
            width: 20%;
            /* Narration */
        }

        .bordered-table th:nth-child(5),
        .bordered-table td:nth-child(5) {
            width: 10%;
            /* Currency */
        }

        .bordered-table th:nth-child(6),
        .bordered-table td:nth-child(6) {
            width: 10%;
            /* FC Amount */
        }

        .bordered-table th:nth-child(7),
        .bordered-table td:nth-child(7) {
            width: 10%;
            /* Rate */
        }

        .bordered-table th:nth-child(7),
        .bordered-table td:nth-child(7) {
            width: 10%;
            /* LC Amount */
        }

        .bordered-table th:nth-child(7),
        .bordered-table td:nth-child(7) {
            width: 5%;
            /* Sign */
        }

        .footer-table {
            margin-top: 24px
        }


        .footer-table td {
            padding: 1px;
            line-height: 1px;
        }
    </style>
</head>

<body>
    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td class="business-info">
                <h2>{{ $title }}</h2>
            </td>
            <td class="report-info">
                <p>Date: {{ $date }}</p>
                <p>Ref No: JV {{ $records->voucher_no }}</p>
            </td>

        </tr>
    </table>

    <table class="bordered-table">
        <thead>
            <tr>
                <th>S.No</th>
                <th>Ledger</th>
                <th>Account</th>
                <th>Narration</th>
                <th>Currency</th>
                <th>FC Amount</th>
                <th>Rate</th>
                <th>LC Amount</th>
                <th>Sign</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records->journalVouchers as $record)
                <tr>
                    <td>{{ $loop->iteration }}</td>

                    <td>
                        @if ($record->ledger === 'party')
                            Party Ledger
                        @elseif ($record->ledger === 'walkin')
                            Walk In Customer
                        @elseif ($record->ledger === 'general')
                            General Ledger
                        @else
                            {{ $record->ledger }} {{-- Fallback: Show original value if it doesn't match any --}}
                        @endif
                    </td>
                    <td>{{ $record?->account_details?->title }}</td>
                    <td>{{ $record->narration }}</td>
                    <td>{{ $record?->currency?->currency_code }}</td>
                    <td>{{ $record->fc_amount }}</td>
                    <td>{{ number_format($record->rate, 2) }}</td>
                    <td>{{ $record->lc_amount }}</td>
                    <td>{{ $record->sign }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <table class="footer-table">
        <tr>
            <td colspan="7">
            </td>
            <td class="amount-info">
                <b>Total Debit</b>
            </td>
            <td class="amount-info text-right">
                <p>{{ number_format($records->total_debit, 2) }}</p>
            </td>
        </tr>
        <tr>
            <td colspan="7">
            </td>
            <td class="amount-info">
                <b>Total Credit</b>
            </td>
            <td class="amount-info text-right">
                <p>{{ number_format($records->total_credit, 2) }}</p>
            </td>
        </tr>
        {{-- <tr>
            <td colspan="7">
            </td>
            <td class="amount-info">
                <b>Difference</b>
            </td>
            <td class="amount-info text-right">
                <p>0</p>
            </td>
        </tr> --}}
    </table>


</body>

</html>
