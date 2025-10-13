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
                        <th style="width: 75px;">date</th>

                    </tr>
                </thead>

                <!-- retirement input data -->
                <tbody>
                    <!-- show data for each type of data -->
                    @php
                        $types =    ["Assumption",               "Income",               "Values",               "Balances",                "Constants"];
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
                        <!-- retirement data points -->
                        @foreach($vars[$idx] as $key=>$balance)
                            @if( $balance[1] == 'd')
                                @php
                                    $value = substr($balance[0], 0, 2) . "/" . substr($balance[0], 2, 2) . "/" . substr($balance[0], 4) ?? NULL;
                                @endphp
                            @else
                                @php
                                    $value = number_format($balance[0] ?? NULL, 2);
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
                                <td style="width: 5px;">{{ $balance[1] ?? NULL }}</td>

                                <!-- input field to override stored value -->
                                <td class="retInputCorrection">
                                    <input 
                                        class="retCorrection" 
                                        name="retCorrection[]" 
                                        style="width: 100%; text-align: right;" 
                                        value={{ $value }}
                                    >
                                </td>

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
                                    <td style="width: 5px;">$</td>

                                    <!-- input field to override stored value -->
                                    <td class="retInputCorrection">
                                        <input 
                                            class="retCorrection" 
                                            id="rentalInput{{ $year }}"
                                            name="retCorrection[]" 
                                            style="width: 100%; text-align: right;" 
                                        >
                                    </td>

                                    <!-- date of value -->
                                    <td class="retInputDate" id="rentalDate{{ $year }}"></td>
                                </tr>                            
                            @endforeach
                        @endif

                    @endforeach

                </tbody>
            </table>
        </div>

        
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
                $('#rentalIncome').on('click', function(e) {
                    e.preventDefault();

                });

            });



        </script>
    </body>

</html>