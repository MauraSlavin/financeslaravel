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
        <h1>Retirement Forecast</h1> 


        <div class="retirementForecast">
            <table id="retirementForecastTable" class="table table-striped table-bordered" style="background-color: lavender;">
                <thead>
                    <tr>
                        <th style="width: 75px;" class="sticky-top bg-info">Type</th>
                        <th style="width: 20px;" class="sticky-top bg-info">Item</th>
                        @php 
                            $currentYear = date("Y");
                            $forecastLength = 2062 - $currentYear;
                            $forecastYears = range($currentYear, $currentYear + $forecastLength);
                        @endphp
                        @foreach($forecastYears as $year)
                            <th style="width: 75px;" class="text-center sticky-top bg-info">
                                {{ $year }}
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody id="forecastBody"  style="text-align: right;">

                    <!-- Ages -->
                    @php
                        $mikeage = $currentYear - 1959;
                        $mikeAges = range($mikeage, $mikeage + $forecastLength);
                        $mauraage = $currentYear - 1962;
                        $mauraAges = range($mauraage, $mauraage + $forecastLength);
                    @endphp
                    <tr>
                        <td>Mike turns</td>
                        <td></td>
                        @foreach($mikeAges as $age)
                            <td>{{ $age }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td>Maura turns</td>
                        <td></td>
                        @foreach($mauraAges as $age)
                            <td>{{ $age }}</td>
                        @endforeach
                    </tr>

                    <!-- break -->
                    <tr>
                        <td style="background-color: #36454F; height: 10px;"></td>
                        <td style="background-color: #36454F;"></td>
                        @foreach($forecastYears as $year)
                            <td style="background-color: #36454F;"></td>
                        @endforeach
                    </tr>

                    <!-- Beginning Balances --> 
                    @php 
                        $accountNames = ["Spending", "Investment", "Taxable Retirement", "Tax Free Retirement"];
                        $accountValues = [
                            $spending, 
                            $investments,
                            $retirementTaxable,
                            $retirementNonTaxable
                        ];
                    @endphp
                    <tr id="beginningForecast">
                        <td style="background-color: blue; color: white;">Beginning balances</td>
                        <td style="background-color: blue; color: white;"></td>
                        @foreach($forecastYears as $idxYear => $year)
                            @if($idxYear == 0)
                            <td style="background-color: blue; color: white;">{{ $date }}</td>
                            @else
                            <td style="background-color: blue; color: white;">Jan 1</td>
                            @endif
                        @endforeach
                    </tr>
                    @foreach($accountNames as $acctIdx=>$account)
                        <tr>
                            <td></td>
                            <td>{{ $account }}</td>
                            @foreach($forecastYears as $yearIdx=>$year)
                                <td>{{ number_format((float)$accountValues[$acctIdx][$yearIdx]) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                    <!-- subtotal -->
                    <tr>
                        <td style="background-color: lightblue;">Sub-total:</td>
                        <td style="background-color: lightblue;"></td>
                        @foreach($forecastYears as $idxSubTot=>$year)
                            <td style="background-color: lightblue;">{{ number_format((float)$beginBalances[$idxSubTot]) }}</td>
                        @endforeach                        
                    </tr>

                    <!-- break -->
                    <tr>
                        <td style="background-color: #36454F; height: 10px;"></td>
                        <td style="background-color: #36454F;"></td>
                        @foreach($forecastYears as $year)
                            <td style="background-color: #36454F;"></td>
                        @endforeach
                    </tr>

                    <!-- Income --> 
                    @php 
                        $accountNames = ["Town of Durham", "GB Limo", "Rental", "NH Retirement", "Mike IBM", "Mike SS", "Maura IBM", "Maura SS", "Tax Retire", "Non-Tax Retire", "Investment Growth"];
                        // NO inherited IRA - income from that goes to LTC
                    @endphp
                    <tr id="incomeForecast">
                        <td style="background-color: green; color: white;">Income</td>
                        <td style="background-color: green; color: white;"></td>
                        @foreach($forecastYears as $idxYear => $year)
                            @if($idxYear == 0)
                            <td style="background-color: green; color: white;">After {{ $date }}</td>
                            @else
                            <td style="background-color: green; color: white;"></td>
                            @endif
                        @endforeach
                    </tr>
                    @foreach($accountNames as $acctIdx=>$account)
                        <tr>
                            <td></td>
                            <td>{{ $account }}</td>
                            @foreach($forecastYears as $yearIdx=>$year)
                                <td>{{ number_format((float)$incomeValues[$acctIdx][$yearIdx]) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                    <!-- subtotal -->
                    <tr>
                        <td style="background-color: lightgreen;">Sub-total:</td>
                        <td style="background-color: lightgreen;"></td>
                        @foreach($forecastYears as $idxSubTot=>$year)
                            <td style="background-color: lightgreen;">{{ number_format((float)$incomeSubTots[$idxSubTot]) }}</td>
                        @endforeach                        
                    </tr>

                    <!-- break -->
                    <tr>
                        <td style="background-color: #36454F; height: 10px;"></td>
                        <td style="background-color: #36454F;"></td>
                        @foreach($forecastYears as $year)
                            <td style="background-color: #36454F;"></td>
                        @endforeach
                    </tr>

                    <!-- Expenses --> 
                    @php 
                        $subCatNames = array_keys($ytdExpensesBySubcategory);
                        // NO inherited IRA - income from that goes to LTC (no longer true)
                    @endphp
                    <tr id="expenseForecast">
                        <td style="background-color: red; color: white;">Expenses</td>
                        <td style="background-color: red; color: white;"></td>
                        @foreach($forecastYears as $idxYear => $year)
                            @if($idxYear == 0)
                            <td style="background-color: red; color: white;">After {{ $date }}</td>
                            @else
                            <td style="background-color: red; color: white;"></td>
                            @endif
                        @endforeach
                    </tr>
                    @foreach($subCatNames as $expIdx=>$subcat)
                        <tr>
                            <td></td>
                            <td>{{ $subcat }}</td>
                            <td>{{ $ytdExpensesBySubcategory[$subcat] }}</td>
                            <!-- left off here -->
                            <!-- add expenses for subsequent years here -->

                        </tr>
                    @endforeach
                    <!-- subtotal -->
                    <tr>
                        <td style="background-color: pink;">Sub-total:</td>
                        <td style="background-color: pink;"></td>

                        <!-- add sub totals here --> 

                    </tr>

                    @php 
                        $accountNames = ["Total"];
                        $accountValues = [
                            [-1000, -2000, -3000, -400, -500, -600, -700, -800, -800, -100, -1100, -1200, -1300, -1400, -1500, -1600, -1700, -1800, -1900, -200, -2100, -2200, -2300, -2400, -2500, -2600, -2700, -2800, -2900, -300, -100, -200, -300, -400, -500, -600, -700, -800, -800, -100, -1100, -1200, -1300, -1400, -1500, -1600, -1700, -1800, -1900, -200, -2100, -2200, -2300, -2400, -2500, -2600, -2700, -2800, -2900, -300, -100, -200, -300, -400, -500, -600, -700, -800, -800, -100, -1100, -1200, -1300, -1400, -1500, -1600, -1700, -1800, -1900, -200, -2100, -2200, -2300, -2400, -2500, -2600, -2700, -2800, -2900, -300, -100, -200, -300, -400, -500, -600, -700, -800, -800, -100, -11]
                            // [-100, -200, -300, -400, -500, -600, -700, -800, -800, -100, -1100, -200, -300, -400, -500, -600, -700, -800, -800, -200, -200, -2200, -2300, -2400, -2500, -2600, -2700, -2800, -2900, -300, -400, -200, -300, -400, -500, -600, -700, -800, -800, -1000, -1100, -200, -300, -400, -500, -600, -700, -800, -800, -200, -200, -2200, -2300, -2400, -2500, -2600, -2700, -2800, -2900, -300, -1100, -200, -300, -400, -500, -600, -700, -800, -800, -1100, -11100, -200, -300, -400, -500, -600, -700, -800, -800, -200, -200, -2200, -2300, -2400, -2500, -2600, -2700, -2800, -2900, -300, -100, -200, -300, -400, -500, -600, -700, -800, -800, -11100, -111 ],
                            // [-100, -200, -300, -40, -50, -60, -70, -80, -80, -10, -110, -120, -130, -140, -150, -160, -170, -180, -190, -20, -210, -220, -230, -240, -250, -260, -270, -280, -290, -30, -10, -20, -30, -40, -50, -60, -70, -80, -80, -10, -110, -120, -130, -140, -150, -160, -170, -180, -190, -20, -210, -220, -230, -240, -250, -260, -270, -280, -290, -30, -10, -20, -30, -40, -50, -60, -70, -80, -80, -10, -110, -120, -130, -140, -150, -160, -170, -180, -190, -20, -210, -220, -230, -240, -250, -260, -270, -280, -290, -30, -10, -20, -30, -40, -50, -60, -70, -80, -80, -10, -11],
                            // [-10, -20, -30, -40, -50, -60, -70, -80, -80, -10, -110, -20, -30, -40, -50, -60, -70, -80, -80, -20, -20, -220, -230, -240, -250, -260, -270, -280, -290, -30, -40, -20, -30, -40, -50, -60, -70, -80, -80, -100, -110, -20, -30, -40, -50, -60, -70, -80, -80, -20, -20, -220, -230, -240, -250, -260, -270, -280, -290, -30, -110, -20, -30, -40, -50, -60, -70, -80, -80, -110, -1110, -20, -30, -40, -50, -60, -70, -80, -80, -20, -20, -220, -230, -240, -250, -260, -270, -280, -290, -30, -10, -20, -30, -40, -50, -60, -70, -80, -80, -1110, -111 ],
                        ];
                    @endphp
                    <tr id="expensesForecast">
                        <td style="background-color: red; color: white;">Expenses</td>
                        <td style="background-color: red; color: white;"></td>
                        @foreach($forecastYears as $idxYear => $year)
                            @if($idxYear == 0)
                            <td style="background-color: red; color: white;">After {{ $date }}</td>
                            @else
                            <td style="background-color: red; color: white;"></td>
                            @endif
                        @endforeach
                    </tr>
                    @foreach($accountNames as $acctIdx=>$account)
                        <tr>
                            <td></td>
                            <td>{{ $account }}</td>
                            @foreach($forecastYears as $yearIdx=>$year)
                                <td>{{ number_format((float)$accountValues[$acctIdx][$yearIdx]) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                    <!-- subtotal -->
                    <tr>
                        <td style="background-color: pink;">Sub-total:</td>
                        <td style="background-color: pink;"></td>
                        @foreach($forecastYears as $year)
                            <td style="background-color: pink;">(calc)</td>
                        @endforeach                        
                    </tr>

                    <!-- break -->
                    <tr>
                        <td style="background-color: #36454F; height: 10px;"></td>
                        <td style="background-color: #36454F;"></td>
                        @foreach($forecastYears as $year)
                            <td style="background-color: #36454F;"></td>
                        @endforeach
                    </tr>

                    <!-- Ending Balances --> 
                    @php 
                        $accountNames = ["Spending", "Investment", "Taxable Retirement", "Tax Free Retirement"];
                        $accountValues = [
                            [10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110, 120, 130, 140, 150, 160, 170, 180, 190, 200, 210, 220, 230, 240, 250, 260, 270, 280, 290, 300, 10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110, 120, 130, 140, 150, 160, 170, 180, 190, 200, 210, 220, 230, 240, 250, 260, 270, 280, 290, 300, 10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110, 120, 130, 140, 150, 160, 170, 180, 190, 200, 210, 220, 230, 240, 250, 260, 270, 280, 290, 300, 10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110 ],
                            [11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111 ],
                            [10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110, 120, 130, 140, 150, 160, 170, 180, 190, 200, 210, 220, 230, 240, 250, 260, 270, 280, 290, 300, 10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110, 120, 130, 140, 150, 160, 170, 180, 190, 200, 210, 220, 230, 240, 250, 260, 270, 280, 290, 300, 10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110, 120, 130, 140, 150, 160, 170, 180, 190, 200, 210, 220, 230, 240, 250, 260, 270, 280, 290, 300, 10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110 ],
                            [11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111 ]
                        ];
                    @endphp
                    <tr id="endingForecast">
                        <td style="background-color: blue; color: white;">Ending balances</td>
                        <td style="background-color: blue; color: white;"></td>
                        @foreach($forecastYears as $year)
                            <td style="background-color: blue; color: white;">Dec 31</td>
                        @endforeach
                    </tr>
                    @foreach($accountNames as $acctIdx=>$account)
                        <tr>
                            <td></td>
                            <td>{{ $account }}</td>
                            @foreach($forecastYears as $yearIdx=>$year)
                                <td>{{ number_format((float)$accountValues[$acctIdx][$yearIdx]) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                    <!-- subtotal -->
                    <tr>
                        <td style="background-color: lightblue;">Sub-total:</td>
                        <td style="background-color: lightblue;"></td>
                        @foreach($forecastYears as $year)
                            <td style="background-color: lightblue;">(calc)</td>
                        @endforeach                        
                    </tr>

                    <!-- break -->
                    <tr>
                        <td style="background-color: #36454F; height: 10px;"></td>
                        <td style="background-color: #36454F;"></td>
                        @foreach($forecastYears as $year)
                            <td style="background-color: #36454F;"></td>
                        @endforeach
                    </tr>
                    
                    <!-- misc (LTC, etc) -->
                    @php 
                        $accountNames = ["LTC", "House"];
                        $accountValues = [
                            [11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111 ],
                            [500000, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111 ]
                        ];
                    @endphp
                    <tr>
                        <td style="background-color: purple; color: white;">Misc Balances</td>
                        <td style="background-color: purple; color: white;"></td>
                        @foreach($forecastYears as $idxYear => $year)
                            @if($idxYear == 0)
                            <td style="background-color: purple; color: white;">{{ $date }}</td>
                            @else
                            <td style="background-color: purple; color: white;">Dec 31</td>
                            @endif
                        @endforeach
                    </tr>
                    @foreach($accountNames as $acctIdx=>$account)
                        <tr>
                            <td></td>
                            <td>{{ $account }}</td>
                            @foreach($forecastYears as $yearIdx=>$year)
                                <td>{{ number_format((float)$accountValues[$acctIdx][$yearIdx]) }}</td>
                            @endforeach
                        </tr>
                    @endforeach             

                </tbody>
            </table>

            NOTES:
            <ul>
                <li>IRA/Retirement distributions go to Spending</li>
                <li>LTC in budget goes to LTC in Misc Balances</li>
                <li>Income from Inherited IRA intended for LTC</li>
                <li>Investment Growth: (end bal before growth - begin bal)/2 * growth
                    <br>or (begin bal - w/ds) * growth
                </li>
                <li>Assume "Irregular Big" expenses are spent, so don't keep track of balance</li>
                <li>Spending:
                    <ul>
                        <li>Savings</li>
                        <li>Checking</li>
                        <li>Big Bills</li>
                        <li>subtract CC (Disc & VISA) balances</li>
                    </ul>
                </li>
                <li>Investment:
                    <ul>
                        <li>WF non-IRA</li>
                        <li>EJ</li>
                    </ul>
                </li>
                <li>Taxable Retirement:
                    <ul>
                        <li>Trad IRA (- LTC portion)</li>
                        <li>TIAA</li>
                        <li>Retirement savings (Disc, Disc Svgs Ret bucket)</li>
                    </ul>
                </li>
                <li>Non-Taxable Retirement:
                    <ul>
                        <li>Roth IRA</li>
                    </ul>
                </li>
                <li>LTC:
                    <ul>
                        <li>Disc LTC</li>
                        <li>Part of WF we transferred for LTC ($ 17,959.64 on 9/30/25)</li>
                        <li>Inherited IRA (income stays in LTC)</li>
                    </ul>
                </li>
                <li>Not included:
                    <ul>
                        <li>M&M Spending accts</li>
                        <li>Security Deposits (not ours)</li>
                        <li>Irreg Big Bills</li>
                        <li>Prudential LI</li>
                        <li>House</li>
                    </ul>
                </li>
            </ul>
        </div>

        
        <script>

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $(document).ready(function() {



            });



        </script>
    </body>

</html>