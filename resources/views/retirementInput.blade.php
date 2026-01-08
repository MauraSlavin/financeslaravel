<html>
    <head>
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script> -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <meta name="csrf-token" content="{{ csrf_token() }}">
    </head>

    <body style="background":"#ccc none repeat scroll 0 0">

        <!-- include common functions -->
        <script src="{{ asset('js/commonFunctions.js') }}"></script>

        <!-- headers -->
        <h1>Retirement input (to calc outlook)</h1> 

        <button type = "button" id="rentalIncome" class="btn btn-success" style="margin: 10px;">Rental Income</button>
        <button type = "button" id="splitIRAs" class="btn btn-success" style="margin: 10px;">Split IRAs (Roth vs Trad vs Inh)</button>

        <div class="retirementInput">
            <table id="editRetirementInputTable">

                <!-- table headers -->
                <thead>
                    <tr>
                        <th style="width: 75px;">account</th>
                        <th style="width: 20px;">type</th>
                        <th style="width: 75px;">amount</th>
                        <th style="width: 5px;">unit</th>
                        <th style="width: 100px;">correction</th>
                        <th style="width: 5px;">save</th>
                        <th style="width: 75px;">date</th>
                    </tr>
                </thead>

                <!-- retirement input data -->
                <tbody>
                    <!-- show data for each type of data -->
                    @php
                        $types =    ["Assumption",               "Income",               "Values (1st of mon)",  "Balances (1st of mon)",   "Constants"];
                        $vars =     [$retirementDataAssumptions, $retirementDataIncomes, $retirementDataValues,  $retirementDataBalances,   $retirementDataConstants];
                        $colors =   ["darkorange",               "lightseagreen",        "blue",                 "purple",                  "black"];

                        // for deleting old rental income records
                        $year = date("y");
                        $years = [ $year, $year+1, $year+2];
                        $month = date("m");
                        $months = [];
                        for($i = 0; $i < 36; $i++) {
                            $months[] = ($month + $i) % 12;
                        }
                    @endphp

                    @foreach($types as $idx=>$type)
                        <!-- for Values (all WF stuff), disable input.
                         use "Split IRAs..." button to change these values -->
                        @if( $type == 'Values' ) 
                            @php
                                $disabled = "disabled";
                            @endphp
                        @else
                            @php
                                $disabled = ""; 
                            @endphp
                        @endif
                        <!-- retirement data points -->
                        @foreach($vars[$idx] as $key=>$balance)
                            <!-- format 'd' (date) as mm/dd/yyyy -->
                            @if( $balance[1] == 'd')
                                @php
                                    $value = substr($balance[0], 0, 2) . "/" . substr($balance[0], 2, 2) . "/" . substr($balance[0], 4) ?? NULL;
                                    if($balance[3] != null) {
                                        // if modified...
                                        $modifiedValue = substr($balance[3], 0, 2) . "/" . substr($balance[3], 2, 2) . "/" . substr($balance[3], 4) ?? NULL;
                                    } else {
                                        // if NOT modified, use original
                                        $modifiedValue = $value;
                                    }
                                @endphp
                            <!-- format everything else (% or $) as a number with 2 decimal places -->
                            @else
                                @php
                                    $value = number_format($balance[0] ?? NULL, 2);
                                    $modifiedValue = $value;
                                    if($balance[3] !== null) {
                                        // if modified...
                                        // if string, remove commas and convert to float
                                        if(gettype($balance[3] == 'string')) $balance[3] = floatval( str_replace(",", "", $balance[3]));
                                        // then format as a number w/2 decimal places
                                        $modifiedValue = number_format($balance[3] ?? $value, 2);
                                    } else {
                                        // if NOT modified, use original
                                        $modifiedValue = $value;
                                    }
                                @endphp
                            @endif
                            <tr>
                                <!-- data point -->
                                <td class="retInputAccount">{{ $key ?? NULL }}</td>
                                
                                <!-- type of data point -->
                                <td class="retType" style="font-weight: bold; color: {{$colors[$idx]}};">{{ $type }}</td>
                                
                                <!-- stored value -->
                                <td 
                                    class="retInputBalance"
                                    style="text-align: right;">{{ $value }}
                                </td>

                                <!-- unit of measure (UOM) -->
                                <td style="width: 5px; text-align: center;">{{ $balance[1] ?? NULL }}</td>

                                <!-- input field to override stored value -->
                                <!-- disable RentalIncomexx --> 
                                <td class="retInputCorrection">
                                    <input 
                                        class="retCorrection" 
                                        name="retCorrection[]" 
                                        style="width: 100%; text-align: right;" 
                                        value={{ $modifiedValue }}
                                        data-field={{ $key ?? NULL }}
                                        {{ $disabled }}
                                    >
                                </td>

                                @if( $type == "Income" || $type == "Balances")
                                <td class="saveCorrection">
                                    Can't save
                                </td>
                                @else
                                <!-- save correction button -->
                                <td class="saveCorrection">
                                    @if(substr($key, 0, 2) != 'WF')
                                    <button class="btn btn-sm btn-primary save-correction-button" data-item-id="{{ $key }}">Save</button>
                                    @else
                                    use Split IRAs btn
                                    @endif
                                </td>
                                @endif
                                    
                                <!-- date of value -->
                                <td class="retInputDate">{{ $balance[2] ?? NULL  }}</td>
                            </tr>
                        @endforeach
    
                        <!-- Add rentalIncome at the end of Incomes --> 
                        @if($type == "Income")
                            @foreach( $years as $year )
                                <tr>
                                    <!-- data point -->
                                    <td class="retInputAccount">RentalIncome{{ $year }}</td>

                                    <!-- type of data point -->
                                    <td class="retType" style="font-weight: bold; color: {{$colors[$idx]}};">Income</td>
                                    
                                    <!-- stored value  - filled in w/code -->
                                    <td 
                                        class="retInputBalance"
                                        id="rental{{ $year }}"
                                        style="text-align: right;">
                                    </td>

                                    <!-- unit of measure (UOM) -->
                                    <td style="width: 5px; text-align: center;">$</td>

                                    <!-- input field to override stored value 
                                        use "Rental Income" button to change details -->
                                    <td class="retInputCorrection">
                                        <input 
                                            class="retCorrection" 
                                            id="rentalInput{{ $year }}"
                                            name="retCorrection[]" 
                                            style="width: 100%; text-align: right;" 
                                            data-field=RentalIncome{{ $year }}
                                            disabled
                                        >
                                    </td>

                                    <!-- save correction button -->
                                    <td class="saveCorrection">
                                        Use Rental Inc btn
                                    </td>

                                    <!-- date of value -->
                                    <td class="retInputDate" id="rentalDate{{ $year }}"></td>
                                </tr>                            
                            @endforeach
                        @endif

                    @endforeach

                </tbody>
            </table>

            <!-- Rental Incomes (only visible when "Rental Income" button clicked) -->
            <div class="rentalIncomeBlock" style="content-visibility: hidden; background-color: #C5F7BE;">
                <h2 style="margin-top: 20px;">Rental income</ph2>
                <form action="{{ route('saveRents') }}" method="POST">
                    @csrf
                    @foreach( [0, 1] as $tenant )
                        <hr>
                        <h3 style="margin-top: 40px;">Tenant {{ $tenant+1 }}</h3>
                        @foreach( [0, 1, 2] as $year)
                            <h4 style="margin-top: 20px; margin-left: 10px;"><span class="year{{$year}}">- Year {{ $year }}</span> - Annual Total: <span id="TotT{{$tenant}}Y{{$year}}">0</span></h4>
                            @foreach( ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jly", "Aug", "Sep", "Oct", "Nov", "Dec"] as $monIdx=>$month)
                                <span style="min-width: 40px; max-width: 40px; width: 40px; margin-left: 10px; justify-content: left; font-size:20px;">{{ $month }}: </span>
                                <input type="number" class="monthRent" id="t{{$tenant}}y{{$year}}m{{$monIdx}}" name="t{{$tenant}}y{{$year}}m{{$monIdx}}" style="width: 75px; font-size: 20px;"></input>
                            @endforeach
                        @endforeach
                    @endforeach
                    <br>
                    <button type="submit" class="btn btn-success" style="margin: 10px;">Save & Close Rents</button>
                </form>
            </div>

        </div>

        <form action="{{ route('retirementForecast') }}" method="POST">
            @csrf
            <button type="submit" id="retirementForecast" class="btn btn-success" style="margin: 10px;">
                Retirement Forecast
            </button>
        </form>

        
        <script>

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $(document).ready(function() {

                function max(a, b) {
                    if(a > b) return a;
                    else return b;
                }

                // calc rental income for this year & the next 2 years for the page (if they're in the retirementdata table)

                // get the rental income data
                var retirementDataRents = @json($retirementDataRents);

                // get today's date (to get current year)
                const now = new Date();

                // init 3 years out (2 digit year and total rental income for that year)
                const year1 = String(now.getFullYear()).slice(-2);
                const year2 = String(Number(year1) + 1).slice(-2);
                const year3 = String(Number(year2) + 1).slice(-2);

                // need this month when inputting rental income by month
                const thisMonth = String(now.getMonth() + 1).slice(-2);
                // monthlyRentalIncome variable also used when inputting rental income by month
                const monthlyRentalIncome = Array.from({length: 2}, (_, tenant) =>
                    Array.from({length: 3}, (_, year) =>
                        Array.from({length: 12}, (_, month) => 
                            0
                        )
                    )
                );

                // yearly rental totals and dates for Retirement Data page
                var year1tot = 0;
                var year2tot = 0;
                var year3tot = 0;
                var year1date = null;
                var year2date = null;
                var year3date = null;

                // sum per year from what's in the retirementdata table
                var rentKeys = Object.keys(retirementDataRents);
                rentKeys.forEach( (key,idx) => {
                    var rentYear = key.substring(12, 14);
                    var rentMonth = key.substring(14, 16);
                    var tenant = key.substring(17, 18);
                    if(rentYear == year1){
                        year1tot += Number(retirementDataRents[key][0]);
                        year1date = max(year1date, retirementDataRents[key][2]);
                    }
                    else if(rentYear == year2){
                        year2tot += Number(retirementDataRents[key][0]);
                        year2date = max(year2date, retirementDataRents[key][2]);
                    }
                    else if(rentYear == year3){
                        year3tot += Number(retirementDataRents[key][0]);
                        year3date = max(year3date, retirementDataRents[key][2]);
                    }

                    // get monthly rental in case details need to be updated
                    if(typeof retirementDataRents[key][0] !== 'undefined') {
                        monthlyRentalIncome[tenant][rentYear-year1][rentMonth-1] = Number(retirementDataRents[key][0]);
                    }
                    
                });

                // format totals
                year1tot = year1tot.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                year2tot = year2tot.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                year3tot = year3tot.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });

                // put totals on page
                $("#rental" + year1).text(year1tot);
                $("#rental" + year2).text(year2tot);
                $("#rental" + year3).text(year3tot);
                $("#rentalInput" + year1).val(year1tot);
                $("#rentalInput" + year2).val(year2tot);
                $("#rentalInput" + year3).val(year3tot);
                $("#rentalDate" + year1).text(year1date);
                $("#rentalDate" + year2).text(year2date);
                $("#rentalDate" + year3).text(year3date);


                // listener for split IRAs button
                $('#splitIRAs').on('click', function(e) {
                    e.preventDefault();

                    // get retirement data
                    var retirementDataAcctNums = @json($retirementDataAcctNums);

                    // Get # TradIRAs
                    var retirementKeys = Object.keys(retirementDataAcctNums);
                    var Mikes = [];
                    var Mauras = [];
                    var Others = [];
                    retirementKeys.forEach( (key,idx) => {
                        var dashIdx = key.indexOf('-');
                        var owner = key.substring(dashIdx + 1);
                        // retirementKeys[idx] = key.substring(0, dashIdx);
                        if(owner == 'MTS') Mikes.push(retirementDataAcctNums[key][0]);
                        else if(owner == 'MMS') Mauras.push(retirementDataAcctNums[key][0]);
                        else Others.push(retirementDataAcctNums[key][0]);
                    });

                    var numberTradIRAKeys = retirementKeys.filter(str => str.startsWith("TradIRA")).length;
                    var numberRothIRAKeys = retirementKeys.filter(str => str.startsWith("RothIRA")).length;

                    // pull ira account info from retirement data
                    var tradAccts = [];
                    for (let i=1; i <= numberTradIRAKeys; i++) {
                        if(typeof retirementDataAcctNums['TradIRA' + i + '-MTS'] !== 'undefined') {
                            tradAccts[i] = retirementDataAcctNums['TradIRA' + i + '-MTS'].slice(0, -1);
                        } else if(typeof retirementDataAcctNums['TradIRA' + i + '-MMS'] !== 'undefined') {
                            tradAccts[i] = retirementDataAcctNums['TradIRA' + i + '-MMS'].slice(0, -1);
                        }
                    }
                    
                    var rothAccts = [];
                    for (let i=1; i <= numberRothIRAKeys; i++) {
                        if(typeof retirementDataAcctNums['RothIRA' + i + '-MTS'] !== 'undefined') {
                            rothAccts[i] = retirementDataAcctNums['RothIRA' + i + '-MTS'].slice(0, -1);
                        } else if(typeof retirementDataAcctNums['RothIRA' + i + '-MMS'] !== 'undefined') {
                            rothAccts[i] = retirementDataAcctNums['RothIRA' + i + '-MMS'].slice(0, -1);
                        }
                    }

                    var inhAcct = retirementDataAcctNums['InhIRA-MMS'].slice(0, -1);
                    var invAcct = retirementDataAcctNums['WF-Inv-Acct'].slice(0, -1);

                    // reformat ira account info into string to pass w/url
                    const params = new URLSearchParams();
                    rothAccts.forEach((account, index) => {
                        params.append(`roth_${index}`, account);
                    });
                    params.append('numberRoth', numberRothIRAKeys);

                    tradAccts.forEach((account, index) => {
                        params.append(`trad_${index}`, account);
                    });
                    params.append('numberTrad', numberTradIRAKeys);

                    params.append("inh", inhAcct);
                    params.append("invest", invAcct);

                    params.append("mikes", Mikes);
                    params.append("mauras", Mauras);

                    // load the new page
                    const url = `/retirement/splitIRAs?${params.toString()}`;
                    window.location.href = url;
                });

                // listener for rental income button
                // show Rental Income inputs at the bottom of the page
                //  fill in with data in retirementData table
                // Allow user to change values and update in database
                $('#rentalIncome').on('click', function(e) {
                    e.preventDefault();

                    // plug text for years on page
                    [0, 1, 2].forEach( thisyear => {
                        $(".year" + thisyear).text(" 20" + eval(`year${thisyear+1}`) + ":");
                    });

                    // for each tenant, year, and month...
                    //      fill in that month's rent,
                    //      and add to yearly total for that year & tenant
                    // If rent before this month, disable the input
                    var disableInput = false;
                    monthlyRentalIncome.forEach( (tenantIncome, tenant) => {
                        tenantIncome.forEach( (yearIncome, year) => {
                            for( var monIdx = 0; monIdx < 12; monIdx++ ) {

                                // selectors for annual rent (for tenant and year)
                                //      and for that specific month
                                const totSelector = "#TotT"+(tenant)+"Y"+year;
                                const monSelector = "#t"+tenant+"y"+year+"m"+monIdx;
                                
                                // get monthly rent to be written to page, and added to total
                                var thisMonRent = yearIncome[monIdx];

                                // set rent to 0 and disable input if rent is in the past
                                if(year == 0 && Number(monIdx+1 < thisMonth) && year == 0) {
                                    thisMonRent = 0;
                                    disableInput = true;
                                } else {
                                    // if no rent found in db, set to 0; make sure it's a number
                                    if(thisMonRent == 'undefined') thisMonRent = 0;
                                    else thisMonRent = Number(thisMonRent);
                                    disableInput = false;
                                }

                                // add this to the yearly total, and format
                                const newTotValue = (Number( $(totSelector).text().replace(/,/g, "")) + thisMonRent).toLocaleString('en-US', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                });

                                // format thisMonRent
                                thisMonRent = thisMonRent.toLocaleString('en-US', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                });

                                // write this month's rent and updated total to page
                                $(totSelector).html(newTotValue);
                                $(monSelector).val(thisMonRent);
                                // disable input, if indicated (in the past)
                                if(disableInput) {
                                    $(monSelector).prop("disabled", true);
                                }
                            }
                        });

                    });

                    // make Rental incomes visible and scroll there
                    $(".rentalIncomeBlock").css({"content-visibility": "visible", "overflow-y": "auto", "height": "1000px"});
                    window.setTimeout(function() {
                        $(window).scrollTop(1400); 
                    }, 0);

                });

                // listener for rent changed - update corresponding total
                $('.monthRent').on('change', function(e) {
                    e.preventDefault();

                    // get prefix for selectors for monthly rents
                    const selectorPrefix = "#" + $(this).attr('id').substr(0, 4);

                    // get selector for annual rent
                    const totSelector = "#Tot" + $(this).attr('id').substr(0, 4).toUpperCase();
                    
                    // add monthly rents to get total
                    var newAnnualTot = 0;
                    for(var monIdx = 0; monIdx < 12; monIdx++) {
                        const thisSelector = selectorPrefix + "m" + String(monIdx);
                        newAnnualTot += Number($(thisSelector).val());
                    }

                    // put new annual rent on page
                    $(totSelector).text ( newAnnualTot );

                });

                // listener to save changes for retirement forecast
                $('.save-correction-button').on('click', function(e) {
                    e.preventDefault();
                    var origValue = $(this).parent().prev().prev().prev().text().trim();
                    var newValue = $(this).parent().prev().find("input").val().trim();
                    var fieldChanged = $(this).data('item-id');
                    var type = $(this).parent().prev().prev().text();

                    // wipe out modified field if newValue is the same as the original
                    // straight compare; then compare values if it's a number (not d for date)
                    if( origValue == newValue) newValue = null;
                    else if( parseFloat(origValue) == parseFloat(newValue) && type != 'd') newValue = null;

                    // save the change.
                    $.ajax({
                        url: '{{ route('writeRetirementDatum') }}',
                        method: 'POST',
                        data: JSON.stringify({
                            _token: '{{ csrf_token() }}',
                            fieldChanged: fieldChanged,
                            newValue: newValue,
                            type: type
                        }),
                        dataType: 'json',
                        // contentType: false,
                        success: function(response) {
                            alert("New value " + newValue + " for " + fieldChanged + " saved.");
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                            alert("Error saving change.");
                        }
                    });
                });

                // listener to do retirement forecast
                $('#retirementForecast').on('click', function(e) {
                    var retirementInput = {};

                    // Iterate over all input fields, write values to use in DB.
                    $('input').each(function() {
                        const input = $(this);
                        const name = input.attr('name');
                        const value = input.val();
                        const field = input.attr('data-field');
                        
                        retirementInput[field] = value;
                        if(name != "retCorrection[]") {
                            return false;
                        }
                    });

                    // save data to do forecast with.
                    $.ajax({
                        type: 'POST',
                        url: '{{ route('writeRetirementInput') }}',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: JSON.stringify(retirementInput),
                        contentType: 'application/json',

                        success: function(response) {
                            console.log("Input retirement data saved to retirementData table.");
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                            alert("Error saving retirement input data.");
                        }
                    });

                });
            });



        </script>
    </body>

</html>