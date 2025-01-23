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
                <th style="width: 140px;">New Balance</th>
                <th style="width: 160px;">New Balance Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($investments as $investment)
                <tr>
                    <td class="invAcct" style="width: 130px;">{{ $investment->account }}</td>
                    <td style="width: 140px;">{{ $investment->amount }}</td>
                    <td style="width: 80px;">{{ $investment->stmtDate }}</td>
                    <td style="width: 300px;">{{ $investment->lastBalanced }}</td>
                    <td>
                        <input class="newBalance" type="number" step="0.01" min="0" max="9999999.99" required>
                    </td>
                    <td>
                        <input class="newDate" type="date" required>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <button type="button" id="saveNewBalances" class="btn btn-success">Save New Balances</button>

    <script>

            
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(document).ready(function() {
            // left off here  --  Need default New Balance Date and...
            //    read and save new dates.

            // set default New Balance Date to last date of previous month

            // get last day of previous month
            const currentDate = new Date();
            const firstDayOfCurrentMonth = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
            const defaultNewBalanceDate = new Date(firstDayOfCurrentMonth.getFullYear(), firstDayOfCurrentMonth.getMonth(), 0);

            // format and put on page
            const year = defaultNewBalanceDate.getFullYear();
            const month = String(defaultNewBalanceDate.getMonth() + 1).padStart(2, '0');
            const day = String(defaultNewBalanceDate.getDate()).padStart(2, '0');
            $(".newDate").val(`${year}-${month}-${day}`);

            $("#saveNewBalances").on('click', function(e) {
                e.preventDefault();

                var newBalancesInfo = [];
                // const today = new Date();
                // const formattedDate = `${today.getFullYear()}-${(today.getMonth() + 1).toString().padStart(2, '0')}-${today.getDate().toString().padStart(2, '0')}`;
                // console.log(formattedDate);

                // left off here  -  based on ajax call in transactions.blade.php line 582+
                $('tbody > tr').each(function() {
                    console.log(" --- ");
                    const newBalance = $(this).find(".newBalance").val();
                    if(newBalance) {
                        const invAcct = $(this).find(".invAcct").text();
                        const newDate = $(this).find(".newDate").val();
                        const newAmt = $(this).find(".newBalance").val();
                        console.log("invAcct: ", invAcct);
                        console.log("newDate: ", newDate);
                        console.log("newBalance: ", newBalance);
                        
                        var newBalanceInfo = {
                            "trans_date" : $(this).find(".newDate").val(),
                            "account"    : invAcct,
                            "amount"     : newBalance
                        }

                        newBalancesInfo.push(newBalanceInfo);

                    }
                });

                console.log("----------------");
                newBalancesInfo.forEach(newInfo => console.log(newInfo));


            });

        });

    </script>

    </body>
</html>