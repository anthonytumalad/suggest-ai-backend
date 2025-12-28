<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Feedback Report - {{ $formTitle }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 40px;
        }

        h1 {
            text-align: center;
            color: #222831;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            color: #545454;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f7f7f7;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            color: #888;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <h1>{{ $formTitle }}</h1>
    <div class="header">
        <p><strong>Total Responses:</strong> {{ $responseCount }}</p>
        <p><strong>Exported on:</strong> {{ $exportDate }}</p>
    </div>

    <table>
        <thead>
            <tr>
                @foreach(array_keys($feedbacks[0] ?? []) as $header)
                <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($feedbacks as $row)
            <tr>
                @foreach($row as $value)
                <td>{{ $value }}</td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Exported from Feedback System • All submitted data included</p>
    </div>
</body>

</html>