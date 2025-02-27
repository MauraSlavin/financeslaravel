<html>
    <head>
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </head>

    <body>
    <h1>{{$who}} Spending</h1>
    
        <table>
            <thead>
                <tr>
                    <th>Trans Date</th>
                    <th>Year</th>
                    <th>Clear Date</th>
                    <th>Account</th>
                    <th>To/From</th>
                    <th>Amount</th>
                    <th>Tot Amount (split)</th>
                    <th>Notes</th>
                    <th>Category</th>
                    <th>Left in Budget</th>
                </tr>
            </thead>
            <tbody>
                @foreach($spendingTransactions as $transaction)
                    @if($transaction->account == "budget")
                    <tr style="background-color: palegoldenrod;">
                    @else
                    <tr>
                    @endif
                        <td>{{ $transaction->trans_date }}</td>
                        <td style="text-align: right;">{{ $transaction->year }}</td>
                        <td style="text-align: right;">{{ $transaction->clear_date }}</td>
                        <td>{{ $transaction->account }}</td>
                        <td>{{ $transaction->toFrom }}</td>
                        <td>{{ $transaction->amount }}</td>
                        <td>{{ $transaction->total_amt }}</td>
                        <td>{{ $transaction->notes }}</td>
                        <td>{{ $transaction->category }}</td>
                        <td>{{ $transaction->remainingSpendingBudget }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <script>

            $(document).ready(function() {

            });
 
        </script>

    </body>
</html>