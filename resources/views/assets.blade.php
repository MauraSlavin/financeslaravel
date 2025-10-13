<html>
    <head>
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script> -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </head>

    <body>
    <h1 style="color: green;">Assets <small>({{ env('APP_ENV', 'unknown') }})</small></h1>

        <table>
            <thead>
                <tr>
                    <th style="width: 80px;">Account</th>
                    <th style="width: 100px;">Amount</th>
                    <th style="width: 200px;">Last Balanced</th>
                    <th style="width: 50px;">Invest or Trans</th>
                </tr>
            </thead>
          
            <tbody>
                @foreach($accounts as $account)
                    @if($account->account != null)
                        @if($account->account == 'Total')
                            <tr style="background-color: blue; color: lightblue;">
                        @else
                            <tr>
                        @endif
                            <td>{{ $account->account }}</td>
                            <td class="text-end">{{ $account->amount }}</td>
                            <td class="text-center">{{ $account->max_last_balanced }}</td>
                            <td>{{ $account->type }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>

        </table>
    </body>
</html>