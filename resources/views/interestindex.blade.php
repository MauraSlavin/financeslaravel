<html>
    <head>
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Favicon -->
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/favicon/apple-touch-icon.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/favicon/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/img/favicon/favicon-16x16.png') }}">
        <link rel="shortcut icon" href="{{ asset('assets/img/favicon/favicon.ico') }}">
        <link rel="manifest" href="{{ asset('assets/img/favicon/site.webmanifest') }}">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </head>

    <body>
    <h1>Add Interest Credited to Interest-Bearing Accounts</h1>
    <h2 id="note" style="color: red;" hidden>NOTE: Done for last month</h2>

    <table>
        <thead>
            <tr>
                <th style="width: 130px;">Account</th>
                <th style="width: 80px;">Date last credited</th>
                <th style="width: 80px;">Last interest earned</th>
                <th style="width: 140px;">New Interest Earned</th>
                <th style="width: 160px;">Date Interest Posted</th>
                <th hidden></th>        <!-- bucket - for DiscSavings -->
                <th hidden></th>        <!-- method - keep same method -->
            </tr>
        </thead>
        <tbody>
            @foreach($recentInterestCredited as $interestCredited)
                <tr>
                    <td class="intAcct" style="width: 130px;">{{ $interestCredited->account }}</td>
                    <td style="width: 80px;">{{ $interestCredited->trans_date }}</td>
                    <td style="width: 80px;">{{ $interestCredited->amount }}</td>
                    <td>
                        <input class="newInterest" type="number" step="0.01" min="0" max="9999999.99" required>
                    </td>
                    <td>
                        <input class="intDate" type="date" required />
                    </td>
                    <td hidden class="bucket">{{ $interestCredited->bucket }}</td>
                    <td hidden class="method">{{ $interestCredited->method }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <button type="button" id="insertNewInterestTrans" class="btn btn-success">Insert New Interest Transactions</button>

    <script>

            
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(document).ready(function() {

            // set default Date Interest Posted to last date of previous month,
            //  which is most likely the date needed.

            // get last day of previous month
            const currentDate = new Date();
            const firstDayOfCurrentMonth = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
            const defaultNewInterestDate = new Date(firstDayOfCurrentMonth.getFullYear(), firstDayOfCurrentMonth.getMonth(), 0);

            // format and put on page
            const year = defaultNewInterestDate.getFullYear();
            const month = String(defaultNewInterestDate.getMonth() + 1).padStart(2, '0');
            const day = String(defaultNewInterestDate.getDate()).padStart(2, '0');
            $(".intDate").val(`${year}-${month}-${day}`);

            // if Date Interest Posted for first account = date last credited, show NOTE (Interest done)
            const firstCreditedDate = $('tbody').children(":first-child").children(":first-child").next().html();
            const firstInterestDate = $('tbody').children(":first-child").find(".intDate").val();
            if(firstCreditedDate == firstInterestDate) $("#note").prop('hidden', false);

            // when Save New Balances clicked...
            $("#insertNewInterestTrans").on('click', function(e) {
                e.preventDefault();

                // init var to pass in ajax call
                var newInterestTransactions = [];

                // build newInterestTransactions for each table record <tr> that has a newInterest value
                $('tbody > tr').each(function() {
                    // get the newInterest value
                    const newInterest = $(this).find(".newInterest").val();

                    // if it exists, capture data needed to pass to ajax
                    if(newInterest) {
                        const intAcct = $(this).find(".intAcct").text();
                        const intDate = $(this).find(".intDate").val();
                        const newInterest = $(this).find(".newInterest").val();
                        const bucket = $(this).find(".bucket").html();
                        const method = $(this).find(".method").html();
                        
                        var newInterestInfo = {
                            "trans_date" : $(this).find(".intDate").val(),
                            "account"    : intAcct,
                            "amount"     : newInterest,
                            "bucket"     : bucket,
                            "method"     : method
                        }

                        // add new data to array
                        newInterestTransactions.push(newInterestInfo);

                    }
                }); // end of each tr element

                // if there is data to process, do ajax call to write new balance records to the db
                if(newInterestTransactions.length > 0) {
                    $.ajax({
                        url: '/transactions/insertInterestTransactions',
                        method: 'PUT',
                        contentType: 'application/json',
                        processData: false,
                        data: JSON.stringify({
                            _token: '{{ csrf_token() }}',
                            newInterestTransactions: newInterestTransactions
                        }),
                        success: function(response) {
                            // let user know balances were updated
                            alert("Interest transactions have been inserted.");

                            // reload page with new balances
                            location.reload();
                        }
                    })
                }

            });

        });

    </script>

    </body>
</html>