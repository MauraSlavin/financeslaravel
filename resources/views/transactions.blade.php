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
        <h3>Cleared balance: {{ $clearedBalance }}</h3>
        <h3>Register balance: {{ $registerBalance }}</h3>
        <h3>Last Balanced: {{ $lastBalanced }}</h3>
        @if($upload)
            <h5>Transactions just loaded have an id in <span class="newtransaction">red</span>.</h5>
            <h5>Transactions that may be a duplicate have a background color of <span class="dupMaybe">yellow</span>.</h5>
        @endif
        <input type="hidden" id="accountNames"  name="accountNames"  value={{ json_encode($accountNames) }}>
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
        <p id="dateErrorMsg"></p>
        <!-- table -->
        <table id="editTransactionsTable">

            <!-- table headers -->
            <thead>
                <tr>
                    <th>id</th>
                    <th>trans_date</th>
                    <th>clear_date</th>
                    @if($accountName == 'all')
                        <th>account</th>
                    @endif
                    <th>toFrom</th>
                    <th>amount<br>
                        <span id="splitTotal" hidden>Split Tot: </span>
                    </th>
                    <th>amtMike</th>
                    <th>amtMaura</th>
                    <th>method</th>
                    <th>category</th>
                    <th>tracking</th>
                    <th>stmtDate</th>
                    <th>total_amt</th>
                    <th>total_key</th>
                    @if($accountName == 'DiscSavings' || $accountName == 'all')
                        <th>bucket</th>
                    @endif
                    <th>notes</th>
                    <th>lastBalanced</th>
                    <th>Spent</th>
                    <th>Budget thru this month</th>
                    <th>Full Year Budget</th>
                    <th>Edit/Save</th>
                    <th>Split</th>
                    <th>Delete</th>
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
                        @endif
                        <td class="toFrom">{{ $newTransaction["toFrom"] ?? NULL  }}</td>
                        <td class="amount">{{ $newTransaction["amount"] ?? NULL  }}</td>
                        <td class="amtMike">{{ $newTransaction["amtMike"] ?? NULL  }}</td>
                        <td class="amtMaura">{{ $newTransaction["amtMaura"] ?? NULL  }}</td>
                        <td class="method">{{ $newTransaction["method"] ?? NULL  }}</td>
                        <td class="category">{{ $newTransaction["category"] ?? NULL  }}</td>
                        <td class="tracking">{{ $newTransaction["tracking"] ?? NULL  }}</td>
                        <td class="stmtDate">{{ $newTransaction["stmtDate"] ?? NULL  }}</td>
                        <td class="total_amt">{{ $newTransaction["total_amt"] ?? NULL  }}</td>
                        <td class="total_key">{{ $newTransaction["total_key"] ?? NULL  }}</td>
                        @if($accountName == 'DiscSavings' || $accountName == 'all')
                            <td class="bucket">{{ $newTransaction["bucket"] ?? NULL  }}</td>
                        @endif
                        <td class="notes">{{ $newTransaction["notes"] ?? NULL  }}</td>
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
                        <!-- once this ("edit") is clicked, change to save.  Once saved, change back to edit -->
                        <td>
                            <button id="editTransaction" class="btn btn-primary" data-id={{ $newTransaction["id"] }}>Edit</button>
                        </td>
                        <td>
                            <button id="splitTransaction" class="btn btn-warning" data-id={{ $newTransaction["id"] }}>Split</button>
                        </td>                       
                        <td>
                            <button id="deleteTransaction" class="btn btn-danger" data-id={{ $newTransaction["id"] }}>Delete</button>
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
                    @endif
                    <td class="fw-bold">toFrom (existing trans)</td>
                    <td class="fw-bold">amount</td>
                    <td class="fw-bold">amtMike</td>
                    <td class="fw-bold">amtMaura</td>
                    <td class="fw-bold">method</td>
                    <td class="fw-bold">category</td>
                    <td class="fw-bold">tracking</td>
                    <td class="fw-bold">stmtDate</td>
                    <td class="fw-bold">total_amt</td>
                    <td class="fw-bold">total_key</td>
                    @if($accountName == 'DiscSavings' || $accountName == 'all')
                        <td class="fw-bold">bucket</td>
                    @endif
                    <td class="fw-bold">notes</td>
                    <td class="fw-bold">lastBalanced</td>
                    <td class="fw-bold">Spent</td>
                    <td class="fw-bold">Budget thru this month</td>
                    <td class="fw-bold">Full Year Budget</td>
                    <td class="fw-bold">Edit/Save</td>    
                    <td class="fw-bold">Split</td>    
                    <td class="fw-bold">Delete</td>    
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
                        @endif
                        <td class="toFrom">{{ $transaction->toFrom }}</td>
                        <td class="amount">{{ $transaction->amount }}</td>
                        <td class="amtMike">{{ $transaction->amtMike }}</td>
                        <td class="amtMaura">{{ $transaction->amtMaura }}</td>
                        <td class="method">{{ $transaction->method }}</td>
                        <td class="category">{{ $transaction->category }}</td>
                        <td class="tracking">{{ $transaction->tracking }}</td>
                        <td class="stmtDate">{{ $transaction->stmtDate }}</td>
                        <td class="total_amt">{{ $transaction->total_amt }}</td>
                        <td class="total_key">{{ $transaction->total_key }}</td>
                        @if($accountName == 'DiscSavings' || $accountName == 'all')
                            <td class="bucket">{{ $transaction->bucket }}</td>
                        @endif
                        <td class="notes">{{ $transaction->notes }}</td>
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
                        <!-- once this ("edit") is clicked, change to save.  Once saved, change back to edit -->
                        <!-- may need to edit these transactions -->
                        <td>
                            <button id="editTransaction" class="btn btn-primary" data-id={{ $transaction->id }}>Edit</button>
                        </td>                       
                        <td>
                            <button id="splitTransaction" class="btn btn-warning" data-id={{ $transaction->id }}>Split</button>
                        </td>                       
                        <td>
                            <button id="deleteTransaction" class="btn btn-danger" data-id={{ $transaction->id }}>Delete</button>
                        </td>                       
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        
        <script>
            
            var splitTotal = 0; // used to keep track of total of split transactions

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            
            // number of characters in toFrom to match in automatically using the alias
            var numberOfAliasCharsToMatch = 11;

            $(document).ready(function() {
                
                // get hidden variables
                var accountNames = $("#accountNames").val();
                // console.log("accountNames: ", accountNames);
                accountNames = JSON.parse(accountNames);

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

                // get the date of the first of the current month in mm/dd/yyyy format
                function getFirst() {
                    const now = new Date();
                    var firstDay = new Date(now.getFullYear(), now.getMonth(), 1);

                    // this month as a 0 left padded 2 char string
                    var month = ('00' + (firstDay.getMonth() + 1).toString()).slice(-2);
                    
                    // put in yyyy-mm-01 format (01 for the first of the month)
                    firstDay = `${firstDay.getFullYear()}-${month}-01`;

                    return firstDay;
                }

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
                function verifyDate(date, nullOK = false) {

                    // make sure date is valid
                    var month, day, year;

                    // clear old error msg
                    var errorMsg = "";
                    $("#dateErrorMsg").text(errorMsg);

                    // if nullOK, then a null or empty string is allowed.
                    // return an empty string
                    if (nullOK && (date == "" || date == null)) return "";

                    // determine whether '-' or '/' is used as a delimiter in the date
                    var hasDashDelimiter = date.includes('-');
                    var delimiter;
                    if(hasDashDelimiter) delimiter = '-';
                    else delimiter = '/';
                    
                    // break date into parts
                    var newDate = date.split(delimiter);

                    // if only month and day, add year to newDate variable
                    if(newDate.length == 2) {
                        year = new Date().getFullYear();
                        newDate.push(year.toString());
                    }

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
                        errorMsg = "Year must have 2 or 4 digits (" + date + " entered).";
                        $("#dateErrorMsg").text(errorMsg);

                        return getFirst();
                    }

                    // Only one part can be 4 chars long
                    // Get all indices of the maximum length
                    if(maxLength == 4) {
                        const maxIndices = lengthAndIndices.filter(obj => obj.length === maxLength).map(obj => obj.index);
                        if(maxIndices.length != 1) {
                            errorMsg = "Only one part of the date can have 4 digits (" + date + " entered).";
                            $("#dateErrorMsg").text(errorMsg);

                            return getFirst();
                        }

                        year = newDate[maxIndices];

                        // remove year from newDate
                        newDate.splice(maxIndices, 1);

                        // the remaining parts should be month and day, in that order
                        month = checkMonth(newDate[0]);
                        if(month == 'invalid') {
                            errorMsg = "The month must be between 1 and 12 (" + date + " entered).";
                            $("#dateErrorMsg").text(errorMsg);

                            return getFirst();
                        }

                        day = checkDay(newDate[1], newDate[0]);
                        if(day == 'invalid') {
                            errorMsg = "The day must be between 1 and the end of the month (" + date + " entered).";
                            $("#dateErrorMsg").text(errorMsg);

                            return getFirst();
                        }

                        return year + "-" + month + "-" + day;

                    } else if(maxLength == 2) {
                        // if the year is a two digit year, the date format expected is m/d/y

                        month = checkMonth(newDate[0]);
                        if(month == 'invalid') {
                            errorMsg = "The month must be between 1 and 12 (" + date + " entered).";
                            $("#dateErrorMsg").text(errorMsg);

                            return getFirst();
                        }

                        day = checkDay(newDate[1], newDate[0]);
                        if(day == 'invalid') {
                            errorMsg = "The month must be between 1 and 12 (" + date + " entered).";
                            $("#dateErrorMsg").text(errorMsg);

                            return getFirst();
                        }

                        // any year is accepted
                        // NOTE: 4 digit year needed for SQL query
                        year = "20" + newDate[2];

                        return year + "-" + month + "-" + day;

                    } else {
                        // Throw error message if year does not have 2 or 4 digits
                        errorMsg = "The year must have 2 or 4 digits (" + date + " entered).";
                            $("#dateErrorMsg").text(errorMsg);

                        return getFirst();
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
                    var transId = $record.find('.transId').text();
                    if(transId == 'null') newTransaction['id'] = null;
                    else newTransaction['id'] = Number($record.find('.transId').text());
                    
                    newTransaction['trans_date'] = $record.find('.transDate').children(':first-child').val();

                    var clearDate = $record.find('.clearDate').children(':first-child').val();
                    if(clearDate !== '' && clearDate != 'null' && clearDate !== 'NULL') newTransaction['clear_date'] = clearDate;
                    else newTransaction['clear_date'] = null;
                    
                    var account = $record.find('.account').children(':first-child').val();
                    if(account == undefined) newTransaction['account'] = $('#accountName').text();
                    else newTransaction['account'] = account;
                    
                    newTransaction['toFrom'] = $record.find('.toFrom').children(':first-child').val();
                    newTransaction['amount'] = Number($record.find('.amount').children(':first-child').val());
                    newTransaction['amtMike'] = Number($record.find('.amtMike').children(':first-child').val());
                    newTransaction['amtMaura'] = Number($record.find('.amtMaura').children(':first-child').val());

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
                    if(total_amt !== '' && total_amt !== 'null' && total_amt !== 'NULL') newTransaction['total_amt'] = Number(total_amt);
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
                                console.log("Transaction updated successfully.\n", response.message);
                            },
                            error: function(xhr, status, error) {
                                console.log("** FAILED ** to update transaction", error);
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
                                console.log("Transaction inserted successfully.\n", response.message);
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
                    $(thisElement).parent().parent().find('td').each(function(index, td) {
                        
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
                                .addClass("btn-primary")
                                .attr("id", "editTransaction"); // change the id
                            }
                        }
                        
                    });

                }

                // If a new toFrom is entered, make sure it's not a mistake;
                // If the new toFrom isn't a previously used value, ask if it should be changed automatically.
                function handleToFrom(newValue, toFroms, toFromAliases, origToFrom) {

                    var isGood = true; 

                    // Does toFrom exceed char length in database (100 chars)
                    isGood = verifyVarCharLength(newValue, 100);
                    if(!isGood) {
                        newValue = newValue.substr(0, 100);
                        $(this).val(newValue);
                        // isGood = confirm("toFrom truncated to:\n" + newValue + "\n\nIs this OK?");
                        isGood = true;  // temp 
                        if(!isGood) return false;
                        else isGood = true;
                    } else {
                        $(this).css("background-color", "white");
                    }

                    // is toFrom a new value?  If so, is it ok?
                    if(!(toFroms).includes(newValue)) {     // new toFrom value
                        // If a new toFrom is entered, make sure it's not a mistake;
                        var question = "This is a new toFrom value (" + newValue + ").  Is it correct?";
                        // var isCorrectNewValue = confirm(question);
                        var isCorrectNewValue = true;  // temp 
                        // if not correct, return and let user try again
                        if(!isCorrectNewValue) return false;
                    }

                    // If the new toFrom doesn't have an alias, ask if it should be changed automatically.
                    var foundToFrom = toFromAliases.find(alias => alias.origToFrom === origToFrom);
                    if( typeof foundToFrom === 'undefined') {
                        var question = "Should this toFrom automatically be changed to " + newValue + " when the first " + numberOfAliasCharsToMatch + " characters match?" +
                            '\n\n"' + origToFrom.substr(0, numberOfAliasCharsToMatch) + '..." \n     to\n"' + newValue + '"';
                        // var saveAlias = confirm(question);
                        var saveAlias = false;  // temp 

                        origToFrom = origToFrom.substr(0, numberOfAliasCharsToMatch);
                        origToFrom = encodeURIComponent(origToFrom);
                        newValue = encodeURIComponent(newValue);
                        
                        var url = '/transactions/insertAlias/' + origToFrom + '/' + newValue;

                        if(saveAlias) {
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
                    }

                    return isGood;
                }
                

                // If a new toFrom is entered, make sure it's not a mistake;
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


                function changeCellsToInputs($cell) {
                    // change all the cells to inputs, except the "Edit" button
                    $cell.parent().parent().find('td').each(function(index, td) {
                    
                        // assume true until see otherwise
                        var isGood = true;

                        // What's the tag of the first child (undefined for all but the last, which is "BUTTON")
                        // console.log( "index: " + index + ": " + $cell.children(':first-child').prop('tagName'));
                        
                        // get the current cell
                        var $tdcell = $(this);
                        
                        // for each cell that has no children (all the tdcells except the last "Edit" button)
                        // NOTE: The id (class newtransactionor transId), lastBalanced, or historical (spent, ytmBudget, yearBudget) are not editable
                        if( typeof $(this).children(':first-child').prop('tagName') == 'undefined' 
                            && !$tdcell.prop("class").includes("newtransaction") 
                            && !$tdcell.prop("class").includes("transId")
                            && !$tdcell.prop("class").includes("lastBalanced")
                            && !$tdcell.prop("class").includes("spent")
                            && !$tdcell.prop("class").includes("ytmBudget")
                            && !$tdcell.prop("class").includes("yearBudget")
                        ) 
                        {

                            
                            // get type of input field
                            $class = "editable-cell " + $tdcell.prop("class");

                            // make it an input tdcell
                            var $input = $('<input>')
                                .attr('type', 'text')
                                .val($tdcell.text())
                                .addClass($class);
                                // .addClass('editable-cell');
                                $tdcell.empty().append($input);

                            // drop class from td
                            // $(this).closest("td").removeClass();
                            $cell.closest("td").removeClass();
                            
                            // listen for changes to the input
                            // $input.on('change', function() {
                            $(document).on('change', 'input', function() {
                                
                                var newValue = $(this).val();
                                var fieldClass = $(this).prop("class");
                                // drop editable-cell from fieldClass
                                fieldClass = fieldClass.replace("editable-cell ", "");

                                // check trans_date and clear_date
                                // verifyDate returns a valid date,
                                //  or an empty string if the date is '' or null and that's allowed.
                                if(fieldClass.includes('Date') && !fieldClass.includes('stmtDate')) {
                                    // clear date can have a null or '' date
                                    var nullOK;
                                    if("clearDate" == fieldClass) nullOK = true;
                                    else nullOK = false;

                                    isGood = verifyDate(newValue, nullOK);
                                    $(this).val(isGood);

                                // check account (in list of defined accounts)
                                } else if(fieldClass.includes('account')) {
                                    // is account entered (newValue) in list of defined values (accountNames)
                                    isGood = verifyEnums(newValue, accountNames);

                                    if(!isGood) {
                                        $(this).val("enter defined account name (orig: " + origAccount + ")");
                                    } else {
                                        // reset origAccount (since last entered is good)
                                        origAccount = newValue;

                                        // if not DiscSavings, erase Bucket and alert user
                                        // var $accountcell = $(this);  // don't lose "this"

                                        // if(newValue != 'DiscSavings' && $accountcell.closest("tr").find('.bucket').val()) {
                                        if(newValue != 'DiscSavings' && $cell.closest("tr").find('.bucket').val()) {
                                            // $accountcell.closest("tr").find('.bucket').val("");        // erase bucket value
                                            $cell.closest("tr").find('.bucket').val("");        // erase bucket value
                                            alert("Bucket value removed since only DiscSavings uses buckets");  // tell user
                                        }
                                    }

                                // check toFrom (make automatic?)
                                } else if(fieldClass.includes('toFrom')) {
                                    isGood = handleToFrom(newValue, toFroms, toFromAliases, origToFrom);
                                    if(!isGood) {
                                        $(this).val("enter correct toFrom (orig: "  + origToFrom + ")");
                                    }
                                

                                // check amount values  - number; question if more than 2 decimal places
                                // checks amount, amtMike, amtMaura, total_amt  
                                } else if(fieldClass.includes('amount') || fieldClass.includes('amt')) {
                                    isGood = verifyAmount(newValue);
                                    if(!isGood) {
                                        $(this).val("enter integer or decimal dollar amount (no $)");
                                    }

                                    // handle splitTotal if amount is changed
                                    if(isGood && fieldClass.includes('amount')) {

                                        // recalc splitTotal - add all amount input fields
                                        splitTotal = 0;
                                        var $theseTransactions = $(this).parent().parent().parent();
                                        $theseTransactions.find('td').each(function(index, td) {
                                            if( $(td).attr('class') == "amount" && $(td).children(':first-child').prop('tagName') == 'INPUT') {
                                                splitTotal += Number($(td).children(':first-child').val());
                                            };
                                        });

                                        // if the total is off, turn it red and note the difference to put on the page
                                        var thisTotalAmt = $(this).parent().parent().find(".total_amt").children(":first-child").val();
                                        var totalDiffText = ""; // assume no difference

                                        if(splitTotal != thisTotalAmt) {
                                            var totalDiffText = " (" + (thisTotalAmt - splitTotal) + ")";
                                            $("#splitTotal").css("color","red");

                                        // otherwise, change the color back to skyblue
                                        } else {
                                            $("#splitTotal").css("color","skyblue");
                                        }

                                        // put the splitTotal in the amount header (span id = splitTotal)
                                        $('#splitTotal').text("Split Total: " + splitTotal + totalDiffText);
                                    }

                                // is method <= 10 chars (size of database column)
                                } else if(fieldClass.includes('method')) {
                                    isGood = verifyVarCharLength(newValue, 10);
                                    if(!isGood) {
                                        $(this).val(newValue.substr(0, 10) + " - truncated" );
                                        $(this).css("background-color", "yellow");
                                    } else {
                                        $(this).css("background-color", "white");
                                    }

                                // check category (in list of defined categories)
                                } else if(fieldClass.includes('category')) {
                                    // is category entered (newValue) in list of defined values (categories)
                                    isGood = verifyEnums(newValue, categories);
                                    if(!isGood) {
                                        $(this).val('"' + newValue + '" is not a defined category name.');
                                    }

                                // is tracking <= 10 chars (size of database column)
                                //  verify if it's a new value
                                } else if(fieldClass.includes('tracking')) {
                                    isGood = verifyVarCharLength(newValue, 10);
                                    if(!isGood) {
                                        $(this).val(newValue.substr(0, 10) + " - truncated" );
                                        $(this).css("background-color", "yellow");
                                    } else {
                                        $(this).css("background-color", "white");
                                        isGood = verifyEnums(newValue, trackings);
                                        if(!isGood) {
                                            // isGood = confirm('"' + newValue + '" is a new tracking value.  Is this correct?');
                                            isGood = true;  // temp 
                                            if(!isGood) {
                                                $(this).val('Enter a tracking value (was "' + newValue + '").');
                                            }
                                        }
                                    }

                                // is stmtDate valid (i.e. 24-Oct)
                                } else if(fieldClass.includes('stmtDate')) {
                                    isGood = verifyStmtDate(newValue);
                                    if(!isGood) {
                                        $(this).val('stmtDate needs to be in the format yy-Mon (' + newValue + ' entered)');
                                    }


                                // is total_key <= 5 chars (size of database column)
                                } else if(fieldClass.includes('total_key')) {
                                    isGood = verifyVarCharLength(newValue, 5);
                                    if(!isGood) {
                                        $(this).val(newValue.substr(0, 5) + " - truncated" );
                                        $(this).css("background-color", "yellow");
                                    } else {
                                        $(this).css("background-color", "white");
                                    }
                                
                                // check bucket (in list of defined buckets);
                                // required for DiscSavings account, should be blank for all others
                                } else if(fieldClass.includes('bucket')) {
                                    var $cell = $(this);

                                    // only enter bucket for DiscSavings account
                                    if(origAccount == 'DiscSavings') {
                                        // is bucket entered (newValue) in list of defined values (buckets)
                                        isGood = verifyEnums(newValue, buckets);
                                        if(!isGood) {
                                            $cell.val('"' + newValue + '" is not a defined bucket.');
                                        }
                                    } else {
                                        alert("Only DiscSavings accounts use buckets");
                                        $cell.val("");
                                    }


                                // is notes <= 100 chars (size of database column)
                                } else if(fieldClass.includes('notes')) {
                                    isGood = verifyVarCharLength(newValue, 100);
                                    if(!isGood) {
                                        $(this).val(newValue.substr(0, 100) + " - truncated" );
                                        $(this).css("background-color", "yellow");
                                    } else {
                                        $(this).css("background-color", "white");
                                    }
                                }
                                
                            });

                        } else {
                            // change the "Edit" button to a "Save" button
                            if( $(this).children(":first-child").text() == "Edit") {
                                
                                $(this).children(':first-child')
                                    .text('Save')
                                    .removeClass("btn-primary")     // blue to green
                                    .addClass("btn-success")
                                    .attr("id", "saveTransaction"); // change the id
                            }
                        }
                    
                    });
                }   // end function changeCellsToInputs


                // get begin and end dates in expected format on page
                formatDefaultDate("#beginDate");
                formatDefaultDate("#endDate");

                // handle changing beginning date
                $('#beginDate').on('change', function() {
                    newBeginDate = verifyDate($(this).val());
                    $(this).val(newBeginDate);
                });
                
                // handle changing end date
                $('#endDate').on('change', function() {
                    newEndDate = verifyDate($(this).val());
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

                    window.location.href = "/transactions/add";
                })


                // listen for "Edit" being clicked
                $(document).on('click', '#editTransaction', function(e) {
                    e.preventDefault();
                    
                    var $cell = $(this);

                    // get the id of the transaction being editted
                    var id = $(this).data('id');
                    console.log("id (editting): " + id);
                    // console.log("cell id: " + $cell.data('id'));

                    // get the original account, toFrom, amount
                    var origAccount = $cell.closest("tr").find('.account').text();
                    if (origAccount == '') origAccount = "{{$accountName}}";

                    var origToFrom = $cell.closest("tr").find('.toFrom').text();
                    var origAmount = $cell.closest("tr").find('.amount').text();
                    // console.log("origAccount: ", origAccount, "\norigToFrom: ", origToFrom, "\norigAmount: ", origAmount);

                    // change all the cells to inputs, except the "Edit" button
                    changeCellsToInputs($cell);
                   
                });
                
                
                // listen for "Save" being clicked
                $(document).on('click', '#saveTransaction', function(e) {
                    e.preventDefault();
                    
                    // get the id
                    var id = $(this).data('id');
                    if(id == 'null') id = null;
                    var thisElement = this;
                    console.log("id (saving): " + id);
                    
                    // are the values in the record good
                    try {

                        $record = $(this).parent().parent();
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

                        // if total_amt is complete...
                        // sum of amounts for all total_keys should = total_amt
                        // all total_amts should be the same for all total_keys
                        var total_amt_done = false;  // false if no total_key, so it's not checked
                        if(total_key != '') {
                            // total_amt_done = confirm("Are all the split transactions for total_key: " + total_key + " entered?");
                            total_amt_done = false;     // temp
                        }

                        if(total_amt_done) {
                            // for all records with the same total_key,
                            // sumTotalAmts is the sum of all the amount values where total_key is the given total_key
                            // totalAmts is an array of each of the total_amt values where total_key is the given total_key

                            $.ajax({
                                url: '/transactions/totalKey/' + total_key,
                                type: 'GET',
                                dataType: 'json',
                                data: {
                                    _token: "{{ csrf_token() }}"
                                    // totalKey: total_key
                                },
                                success: function(response) {
                                    // calculate sumTotalAmts and totalAmts
                                    var sumTotalAmts = 0;
                                    var totalAmts = [];

                                    console.log("response: ", response);
                                    var thisIdFound = false;    // make sure record being updated is included

                                    response.forEach(record => {
                                        // use amount & total_amt entered for current id
                                        if(record['id'] == id) {
                                            sumTotalAmts += amount;
                                            totalAmts.push(total_amt);
                                            thisIdFound = true;
                                        // otherwise use amount and total_amt in database
                                        } else {
                                            sumTotalAmts += Number(record['amount']);
                                            totalAmts.push(Number(record['total_amt']));
                                        }
                                    });

                                    // include this record, if not in response from database
                                    //      Can happen if total_key is changed.
                                    if(!thisIdFound) {
                                        sumTotalAmts += amount;
                                        totalAmts.push(total_amt);  
                                    }

                                    console.log("sumTotalAmts: ", sumTotalAmts);
                                    console.log("totalAmts:", totalAmts);

                                    // sum of amounts for all total_keys should = total_amt                       
                                    if(sumTotalAmts.toFixed(4) != total_amt.toFixed(4)) {
                                        errMsg = "Sum of all the amounts where total_key is " + total_key + " should be " + total_amt;
                                        alert(errMsg + "\nTransaction not updated in database.");
                                        throw errMsg;
                                    }
                                    console.log("sumTotalAmts ok");

                                    // all total_amts should be the same for all total_keys
                                    if(!totalAmts.every(element => element.toFixed(4) === totalAmts[0].toFixed(4))) {
                                        errMsg = "Each amount for records where total_key is " + total_key + " should be the same (" + total_amt + ")";
                                        alert(errMsg + "\nTransaction not updated in database.");
                                        throw errMsg;
                                    }
                                    console.log("totalAmts ok");

                                    // OK to write record
                                    updateTransactionRecord($record);

                                    // change edittable cells in record to non-edittable
                                    makeNotEdittable(thisElement);

                                },
                                error: function(xhr, status, error) {
                                    var errorMsg = "Error getting total_key transactions.";
                                    console.error(errorMsg, error);
                                    alert(errorMsg, error);
                                }
                            });

                        } else {

                            // OK to write record
                            updateTransactionRecord($record);

                            // change edittable cells in record to non-edittable
                            makeNotEdittable(this);

                        }
                    } catch (error) {
                        console.error("Error checking record: ", error);
                    }                  
                                    
                });
                
                // listen for "Split" being clicked
                $(document).on('click', '#splitTransaction', function(e) {
                    e.preventDefault();

                    var $cell = $(this);    // used to change origTransaction to input fields
                    var $origTransaction = $(this).parent().parent();

                    // update original transaction on page when it's changed
                    // create new transaction for split
                    // click "Save" button to save each transaction

                    // needed to link the two new transactions
                    var total_amt = $origTransaction.find('.amount').text();
                    var total_key = $(this).data('id').toString();
                    
                    // put total amount in "amount" column heading
                    $("#splitTotal").removeAttr("hidden").text("Split Total: " + total_amt);
                    splitTotal = total_amt;

                    // amount, amtsplitTotalMike, amtMaura - all of these are div by 2, and need to be updated on page
                    var newAmount = total_amt / 2;
                    $origTransaction.find(".amount").text(newAmount);  // change amount in original transaction

                    var newAmtMike = $origTransaction.find('.amtMike').text() / 2
                    $origTransaction.find(".amtMike").text(newAmtMike);                 // change amtMike in original transaction

                    var newAmtMaura = $origTransaction.find('.amtMaura').text() / 2
                    $origTransaction.find(".amtMaura").text(newAmtMaura);               // change amtMaura in original transaction

                    // total_amt and total_key are changed in original transaction, so update on page
                    $origTransaction.find(".total_amt").text(total_amt);
                    $origTransaction.find(".total_key").text(total_key);
                    
                    changeCellsToInputs($cell);
                    // NOTE:  original transaction gets updated when "Save" button hit

                    // clone splitTransaction on page with edittable cells
                    var $clonedTransaction = $origTransaction.clone();

                    // set id to null
                    $clonedTransaction.find("tr").attr("data-id", "null");
                    $clonedTransaction.find(".transId").text("null");
                    $clonedTransaction.find(".saveTransaction").attr("data-id", "null");
                    $clonedTransaction.find(".splitTransaction").attr("data-id", "null");
                    $clonedTransaction.find(".deleteTransaction").attr("data-id", "null");

                    // add to page  --  gets saved when "Save" clicked on page.
                    $origTransaction.after($clonedTransaction);
                    
                });
                
                // listen for "Delete" being clicked
                $(document).on('click', '#deleteTransaction', function(e) {
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
        
            });
        </script>
    </body>

</html>