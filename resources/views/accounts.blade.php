<html>
    <head>
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </head>

    <body>
    <h1 style="color: green;">Accounts <small>({{ env('APP_ENV', 'unknown') }})</small></h1>
    @if(session()->has('acctsMsg'))
        <h2 id="acctsMsg">{{ session('acctsMsg') }}</h2>
    @endif
    @if( $acctsMsg != null )
        <h2 id="acctsMsg">{{ $acctsMsg }}</h2>
    @endif

    <div style="margin-bottom: 10px;">
        <!-- Assets button -->
        <a href="{{ route('assets') }}" class="image-button-href">
            <img src="{{ asset('images/buttons/Assets.png') }}" alt="Clickable Assets Image" class="image-button">
        </a>
        
        <!-- GB Limo button -->
        <a href="{{ route('gblimo') }}" class="image-button-href">
            <img src="{{ asset('images/buttons/GBLimo.png') }}" alt="Clickable Limo Image" class="image-button">
        </a>
        
        <!-- Trips button -->
        <a href="{{ route('trips') }}" class="image-button-href">
            <img src="{{ asset('images/buttons/Trips.png') }}" alt="Clickable Trips Image" class="image-button">
        </a>
        
        <!-- Buckets button -->
        <a href="{{ route('buckets') }}" class="image-button-href">
            <img src="{{ asset('images/buttons/Buckets.png') }}" alt="Clickable Buckets Image" class="image-button">
        </a>
        
        <!-- mikeSpending button -->
        <a href="{{ route('spendingMike') }}" class="image-button-href">
            <img src="{{ asset('images/buttons/Mike.png') }}" alt="Clickable Mike Image" class="image-button">
        </a>

        <!-- mauraSpending button -->
        <a href="{{ route('spendingMaura') }}" class="image-button-href">
            <img src="{{ asset('images/buttons/Maura.png') }}" alt="Clickable Maura Image" class="image-button">
        </a>
        
        <!-- investments button -->
        <a href="{{ route('investmentsindex') }}" class="image-button-href">
            <img src="{{ asset('images/buttons/Investments.png') }}" alt="Clickable Investments Image" class="image-button">
        </a>
        
        <!-- budget button -->
        <a href="{{ route('budget') }}" class="image-button-href">
            <img src="{{ asset('images/buttons/Budget.png') }}" alt="Clickable Budget Image" class="image-button">
        </a>
        
        <!-- Monthly Transactions button -->
        <a href="{{ route('monthly') }}" class="image-button-href">
            <img src="{{ asset('images/buttons/Monthly.png') }}" alt="Clickable Calendar Image" class="image-button">
        </a>

        <!-- Retirement button -->
        <a href="{{ route('retirement') }}" class="image-button-href">
            <img src="{{ asset('images/buttons/Retirement.png') }}" alt="Clickable Retirement Image" class="image-button">
        </a>

        @if(env('APP_ENV', 'unknown') == 'remote')
            <!-- sync database changes between local and remote db button -->
            <a href="{{ route('syncdbchanges') }}" class="image-button-href">
                <img src="{{ asset('images/buttons/sync.png') }}" alt="Clickable sync Image" class="image-button">
            </a>
            
        @endif
    </div>
    <p class="uploadnote" style=font="small">NOTE: Can only do database uploads/downloads using remote .env file.</p>
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

                // If local database, highlight it
                if( $("h1").text() == 'Accounts (local)') {
                    $("h1").addClass('blinking');
                } else {
                    $(".uploadnote").hide();
                }
                // Assets button
                // $('#assets').on('click', function(e) {
                //     e.preventDefault();

                //     const url = '/accounts/assets';
                //     window.location.href = url;
                // });

                // GB Limo button
                // $('#gblimo').on('click', function(e) {
                //     e.preventDefault();

                //     const url = '/accounts/gblimo';
                //     window.location.href = url;
                // });

                // Update Investments button
                // $('#investmentsindex').on('click', function(e) {
                //     e.preventDefault();

                //     const url = '/accounts/investmentsindex';
                //     window.location.href = url;
                // });

                // See Buckets and balances, etc.
                // $('#buckets').on('click', function(e) {
                //     e.preventDefault();

                //     const url = '/accounts/buckets';
                //     window.location.href = url;
                // });

                // Budget page
                // $('#budget').on('click', function(e) {
                //     e.preventDefault();

                //     const url = '/accounts/budget';
                //     window.location.href = url;
                // });

                // Mike Spending
                // $('#mikeSpending').on('click', function(e) {
                //     e.preventDefault();

                //     const url = '/accounts/spending/mike';
                //     window.location.href = url;
                // });

                // Maura Spending
                // $('#mauraSpending').on('click', function(e) {
                //     e.preventDefault();

                //     const url = '/accounts/spending/maura';
                //     window.location.href = url;
                // });

                // Trips
                // $('#trips').on('click', function(e) {
                //     e.preventDefault();

                //     const url = '/accounts/trips';
                //     window.location.href = url;
                // });

                // Monthly transactions page
                // $('#monthly').on('click', function(e) {
                //     e.preventDefault();

                //     const url = '/accounts/monthly';
                //     window.location.href = url;
                // });

            });

        </script>

    </body>
</html>