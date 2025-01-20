<html>
    <head>
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script> -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </head>

    <body>
    <h1>Accounts</h1>
    <!-- <a href="{{ route('assets') }}" class="btn btn-primary">Assets</a> -->
    <button type="button" id="assets" class="btn btn-primary">Assets</button>
    <button type="button" id="gblimo" class="btn btn-primary">GB Limo</button>

        <table>
            <thead>
                <tr>
                    <th>Account</th>
                    <th>Cleared</th>
                    <th>Register</th>
                    <th>Last Balanced</th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($accounts as $account)
                    <tr>
                        <td>{{ $account->account }}</td>
                        <td>{{ $account->cleared }}</td>
                        <td>{{ $account->register }}</td>
                        <td>{{ $account->max_last_balanced }}</td>
                        <td>
                            <a href="{{ route('transactions',['accountName' => $account->account, 'beginDate' => 'null', 'endDate' => 'null', 'clearedBalance' => $account->cleared, 'registerBalance' => $account->register, 'lastBalanced' => $account->max_last_balanced]) }}" class="btn btn-primary btn-sm">Transactions</a>
                        </td>
                        <td>
                            <a href="{{ route('balances',['accountName' => $account->account]) }}" class="btn btn-primary btn-sm">Balances</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <script>

            $(document).ready(function() {

                $('#assets').on('click', function(e) {
                    e.preventDefault();


                    const url = '/accounts/assets';
                    window.location.href = url;
                });

                $('#gblimo').on('click', function(e) {
                    e.preventDefault();


                    const url = '/accounts/gblimo';
                    window.location.href = url;
                });
            });

        </script>

    </body>
</html>