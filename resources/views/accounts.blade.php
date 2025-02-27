<html>
    <head>
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </head>

    <body>
    <h1>Accounts</h1>
    @if(session()->has('acctsMsg'))
        <h2 id="acctsMsg">{{ session('acctsMsg') }}</h2>
    @endif
    <button type="button" id="assets" class="btn btn-primary">Assets</button>
    <button type="button" id="gblimo" class="btn btn-primary">GB Limo</button>
    <button type="button" id="investmentsindex" class="btn btn-warning">Update Investments</button>
    <button type="button" id="buckets" class="btn btn-success">Buckets</button>
    <button type="button" id="budget" class="btn btn-danger">Budget</button>
    <button type="button" id="mikeSpending" class="btn btn-primary mike">Mike Spending</button>
    <button type="button" id="mauraSpending" class="btn btn-primary maura">Maura Spending</button>

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
                        <td style="text-align: right;">{{ $account->cleared }}</td>
                        <td style="text-align: right;">{{ $account->register }}</td>
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

                // Assets button
                $('#assets').on('click', function(e) {
                    e.preventDefault();

                    const url = '/accounts/assets';
                    window.location.href = url;
                });

                // GB Limo button
                $('#gblimo').on('click', function(e) {
                    e.preventDefault();

                    const url = '/accounts/gblimo';
                    window.location.href = url;
                });

                // Update Investments button
                $('#investmentsindex').on('click', function(e) {
                    e.preventDefault();

                    const url = '/accounts/investmentsindex';
                    window.location.href = url;
                });

                // See Buckets and balances, etc.
                $('#buckets').on('click', function(e) {
                    e.preventDefault();

                    const url = '/accounts/buckets';
                    window.location.href = url;
                });

                // Budget page
                $('#budget').on('click', function(e) {
                    e.preventDefault();

                    const url = '/accounts/budget';
                    window.location.href = url;
                });

                // Mike Spending
                $('#mikeSpending').on('click', function(e) {
                    e.preventDefault();

                    const url = '/accounts/spending/mike';
                    window.location.href = url;
                });

                // Maura Spending
                $('#mauraSpending').on('click', function(e) {
                    e.preventDefault();

                    const url = '/accounts/spending/maura';
                    window.location.href = url;
                });
            });

        </script>

    </body>
</html>