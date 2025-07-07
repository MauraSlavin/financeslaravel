<html>
    <head>
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <meta name="csrf-token" content="{{ csrf_token() }}">
    </head>

    <body id="gb">

        <!-- include common functions, if needed -->
        <!-- <script src="{{ asset('js/commonFunctions.js') }}"></script> -->

        <!-- headers -->
        <h1>GB Limo Paycheck Processing Page</h1>

        <!-- <form> -->
        <form action="{{ route('writegblimo') }}" method="POST">
            @csrf

            <!-- step='0.01' allows up to 2 decimal places -->
             
            <!-- Net paycheck amt -->
            <div class="form-row">
                <label class="gbnetpaylabel" for="gbnetpay">Paycheck total (from checking): </label><br>
                <input class="form-control gbnetpayinput" type="number" id="gbnetpay" name="gbnetpay"  step="0.01" required>
            </div>

            <!-- SS withheld -->
            <div class="form-row">
                <label class="gbsswhlabel" for="gbnetpay">SS withheld (from paystub): </label><br>
                <input class="form-control gbsswhinput" type="number" id="gbsswh" name="gbsswh"  step="0.01" required>
            </div>

            <!-- Net paycheck amt -->
            <div class="form-row">
                <label class="gbmcwhlabel" for="gbmcwh">Medicare withheld (from paystub): </label><br>
                <input class="form-control gbmcwhinput" type="number" id="gbmcwh" name="gbmcwh"  step="0.01" required>
            </div>

            <!-- Federal taxes -->
            <div class="form-row">
                <label class="gbtaxwhlabel" for="gbtaxwh">Federal taxes withheld (from paystub): </label><br>
                <input class="form-control gbtaxwhinput" type="number" id="gbtaxwh" name="gbtaxwh"  step="0.01" value=0>
            </div>

            <!-- Amt that Mike & Maura get for spending -->
            <div class="form-row">
                <label class="gbspendinglabel" for="gbspending">M&M Spending (each, not total - from GB Limo Google Sheets): </label><br>
                <input class="form-control gbspendinginput" type="number" id="gbspending" name="gbspending"  step="0.01" required>
            </div>

            <!-- Paycheck date -->
            <div class="form-row">
                <label class="gbpaycheckdatelabel" for="gbpaycheckdate">Paycheck date (from checking): </label><br>
                <input class="form-control gbpaycheckdateinput" type="date" id="gbpaycheckdate" name="gbpaycheckdate" value="{{ $gbpaycheckdate }}" required>
            </div>

            <!-- Spending transfer date -->
            <div class="form-row">
                <label class="gbspendingdatelabel" for="gbspendingdate">Date Spending transfered to M&M (probably today's date): </label><br>
                <input class="form-control gbspendingdateinput" type="date" id="gbspendingdate" name="gbspendingdate" value="{{ $gbspendingdate }}" required>
            </div>

            <!-- Statement date -->
            <div class="form-row">
                <label class="gbstmtdatelabel" for="gbstmtdate">Statement date for checking (only month and year are used - probably this month and year): </label><br>
                <input class="form-control gbstmtdateinput" type="text" id="gbstmtdate" name="gbstmtdate" value="{{ $gbspendingdate }}" required>
            </div>

            <!-- Pay period (for notes column of transactions table - for paycheck deposit) -->
            <div class="form-row">
                <label class="gbpayperiodnotelabel" for="gbpayperiodnote">Pay period (Mon - Sun): </label><br>
                <input class="form-control gbpayperiodnoteinput" type="text" id="gbpayperiodnote" name="gbpayperiodnote" required>
            </div>

            <!-- Spend note (for notes column of transacctions table - for spending transfer) -->
            <div class="form-row">
                <label class="gbspendingnotelabel" for="gbspendingnote">Note for Spending transactions<br>(ie: Great Bay Limo; mm/dd/yyyy pay - using date deposited in checking): </label><br>
                <input class="form-control gbspendingnoteinput" type="text" id="gbspendingnote" name="gbspendingnote" required>
            </div>

            <button type="submit" class="btn btn-success" style="margin: 20px;">Process GB Limo pay</button>

        </form>        
        
        <script>
            
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            
            $(document).ready(function() {

                // Pay period is the Monday - Sunday immediately preceding the paycheck date
                function getPayPeriod() {
                    // get paycheckdate
                    var paycheckdate = $("#gbpaycheckdate").val();

                    // get end of previous pay period
                    var endpayperiod = getPreviousSunday(paycheckdate);

                    // get beginning of previous pay period
                    var beginpayperiod = getSixDaysBefore(endpayperiod);

                    return beginpayperiod + " - " + endpayperiod;
                }

                // Get previous Sunday (end of pay period)
                function getPreviousSunday(dateString) {
                    const date = new Date(dateString);
                    
                    // Subtract days until we reach a Sunday
                    while (date.getDay() !== 0 && date > new Date(1970, 0, 1)) {
                        date.setDate(date.getDate()-1);
                    }
                    
                    // Set the timezone to New York
                    date.setUTCHours(date.getUTCHours() - date.getTimezoneOffset() / 60);

                    // Return the formatted date
                    return date.toISOString().split('T')[0];
                }   // end function getPreviousSunday

                function getSixDaysBefore(dateString) {
                    const date = new Date(dateString);
                    
                    // Subtract 6 days
                    date.setDate(date.getDate() - 6);
                    
                    // Format the date as YYYY-MM-DD
                    return date.toISOString().split('T')[0];
                }   // end function getSixDaysBefore

                // reformat statement date
                // get default date from page (format yyyy-mm-dd)
                var stmtDate = $("#gbstmtdate").val();
                // get numeric string month
                var month = stmtDate.substr(5, 2);

                // convert month to a 3 char abbrev
                const months = [
                    "Jan", "Feb", "Mar", "Apr", "May", "Jun",
                    "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"
                ];
                month = months[parseInt(month, 10) - 1];

                // change stmtDate to yy-Mon & put on page
                stmtDate = stmtDate.substr(2, 2) + "-" + month;
                $("#gbstmtdate").val(stmtDate);

                // default note for paycheck deposit
                var payperiod = getPayPeriod();               
                $("#gbpayperiodnote").val("Pay " + payperiod);

                // default note for spending transaction
                $("#gbspendingnote").val("Great Bay Limo; " + $("#gbpaycheckdate").val() + " pay");

            });

        </script>
    </body>

</html>