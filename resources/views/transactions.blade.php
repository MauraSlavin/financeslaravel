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
        <h1>Transactions loaded for 
            <span id="accountName">{{$accountName}}</span>
        </h1>
        @if($accountName != "all")
            <p id="accountId" style="display: none;"></p>
        @endif
        <h3>Cleared balance: {{ $clearedBalance }}</h3>
        <h3>Register balance: {{ $registerBalance }}</h3>
        <h3>Last Balanced: {{ $lastBalanced }}</h3>
        @if($upload)
            <h5>Transactions just loaded have an id in <span class="newtransaction">red</span>.</h5>
            <h5>Transactions that may be a duplicate have a background color of <span class="dupMaybe">yellow</span>.</h5>
        @endif
        <input type="hidden" id="accountNames"  name="accountNames"  value={{ json_encode($accountNames) }}>
        <input type="hidden" id="accountIds"  name="accountIds"  value={{ json_encode($accountIds) }}>
        <input type="hidden" id="lastStmtDates"  name="lastStmtDates"  value={{ json_encode($lastStmtDates) }}>
        <input type="hidden" id="toFroms"       name="toFroms"       value={{ $toFroms }}>
        <input type="hidden" id="toFromAliases" name="toFromAliases" value={{ $toFromAliases }}>
        <input type="hidden" id="categories"    name="categories"    value={{ $categories }}>
        <input type="hidden" id="trackings"     name="trackings"     value={{ $trackings }}>
        <input type="hidden" id="buckets"       name="buckets"       value={{ $buckets }}>

        <form>
            <div class="mb-3">
                <label for="beginDate" class="form-label">Select Begin and End Dates:</label>
                <div class="d-flex">
                    <input type="text" class="form-control" id="beginDate" placeholder="Begin (m/d/y or m/d)" value={{ $beginDate }}>
                    -
                    <input type="text" class="form-control" id="endDate" placeholder="End (m/d/y or m/d)" value={{ $endDate }}>
                    <button type="button" id="refreshTransactions" class="btn btn-success rounded-sm">Refresh Transactions</button>
                    <button type="button" id="backToAccount" class="btn btn-primary">Back to Accounts</button>
                    <button type="button" id="uploadTransactions" class="btn btn-warning">Upload</button>
                    <button type="button" id="addTransaction" class="btn btn-success">Add Transaction</button>
                </div>
            </div>
        </form>
        <p id="errorMsg"></p>
        <!-- table -->
        <table id="editTransactionsTable">

            <!-- table headers -->
            <thead>
                <tr>
                    <th style="width: 100px; word-break: break-word;">id</th>
                    <th style="width: 100px; word-break: break-word;">trans_date</th>
                    <th style="width: 100px; word-break: break-word;">clear_date</th>
                    @if($accountName == 'all')
                        <th style="width: 100px; word-break: break-word;">account</th>
                        <th style="display: none;">id</th>
                    @endif
                    <th style="width: 100px; word-break: break-word;">toFrom</th>
                    <th style="width: 100px; word-break: break-word;">amount</th>
                    <th style="width: 100px; word-break: break-word;">category</th>
                    <th style="width: 100px; word-break: break-word;">notes</th>
                    <th style="width: 100px; word-break: break-word;">method</th>
                    <th style="width: 100px; word-break: break-word;">tracking</th>
                    <th style="width: 100px; word-break: break-word;">Edit/Save</th>
                    <th style="width: 100px; word-break: break-word;">Split</th>
                    <th style="width: 100px; word-break: break-word;">Delete</th>
                    <th style="width: 100px; word-break: break-word;">stmtDate</th>
                    <th style="width: 100px; word-break: break-word;">amtMike</th>
                    <th style="width: 100px; word-break: break-word;">amtMaura</th>
                    <th style="width: 100px; word-break: break-word;">total_amt</th>
                    <th style="width: 100px; word-break: break-word;">total_key</th>
                    <th style="width: 100px; word-break: break-word;">split_total</th>
                    @if($accountName == 'DiscSavings' || $accountName == 'all')
                        <th style="width: 100px; word-break: break-word;">bucket</th>
                    @endif
                    <th style="width: 100px; word-break: break-word;">lastBalanced</th>
                    <th style="width: 100px; word-break: break-word;">Spent</th>
                    <th style="width: 100px; word-break: break-word;">Budget thru this month</th>
                    <th style="width: 100px; word-break: break-word;">Full Year Budget</th>
                </tr>
            </thead>

            <tbody>
                <!-- transactions just uploaded -->
                @foreach($newTransactions as $newTransaction)
                    <tr data-id={{ $newTransaction["id"] }} 
                        @if($newTransaction["dupMaybe"]) 
                            class="dupMaybe" 
                        @endif
                    >
                        <td class="newtransaction">{{ $newTransaction["id"] ?? NULL }}</td>
                        <td class="transDate">{{ $newTransaction["trans_date"] ?? NULL }}</td>
                        <td class="clearDate">{{ $newTransaction["clear_date"] ?? NULL  }}</td>
                        @if($accountName == 'all')
                            <td class="account">{{ $newTransaction["account"] ?? NULL  }}</td>
                            <td style="display: none;" class="accountId">{{ $newTransaction["accountId"] ?? "id"  }}</td>
                        @endif
                        <td class="toFrom">{{ $newTransaction["toFrom"] ?? NULL  }}</td>
                        <td class="amount">{{ $newTransaction["amount"] ?? NULL  }}</td>
                        <td class="category">{{ $newTransaction["category"] ?? NULL  }}</td>
                        <td class="notes">{{ $newTransaction["notes"] ?? NULL  }}</td>
                        <td class="method">{{ $newTransaction["method"] ?? NULL  }}</td>
                        <td class="tracking">{{ $newTransaction["tracking"] ?? NULL  }}</td>
                        <!-- once this ("edit") is clicked, change to save.  Once saved, change back to edit -->
                        <td>
                            <button class="btn btn-primary editTransaction" data-id={{ $newTransaction["id"] }}>Edit</button>
                        </td>
                        <td>
                            <button class="btn btn-warning splitTransaction" data-id={{ $newTransaction["id"] }}>Split</button>
                        </td>                       
                        <td>
                            <button class="btn btn-danger deleteTransaction" data-id={{ $newTransaction["id"] }}>Delete</button>
                        </td>
                        <td class="stmtDate">{{ $newTransaction["stmtDate"] ?? NULL  }}</td>
                        <td class="amtMike">{{ $newTransaction["amtMike"] ?? NULL  }}</td>
                        <td class="amtMaura">{{ $newTransaction["amtMaura"] ?? NULL  }}</td>
                        <td class="total_amt">{{ $newTransaction["total_amt"] ?? NULL  }}</td>
                        <td class="total_key">{{ $newTransaction["total_key"] ?? NULL  }}</td>
                        <td class="split_total">{{ $newTransaction["split_total"] ?? NULL  }}</td>
                        @if($accountName == 'DiscSavings' || $accountName == 'all')
                            <td class="bucket">{{ $newTransaction["bucket"] ?? NULL  }}</td>
                        @endif
                        <td class="lastBalanced"></td>
                        <td class="spent">@if(isset($newTransaction["spent"])){{ $newTransaction["spent"] }}
                                          @else - 
                                          @endif
                        </td>
                        <td class="ytmBudget">@if(isset($newTransaction["ytmBudget"])){{ $newTransaction["ytmBudget"] }}
                                          @else - 
                                          @endif
                        </td>
                        <td class="yearBudget">@if(isset($newTransaction["yearBudget"])){{ $newTransaction["yearBudget"] }}
                                          @else - 
                                          @endif
                        </td>
                    </tr>
                @endforeach

                <!-- line to separate new & old transactions -->
                @if($upload)
                <tr>
                    <td class="fw-bold">id</td>
                    <td class="fw-bold">trans_date</td>
                    <td class="fw-bold">clear_date</td>
                    @if($accountName == 'all')
                        <td class="fw-bold">account</td>
                        <td style="display: none;" class="fw-bold">id</td>
                    @endif
                    <td class="fw-bold">toFrom (existing trans)</td>
                    <td class="fw-bold">amount</td>
                    <td class="fw-bold">category</td>
                    <td class="fw-bold">notes</td>
                    <td class="fw-bold">method</td>
                    <td class="fw-bold">tracking</td>
                    <td class="fw-bold">Edit/Save</td>    
                    <td class="fw-bold">Split</td>    
                    <td class="fw-bold">Delete</td>    
                    <td class="fw-bold">stmtDate</td>
                    <td class="fw-bold">amtMike</td>
                    <td class="fw-bold">amtMaura</td>
                    <td class="fw-bold">total_amt</td>
                    <td class="fw-bold">total_key</td>
                    <td class="fw-bold">split_total</td>
                    @if($accountName == 'DiscSavings' || $accountName == 'all')
                        <td class="fw-bold">bucket</td>
                    @endif
                    <td class="fw-bold">lastBalanced</td>
                    <td class="fw-bold">Spent</td>
                    <td class="fw-bold">Budget thru this month</td>
                    <td class="fw-bold">Full Year Budget</td>
                </tr>
                @endif

                <!-- existing (old) transactions -->
                @foreach($transactions as $transaction)
                    <tr data-id={{ $transaction->id }}>
                        <td class="transId">{{ $transaction->id}}</td>
                        <td class="transDate">{{ $transaction->trans_date}}</td>
                        <td class="clearDate">{{ $transaction->clear_date }}</td>
                        @if($accountName == 'all')
                            <td class="account">{{ $transaction->account }}</td>
                            <!-- <td class="accountId">{{ $transaction->account }}</td> -->
                            <td style="display: none;" class="accountId">{{ $transaction->accountId }}</td>
                        @endif
                        <td class="toFrom">{{ $transaction->toFrom }}</td>
                        <td class="amount">{{ $transaction->amount }}</td>
                        <td class="category">{{ $transaction->category }}</td>
                        <td class="notes">{{ $transaction->notes }}</td>
                        <td class="method">{{ $transaction->method }}</td>
                        <td class="tracking">{{ $transaction->tracking }}</td>
                        <!-- once this ("edit") is clicked, change to save.  Once saved, change back to edit -->
                        <!-- may need to edit these transactions -->
                        <td>
                            <button class="btn btn-primary editTransaction" data-id={{ $transaction->id }}>Edit</button>
                        </td>                       
                        <td>
                            <button class="btn btn-warning splitTransaction" data-id={{ $transaction->id }}>Split</button>
                        </td>                       
                        <td>
                            <button class="btn btn-danger deleteTransaction" data-id={{ $transaction->id }}>Delete</button>
                        </td> 
                        <td class="stmtDate">{{ $transaction->stmtDate }}</td>
                        <td class="amtMike">{{ $transaction->amtMike }}</td>
                        <td class="amtMaura">{{ $transaction->amtMaura }}</td>
                        <td class="total_amt">{{ $transaction->total_amt }}</td>
                        <td class="total_key">{{ $transaction->total_key }}</td>
                        <td class="split_total">{{ $transaction->split_total }}</td>
                        @if($accountName == 'DiscSavings' || $accountName == 'all')
                            <td class="bucket">{{ $transaction->bucket }}</td>
                        @endif
                        <td class="lastBalanced">{{ $transaction->lastBalanced ? substr($transaction->lastBalanced, 0, 10) : NULL }}</td>
                        <td class="spent">@if(isset($transaction->spent)){{ $transaction->spent }}
                                          @else - 
                                          @endif
                        </td>
                        <td class="ytmBudget">@if(isset($transaction->ytmBudget)){{ $transaction->ytmBudget }}
                                          @else - 
                                          @endif
                        </td>
                        <td class="yearBudget">@if(isset($transaction->yearBudget)){{ $transaction->yearBudget }}
                                          @else - 
                                          @endif
                        </td>                      
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        
        <script>
            
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            
            // number of characters in toFrom to match in automatically using the alias
            var numberOfAliasCharsToMatch = 11;

            $(document).ready(function() {
                
                // highlight split_totals where they != total_amt for the key (total_key)
                var rows = $("#editTransactionsTable tbody tr");
                var missingSplitValues = [];     // remeber if some splits don't equal the total
                rows.each(function(index, row) {
                    var rowData = $(row);

                    // if there is a total_key
                    if(rowData.find(".total_key").text() != '') {
                        // and the total_amt != split_total
                        if(Number(rowData.find(".total_amt").text()) != Number(rowData.find(".split_total").text())) {
                            // highlight total_amt and split_total
                            rowData.find(".total_amt").css("background-color", "yellow");
                            rowData.find(".split_total").css("background-color", "yellow");
                            // note id for row where split total doesn't match total_amt
                            missingSplitValues.push(rowData.attr('data-id'));
                        }
                    }

                });

                // show an alert warning the user that some mismatches were found.
                if(missingSplitValues.length >0) {
                    var msg = missingSplitValues.join("\n");
                    msg = "The following ids had split totals that do not add up to the total_amt:\n" + msg;
                    alert(msg);                        
                }

                // get hidden variables
                var accountNames = $("#accountNames").val();
                // console.log("accountNames: ", accountNames);
                accountNames = JSON.parse(accountNames);

                var accountIds = $("#accountIds").val();
                accountIds = JSON.parse(accountIds);

                // fill in hidden accountId, if accountName is not "all"
                if($("#accountName").text() != "all") {
                    // alert($("#accountName").text());
                    var accountIdx = accountNames.indexOf($('#accountName').text());
                    $("#accountId").text(accountIds[accountIdx]);
                }

                var toFroms = $("#toFroms").val();
                toFroms = toFroms.replaceAll("%20", " ");
                // console.log("toFroms: ", toFroms);
                toFroms = JSON.parse(toFroms);
                
                var toFromAliases = $("#toFromAliases").val();
                toFromAliases = toFromAliases.replaceAll("%20", " ");
                // console.log("toFromAliases: ", toFromAliases);
                toFromAliases = JSON.parse(toFromAliases);

                var categories = $("#categories").val();
                categories = categories.replaceAll("%20", " ");
                categories = JSON.parse(categories);

                var trackings = $("#trackings").val();
                trackings = trackings.replaceAll("%20", " ");
                trackings = JSON.parse(trackings);

                var buckets = $("#buckets").val();
                buckets = buckets.replaceAll("%20", " ");
                buckets = JSON.parse(buckets);

                var origToFrom = '';    // want it scoped here

                // is the month a valid month? Returns "invalid" if not, the month if it is valid
                function checkMonth(month) {
                    if(month < 1 || month > 12) month = "invalid";
                    // make month 2 digits, padded with leading 0s
                    return month.padStart(2, "0");
                }

                // is the day a valid day of the month? Returns "invalid" if not, the day if it is valid
                function checkDay(day, month) {

                    var lastDay;
                    // these months have 31 days
                    if([1, 3, 5, 7, 8, 10, 12].includes(parseInt(month))) lastDay = 31;
                    // February can have 29 days
                    else if(month == 2) lastDay = 29;
                    // the rest of the months have 30 days
                    else lastDay = 30;

                    if(day < 1 || day > lastDay) day = 'invalid';
                    // make day 2 digits, padded with leading 0s
                    return day.padStart(2, "0");
                }

                // check to see if the date is valid
                // Note: 2/29 will be ok, even if it is not a leap year.
                // if nullOK is true, NULL is allowed
                // returns a valid date, or false if date is not valid
                function verifyDate(date, field, nullOK = false) {

                    // make sure date is valid
                    var month, day, year;

                    // clear old error msg
                    var errorMsg = "";
                    // $("#errorMsg").text(errorMsg);

                    // if nullOK, then a null or empty string is allowed.
                    // return true (existing value ok)
                    if (nullOK && (date == "" || date == null)) return "";

                    // determine whether '-' or '/' is used as a delimiter in the date
                    var hasDashDelimiter = date.includes('-');
                    var delimiter;
                    if(hasDashDelimiter) delimiter = '-';
                    else delimiter = '/';

                    // break date into parts
                    var newDate = date.split(delimiter);

                    // needs at least 2 elements in newDate (month & day)
                    if(newDate.length < 2) {
                        errorMsg = field + ": Date needs to be yyyy-mm-dd or yy-mm-dd or mm-dd (" + date + " entered).";
                        $("#errorMsg").text(errorMsg);
                        return false;
                    }

                    // if only month and day, add year to newDate variable
                    if(newDate.length == 2) {
                        var lengths = newDate.map(element => element.length);
                        
                        if(Math.max(...lengths) > 2) {
                            errorMsg = field + ": Month and date should be 2 chars each (" + date + " entered).";
                            $("#errorMsg").text(errorMsg);
                        }
                        year = new Date().getFullYear();
                        newDate.push(year.toString());
                    }
                    if(errorMsg != '') return false;

                    // create array of objects w/length & index
                    const lengthAndIndices = newDate.map((item, index) => ({ length: item.length, index }));

                    // Find the maximum length of all the parts
                    const maxIndex = lengthAndIndices.reduce((max, current) => 
                        current.length > max.length ? current : max,
                        { length: -Infinity }
                    ).index;
                    const maxLength = lengthAndIndices[maxIndex]['length'];

                    // year should be the longest, and should have 2 or 4 digits
                    if(![2,4].includes(maxLength)) {
                        errorMsg = field + ": Year must have 2 or 4 digits (" + date + " entered).";
                        $("#errorMsg").text(errorMsg);

                        return false;
                    }

                    // Only one part can be 4 chars long
                    // Get all indices of the maximum length
                    var minYear = 2020;
                    var maxYear = 2030;

                    if(maxLength == 4) {
                        const maxIndices = lengthAndIndices.filter(obj => obj.length === maxLength).map(obj => obj.index);
                        if(maxIndices.length != 1) {
                            errorMsg = field + ": Only one part of the date can have 4 digits (" + date + " entered).";
                            $("#errorMsg").text(errorMsg);

                            return false;
                        }

                        var year = newDate[maxIndices];
                        var numYear = parseInt(year);
                        
                        // year must be a number
                        if((typeof numYear === "number" && isNaN(numYear)) || typeof numYear != "number") {
                            errorMsg = field + ": Year must be a number (" + year + " entered).";
                            $("#errorMsg").text(errorMsg);
                            
                            return false;
                        }

                        // year must be between minYear and maxYear
                        if(numYear < minYear || numYear > maxYear) {
                            errorMsg = field + ": Year must be between " + minYear + " and " + maxYear + " (" + year + " entered).";
                            $("#errorMsg").text(errorMsg);
                            
                            return false;
                        }

                        // remove year from newDate
                        newDate.splice(maxIndices, 1);

                        // the remaining parts should be month and day, in that order
                        month = checkMonth(newDate[0]);
                        if(month == 'invalid') {
                            errorMsg = field + ": The month must be between 1 and 12 (" + date + " entered).";
                            $("#errorMsg").text(errorMsg);

                            return false;
                        }

                        day = checkDay(newDate[1], newDate[0]);
                        if(day == 'invalid') {
                            errorMsg = field + ": The day must be between 1 and the end of the month (" + date + " entered).";
                            $("#errorMsg").text(errorMsg);

                            return false;
                        }

                        return year + "-" + month + "-" + day;

                    } else if(maxLength == 2) {
                        // if the year is a two digit year, the date format expected is m/d/y

                        month = checkMonth(newDate[0]);
                        if(month == 'invalid') {
                            errorMsg = field + ": The month must be between 1 and 12 (" + date + " entered).";
                            $("#errorMsg").text(errorMsg);

                            return false;
                        }

                        day = checkDay(newDate[1], newDate[0]);
                        if(day == 'invalid') {
                            errorMsg = field + ": The month must be between 1 and 12 (" + date + " entered).";
                            $("#errorMsg").text(errorMsg);

                            return false;
                        }

                        // any year is accepted
                        // NOTE: 4 digit year needed for SQL query
                        year = "20" + newDate[2];
                        // year must be between minYear and maxYear
                        if(year < minYear || year > maxYear) {
                            errorMsg = field + ": Year must be between " + minYear + " and " + maxYear + " (" + year + " entered).";
                            $("#errorMsg").text(errorMsg);
                            
                            return false;
                        }

                        return year + "-" + month + "-" + day;

                    } else {
                        // Throw error message if year does not have 2 or 4 digits
                        errorMsg = field + ": The year must have 2 or 4 digits (" + date + " entered).";
                        $("#errorMsg").text(errorMsg);

                        return false;
                    }

                }

                // change yyyy-mm-dd from database to mm/dd/yyyy for webpage
                function formatDefaultDate(htmlId) {
                    const date = $(htmlId).val();
                    const delimiters = ['-'];
                    var newDate = date.split(new RegExp(`[${delimiters.join('')}]`, 'g')).filter(char => char.trim() !== '');
                    newDate = newDate[1] + "/" + newDate[2] + "/" + newDate[0];
                    $(htmlId).val(newDate);
                }
                

                function updateTransactionRecord($record) {

                    // make array to send to ajax
                    var newTransaction = {};

                    // id has class transId for existing transactions, and newtransaction for just added transactions
                    var transId = $record.data('id');
                    if(transId == 'null' || transId == null) newTransaction['id'] = null;
                    else newTransaction['id'] = Number(transId);
                    
                    newTransaction['trans_date'] = $record.find('.transDate').children(':first-child').val();

                    var clearDate = $record.find('.clearDate').children(':first-child').val();
                    if(clearDate !== '' && clearDate != 'null' && clearDate !== 'NULL') newTransaction['clear_date'] = clearDate;
                    else newTransaction['clear_date'] = null;
                    
                    var account = $record.find('.account').children(':first-child').val();
                    if(account == undefined) newTransaction['account'] = $('#accountName').text();
                    else newTransaction['account'] = account;
                    
                    newTransaction['toFrom'] = $record.find('.toFrom').children(':first-child').val();
                    newTransaction['amount'] = parseFloat($record.find('.amount').children(':first-child').val());
                    newTransaction['amtMike'] = parseFloat($record.find('.amtMike').children(':first-child').val());
                    newTransaction['amtMaura'] = parseFloat($record.find('.amtMaura').children(':first-child').val());

                    var method = $record.find('.method').children(':first-child').val();
                    if(method !== '' && method !== 'null' && method !== 'NULL') newTransaction['method'] = method;
                    else newTransaction['method'] = null;
                    
                    var category = $record.find('.category').children(':first-child').val();
                    if(category !== '' && category !== 'null' && category !== 'NULL') newTransaction['category'] = category;
                    else newTransaction['category'] = null;
                    
                    var tracking = $record.find('.tracking').children(':first-child').val();
                    if(tracking !== '' && tracking !== 'null' && tracking !== 'NULL') newTransaction['tracking'] = tracking;
                    else newTransaction['tracking'] = null;
                    
                    var stmtDate = $record.find('.stmtDate').children(':first-child').val();
                    if(stmtDate !== '' && stmtDate !== 'null' && stmtDate !== 'NULL') newTransaction['stmtDate'] = stmtDate;
                    else newTransaction['stmtDate'] = null;
                    
                    var total_amt = $record.find('.total_amt').children(':first-child').val();
                    if(total_amt !== '' && total_amt !== 'null' && total_amt !== 'NULL') newTransaction['total_amt'] = parseFloat(total_amt);
                    else newTransaction['total_amt'] = null;
                    
                    var total_key = $record.find('.total_key').children(':first-child').val();
                    if(total_key !== '' && total_key !== 'null' && total_key !== 'NULL') newTransaction['total_key'] = total_key;
                    else newTransaction['total_key'] = null;
                    
                    var bucket = $record.find('.bucket').children(':first-child').val();
                    if(bucket == undefined) newTransaction['bucket'] = null;
                    else newTransaction['bucket'] = bucket;
                    
                    var notes = $record.find('.notes').children(':first-child').val();
                    if(notes !== '' && notes !== 'null' && notes !== 'NULL') newTransaction['notes'] = notes;
                    else newTransaction['notes'] = null;

                    // get id before stringifying
                    var id = newTransaction['id'];

                    // stringify to add to payload
                    newTransaction = JSON.stringify(newTransaction);
                    
                    // handle blanks and special chars
                    newTransaction = encodeURIComponent(newTransaction);

                    // if id is null, need to insert transaction
                    // if not null, update existing transaction
                    if(id !== null) {
                        $.ajax({
                            url: '/transactions/update',
                            method: 'PUT',
                            contentType: 'application/json',        // added
                            processData: false,                     // added
                            data: JSON.stringify({
                                _token: '{{ csrf_token() }}',
                                newTransaction: newTransaction
                            }),
                            success: function(response) {
                                console.log(response)
                                console.log(response.message);
                            },
                            error: function(xhr, status, error) {
                                console.log("** FAILED ** to update transaction", error);
                                console.log("** FAILED ** to update transaction. status", status);
                                console.log("** FAILED ** to update transaction. xhr", xhr);
                                alert("Failed to update transaction");
                            }

                        });
                    } else {
                        $.ajax({
                            url: '/transactions/insertTrans',
                            method: 'POST',
                            data: JSON.stringify({
                                _token: '{{ csrf_token() }}',
                                newTransaction: newTransaction
                            }),
                            dataType: 'json',
                            success: function(response) {
                                console.log(response.message);
                                // put new id on page where needed
                                $record.find('.transId').text(response.recordId);
                                $record.find('.transId').parent().attr('data-id', response.recordId);
                                $record.find('.editTransaction').attr('data-id', response.recordId);
                                $record.find('.splitTransaction').attr('data-id', response.recordId);
                                $record.find('.deleteTransaction').attr('data-id', response.recordId);

                                // if the total_key was resolved response.newTotalKey has the new key), change that on the page
                                if(response.newTotalKey !== false) {
                                    var oldTotalKey = $record.find('.total_key').text();
                                    $record.find('.total_key').text(response.newTotalKey);

                                    // change the total_key for other transactions with the same placeholder
                                    var rows = $("#editTransactionsTable tbody tr");
                                    rows.each(function(index, row) {
                                        var rowData = $(row);

                                        // rows not in edit mode
                                        if(rowData.find(".total_key").text() == oldTotalKey) {
                                            rowData.find(".total_key").text(response.newTotalKey);
                                        }

                                        // rows in edit mode
                                        if(rowData.find(".total_keyEdit").val() == oldTotalKey) {
                                            rowData.find(".total_keyEdit").val(response.newTotalKey);
                                        }
                                    });

                                }
                            },
                            error: function(xhr, status, error) {
                                console.log("** FAILED ** to insert transaction", error);
                                alert("Failed to insert transaction");

                            }

                        });
                    }
                }

                // change edittable cells in record to non-edittable
                function makeNotEdittable(thisElement) {

                    // change all the cells to text (not inputs), except the "Save" button
                    //  & change Save button to Edit
                    var $trElt;
                    if( $(thisElement).parent().prop('tagName') == 'TR') {
                        $trElt = $(thisElement).parent();
                    } else {
                        $trElt = $(thisElement).parent().parent();
                    }
                    // $(thisElement).parent().parent().find('td').each(function(index, td) {
                    $trElt.find('td').each(function(index, td) {
                        
                        // index of cells in row (0 thru 17)
                        
                        // What's the tag of the first child (undefined for all but the last, which is "BUTTON")
                        // console.log( $(td).children(':first-child').prop('tagName'));
                        
                        // get the current cell
                        var $cell = $(td);
                        
                        // for each cell whose child is an INPUT (all except the last Save button)
                        if( $(td).children(':first-child').prop('tagName') == 'INPUT' ) {
                            
                            // save cell field class before emptying
                            var cellClass = $cell.children(':first-child').prop("class");
                            // drop editable-cell class
                            cellClass = cellClass.replace("editable-cell", "");
                            cellClass = cellClass.slice(0, -4);  // drop the "Edit" suffix from the class


                            // use the input value for the html (text) of the non-input cell (replacing the input)
                            var cellValue = $cell.children(':first-child').val();
                            $cell.empty()                               // clear out the element
                                .html(cellValue)                        // keep the value
                                .addClass(cellClass);                   // keep the field class
                            
                        } else {
                            // change the Save button to an Edit button
                            if($(td).children(":first-child").text() == "Save") {
                                $(td).children(':first-child')
                                .text('Edit')
                                .removeClass("btn-success")     // change green to blue
                                .removeClass("saveTransaction")
                                .addClass("btn-primary")
                                .addClass("editTransaction"); // change the id
                            }
                        }
                        
                    });

                }

                // If a new toFrom is entered, make sure it's not a mistake;
                // If the new toFrom isn't a previously used value, ask if it should be changed automatically.
                // Returns isGood (true/false/newValue) and an error message (null or null string if isGood is true or newValue);
                function handleToFrom(newValue, account, accountId, toFroms, toFromAliases, origToFrom) {
                    var isGood = true;
                    var errorMsg = ''; 

                    // Does toFrom exceed char length in database (100 chars)
                    var maxToFromChars = 100;
                    isGood = verifyVarCharLength(newValue, maxToFromChars);
                    if(!isGood) {
                        okToTruncToFrom = confirm("toFrom truncated from (length " + newValue.length + "):\n" + newValue + 
                            "\nto (length " + maxToFromChars + "):\n" + newValue.substr(0, maxToFromChars) +
                            "\n\nIs this OK?");
                        if(!okToTruncToFrom) {
                            return [false, "toFrom: Max chars of " + maxToFromChars + " exceeded."];
                        } else {
                            newValue = newValue.substr(0, maxToFromChars);
                            isGood = newValue;
                        }
                    }

                    // Does toFrom have at least one character?
                    if(newValue.trim().length < 1) {
                        errorMsg = "toFrom: Enter at least one non-blank char.";
                        return [false, errorMsg];
                    }

                    // is toFrom a new value (look at existing toFrom values, and toFroms in aliases)?
                    // If so, is it ok?
                    var existingOrigToFroms = toFroms.concat(toFromAliases.map(obj => obj.origToFrom));
                    existingOrigToFroms = existingOrigToFroms.map(elt => elt.toLowerCase());

                    if(!existingOrigToFroms.includes(newValue.toLowerCase())) {     // new toFrom value
                        // If a new toFrom is entered, make sure it's not a mistake;
                        var question = "This is a new toFrom value: " + newValue + ".  Is it correct?";
                        var isCorrectNewValue = confirm(question);
                        // if not correct, return and let user try again
                        if(!isCorrectNewValue) return [false, "toFrom: Incorrect toFrom entered (" + newValue + ")."];
                    }

                    // If the new toFrom doesn't have an alias, should future examples be auto replaced in the future;
                    // If it DOES have an alias, if it should this case be changed.
                    // Account has to match, too.
                    var foundToFrom = toFromAliases.find(alias => alias.origToFrom.toLowerCase() === newValue.toLowerCase() && alias.account_id == accountId);

                    // if no alias found... and origToFrom is not null... and newValue is changed from origToFrom
                    if( typeof foundToFrom === 'undefined' && origToFrom !== null && newValue.toLowerCase() != origToFrom.toLowerCase()) {
                        var question = "In the future, should this toFrom automatically be changed to " + newValue + " when the first " + numberOfAliasCharsToMatch + " characters match?" +
                            '\n\n"' + origToFrom.substr(0, numberOfAliasCharsToMatch) + '..." \n     to\n"' + newValue + '"';
                        var saveAlias = confirm(question);
                        
                        if(saveAlias) {
                            origToFrom = origToFrom.substr(0, numberOfAliasCharsToMatch);
                            origToFrom = encodeURIComponent(origToFrom);
                            newValue = encodeURIComponent(newValue);
                            
                            var url = '/transactions/insertAlias/' + origToFrom + '/' + newValue;
                           
                            $.ajax({
                                url: url,
                                type: 'POST',
                                data: {
                                    _token: "{{ csrf_token() }}",
                                    origToFrom: origToFrom,
                                    newValue: newValue
                                },
                                dataType: 'json',

                                success: function(response) {
                                    // no need to do anything
                                    console.log("ToFrom alias saved successfully: ", response);
                                },

                                error: function(xhr, status, error) {
                                    var errorMsg = "Error saving alias to table toFromAliases.";
                                    console.error(errorMsg, xhr.responseJSON ? xhr.responseJSON.error : error);
                                    alert(errorMsg + ": " + (xhr.responseJSON ? xhr.responseJSON.details : error));
                                }
                            });
                        }

                    // if alias already exists, replace the toFrom with the alias
                    } else if(typeof foundToFrom !== 'undefined') {
                        isGood = foundToFrom['transToFrom'];
                    }

                    return [isGood, null];
                }
                

                // If a new tracking is entered, make sure it's not a mistake;
                // Returns isGood (true/false/newValue) and an error message (null or null string if isGood is true or newValue);
                function handleTracking(newValue, trackings, origTracking) {
                    var isGood = true;
                    var errorMsg = ''; 

                    // Does tracking exceed char length in database (10 chars)
                    var maxTrackingChars = 10;
                    isGood = verifyVarCharLength(newValue, maxTrackingChars);

                    if(!isGood) {
                        // ask if truncating the input is ok
                        truncTracking = newValue.slice(0, maxTrackingChars);
                        okToTruncTracking = confirm("Tracking truncated to:\n" + truncTracking + "\n\nIs this OK?");
                        if(!okToTruncTracking) {
                            return [false, "tracking: Max chars of " + maxTrackingChars + " exceeded. Entered: " + newValue];
                        } else {
                            newValue = truncTracking;
                            isGood = newValue;
                        }
                    }

                    // is tracking a new value (look at existing tracking values, and toFroms in aliases)?
                    // If so, is it ok?
                    if(!trackings.includes(newValue)) {     // new tracking value
                        // If a new tracking is entered, make sure it's not a mistake;
                        var question = "This is a new tracking value: " + newValue + ".  Is it correct?";
                        var isCorrectNewTracking = confirm(question);
                        // if not correct, return and let user try again
                        if(!isCorrectNewTracking) return [false, "tracking: Incorrect tracking entered (" + newValue + ")."];
                    }

                    return [isGood, null];
                }


                function handleStmtDate(newValue) {
                    var isGood = true;
                    var errMsg = '';

                    // should be in the format ##-Ull (#: number; -: dash; U: uppercase letter; l: lowercase letter)
                    var numbers = newValue.slice(0, 2);
                    var dash = newValue[2];
                    var upper = newValue[3];
                    var lowers = newValue.slice(4, 6);

                    // must be 6 characters
                    if (newValue.length != 6) {
                        errMsg = "Must of in the format yy-Mmm (i.e. 24-Dec).  Entered: " + newValue;
                        isGood = false;
                        return [isGood, errMsg];
                    }

                    // first 2 characters need to be numbers
                    if (isNaN(numbers)) {
                        errMsg = "First two chars should be a 2-digit year.  Entered: " + newValue;
                        isGood = false;
                        return [isGood, errMsg];
                    }

                    // 3rd character must be a dash
                    if (dash != "-") {
                        errMsg = "Third char should be a dash.  Entered: " + newValue;
                        isGood = false;
                        return [isGood, errMsg];   
                    }

                    // 4th character must be an uppercase letter
                    if (!(upper === upper.toUpperCase() && /[A-Z]/.test(upper))) {
                        errMsg = "Fourth char should be an uppercase letter.  Entered: " + newValue;
                        isGood = false;
                        return [isGood, errMsg];
                    }

                    // 5th and 6th characters must be lower case letters
                    if (!(lowers === lowers.toLowerCase() && /^[a-z]+$/.test(lowers))) {
                        errMsg = "Fifth & sixth chars should be lowercase letters.  Entered: " + newValue;
                        isGood = false;
                        return [isGood, errMsg];  
                    }

                    // first two characters must be the last two digits of this year, last year or next year
                    var thisYear = new Date().getFullYear();
                    thisYear = thisYear.toString().substr(-2);
                    const lastYear = (parseInt(thisYear) - 1).toString();
                    const nextYear = (parseInt(thisYear) + 1).toString();
                    if (![thisYear, nextYear, lastYear].includes(numbers)) {
                        errMsg = "First two chars should be a 2-digit year (" + lastYear +", " + thisYear + ", or " + nextYear + ".  Entered: " + newValue;
                        isGood = false;
                        return [isGood, errMsg];   
                    }

                    // last 3 chars must be a month abbreviation
                    const month = upper + lowers;
                    const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                    if(!months.includes(month)) {
                        errMsg = "Last three chars should be a 3-digit month abbreviation.  Entered: " + newValue;
                        isGood = false;
                        return [isGood, errMsg]; 
                    }

                    return [isGood, errMsg];
                }


                // If the new toFrom isn't a previously used value, ask if it should be changed automatically.
                function insertTrans(transaction) {
                      
                    var url = '/transactions/insertTrans';

                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            transaction: transaction
                        },
                        dataType: 'json',

                        success: function(response) {
                            // no need to do anything
                            console.log("Transaction saved successfully: ", response);
                        },

                        error: function(xhr, status, error) {
                            var errorMsg = "Error saving transaction.";
                            console.error(errorMsg, xhr.responseJSON ? xhr.responseJSON.error : error);
                            alert(errorMsg + ": " + (xhr.responseJSON ? xhr.responseJSON.details : error));
                        }
                    });

                }


                // called for each cell to change to an input cell (where appropriate)
                function changeCellToInput($tdcell, $cell, thisElt, typeElt) {

                    // assume true until see otherwise
                    var isGood = true;
                    // for each cell that has no children (all the tdcells except the last "Edit" button)
                    // NOTE: The id (class newtransactionor transId), lastBalanced, or historical (spent, ytmBudget, yearBudget) are not editable
                    if( typeof $(thisElt).children(':first-child').prop('tagName') == 'undefined' 
                        && !$tdcell.prop("class").includes("newtransaction") 
                        && !$tdcell.prop("class").includes("transId")
                        && !$tdcell.prop("class").includes("lastBalanced")
                        && !$tdcell.prop("class").includes("spent")
                        && !$tdcell.prop("class").includes("ytmBudget")
                        && !$tdcell.prop("class").includes("yearBudget")
                        && !$tdcell.prop("class") == ''
                    ) 
                    {
                        // get type of input field
                        $class = "editable-cell " + $tdcell.prop("class");
                        $class = $class + "Edit";

                        // make it an input tdcell
                        var $input = $('<input>')
                            .attr('type', 'text')
                            .val($tdcell.text())
                            .addClass($class);
                            // .addClass('editable-cell');
                            $tdcell.empty().append($input);

                        // drop class from td
                        if(typeElt == "BUTTON") $cell.closest("td").removeClass();
                       
                    } else {
                        // change the "Edit" button to a "Save" button
                        if( $(thisElt).children(":first-child").text() == "Edit") {
                            
                            $(thisElt).children(':first-child')
                                .text('Save')
                                .removeClass("btn-primary")     // blue to green
                                .removeClass("editTransaction")
                                .addClass("btn-success")
                                .addClass("saveTransaction"); // change the id
                        }

                    }
                }   // end of function changeCellToInput


                function getOrigValues($cell) {

                    var origTransDate = $cell.closest("tr").find('.transDate').text();
                    if(origTransDate == null) {
                        origTransDate = $cell.closest("tr").find('.transDateEdit').text();
                        var origClearDate = $cell.closest("tr").find('.clearDateEdit').text();
                        var origToFrom = $cell.closest("tr").find('.toFromEdit').text();
                        var origAmount = $cell.closest("tr").find('.amountEdit').text();
                        var origAmtMike = $cell.closest("tr").find('.amtMikeEdit').text();
                        var origAmtMaura = $cell.closest("tr").find('.amtMauraEdit').text();
                        var origMethod = $cell.closest("tr").find('.methodEdit').text();
                        var origCategory = $cell.closest("tr").find('.categoryEdit').text();
                        var origTracking = $cell.closest("tr").find('.trackingEdit').text();
                        var origStmtDate = $cell.closest("tr").find('.stmtDateEdit').text();
                        var origTotalAmt = $cell.closest("tr").find('.total_amtEdit').text();
                        var origTotalKey = $cell.closest("tr").find('.total_keyEdit').text();
                        var origNotes = $cell.closest("tr").find('.notesEdit').text();
                    } else {
                        var origClearDate = $cell.closest("tr").find('.clearDate').text();
                        var origToFrom = $cell.closest("tr").find('.toFrom').text();
                        var origAmount = $cell.closest("tr").find('.amount').text();
                        var origAmtMike = $cell.closest("tr").find('.amtMike').text();
                        var origAmtMaura = $cell.closest("tr").find('.amtMaura').text();
                        var origMethod = $cell.closest("tr").find('.method').text();
                        var origCategory = $cell.closest("tr").find('.category').text();
                        var origTracking = $cell.closest("tr").find('.tracking').text();
                        var origStmtDate = $cell.closest("tr").find('.stmtDate').text();
                        var origTotalAmt = $cell.closest("tr").find('.total_amt').text();
                        var origTotalKey = $cell.closest("tr").find('.total_key').text();
                        var origNotes = $cell.closest("tr").find('.notes').text();
                    }
                    return [
                        origTransDate,
                        origClearDate,
                        origToFrom,
                        origAmount,
                        origAmtMike,
                        origAmtMaura,
                        origMethod,
                        origCategory,
                        origTracking,
                        origStmtDate,
                        origTotalAmt,
                        origTotalKey,
                        origNotes
                    ];

                }   // end of function getOrigValues


                function changeCellsToInputs($cell, origTransDate = null, origClearDate = null, origToFrom = null, origAmount = null, origAmtMike = null, origAmtMaura = null, origMethod = null, origCategory = null, origTracking = null, origStmtDate = null, origTotalAmt = null, origTotalKey = null, origNotes = null) {
                    // change all the cells to inputs, except the "Edit" button
                    if($cell.prop('tagName') == "BUTTON") {
                        $cell.parent().parent().find('td').each(function(index, td) {
                        
                            // What's the tag of the first child (undefined for all but the last, which is "BUTTON")
                            // console.log( "index: " + index + ": " + $cell.children(':first-child').prop('tagName'));
                            
                            // get the current cell
                            var $tdcell = $(this);
                            
                            changeCellToInput($tdcell, $cell, this, "BUTTON");

                        });
                    } else {
                        // tr passed in
                        $cell.find('td').each(function(index, td) {
                            // get the current cell
                            var $tdcell = $(this);
                            
                            changeCellToInput($tdcell, $cell, this, "TR");
                            
                        });
                    }

                    // listen for changes to each input field

                    // transDate
                    $('#editTransactionsTable').off('change', '.transDateEdit').on('change', '.transDateEdit', function(e) {
                        e.stopPropagation();

                        var $input = $(this);
                        var newValue = $input.val();
                        var fieldClass = $input.prop("class");
                        $("#errorMsg").text("");

                        // check trans_date and clear_date
                        // verifyDate returns a valid date string (could be "") or false if not valid,
                        if(fieldClass.includes('editable-cell')) {
                                
                            var fieldClassClicked = fieldClass;
                            // trans date can NOT have a null or '' date
                            var nullOK = false;

                            isGood = verifyDate(newValue, "trans-date", nullOK);
                            if(isGood === false) {
                                $input.css("background-color", "yellow").val(origTransDate);
                            } else {
                                $input.css("background-color", "white").val(isGood);
                                $("#errorMsg").text("");
                            }
                        }
                    });

                    // clearDate
                    $("#editTransactionsTable").off('change', '.clearDateEdit').on('change', '.clearDateEdit', function(e) {
                        e.stopPropagation();
 
                        var $input = $(this);
                        var newValue = $input.val();
                        var fieldClass = $input.prop("class");
                        $("#errorMsg").text("");

                        // check trans_date and clear_date
                        // verifyDate returns a valid date string (could be "") or false if not valid,
                        if(fieldClass.includes('editable-cell')) {

                            var fieldClassClicked = fieldClass;
                            // clear date can have a null or '' date
                            var nullOK = true;

                            isGood = verifyDate(newValue, "clear-date", nullOK);
                            if(isGood === false) {
                                $input.css("background-color", "yellow").val(origClearDate);
                            } else {
                                $input.css("background-color", "white").val(isGood);
                                $("#errorMsg").text("");
                            }
                        }
                    });

                    // toFrom
                    $("#editTransactionsTable").off('change', '.toFromEdit').on('change', '.toFromEdit', function(e) {
                        e.stopPropagation();
      
                        var $input = $(this);
                        var newValue = $input.val();
                        $("#errorMsg").text("");

                        var account = $("#accountName").text();
                        var accountId;
                        if(account == "all") {
                            accountId = $input.parent().parent().find(".accountIdEdit").val();
                        } else {
                            accountId = $("#accountId").text();
                        }

                        [isGood, errorMsg] = handleToFrom(newValue, account, accountId, toFroms, toFromAliases, origToFrom);
                        if(isGood === false) {
                            $("#errorMsg").text(errorMsg);
                            $input.css("background-color", "yellow").val(origToFrom);
                        } else if(isGood != true) {
                            $input.css("background-color", "white").val(isGood);
                        } else {
                            $("#errorMsg").text("");
                        }

                        // fill in defaults

                        // get default
                        $.ajax({
                            url: '/transactions/getDefaults/' + account + "/" + newValue,
                            type: 'GET',
                            dataType: 'json',
                            data: {
                                _token: "{{ csrf_token() }}"
                                // totalKey: total_key
                            },
                            success: function(response) {

                                if(response != null && Object.keys(response).length !== 0) {
                                    $input.parent().parent().find(".category").find("input").val(response['category']);

                                    // get any extra defaults and process them (if they exist)
                                    var extraDefaults = JSON.parse(response['extraDefaults']);
                                    if(extraDefaults != null) {

                                        // fill in the default notes
                                        if('notes' in extraDefaults) {
                                            $input.parent().parent().find(".notesEdit").val(extraDefaults['notes']);
                                        }

                                        // fill in the default tracking
                                        if('tracking' in extraDefaults) {
                                            $input.parent().parent().find(".trackingEdit").val(extraDefaults['tracking']);
                                        }

                                        // fill in the default method
                                        if('method' in extraDefaults) {
                                            $input.parent().parent().find(".methodEdit").val(extraDefaults['method']);
                                        }

                                        // create default splits
                                        // If this is a number, create that many splits (no default categories, etc)
                                        // If this is an array, create a split for each category in the array
                                        if('splits' in extraDefaults) {
                                            // If this is a number, create that many splits (no default categories, etc)
                                            if(typeof extraDefaults['splits'] == 'number') {
                                                // make this many splits, but no additional changes (blank categories)
                                                var numberSplits = extraDefaults['splits'];
                                                
                                                // add "xxx" for total_key placeholder; to be changed when the first split is saved
                                                $input.parent().parent().find(".total_keyEdit").val("xxx");

                                                // clone original transaction
                                                var $newTransaction = $input.parent().parent().clone();

                                                // make sure id is null (since it's new)
                                                $newTransaction.attr('data-id', 'null');
                                                $newTransaction.find('td').each(function(index, td) {
                                                    var $cell = $(td);
                                                    switch ($cell.prop('class')) {

                                                        case 'transId': 
                                                            $cell.text('null');
                                                            break;

                                                        case 'undefined':
                                                            $cell.children(':first-child').attr('data-id', 'null');
                                                            break;

                                                        case '':
                                                            $cell.children(':first-child').attr('data-id', 'null');
                                                            break;
                                                            
                                                        default:
                                                            $cell.text('');
                                                    }
                                                    
                                                });
                                                // make it edittable
                                                changeCellsToInputs($newTransaction);

                                                // put values for trans_date, clear_date, toFrom in cloned transaction
                                                var toFrom = $input.parent().parent().find(".toFromEdit").val();
                                                $newTransaction.find(".toFromEdit").val(toFrom);
                                                
                                                var transDate = $input.parent().parent().find(".transDateEdit").val();
                                                $newTransaction.find(".transDateEdit").val(transDate);
                                                
                                                var clearDate = $input.parent().parent().find(".clearDateEdit").val();
                                                $newTransaction.find(".clearDateEdit").val(clearDate);

                                                // total_key gets set to "xxx" as a placeholder
                                                $newTransaction.find(".total_keyEdit").val("xxx");
                                                
                                                // make sure category is blanked out in clones
                                                $newTransaction.find(".category").val("");

                                                // add this to the list of transactions for each split
                                                for(var i = 1; i <= numberSplits; i++) {
                                                    // prepend it to the list of transactions
                                                    $("tbody").prepend($newTransaction.clone());
                                                }

                                            // If this is an array, create a split for each category in the array
                                            } else {
                                                var numberSplits = extraDefaults['splits'].length;
                                                                   
                                                // add "xxx" for total_key placeholder; changed to a number when the first split is saved.
                                                $input.parent().parent().find(".total_keyEdit").val("xxx");

                                                // clone original transaction
                                                var $newTransaction = $input.parent().parent().clone();

                                                // make sure id is null (since it's new)
                                                $newTransaction.attr('data-id', 'null');
                                                $newTransaction.find('td').each(function(index, td) {
                                                    var $cell = $(td);
                                                    switch ($cell.prop('class')) {

                                                        case 'transId': 
                                                            $cell.text('null');
                                                            break;

                                                        case 'undefined':
                                                            $cell.children(':first-child').attr('data-id', 'null');
                                                            break;

                                                        case '':
                                                            $cell.children(':first-child').attr('data-id', 'null');
                                                            break;
                                                            
                                                        default:
                                                            $cell.text('');
                                                    }
                                                    
                                                });

                                                // make it edittable
                                                changeCellsToInputs($newTransaction);

                                                // put values for trans_date, clear_date, toFrom in cloned transaction
                                                var toFrom = $input.parent().parent().find(".toFromEdit").val();
                                                $newTransaction.find(".toFromEdit").val(toFrom);
                                                
                                                var transDate = $input.parent().parent().find(".transDateEdit").val();
                                                $newTransaction.find(".transDateEdit").val(transDate);
                                                
                                                var clearDate = $input.parent().parent().find(".clearDateEdit").val();
                                                $newTransaction.find(".clearDateEdit").val(clearDate);
                                                
                                                // notes
                                                var notes = $input.parent().parent().find(".notesEdit").val();
                                                $newTransaction.find(".notesEdit").val(notes);
                                                
                                                // tracking
                                                var multipleTracking, tracking;
                                                // if multiple tracking entered, there should be one for each category (including the original)
                                                // set original value here
                                                if(typeof extraDefaults['tracking'] == 'object') {
                                                    multipleTracking = true;
                                                    tracking = extraDefaults['tracking'][0];
                                                    $cell.find(".trackingEdit").val(tracking);  // needs to be in the original transaction on the page
                                                    $newTransaction.find(".trackingEdit").val(tracking);
                                                } else {
                                                    multipleTracking = false;
                                                    tracking = $input.parent().parent().find(".trackingEdit").val();
                                                    $newTransaction.find(".trackingEdit").val(tracking);
                                                }

                                                // stmtDate
                                                var stmtDate = $input.parent().parent().find(".stmtDateEdit").val();
                                                $newTransaction.find(".stmtDateEdit").val(stmtDate);

                                                // total_key gets set to "xxx" as a placeholder; set to number when first split saved
                                                $newTransaction.find(".total_keyEdit").val("xxx");
                                                
                                                // add this to the list of transactions for each split with the new category
                                                var newCategories = extraDefaults['splits'];
                                                var newTracking = extraDefaults['tracking'];
                                                newCategories.forEach( (category, idx) => {
                                                    // make sure category is blanked out in clones
                                                    $newTransaction.find(".categoryEdit").val(category);
                                                    if(multipleTracking) $newTransaction.find(".trackingEdit").val(newTracking[idx+1]);
                                                    // prepend it to the list of transactions
                                                    $("tbody").prepend($newTransaction.clone());
                                                });

                                            }
                                        }
                                    }  // end of if extraDefaults != null
                                }

                            },
                            error: function(xhr, status, error) {
                                var errorMsg = "Error getting defaults for toFrom: " + newValue;
                                console.error(errorMsg, error);
                                alert(errorMsg, error);
                            }
                        });
                    });

                    // amount
                    $("#editTransactionsTable").off('change', '.amountEdit').on('change', '.amountEdit', function(e) {
                        e.stopPropagation();

                        var $input = $(this);
                        var newValue = $input.val();
                        $("#errorMsg").text("");

                        isGood = verifyAmount(newValue);
                        if(!isGood) {
                            errorMsg = 'amount: Amount must be a number with no "$". Entered: ' + newValue;
                            $("#errorMsg").text(errorMsg);
                            $input.css("background-color", "yellow").val(origAmount);
                        } else {
                            $("#errorMsg").text("");
                            $input.css("background-color", "white");
                        }

                        // handle amtMike/amtMaura if amount is changed
                        // thisRcdCategory gets the category. MikeSpending, MauraSpending handled diffeently from the rest.
                        var thisRcdCategory = $input.parent().parent().find(".categoryEdit").first().val();

                        // if not MikeSpendinng or MauraSpending, split between amtMike & amtMaura
                        if(!["MikeSpending", "MauraSpending"].includes(thisRcdCategory)) {

                            // recalc Mike & Maura splits
                            var $amtMikeEdit = $input.parent().parent().find('.amtMikeEdit').first();
                            $amtMikeEdit.val($input.val() / 2);

                            var $amtMauraEdit = $input.parent().parent().find('.amtMauraEdit').first();
                            $amtMauraEdit.val($input.val() / 2);

                        // if MikeSpending, total is set to amtMike
                        } else if( thisRcdCategory == "MikeSpending") {
                            var $amtMikeEdit = $input.parent().parent().find('.amtMikeEdit').first();
                            $amtMikeEdit.val($input.val());
                            var $amtMauraEdit = $input.parent().parent().find('.amtMauraEdit').first();
                            $amtMauraEdit.val(0);

                        // if MauraSpending, total is set to amtMaura
                        } else if( thisRcdCategory == "MauraSpending") {
                            var $amtMauraEdit = $input.parent().parent().find('.amtMauraEdit').first();
                            $amtMauraEdit.val($input.val());
                            var $amtMikeEdit = $input.parent().parent().find('.amtMikeEdit').first();
                            $amtMikeEdit.val(0);
                        }


                        // NOTE:  This doesn't handle the transactions that are NOT in edit mode


                        // if there is a total_key, adjust total_amt for all records with that key
                        // if any of the records are not in edit mode, put them in edit mode.  Throw an alert.
                        var totalKey = $input.parent().parent().find(".total_keyEdit").val();

                        if($input.parent().parent().find(".total_keyEdit").val() != '') {
                            // get all the total_key input values
                            var totalKeyElmts = $input.parent().parent().parent().find('.total_keyEdit');

                            // get new total amount (sum of current amounts)
                            var newTotalAmt = 0;
                            var amount;
                            totalKeyElmts.each( (index, totalKeyElt) => {
                                if($(totalKeyElt).val() == totalKey) {
                                    // get amount for this transaction (with matching totalKey)
                                    amount = $(totalKeyElt).parent().parent().find('.amountEdit').val();
                                    // add to total amount
                                    newTotalAmt += Number(amount);
                                }
                            });

                            // update total amount for each transaction with the matching total_key
                            totalKeyElmts.each( (index, totalKeyElt) => {
                                if($(totalKeyElt).val() == totalKey) {
                                    $(totalKeyElt).parent().parent().find('.total_amtEdit').val(newTotalAmt);
                                }
                            });
                        }
                                               
                    });

                    // amtMike
                    $("#editTransactionsTable").off('change', '.amtMikeEdit').on('change', '.amtMikeEdit', function(e) {
                        e.stopPropagation();

                        var $input = $(this);
                        var newValue = $input.val();
                        $("#errorMsg").text("");

                        isGood = verifyAmount(newValue);
                        // if the amount entered is not valid,
                        //      display an error msg
                        //      and put the original amt back on the page.
                        if(!isGood) {
                            errorMsg = 'amtMike: Amount must be a number with no "$". Entered: ' + newValue;
                            $("#errorMsg").text(errorMsg);
                            $('.amtMikeEdit').prop('disabled', true);   // turn triggers off
                            $input.css("background-color", "white").val(origAmount);
                            $('.amtMikeEdit').prop('disabled', false);   // turn triggers back on
                            newValue = origAmount
                        } else {
                            $("#errorMsg").text("");
                            $input.css("background-color", "white");
                        }

                        // handle amount if amtMike changed

                        // If category is 
                        // -- "MauraSpending", don't allow amtMike change (must be 0)
                        // -- "MikeSpending", change total amount instead of amtMaura (with warning)
                        // thisRcdCategory gets the category. MikeSpending, MauraSpending handled diffeently from the rest.
                        var thisRcdCategory = $input.parent().parent().find(".categoryEdit").val();
                        
                        if(thisRcdCategory == "MauraSpending") {
                            alert("Category is MauraSpending, so amtMike must be 0");
                            $('.amtMikeEdit').prop('disabled', true);   // turn triggers off
                            $input.val("0");

                        } else if (thisRcdCategory == "MikeSpending") {
                            // change amount to equal amtMike
                            var amtChangeOK = confirm("OK to change the total amount to " + newValue + "?");
                            // get change amount element value to newValue
                            if(amtChangeOK) $input.parent().parent().find(".amountEdit").val(newValue);
                            else {
                                $('.amtMikeEdit').prop('disabled', true);   // turn triggers off
                                $input.val(origAmtMike);
                            }
                            
                        } else {
                            // change amtMaura to equal amount - amtMike
                            // get amtMaura element
                            var $amtMaura = $input.parent().parent().find(".amtMauraEdit");
                            
                            // get total amount value
                            var amount = $input.parent().parent().find(".amountEdit").val();
                            
                            // newValue is Mike's amt
                            // calc new amtMaura and put on page
                            var newAmtMauraVal = (Number(amount) - Number(newValue)).toFixed(6);
                            $('.amtMauraEdit').prop('disabled', true);   // turn triggers off
                            $amtMaura.val(newAmtMauraVal);
                            $('.amtMauraEdit').prop('disabled', false);   // turn triggers back on
                        }
                        $('.amtMikeEdit').prop('disabled', false);   // turn triggers back on


                        // handle splitTotal if amount is changed

                    });

                    // amtMaura
                    $("#editTransactionsTable").off('change', '.amtMauraEdit').on('change', '.amtMauraEdit', function(e) {
                        e.stopPropagation();

                        var $input = $(this);
                        var newValue = $input.val();
                        $("#errorMsg").text("");

                        isGood = verifyAmount(newValue);
                        // if the amount entered is not valid,
                        //      display an error msg
                        //      and put the original amt back on the page.
                        if(!isGood) {
                            errorMsg = 'amtMaura: Amount must be a number with no "$". Entered: ' + newValue;
                            $("#errorMsg").text(errorMsg);
                            $('.amtMauraEdit').prop('disabled', true);   // turn triggers off
                            $input.css("background-color", "yellow").val(origAmount);
                            $('.amtMauraEdit').prop('disabled', false);   // turn triggers back on
                            newValue = origAmount
                        } else {
                            $("#errorMsg").text("");
                            $input.css("background-color", "white");
                        }

                        // handle amount if amtMaura changed

                        // If category is 
                        // -- "MikeSpending", don't allow amtMaura change (must be 0)
                        // -- "MauraSpending", change total amount instead of amtMike (with warning)
                        // thisRcdCategory gets the category. MauraSpending, MikeSpending handled diffeently from the rest.
                        var thisRcdCategory = $input.parent().parent().find(".categoryEdit").val();
                        
                        if(thisRcdCategory == "MikeSpending") {
                            alert("Category is MikeSpending, so amtMaura must be 0");
                            $('.amtMauraEdit').prop('disabled', true);   // turn triggers off
                            $input.val("0");
                            $('.amtMauraEdit').prop('disabled', false);   // turn triggers back on

                        } else if (thisRcdCategory == "MauraSpending") {
                            // change amount to equal amtMaura
                            var amtChangeOK = confirm("OK to change the total amount to " + newValue + "?");
                            // get change amount element value to newValue
                            if(amtChangeOK) $input.parent().parent().find(".amountEdit").val(newValue);
                            else {
                                $('.amtMauraEdit').prop('disabled', true);   // turn triggers off
                                $input.val(origAmtMaura);
                                $('.amtMauraEdit').prop('disabled', false);   // turn triggers back on
                            }
                            
                        } else {
                            // change amtMike to qual amount - amtMaura
                            // get amtMike element
                            var $amtMike = $input.parent().parent().find(".amtMikeEdit");

                            // get total amount value
                            var amount = $input.parent().parent().find(".amountEdit").val();
                            
                            // newValue is Maura's amt
                            // calc new amtMike and put on page
                            var newAmtMikeVal = (Number(amount) - Number(newValue)).toFixed(6);
                            $('.amtMikeEdit').prop('disabled', true);   // turn triggers off
                            $amtMike.val(newAmtMikeVal);
                            $('.amtMikeEdit').prop('disabled', false);   // turn triggers back on
                        }
                        $('.amtMauraEdit').prop('disabled', false);   // turn triggers back on

                        // handle splitTotal if amount is changed
                        // if(isGood) {

                        //     // recalc splitTotal - add all amount input fields
                        //     splitTotal = 0;
                        //     // var $theseTransactions = $(thisElt).parent().parent().parent();
                        //     var $theseTransactions = $input.parent().parent().parent();
                        //     $theseTransactions.find('td').each(function(index, td) {
                        //         if( $(td).attr('class') == "amount" && $(td).children(':first-child').prop('tagName') == 'INPUT') {
                        //             splitTotal += Number($(td).children(':first-child').val());
                        //         };
                        //     });

                        //     // if the total is off, turn it red and note the difference to put on the page
                        //     var thisTotalAmt = $input.parent().parent().find(".total_amt").children(":first-child").val();
                        //     var totalDiffText = ""; // assume no difference

                        //     if(splitTotal != thisTotalAmt) {
                        //         var totalDiffText = " (" + (thisTotalAmt - splitTotal) + ")";
                        //         $("#splitTotal").css("color","red");

                        //     // otherwise, change the color back to skyblue
                        //     } else {
                        //         $("#splitTotal").css("color","skyblue");
                        //     }

                        //     // put the splitTotal in the amount header (span id = splitTotal)
                        //     $('#splitTotal').text("Split Total: " + splitTotal + totalDiffText);
                        // }
                    });

                    // method
                    $("#editTransactionsTable").off('change', '.methodEdit').on('change', '.methodEdit', function(e) {
                        e.stopPropagation();

                        var $input = $(this);
                        var newValue = $input.val();
                        $("#errorMsg").text("");

                        const methodMaxLength = 10;
                        isGood = verifyVarCharLength(newValue, methodMaxLength);
                        if(!isGood) {
                            const truncMethod = newValue.slice(0, methodMaxLength);
                            const question = "Method cannot be more than " + methodMaxLength + " chars long. OK to trunc to " + truncMethod + "?";
                            const OKtoTrunc = confirm(question);
                            if(OKtoTrunc) {
                                $input.css("background-color", "white").val(truncMethod);
                            } else {
                                errorMsg = "Method cannot be more than " + methodMaxLength + " chars long. Entered: " + newValue + ".";
                                $input.css("background-color", "yellow").val(origMethod);
                                $("#errorMsg").text(errorMsg);
                            }
                        } else {
                            $("#errorMsg").text("");
                            $input.css("background-color", "white");
                        }

                    });

                    // category
                    $("#editTransactionsTable").off('change', '.categoryEdit').on('change', '.categoryEdit', function(e) {
                        e.stopPropagation();
     
                        var $input = $(this);
                        var newValue = $input.val();
                        $("#errorMsg").text("");

                        isGood = categories.includes(newValue);
                        if(isGood === false) {
                            errorMsg = "Not a valid category (entered: "  + newValue + ")";
                            $("#errorMsg").text(errorMsg);
                            $input.css("background-color", "yellow").val(origCategory);

                        // amtMike & amtMaura may need to be changed..
                        // If category changes from
                        //      any category to MauraSpending
                        //          - set amtMaura to what amount is, and amtMike to 0
                        //      any category to MikeSpending
                        //          - set amtMike to what amount is, and amtMaura to 0
                        //      MauraSpending or MikeSpending to any category (except MikeSpending or MauraSpending - see above)
                        //          - split amount between amtMike and amtMaura
                        } else {
                            $("#errorMsg").text("");
                            $input.css("background-color", "white");

                            if(newValue == "MauraSpending") {
                                // get amount
                                var amount = $input.parent().parent().find(".amountEdit").val();
                                // set amtMaura value to amount
                                $input.parent().parent().find(".amtMauraEdit").val(amount);
                                $input.parent().parent().find(".amtMikeEdit").val(0);
                            } else if (newValue == "MikeSpending") {
                                // get amount
                                var amount = $input.parent().parent().find(".amountEdit").val();
                                // set amtMike value to amount
                                $input.parent().parent().find(".amtMikeEdit").val(amount);
                                $input.parent().parent().find(".amtMauraEdit").val(0)
                            } else if (["MikeSpending", "MauraSpending"].includes(origCategory)) {
                                // get 1/2 amount
                                var halfAmount = $input.parent().parent().find(".amountEdit").val() / 2;
                                // set amtMike and amtMaura each to 1/2 amount
                                $input.parent().parent().find(".amtMauraEdit").val(halfAmount);
                                $input.parent().parent().find(".amtMikeEdit").val(halfAmount);
                            }

                        }
                        // put else here to implement adding a new category.
                        // Would need to change column definition to allow the new category.

                    });

                    // tracking
                    $("#editTransactionsTable").off('change', '.trackingEdit').on('change', '.trackingEdit', function(e) {
                        e.stopPropagation();

                        var $input = $(this);
                        var newValue = $input.val();
                        $("#errorMsg").text("");

                        [isGood, errorMsg] = handleTracking(newValue, trackings, origTracking);
                        if(isGood === false) {
                            $("#errorMsg").text(errorMsg);
                            $input.css("background-color", "yellow").val(origTracking);
                        } else if (isGood !== true) {
                            $input.css("background-color", "white").val(isGood);
                        } else {
                            $input.css("background-color", "white");
                        }

                    });

                    // stmtDate
                    $("#editTransactionsTable").off('change', '.stmtDateEdit').on('change', '.stmtDateEdit', function(e) {
                        e.stopPropagation();

                        var $input = $(this);
                        var newValue = $input.val();
                        $("#errorMsg").text("");

                        [isGood, errorMsg] = handleStmtDate(newValue);
                        if(!isGood) {
                            $("#errorMsg").text(errorMsg);
                            $input.css("background-color", "yellow").val(origStmtDate);
                        } else {
                            $("#errorMsg").text("");
                            $input.css("background-color", "white");
                        }

                    });

                    // total_amt
                    $("#editTransactionsTable").off('change', '.total_amtEdit').on('change', '.total_amtEdit', function(e) {
                        e.stopPropagation();

                        var $input = $(this);
                        var newValue = $input.val();
                        $("#errorMsg").text("");

                        isGood = verifyAmount(newValue);
                        if(!isGood) {
                            errorMsg = 'total_amt: Total amount must be a number with no "$". Entered: ' + newValue;
                            $("#errorMsg").text(errorMsg);
                            $input.css("background-color", "yellow").val(origTotalAmt);
                        } else {
                            $("#errorMsg").text("");
                            $input.css("background-color", "white");
                        }


                    });  
                    
                    
                    // total_key
                    $("#editTransactionsTable").off('change', '.total_keyEdit').on('change', '.total_keyEdit', function(e) {
                        e.stopPropagation();

                        var $input = $(this);
                        var newValue = $input.val();
                        $("#errorMsg").text("");

                        const totalKeyMaxLength = 6;
                        isGood = verifyVarCharLength(newValue, totalKeyMaxLength);
                        if(!isGood) {
                            const truncTotalKey = newValue.slice(0, totalKeyMaxLength);
                            const question = "Total_key cannot be more than " + totalKeyMaxLength + " chars long. OK to trunc to " + truncTotalKey + "?";
                            const OKtoTrunc = confirm(question);
                            if(OKtoTrunc) {
                                $input.css("background-color", "white").val(truncTotalKey);
                                $("#errorMsg").text("");
                            } else {
                                errorMsg = "Total_key cannot be more than " + totalKeyMaxLength + " chars long. Entered: " + newValue + ".";
                                $("#errorMsg").text(errorMsg);
                                $input.css("background-color", "yellow").val(origTotalKey);
                            }
                        } else {
                            $input.css("background-color", "white");
                        }

                    });


                    // notes
                    $("#editTransactionsTable").off('change', '.notesEdit').on('change', '.notesEdit', function(e) {
                        e.stopPropagation();

                        var $input = $(this);
                        var newValue = $input.val();
                        $("#errorMsg").text("");

                        const notesMaxLength = 100;
                        isGood = verifyVarCharLength(newValue, notesMaxLength);
                        if(!isGood) {
                            const truncNotes = newValue.slice(0, notesMaxLength);
                            const question = "Notes cannot be more than " + notesMaxLength + " chars long. OK to trunc to " + truncNotes + "?";
                            const OKtoTrunc = confirm(question);
                            if(OKtoTrunc) {
                                $input.css("background-color", "white").val(truncNotes);
                            } else {
                                errorMsg = "Notes cannot be more than " + notesMaxLength + " chars long. Entered: " + newValue + ".";
                                $input.css("background-color", "yellow").val(origNotes);
                                $("#errorMsg").text(errorMsg);
                            }
                        } else {
                            $("#errorMsg").text("");
                            $input.css("background-color", "white");
                        }

                    });

                }   // end function changeCellsToInputs


                // get begin and end dates in expected format on page
                formatDefaultDate("#beginDate");
                formatDefaultDate("#endDate");

                // handle changing beginning date
                $('#beginDate').on('change', function() {
                    newBeginDate = verifyDate($(this).val(), "begin date");
                    $(this).val(newBeginDate);
                });
                
                // handle changing end date
                $('#endDate').on('change', function() {
                    newEndDate = verifyDate($(this).val(), "end date");
                    $(this).val(newEndDate);
                });
            
                // refresh transactions
                $('#refreshTransactions').on('click', function(e) {
                    e.preventDefault();

                    // get information needed from page for new query to transactions table
                    const accountName = $("#accountName").text();
                    var beginDate = $("#beginDate").val();
                    var endDate = $("#endDate").val();

                    // put dates in yyyy-mm-dd format for query, if needed
                    //      for beginDate and endDate
                    const beginHasDelimiterSlash = beginDate.includes("/");
                    var beginDelimiter;
                    if(beginHasDelimiterSlash) beginDelimiter = '/';
                    else beginDelimiter = '-';
                    
                    const endHasDelimiterSlash = endDate.includes("/");
                    var endDelimiter;
                    if(endHasDelimiterSlash) endDelimiter = '/';
                    else endDelimiter = '-';

                    // change format, if needed, for beginDate and endDate
                    var newDate = beginDate.split(beginDelimiter);
                    if(beginHasDelimiterSlash) beginDate = newDate[2] + "-" + newDate[0] + "-" + newDate[1];
                    else beginDate = newDate.join('-');

                    var newDate = endDate.split(endDelimiter);
                    if(endHasDelimiterSlash) endDate = newDate[2] + "-" + newDate[0] + "-" + newDate[1];
                    else endDate = newDate.join('-');
                    
                    // url to load page with new dates
                    const url = `/accounts/${accountName}/${beginDate}/${endDate}`;

                    // load new page
                    window.location.href = url;

                });


                // Back to Accounts button
                $('#backToAccount').on('click', function(e) {
                    e.preventDefault();

                    // url to load page with new dates
                    const url = '/accounts';

                    // load new page
                    window.location.href = url;
                });


                // Upload button
                $('#uploadTransactions').on('click', function(e) {
                    e.preventDefault();

                    // url to load page with new dates
                    const accountName = $("#accountName").text();
                    const url = `/accounts/${accountName}/upload`;

                    // load new page
                    window.location.href = url;
                });


                // Add a transaction
                $('#addTransaction').on('click', function(e) {
                    e.preventDefault();

                    // window.location.href = "/transactions/add";
                    
                    // clone the first transaction
                    $firstTransaction = $("tbody").children(':first-child');
                    $newTransaction = $firstTransaction.clone();

                    // clear out info for new transaction
                    $newTransaction.attr('data-id', 'null');
                    $newTransaction.find('td').each(function(index, td) {
                        var $cell = $(td);
                        switch ($cell.prop('class')) {

                            case 'transId': 
                                $cell.text('null');
                                break;

                            case 'undefined':
                                $cell.children(':first-child').attr('data-id', 'null');
                                break;

                            case '':
                                $cell.children(':first-child').attr('data-id', 'null');
                                break;
                                
                            default:
                                $cell.text('');
                        }
                            
                    });
                        
                    // set stmtDate based on account

                    // get last statement dates from hidden field on page
                    // note: if not in this array, defaults to the last day of the month
                    var lastStmtDates = $("#lastStmtDates").val();
                    lastStmtDates = JSON.parse(lastStmtDates);
            
                    // get accountName
                    var accountName = $("#accountName").text();

                    // if transactions have multiple accounts, can't determine the statement date.
                    // can do this when the account is changed.
                    if(accountName != "all") {
                        lastStmtDate = lastStmtDates.find(item => item.accountName === accountName);

                        // get current date info
                        const currentDate = new Date();
                        var year = currentDate.getFullYear() - 2000;
                        var month = currentDate.toLocaleString('default', { month: 'short' });
                        var day = currentDate.getDate();

                        if(lastStmtDate) {
                            // this year = month if today is before the cutoff date,
                            // otherwise it's next month
                            var cutoffdate = lastStmtDate['lastStmtDate'];

                            if(day < cutoffdate) {
                                $newTransaction.find(".stmtDate").text(year.toString() + "-" + month);
                            } else if(month == 'Dec') {
                            // if it's after the cutoff date, and this is December, the stmtDate is jan of next year
                                $newTransaction.find(".stmtDate").text((year+1).toString() + "-" + 'Jan');
                            } else {
                            // if it's after the cutoff day, add a month.
                                const nextMonth = new Date(currentDate.setMonth(currentDate.getMonth() + 1));
                                const nextMonthAbbrev = nextMonth.toLocaleString('default', { 
                                    month: 'short',
                                    year: 'numeric',
                                    day: 'numeric'
                                }).split(' ')[0];
                                $newTransaction.find(".stmtDate").text(year.toString() + "-" + nextMonthAbbrev);
                            }
                        } else {
                            // default to last day; so stmtDate defaults to this year - month
                            $newTransaction.find(".stmtDate").text(year.toString() + "-" + month);
                        }
                    }

                    // make it edittable
                    changeCellsToInputs($newTransaction);

                    // prepend it to the list of transactions
                    $("tbody").prepend($newTransaction);



                })


                // listen for "Edit" being clicked
                $(document).on('click', '.editTransaction', function(e) {
                    e.preventDefault();
                    
                    var $cell = $(this);

                    // get the id of the transaction being editted
                    var id = $(this).data('id');
                    console.log("id (editting): " + id);

                    // get the original account, toFrom, amount
                    // var origAccount = $cell.closest("tr").find('.account').text();
                    // if (origAccount == '') origAccount = "{{$accountName}}";

                    var origTransDate,
                        origClearDate,
                        origToFrom,
                        origAmount,
                        origAmtMike,
                        origAmtMaura,
                        origMethod,
                        origCategory,
                        origTracking,
                        origStmtDate,
                        origTotalAmt,
                        origTotalKey,
                        origNotes;
                    [
                        origTransDate,
                        origClearDate,
                        origToFrom,
                        origAmount,
                        origAmtMike,
                        origAmtMaura,
                        origMethod,
                        origCategory,
                        origTracking,
                        origStmtDate,
                        origTotalAmt,
                        origTotalKey,
                        origNotes
                    ] = getOrigValues($cell);

                    // change all the cells to inputs, except the "Edit" button
                    changeCellsToInputs($cell, origTransDate, origClearDate, origToFrom, origAmount, origAmtMike, origAmtMaura, origMethod, origCategory, origTracking, origStmtDate, origTotalAmt, origTotalKey, origNotes);
                   
                });
                
                
                // listen for "Save" being clicked
                $(document).on('click', '.saveTransaction', function(e) {
                    e.preventDefault();
                    
                    // get the id
                    var id = $(this).data('id');
                    if(id == 'null') id = null;
                    var thisElement = this;

                    // are the values in the record good
                    try {

                        $record = $(thisElement).parent().parent();
                        // Individual values should be good, since they are all checked as they are entered.
                        
                        // Check to make sure they "add up"...

                        // get account
                        var account = $record.find('.account').text();
                        if (account == '') account = "{{$accountName}}";
                        
                        // get needed data from record
                        var category = $record.find('.category').children(':first-child').val();
                        var amount = Number($record.find('.amount').children(':first-child').val());
                        var amtMike = Number($record.find('.amtMike').children(':first-child').val());
                        var amtMaura = Number($record.find('.amtMaura').children(':first-child').val());
                        var total_key = $record.find('.total_key').children(':first-child').val();
                        var total_amt = Number($record.find('.total_amt').children(':first-child').val());
                        var bucket = $record.find('.bucket').children(':first-child').val();

                        // console.log("\naccount: ", account);
                        // console.log("category: ", category);
                        // console.log("amount: ", amount);
                        // console.log("amtMike: ", amtMike);
                        // console.log("amtMaura: ", amtMaura);
                        // console.log("total_key: ", total_key);
                        // console.log("total_amt: ", total_amt);
                        // console.log("bucket: ", bucket);

                        var errMsg;

                        // If category is MauraSpending, amtMaura should be amount and amtMike should be 0
                        if(category == "MauraSpending") {
                            if(amtMike != 0) {
                                errMsg = "amtMike should be 0 for category MauraSpending";
                                alert(errMsg + "\nTransaction not updated in database.");
                                throw errMsg;
                            }
                            if(amtMaura != amount) {
                                errMsg = "amtMaura should equal amount for category MauraSpending";
                                alert(errMsg + "\nTransaction not updated in database.");
                                throw errMsg;
                            }
                        }

                        // If category is MikeSpending, amtMike should be amount and amtMaura should be 0
                        else if(category == "MikeSpending") {
                            if(amtMaura != 0) {
                                errMsg = "amtMaura should be 0 for category MikeSpending";
                                alert(errMsg + "\nTransaction not updated in database.");
                                throw errMsg;
                            }
                            if(amtMike != amount) {
                                errMsg = "amtMike should equal amount for category MikeSpending";    
                                alert(errMsg + "\nTransaction not updated in database.");
                                throw errMsg;
                            }
                        }

                        // amtMike + amtMaura = amount
                        if(amount != amtMike + amtMaura) {
                            errMsg = "amtMike + amtMaura should = amount";
                            alert(errMsg + "\nTransaction not updated in database.");
                            throw errMsg;
                        }

                        // If bucket is on the page (for DiscSavings), it should be filled in
                        if(typeof bucket !== 'undefined') {
                            if(bucket == '' || bucket == null) {
                                errMsg = "bucket needs to have a value";
                                alert(errMsg + "\nTransaction not updated in database.");
                                throw errMsg;
                            }
                        }

                        // OK to write record (will update total_key if this is the first of a group of split transactions to be saved)
                        updateTransactionRecord($record);
                        
                        // change edittable cells in record to non-edittable
                        makeNotEdittable(this);

                    } catch (error) {
                        console.error("Error checking record: ", error);
                    }                  
                                    
                });
                
                // listen for "Split" being clicked
                $(document).on('click', '.splitTransaction', function(e) {
                    e.preventDefault();

                    var $cell = $(this);    // used to change origTransaction to input fields
                    var $origTransaction = $(this).parent().parent();

                    // update original transaction on page when it's changed
                    // create new transaction for split
                    // click "Save" button to save each transaction

                    // needed to link the two new transactions
                    var total_key, total_this_split, total_all_splits, useEdit;

                    total_this_split = $(this).parent().parent().find(".amount").text();
                    useEdit = '';
                    if(total_this_split == '') {
                        useEdit = "Edit";
                        total_this_split = $(this).parent().parent().find(".amountEdit").val();
                    }

                    if($(this).parent().parent().find(".total_keyEdit").val() != null) {
                        // useEdit = "Edit";
                        total_key = $(this).parent().parent().find(".total_keyEdit").val();
                        total_all_splits = $(this).parent().parent().find(".total_amtEdit").val();
                    } else {
                        // useEdit = '';
                        total_key = $(this).data('id').toString();
                        total_all_splits = total_this_split;
                    }

                    // amount, amtMike, amtMaura - all of these are div by 2, and need to be updated on page
                    var newAmount = total_this_split / 2;
                    $origTransaction.find(".amount").text(newAmount);  // change amount in original transaction
                    $origTransaction.find(".amount" + useEdit).val(newAmount);  // change amount in original transaction

                    // var newAmtMike = $origTransaction.find('.amtMike' + useEdit).text();
                    // if(newAmtMike == '') {
                        // newAmtMike = $origTransaction.find('.amtMike' + useEdit).val();
                    // }
                    newAmt = total_this_split / 4;
                    $origTransaction.find(".amtMike" + useEdit).text(newAmt);                 // change amtMike in original transaction
                    $origTransaction.find(".amtMike" + useEdit).val(newAmt);                 // change amtMike in original transaction
                    $origTransaction.find(".amtMaura" + useEdit).text(newAmt);               // change amtMaura in original transaction
                    $origTransaction.find(".amtMaura" + useEdit).val(newAmt);               // change amtMaura in original transaction

                    // var newAmtMaura = $origTransaction.find('.amtMaura' + useEdit).text();
                    // if(newAmtMaura == '') {
                    //     newAmtMaura = $origTransaction.find('.amtMaura' + useEdit).val();
                    // }

                    // if total_amt is not null, 
                    //   set total_amt & total_key in original transaction, so update on page
                    if($origTransaction.find(".total_").text() == '') {
                        $origTransaction.find(".total_amt").text(total_all_splits);
                        $origTransaction.find(".total_key").text(total_key);
                    }
                    
                    var origTransDate,
                        origClearDate,
                        origToFrom,
                        origAmount,
                        origAmtMike,
                        origAmtMaura,
                        origMethod,
                        origCategory,
                        origTracking,
                        origStmtDate,
                        origTotalAmt,
                        origTotalKey,
                        origNotes;
                    [
                        origTransDate,
                        origClearDate,
                        origToFrom,
                        origAmount,
                        origAmtMike,
                        origAmtMaura,
                        origMethod,
                        origCategory,
                        origTracking,
                        origStmtDate,
                        origTotalAmt,
                        origTotalKey,
                        origNotes
                    ] = getOrigValues($origTransaction);

                    changeCellsToInputs($origTransaction, origTransDate, origClearDate, origToFrom, origAmount, origAmtMike, origAmtMaura, origMethod, origCategory, origTracking, origStmtDate, origTotalAmt, origTotalKey, origNotes);
                    // NOTE:  original transaction gets updated when "Save" button hit

                    // clone splitTransaction on page with edittable cells
                    var $clonedTransaction = $origTransaction.clone();

                    // set id to null
                    // $clonedTransaction.find("tr").attr("data-id", "null");
                    $clonedTransaction.attr("data-id", "null");
                    $clonedTransaction.find(".transId").text("null");
                    $clonedTransaction.find(".saveTransaction").attr("data-id", "null");
                    $clonedTransaction.find(".splitTransaction").attr("data-id", "null");
                    $clonedTransaction.find(".deleteTransaction").attr("data-id", "null");

                    // add to page  --  gets saved when "Save" clicked on page.
                    $origTransaction.after($clonedTransaction);
                    
                });
                
                // listen for "Delete" being clicked
                $(document).on('click', '.deleteTransaction', function(e) {
                    e.preventDefault();
                    
                    var $row = $(this).closest('tr');
                    var id = $row.data('id');
                    var trans_date = $row.find('td:nth-child(2)').text();
                    var account = $row.find('td:nth-child(4)').text();
                    var toFrom = $row.find('td:nth-child(5)').text();
                    var amount = $row.find('td:nth-child(6)').text();

                    if (confirm("Are you sure you want to delete this transaction?"
                        + "\n - Id: " + id
                        + "\n - Trans date: " + trans_date
                        + "\n - Account: " + account
                        + "\n - toFrom: " + toFrom
                        + "\n - Amount: " + amount
                    )) {
                    
                        // handle delete a transaction
                        $.ajax({
                            url: '/transactions/delete/' + id,
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                id: id
                            },
                            success: function(response) {
                                console.log('Transaction deleted successfully:', response.message);
                                // remove the row from the page
                                $row.remove();
                            },
                            error: function(xhr, status, error) {
                                console.error("Error deleting record:", error);
                                alert('Failed to delete transaction.');
                            }
                        });
                    }
            
                });

                // when amount entered, fill in 1/2 amtMike, 1/2 amtMaura
                $("body").on('change', '.amount', function(e) {
                    e.preventDefault();

                    // get amount entered
                    var amount = $(this).val();
                    // fill in amtMike
                    $(this).parent().next().find("input").val(1/2*Number(amount));
                    // fill in amtMaura
                    $(this).parent().next().next().find("input").val(1/2*Number(amount));

                })
        
            });
        </script>
    </body>

</html>