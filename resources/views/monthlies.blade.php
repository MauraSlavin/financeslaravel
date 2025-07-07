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

        <!-- <p id="msg" style="color: red;">{{ nl2br($msg ?? '') }}</p> -->
        <div class="tranRecorded" style="color: green;">
            @foreach($transRecorded as $tranRecorded)
                <h6 style="font-size: 25px;">
                    <u>{{ $tranRecorded['name'] }}</u> 
                    recorded for account <u>{{ $tranRecorded['account'] }}</u>
                    to/from <u>{{ $tranRecorded['to_from'] }}</u>
                    for <u>{{ $tranRecorded['amount'] }}</u>
                    with category {{ $tranRecorded['category'] }}
                    <span style="color: red;">{{ ($tranRecorded['dotrans'] == '1') ? " -- DO THE TRANS!!" : "" }}</span>
                </h6>
            @endforeach
        </div>

        <!-- <form id="monthliesForm" action="" method="GET"> -->
            <!-- @csrf -->
            
            <!-- button w/ explanation -->
            <span style="margin-left: 10px;">Click <b>RECORD</b> to record the <b>checked</b> transactions in the transactions table.</span><br>
            <!-- <button id="recordMonthlies" class="btn btn-success" type="submit" data-action="doublecurly route('writeMonthlyTransactions') doublecurly" style="margin-left: 10px; margin-bottom: 10px;">Record</button> -->
            <button id="recordMonthlies" class="btn btn-success" type="submit" style="margin-left: 10px; margin-bottom: 10px;">Record</button>

            <!-- monthlies data -->
            <input type="hidden" id="monthlies-data" name="monthlies" value="{{ json_encode($monthlies) }}">

            <!-- table -->
            <table id="editMonthliesTable">

                <!-- table headers -->
                <thead>
                    <tr>
                        <th style="width: 10px;">Run</th>
                        <th>Save changes to defaults</th>
                        <th style="width: 130px; word-break: break-word;">NAME</th>
                        <th style="width: 40px; word-break: break-word;">Reg Date</th>
                        <th style="width: 90px; word-break: break-word;">Date Sched or Done</th>
                        <th style="width: 90px; word-break: break-word;">Status</th>
                        <th style="width: 100px; word-break: break-word;">Account</th>
                        <th style="width: 100px; word-break: break-word;">To/From</th>
                        <th style="width: 75px; word-break: break-word;">Normal amount</th>
                        <th style="width: 110px; word-break: break-word;">Category</th>
                        <th style="width: 100px; word-break: break-word;">Bucket</th>
                        <th style="width: 160px; word-break: break-word;">Notes</th>
                        <th style="width: 300px; word-break: break-word;">Comments</th>
                    </tr>
                </thead>

                <tbody>
                    <!-- transactions saved in monthlies table -->
                    @foreach($monthlies as $sequence=>$monthly)
                        <!-- <form id="changeMonthly" action="" method="GET"> -->
                            <tr data-id={{ $monthly->id }}>
                                <td style="text-align: center;">
                                    <input type="checkbox" name="checkbox[]" class="check" style="width: 10px;">
                                    <input hidden class="chosen" name="chosen[]" value=false>
                                    <input hidden class="dotrans" name="dotrans[]" value="{{ $monthly->doTrans ? true : false }}">
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary save-button" data-item-id="{{ $monthly->id }}">Save</button>
                                </td>
                                <td>
                                    <input type="text" name="name" class="name" data-field="name" style="width: 130px;" value="{{ $monthly->name ?? NULL  }}">
                                    <hidden class="origName" value="{{ $monthly->name ?? NULL  }}"></hidden>
                                    <hidden class="recentName" value="{{ $monthly->name ?? NULL  }}"></hidden>
                                    <hidden class="sequence" style="display: none;">{{ $sequence }}</hidden>
                                </td>
                                <td>
                                    <input type="text" name="dateOfMonth" class="date" data-field="dateOfMonth" style="text-align: center; width: 40px;" value="{{ $monthly->dateOfMonth ?? NULL  }}">
                                </td>
                                <td>
                                    <input type="text" name="transDate" class="transDate" data-field="transDate" style="width: 90px;" value="{{ $monthly->trans_date ?? NULL }}">
                                    <input hidden class="completedDate" value="{{ $monthly->trans_date ?? NULL }}">
                                </td>
                                <td>
                                    <input type="text" name="status" class="status" data-field="status" style="width: 90px;" value="{{ $monthly->status ?? NULL }}">
                                </td>
                                <td>
                                    <input type="text" name="account" class="account" data-field="account" style="width: 100px;" value="{{ $monthly->account ?? NULL  }}">
                                </td>
                                <td>
                                    <input type="text" name="toFrom" class="toFrom" data-field="toFrom" style="width: 100px;" value="{{ $monthly->toFrom ?? NULL  }}">
                                </td>
                                <td>
                                    <input type="text" name="amount" class="amount" data-field="amount" style="text-align: right; width: 75px;" value="{{ number_format(round($monthly->amount,2), 2, '.', '') ?? NULL  }}">
                                </td>
                                <td>
                                    <input type="text" name="category" class="category" data-field="category" style="width: 110px;" value="{{ $monthly->category ?? NULL  }}">
                                </td>
                                <td>
                                    <input type="text" name="bucket" class="bucket" data-field="bucket" style="width: 100px;" value="{{ $monthly->bucket ?? NULL  }}">
                                </td>
                                <td>
                                    <input type="text" name="notes" class="notes" data-field="notes" style="width: 160px;" value="{{ $monthly->notes ?? NULL  }}">
                                </td>
                                <td>
                                    <input type="text" name="comments" class="comments" data-field="comments" style="width: 300px;" value="{{ $monthly->comments ?? NULL  }}">
                                </td>
                            </tr>
                        <!-- </form> -->
                    @endforeach

                </tbody>
            </table>

        <!-- </form> -->
        
        <script>
            
            // return next month or previous month with day of month passed in
            //      as "yyyy-mm-yy"
            // curDate in format "yyyy-mm-yy"
            function changeDate(curDate, dayOfMonth) {

                var year, month, day;

                // get parts of the date
                const dateParts = curDate.split("-");
                [year, month, day] = dateParts;

                day = dayOfMonth.padStart(2, "0");   // ensure it's 2 digits, left padded w/0s

                month = parseInt(month) + 1;
                if(month == 13) {
                    month = 1;
                    year = parseInt(year) + 1;
                }

                // make month a 2 digit string left padded with 0s
                month = month.toString().padStart(2, '0');

                // if day doesn't exist in new month, make it earlier
                if(month == '02' && ['29', '30', '31'].includes(day)) day = '28';
                else if(day == '31' && ['04', '06', '09', '11'].includes(month)) day = '30';

                return `${year}-${month}-${day}`;

            }  // end of function changeDate


            // update status (& related fields) of monthly transaction records paired with a given record (same transaction name)
            function updateRelatedRcds(row, newStatus, statusColor, dateColor, newDate) {
                // get the transaction name
                var txnName = row.find('.name').val();

                // get the next transaction & it's name
                var nextRow = row.next();
                var nextTrxName = nextRow.find('.name').val();

                // transactions with the same name (paired together) are together on the page
                //      so if the transaction name changes, it's a different group of transactions, and we're done.
                while(nextTrxName == txnName) {
                    // change the status and transDate values and background colors
                    nextRow.find('.status').val(newStatus).css('background-color', statusColor);
                    nextRow.find('.transDate').val(newDate).css('background-color', dateColor);

                    // update chosen, so Controller know which is checked
                    var chosen;
                    if(newStatus == 'Completed') chosen = false;
                    else chosen = true;
                    nextRow.find('.chosen').val(chosen);

                    // get the next transaction and it's name
                    nextRow = nextRow.next();
                    nextTrxName = nextRow.find('.name').val();

                }

            }   // end of function updateRelatedRcds


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

            // status background colors
            var chosenColor = 'pink';
            var completedColor = 'lightgreen';

            $(document).ready(function() {
                // get monthlies as an array of objects
                // get value from page
                var monthlies = $('#monthlies-data').val();
                // replace $quot; with '
                monthlies = monthlies.replaceAll("&quot;", '"');
                // parse the json
                monthlies = JSON.parse(monthlies);

                // set background colors of grouped transactions the same; 
                //  highlight $0
                //  highlight missing Buckets
                //  color-code Pending/Completed statuses
                var colorId = -1;
                $('tbody tr').each(function(index, element) {
                    var row = $(this);

                    // set background color; get name and previous transaction name first
                    var name = row.find('.name').val();
                    var prevName = row.prev().find('.name').val();

                    // if this is a new transaction group, change the color
                    if(name != prevName) {
                        colorId++;
                    } else {
                        // hide checkbox if it's the same group of transactions
                        row.find('.check').css('display', 'none');
                    }

                    // have we gone through all the colors yet?  If so, start over.
                    if(colorId >= colors.length) colorId = 0;
                    row.css('background-color', colors[colorId]);
                    row.find('input').css('background-color', colors[colorId]);

                    // if the amount is 0, highlight it
                    var amount = row.find('.amount').val();
                    if(amount == 0) row.find('.amount').css('background-color', 'pink');

                    // if the account is DiscSavings, and bucket is blank, highlight and set bucket to 'Misc'
                    var account = row.find('.account').val();
                    var bucket = row.find('.bucket').val();
                    if(account == 'DiscSavings' && bucket == '') row.find('.bucket').css('background-color', 'pink').val('Misc');

                    // if the account is NOT DiscSavings, and the bucket has a value, highlight and clear the bucket
                    if(account != 'DiscSavings' && bucket != '') row.find('.bucket').css('background-color', 'pink').val('');

                    // color Completed/Pending
                    var status = row.find('.status').val();
                    if(status == 'Completed') {
                        row.find('.status').css('background-color', 'lightgreen')
                            .parent().css('background-color', 'lightgreen');
                    } else if(status == 'Pending') {
                        row.find('.status').css('background-color', '#f7d98d')
                            .parent().css('background-color', '#f7d98d');
                        // hide the checkbox if transaction is Pending
                        row.find('.check').css('display', 'none');
                    }
                });

                $('.check').on('click', function(e) {
                    // get row we're working with
                    var row = $(this).parent().parent();

                    // will need current trans date element & status element
                    var statusElt = row.find('.status');        // status jquery element
                    var curDateElt = row.find('.transDate');    // date in transDate column jquery element
                    var curDate = curDateElt.val();             // current date (in transDate)
                    var dayOfMonth = row.find('.date').val();   // day of month for this transaction to happen


                    // if checking the transaction...
                    if($(this).prop('checked')) {
                        
                        // change status to Chosen w/pink background
                        statusElt.val('Chosen').css('background-color', chosenColor);
                        // change date to next month
                        var newDate = changeDate(curDate, dayOfMonth);
                        curDateElt.val(newDate).css('background-color', chosenColor);

                        // update chosen, so Controller know which is checked
                        row.find('.chosen').val(true);

                        // update related records
                        updateRelatedRcds(row, 'Chosen', chosenColor, chosenColor, newDate);

                    // if UNchecking the transaction...
                    } else {
                        // get background color to change it back to
                        var dateColor = row.find('.name').css('background-color');

                        // change status back to Completed with lightgreen background
                        statusElt.val('Completed').css('background-color', completedColor);
                        // change date to previous month
                        var completedDate = row.find('.completedDate').val();
                        curDateElt.val(completedDate).css('background-color', dateColor);

                        // update chosen, so Controller know which is checked
                        row.find('.chosen').val(false);

                        // update related records
                        updateRelatedRcds(row, 'Completed', completedColor, dateColor, completedDate);
                        
                    }

                });

                $('input').on('change', function(e) {
                    // this has been changed:
                    var sequence = $(this).parent().parent().find('.sequence').text();
                    var field = $(this).attr('name');
                    var field = field.replaceAll('[', '').replaceAll(']', '');
                    var newValue = $(this).val();

                    // alert("Changed: " + newValue + ";\n"
                    //     + "sequence text: " + sequence + ";\n"
                    //     + "field changed: " + field + ";\n"
                    //     + "old value: " + monthlies[sequence][field]
                    // );

                    // update in monthlies variable
                    monthlies[sequence][field] = newValue;

                });








                $('#recordButton').on('click', function(e) {
                    const formData = new FormData();
                    
                    $('input[type="checkbox"]:checked').each(function() {
                        formData.append(`checked_items[${$(this).data('item-id')}]`, $(this).prop('checked'));
                    });
                    
                    $.ajax({
                        url: '{{ route('writeMonthlyTransactions') }}',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            console.log(response);
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                        }
                    });
                });

                // Handle individual row saves
                $('.save-button').on('click', function() {
                    const itemId = $(this).data('item-id');
                    const $row = $(this).closest('tr');
                    
                    const updates = {};
                    $row.find('[data-field]').each(function() {
                        updates[$(this).data('field')] = $(this).val();
                    });

                    console.log("updates: ", updates);
                    
                    $.ajax({
                        // url: `doublecurly url('/items/${itemId}') doublecurly/update`,
                        url: `{{ url('/transactions/saveMonthly/${itemId}') }}`,
                        method: 'PUT',
                        data: JSON.stringify(updates),
                        contentType: 'application/json',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            // console.log(response);
                            alert("Save completed");
                        },
                        error: function(xhr, status, error) {
                            alert('Error occurred:', error);
                        }
                    });
                });











                // $('#monthliesForm').on('submit', function(e) {
                //     // e.preventDefault(e); // Prevent immediate submission

                //     const data = JSON.stringify(monthlies);
                //     $('.monthlies-data').val( JSON.stringify(monthlies) );

                //     // this.submit;
                // });

                // // shouldn't be needed
                // $('#changeMonthly').on('submit', function(e) {
                //     alert("Submitting change to one monthly");
                //     this.submit;
                // })

            });

        </script>
    </body>

</html>