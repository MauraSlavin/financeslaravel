<html>
    <head>
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </head>

    <body>
    <h1>Investments</h1>

    <table>
        <thead>
            <tr>
                <th style="width: 130px;">Account</th>
                <th style="width: 140px;">Balance</th>
                <th style="width: 80px;">Stmt Date</th>
                <th style="width: 300px;">Last Balanced</th>
                <th></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($investments as $investment)
                <tr>
                    <td style="width: 130px;">{{ $investment->account }}</td>
                    <td style="width: 140px;">{{ $investment->amount }}</td>
                    <td style="width: 80px;">{{ $investment->stmtDate }}</td>
                    <td style="width: 300px;">{{ $investment->lastBalanced }}</td>
                    <td>
                        "Button?"
                    </td>
                    <td>
                        "Button?"
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <script>

        $(document).ready(function() {
            // left off here  --  Need inputs & buttons to add new values


        });

    </script>

    </body>
</html>