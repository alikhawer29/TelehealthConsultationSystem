<!DOCTYPE html>
<html>

<head>
    <title>{{ $title }}</title>
    <style>
        @page {
            size: landscape;
            margin: 15mm;
        }

        body {
            font-family: "Courier Prime", monospace;
            font-size: 12px;
            margin: 0;
            padding: 0;
            line-height: 1.2;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            padding: 5px;
            word-wrap: break-word;
            text-align: left;
        }

        .header-table {
            width: 100%;
            margin-bottom: 10px;
        }

        .header-table td {
            vertical-align: top;
        }

        .business-info {
            text-align: center;
        }

        .report-info {
            text-align: center;
        }

        .bordered-table {
            border: 1px solid #000;
        }

        .bordered-table th,
        .bordered-table td {
            border: 1px solid #000;
            padding: 5px;
        }

        .thead tr th {
            text-align: center;
            background-color: #f5f5f5;
        }

        .details-container {
            margin-bottom: 15px;
            padding: 10px;
        }

        .details-container div {
            margin-bottom: 5px;
        }

        .voucher-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
        }

        .voucher-section {
            margin-bottom: 15px;
        }

        .voucher-section-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .voucher-details {
            margin-left: 20px;
            padding: 10px
        }

        .voucher-inner-details {
            padding: 10px
        }


        .voucher-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 10px;
        }
    </style>
</head>

<body>
    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td class="business-info">
                <h2>{{ $business_name }}</h2>
                <p>{{ $address }}</p>
                <p>{{ $phone }}</p>
            </td>
            <td class="report-info">
                <p>Date: {{ $date }}</p>
                <p>Time: {{ $time }}</p>
                <p>Ref No: PV {{ $records->voucher_no }}</p>
            </td>
        </tr>
    </table>

    <!-- Voucher Title -->
    <div class="voucher-title">Internal Payment Voucher</div>

    <!-- Details Section -->
    <div class="voucher-section">
        <div class="voucher-section-title">Transaction Details</div>
        <div class="voucher-details">
            <div class="voucher-inner-details"><b>Credit Account:</b>
                @if ($records->internalPaymentVouchers->ledger === 'party')
                    Party Ledger
                @elseif ($records->internalPaymentVouchers->ledger === 'walkin')
                    Walk In Customer
                @elseif ($records->internalPaymentVouchers->ledger === 'general')
                    General Ledger
                @else
                    {{ $records->internalPaymentVouchers->ledger }}
                @endif
                - {{ $records->internalPaymentVouchers->account_details->title }}
            </div>

            <div class="voucher-inner-details"><b>Mode:</b> {{ $records->internalPaymentVouchers->mode }} -
                {{ $records->internalPaymentVouchers->mode_account->account_name }}</div>

            @if ($records->internalPaymentVouchers->mode !== 'Cash')
                <div class="voucher-inner-details"><b>Party's Bank:</b>
                    {{ $records->internalPaymentVouchers->party_bank }}
                </div>
                <div class="voucher-inner-details"><b>Cheque Number:</b>
                    {{ $records->internalPaymentVouchers->cheque_number }}
                </div>
                <div class="voucher-inner-details"><b>Due Date:</b> {{ $records->internalPaymentVouchers->due_date }}
                </div>
            @endif

            <div class="voucher-inner-details"><b>Narration:</b> {{ $records->internalPaymentVouchers->narration }}
            </div>
            <div class="voucher-inner-details"><b>Amount:</b>
                {{ $records->internalPaymentVouchers->currency->currency_code }} -
                {{ $records->internalPaymentVouchers->amount }}</div>

            @if (
                $records->internalPaymentVouchers->special_commission &&
                    $records->internalPaymentVouchers->special_commission != []
            )
                <div class="voucher-inner-details"><b>Commission Type:</b>
                    {{ $records->internalPaymentVouchers->commission_type }}</div>
                <div class="voucher-inner-details"><b>Commission:</b>
                    {{ $records->internalPaymentVouchers->commission }}</div>
            @endif

            <div class="voucher-inner-details"><b>VAT Terms:</b> {{ $records->internalPaymentVouchers->vat_terms }}
            </div>
            <div class="voucher-inner-details"><b>VAT Amount:</b> {{ $records->internalPaymentVouchers->vat_amount }}
            </div>
            <div class="voucher-inner-details"><b>Net Total:</b> {{ $records->internalPaymentVouchers->net_total }}
            </div>
            <div class="voucher-inner-details"><b>Comment:</b> {{ $records->internalPaymentVouchers->comment }}</div>
        </div>
    </div>

    <!-- Footer Section -->
    <div class="voucher-footer">
        <p>Generated on: {{ $date }} at {{ $time }}</p>
    </div>
</body>

</html>
