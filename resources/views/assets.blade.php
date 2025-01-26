<html>
    <head>
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script> -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </head>

    <body>
    <h1>Assets</h1>

        <table>
            <thead>
                <tr>
                    <th>Account</th>
                    <th>Amount</th>
                    <th>Last Balanced</th>
                    <th>Invest or Trans</th>
                </tr>
            </thead>
          
            <tbody>
                @foreach($accounts as $account)
                    @if($account->account != null)
                        <tr>
                            <td>{{ $account->account }}</td>
                            <td style="text-align: right;">{{ $account->amount }}</td>
                            <td>{{ $account->max_last_balanced }}</td>
                            <td>{{ $account->type }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>

        </table>
    </body>
</html>