<html>
    <head>
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script> -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <meta name="csrf-token" content="{{ csrf_token() }}">
    </head>

    <body>

        <!-- include common functions -->
        <script src="{{ asset('js/commonFunctions.js') }}"></script>


        <!-- headers -->
        <h1>Monthly transactions</h1> 

        <p id="errorMsg"></p>
        <!-- table -->

        <form action="{{ route('writeMonthlyTransactions') }}" method="GET">
        
            <!-- button w/ explanation -->
            <span style="margin-left: 10px;">Click <b>RECORD</b> to record the <b>checked</b> transactions in the transactions table.</span><br>
            <button class="btn btn-success" type="submit" style="margin-left: 10px; margin-bottom: 10px;">Record</button>

            <table id="editMonthliesTable">

                <!-- table headers -->
                <thead>
                    <tr>
                        <th style="width: 10px;">Run</th>
                        <th style="width: 90px; word-break: break-word;">NAME</th>
                        <th style="width: 40px; word-break: break-word;">reg date</th>
                        <th style="width: 90px; word-break: break-word;">date sched or done</th>
                        <th style="width: 90px; word-break: break-word;">status</th>
                        <th style="width: 100px; word-break: break-word;">account</th>
                        <th style="width: 100px; word-break: break-word;">toFrom</th>
                        <th style="width: 75px; word-break: break-word;">amount</th>
                        <th style="width: 110px; word-break: break-word;">category</th>
                        <th style="width: 100px; word-break: break-word;">bucket</th>
                        <th style="width: 160px; word-break: break-word;">notes</th>
                        <th style="width: 300px; word-break: break-word;">comments</th>
                    </tr>
                </thead>

                <tbody>
                    <!-- transactions just uploaded -->
                    @foreach($monthlies as $monthly)
                        <tr data-id={{ $monthly->id }}>
                            <td style="text-align: center;">
                                <input type="checkbox" name="checkbox" class="check" style="width: 10px;">
                            </td>
                            <td>
                                <input type="text" name="name" class="name" style="width: 90px;" value={{ $monthly->name ?? NULL  }}>
                            </td>
                            <td>
                                <input type="text" name="dateOfMonth" class="date" style="text-align: center; width: 40px;" value={{ $monthly->dateOfMonth ?? NULL  }}>
                            </td>
                            <td>
                                <input type="text" name="transDate" class="transDate" style="width: 90px;" value={{ $monthly->trans_date ?? NULL }}>
                            </td>
                            <td>
                                <input type="text" name="status" class="status" style="width: 90px;" value={{ $monthly->status ?? NULL }}>
                            </td>
                            <td>
                                <input type="text" name="account" class="account" style="width: 100px;" value={{ $monthly->account ?? NULL  }}>
                            </td>
                            <td>
                                <input type="text" name="toFrom" class="toFrom" style="width: 100px;" value={{ $monthly->toFrom ?? NULL  }}>
                            </td>
                            <td>
                                <input type="text" name="amount" class="amount" style="text-align: right; width: 75px;" value={{ number_format(round($monthly->amount,2), 2, '.', '') ?? NULL  }}>
                            </td>
                            <td>
                                <input type="text" name="category" class="category" style="width: 110px;" value={{ $monthly->category ?? NULL  }}>
                            </td>
                            <td>
                                <input type="text" name="bucket" class="bucket" style="width: 100px;" value={{ $monthly->bucket ?? NULL  }}>
                            </td>
                            <td>
                                <input type="text" name="notes" class="notes" style="width: 160px;" value={{ $monthly->notes ?? NULL  }}>
                            </td>
                            <td>
                                <input type="text" name="comments" class="comments" style="width: 300px;" value={{ $monthly->comments ?? NULL  }}>
                            </td>
                        </tr>
                    @endforeach

                </tbody>
            </table>

        </form>
        
        <script>
            
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            
            // colors to rotate through for each transaction group
            const colors = [
                'palegoldenrod',
                'skyblue'
            ];

            $(document).ready(function() {
                var colorId = -1;
                $('tbody tr').each(function(index, element) {
                    var row = $(this);

                    // set background color
                    var name = row.find('.name').val();
                    var prevName = row.prev().find('.name').val();

                    if(name != prevName) {
                        colorId++;
                    } else {
                        // hide checkbox if it's the same group of transactions
                        row.find('.check').css('display', 'none');
                    }

                    // have we gone through all the colors yet?
                    if(colorId >= colors.length) colorId = 0;
                    row.css('background-color', colors[colorId]);
                    row.find('input').css('background-color', colors[colorId]);

                    // color Completed/Pending
                    var status = row.find('.status').val();
                    if(status == 'Completed') {
                        row.find('.status').css('background-color', 'lightgreen')
                            .parent().css('background-color', 'lightgreen');
                    } else if(status == 'Pending') {
                        row.find('.status').css('background-color', '#f7d98d')
                            .parent().css('background-color', '#f7d98d');
                    }
                });


            });


        </script>
    </body>

</html>