<html>
    <head>
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </head>

    <body>
    <h1>Buckets</h1>
    <h5>{{ $totalBucketBalance }} (Bucket total)</h5>
    <h5>{{ $transactionsBucketBalance }} (Transaction total)</h5>
    @if( $totalBucketBalance != $transactionsBucketBalance )
        <h6 style="color: red;">These numbers should match!<br>Could non-Disc Svgs transactions have a bucket?<br>Or a Disc Svgs transaction be missing a Bucket?</h6>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width: 100px;">Bucket</th>
                <th style="width: 100px;">Goal Amt</th>
                <th style="width: 100px;">Balance</th>
                <th style="width: 100px;">NEEDS</th>
                <th style="width: 150px;">Goal Date</th>
                <th style="width: 450px;">notes</th>
            </tr>
        </thead>
        <tbody>

            <!-- past goal dates -->
            <tr style="background-color: blue; color: white;">
                <td>Past Goal Dates</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            @foreach($pastGoalDateBuckets as $bucket)
                @if($bucket->NEEDED > 0)
                <tr style="background-color: yellow;">
                @else
                <tr>
                @endif
                    <td style="width: 100px;">{{ $bucket->bucket }}</td>
                    <td class="goalAmt" style="width: 100px; text-align: right;">{{ $bucket->goalAmount }}</td>
                    <td class="balance" style="width: 100px; text-align: right;">{{ $bucket->balance }}</td>
                    <td style="width: 100px; text-align: right;">{{ $bucket->NEEDED }}</td>
                    <td style="width: 150px; text-align: center;">{{ $bucket->goalDate }}</td>
                    <td style="width: 450px;">{{ $bucket->notes }}</td>
                </tr>
            @endforeach


            <!-- future goal dates -->
            <tr style="background-color: blue; color: white;">
                <td>Future Goal Dates</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            @foreach($futureGoalDateBuckets as $bucket)
                @if($bucket->NEEDED > 0)
                <tr style="background-color: yellow;">
                @else
                <tr>
                @endif
                    <td style="width: 100px;">{{ $bucket->bucket }}</td>
                    <td class="goalAmt"style="width: 100px; text-align: right;">{{ $bucket->goalAmount }}</td>
                    <td class="balance" style="width: 100px; text-align: right;">{{ $bucket->balance }}</td>
                    <td style="width: 100px; text-align: right;">{{ $bucket->NEEDED }}</td>
                    <td style="width: 150px; text-align: center;">{{ $bucket->goalDate }}</td>
                    <td style="width: 450px;">{{ $bucket->notes }}</td>
                </tr>
            @endforeach


            <!-- no goal dates -->
            <tr style="background-color: blue; color: white;">
                <td>No Goal Dates</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            @foreach($noGoalDateBuckets as $bucket)
                @if($bucket->NEEDED > 0)
                <tr style="background-color: yellow;">
                @else
                <tr>
                @endif
                    <td style="width: 100px;">{{ $bucket->bucket }}</td>
                    <td class="goalAmt" style="width: 100px; text-align: right;">{{ $bucket->goalAmount }}</td>
                    <td class="balance" style="width: 100px; text-align: right;">{{ $bucket->balance }}</td>
                    <td style="width: 100px; text-align: right;">{{ $bucket->NEEDED }}</td>
                    <td style="width: 150px; text-align: center;">{{ $bucket->goalDate }}</td>
                    <td style="width: 450px;">{{ $bucket->notes }}</td>
                </tr>
            @endforeach

            <!-- total -->
            <tr style="background-color: blue; color: white;">
                <td>Goal Totals</td>
                <td id="goalTotal" style="text-align: right;"></td>
                <td id="goalBalance" style="text-align: right;"></td>
                <td id="needOrExtra"></td>
                <td id="diff"></td>
                <td></td>
            </tr>

        </tbody>
    </table>

    <script>

            
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(document).ready(function() {
            var goalTotal = 0;          // total of goals needed
            var goalBalance = 0;        // total of goals saved
            var rows = $("tbody tr");   // table records to loop over

            // loop over each table record
            rows.each(function(index, row) {
                var rowData = $(row);

                // if there is a goal amount, add to goal totals
                if(rowData.find(".goalAmt").text() != '') {
                    var goalAmt = parseFloat(rowData.find(".goalAmt").text())
                    goalTotal += goalAmt;

                    // only increment the goal balance if there is a non-zero goal amt
                    if(goalAmt > 0) {
                        goalBalance += parseFloat(rowData.find(".balance").text());
                    }
                }
            });

            // put totals in last row:
            // What's needed
            $("#goalTotal").text(goalTotal);
            // What' been saved
            $("#goalBalance").text(goalBalance);
            // "Need" or "Extra"
            if(goalBalance > goalTotal) {
                $("#needOrExtra").text("Extra: ");
            } else {
                $("#needOrExtra").text("Need: ").css("background-color", "yellow").css("color", "black");
                $("#diff").text("Need: ").css("background-color", "yellow").css("color", "black");
            }
            // How much needed or extra
            $("#diff").text(goalBalance - goalTotal);
        });

    </script>

    </body>
</html>