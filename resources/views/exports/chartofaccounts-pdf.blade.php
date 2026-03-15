<!DOCTYPE html>
<html>

<head>
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: "Courier Prime", monospace;
            font-size: 12px;
            margin: 0;
            padding: 0;
            line-height: 1;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 10px 20px;
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
            line-height: 1;
        }

        .report-info {
            text-align: right;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            line-height: 1;
        }

        th,
        td {
            padding: 5px;
            text-align: left;
            line-height: 1;
        }

        th {
            background-color: #f0f0f0;
        }

        td.level-1 {
            font-weight: bold;
            padding-left: 0;
        }

        td.level-2 {
            padding-left: 10px;
        }

        td.level-3 {
            padding-left: 20px;
            color: #f60000;
            /* Red for level 3 */
        }

        td.level-4 {
            padding-left: 30px;
            color: #0000FF;
            /* Blue for level 4 */
        }

        td.level-5 {
            padding-left: 40px;
            /* color: #0000FF; */
            /* Blue for level 4 */
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
                <h2>{{ $title }}</h2>
                <p>Run date: {{ $date }}</p>
            </td>
        </tr>
    </table>

    <!-- Chart of Accounts Table -->
    <table>
        <thead style="border: 1px solid black">
            <tr>
                <th>Account</th>
                <th>Title of Account</th>
                <th>Description</th>
                <th>Type</th>
            </tr>
        </thead>
        <tbody>


            @php
                function renderAccountTree($records)
                {
                    foreach ($records as $record) {
                        // Use the level provided in the record
                        $level = $record->level ?? 1;
                        $levelClass = 'level-' . $level;
                        $fontColor = ''; // Default font color

                        if ($level == 3) {
                            $fontColor = 'color: #FF6347;'; // Red for level 3
                        } elseif ($level == 4) {
                            $fontColor = 'color: #4682B4;'; // Blue for level 4
                        }

                        echo '<tr>';
                        echo '<td class="' .
                            $levelClass .
                            '" style="' .
                            $fontColor .
                            '">' .
                            $record->account_code .
                            '</td>';
                        echo '<td class="' .
                            $levelClass .
                            '" style="' .
                            $fontColor .
                            '">' .
                            $record->account_name .
                            '</td>';
                        echo '<td>' . ($record->classification ?? 'N/A') . '</td>';
                        echo '<td>' . $record->account_type . '</td>';
                        echo '</tr>';

                        // Check if the record has children and render them recursively
                        if (!empty($record->children)) {
                            renderAccountTree($record->children);
                        }
                    }
                }
            @endphp

            @php renderAccountTree($records); @endphp
        </tbody>

    </table>
</body>

</html>
