<html>
    <head>
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
        <script src="https://unpkg.com/@popperjs/core@2"></script>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script> -->
        <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <meta name="csrf-token" content="{{ csrf_token() }}">
    </head>

    <body style="background":"#ccc none repeat scroll 0 0">

        <!-- include common functions -->
        <script src="{{ asset('js/commonFunctions.js') }}"></script>

        <!-- headers -->
        <h1>Retirement Forecast</h1> 
        <!-- hidden fields to use in Script -->
        <div hidden id="inflationFactors">{{ json_encode($inflationFactors) }}</div>
        <div hidden id="expectedExpensesForThisYearByCategory">{{ json_encode($expectedExpensesForThisYearByCategory) }}</div>
        <div hidden id="expenseCategoriesWithSummaryCats">{{ json_encode($expenseCategoriesWithSummaryCats) }}</div>
        <div hidden id="sumCategoriesWithDetailCategories">{{ json_encode($sumCategoriesWithDetailCategories) }}</div>
        <div hidden id="defaultInflationFactor">{{ $defaultInflationFactor }}</div>
        <div hidden id="incomeValues">{{ json_encode($incomeValues) }}</div>
        <div hidden id="retirementParameters">{{ json_encode($retirementParameters) }}</div>
        <div hidden id="lastYearRetirementIncome">{{ json_encode($lastYearRetirementIncome) }}</div>
        @php 
        $currentYear = date("Y");
        $forecastLength = 2062 - $currentYear;
        $forecastYears = range($currentYear, $currentYear + $forecastLength);
        @endphp
        <div hidden id="forecastYears">{{ json_encode($forecastYears) }}</div>
        <div hidden id="currentYear">{{ $currentYear }}</div>
        <div hidden id="date">{{ $date }}</div>

        <div class="retirementForecast">
            <table id="retirementForecastTable" class="table table-striped table-bordered" style="background-color: lavender;">
                <thead>
                    <tr>
                        <th style="width: 75px;" class="sticky-top bg-info">Type</th>
                        <th style="width: 20px;" class="sticky-top bg-info">Item</th>
                        <th sytle="width: 20px;" class="sticky-top bg-info">Inf</th>
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
                        <td></td>
                        @foreach($mikeAges as $age)
                            <td>{{ $age }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td>Maura turns</td>
                        <td></td>
                        <td></td>
                        @foreach($mauraAges as $age)
                            <td>{{ $age }}</td>
                        @endforeach
                    </tr>

                    <!-- break -->
                    <tr>
                        <td style="background-color: #36454F; height: 10px;"></td>
                        <td style="background-color: #36454F;"></td>
                        <td style="background-color: #36454F;"></td>
                        @foreach($forecastYears as $year)
                            <td style="background-color: #36454F;"></td>
                        @endforeach
                    </tr>

                    <!-- Beginning Balances --> 
                    @php 
                        $spendingAccountNames = ["Spending", "Investment", "Taxable Retirement", "Tax Free Retirement"];
                        $accountValues = [
                            $spending, 
                            $investments,
                            $retirementTaxable,
                            $retirementNonTaxable
                        ];
                    @endphp
                    <div hidden id="balanceCategories">{{ json_encode(str_replace(' ', '', $spendingAccountNames)) }}</div>
                    <tr id="beginningForecast">
                        <td style="background-color: blue; color: white;">Beginning balances</td>
                        <td style="background-color: blue; color: white;"></td>
                        <td style="background-color: blue; color: white;"></td>
                        @foreach($forecastYears as $idxYear => $year)
                            @if($idxYear == 0)
                            <td style="background-color: blue; color: white;">{{ $date }}</td>
                            @else
                            <td style="background-color: blue; color: white;">Jan 1</td>
                            @endif
                        @endforeach
                    </tr>
                    @php 
                        $acctsIncludedArray = [
                            $spendingAccts,   // Spending
                            $invAccts, // Investment
                            $retTaxAccts,  // taxable retirement accts
                            $retNonTaxAccts   // tax free retirement accts
                        ];
                    @endphp
                    @foreach($spendingAccountNames as $acctIdx=>$account)
                        <tr>
                            <td></td>
                            @php 
                                $accountForId = str_replace(' ', '', $account);
                                if($account == 'Taxable Retirement') {
                                    $acctTooltip .= ' (minus some LTC from Trad IRA)';
                                } else {
                                    $acctTooltip = '';
                                }
                            @endphp
                            <td data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="{{ $acctsIncludedArray[$acctIdx] }}{{ $acctTooltip }}">
                                {{ $account }}
                            </td>
                            <td></td>
                            @foreach($forecastYears as $yearIdx=>$year)
                                <td id="{{$accountForId}}{{$year}}">{{ number_format((float)$accountValues[$acctIdx][$yearIdx]) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                    <!-- subtotal -->
                    <tr>
                        <td style="background-color: lightblue;">Sub-total:</td>
                        <td style="background-color: lightblue;"></td>
                        <td style="background-color: lightblue;"></td>
                        @foreach($forecastYears as $idxSubTot=>$year)
                            <td style="background-color: lightblue;">{{ number_format((float)$beginBalances[$idxSubTot]) }}</td>
                        @endforeach                        
                    </tr>

                    <!-- break -->
                    <tr>
                        <td style="background-color: #36454F; height: 10px;"></td>
                        <td style="background-color: #36454F;"></td>
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
                        <td></td>
                        @foreach($forecastYears as $yearIdx=>$year)
                            @php 
                                $incomeValueArray = json_decode($incomeValues[$acctIdx]);
                                $htmlId = str_replace(' ', '', $account) . $year;
                            @endphp
                            <td id="{{ $htmlId }}">{{ number_format((float)$incomeValueArray[$yearIdx]) }}</td>
                        @endforeach
                    </tr>
                    @endforeach
                    <!-- subtotal -->
                    <tr>
                        <td style="background-color: lightgreen;">Sub-total:</td>
                        <td style="background-color: lightgreen;"></td>
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
                        $sumCatNames = array_keys($expectedExpensesAfterTodayBySUMMARYCategory);
                        // NO inherited IRA - income from that goes to LTC (no longer true)
                    @endphp
                    <!-- header row for expenses, with date on first column -->
                    <tr id="expenseForecast">
                        <td style="background-color: red; color: white;">Expenses</td>
                        <td style="background-color: red; color: white;"></td>
                        <td style="background-color: red; color: white;"></td>
                        @foreach($forecastYears as $idxYear => $year)
                            @if($idxYear == 0)
                            <td style="background-color: red; color: white;">After {{ $date }}</td>
                            @else
                            <td style="background-color: red; color: white;"></td>
                            @endif
                        @endforeach
                    </tr>

                    <!-- row for each EXPENSE summary category -->
                    @foreach($sumCatNames as $expIdx=>$sumcat)
                        <!-- get $categories string -->
                        @php 
                            $categories = [];
                            foreach($expenseCategoriesWithSummaryCats as $sums) {
                                if($sums->summaryCategory == $sumcat) $categories[] = $sums->name;
                            }
                            $categories = implode(", ", $categories);
                        @endphp
                        <!-- SUMMARY category -->
                        <tr>
                            <td  style="background-color: lightpink;"></td>
                            <!-- show categories for each summary category when hovered -->
                            <td data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="{{ $categories }}" style="background-color: lightpink;">
                                {{ $sumcat }}
                            </td>
                            <td  style="background-color: lightpink;"></td>  <!-- inflation column -->
                            <td style="background-color: lightpink;">{{ $expectedExpensesAfterTodayBySUMMARYCategory[$sumcat] }}</td>
                            <!-- summary category expenses for subsequent years -->
                            @foreach($forecastYears as $idxYear => $year)
                                @if($idxYear != 0)
                                <td id="{{ $sumcat }}{{ $year }}SUM" style="background-color: lightpink;">{{ $sumcat }}{{ $year }}</td>
                                @endif
                            @endforeach

                        </tr>
                        <!-- row for each EXPENSE detail category --> 
                         @foreach($sumCategoriesWithDetailCategories[$sumcat] as $detailCategory)
                            <tr>
                                <td></td>
                                <!-- show categories for each summary category when hovered -->
                                <td>{{ $detailCategory }}</td>
                                <td id="{{ $detailCategory }}INF">{{ $detailCategory }}inf</td>
                                <!-- expenses for subsequent years -->
                                @foreach($forecastYears as $idxYear => $year)
                                    @if($idxYear == 0)
                                    <td id="{{ $detailCategory }}{{ $year }}">{{ round($expectedExpensesAfterTodayByCategory[$detailCategory] ?? 0 ) }}</td>
                                    @else
                                    <td id="{{ $detailCategory }}{{ $year }}">{{ $detailCategory }}{{ $year }}</td>
                                    @endif
                                @endforeach
                            </tr>
                         @endforeach
                    @endforeach

                    <!-- EXPENSES subtotals -->
                    <tr>
                        <td style="background-color: pink;">Sub-total:</td>
                        <td style="background-color: pink;"></td>
                        <td style="background-color: pink;">{{ $expectedExpensesAfterTodayTotal }}</td>  <!-- current year -->
                        <!-- add sub totals here --> 
                        @foreach($forecastYears as $idxYear => $year)
                            @if($idxYear != 0)   <!-- first column is already done -->
                            <td id="expenses{{ $year }}" style="background-color: pink;">{{ $expectedExpensesAfterTodayTotal }}</td>  <!-- current year -->
                            @endif
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
                        <td style="background-color: blue; color: white;"></td>
                        @foreach($forecastYears as $year)
                            <td style="background-color: blue; color: white;">Dec 31</td>
                        @endforeach
                    </tr>
                    @foreach($spendingAccountNames as $acctIdx=>$account)
                        <tr>
                            <td></td>
                            <td>{{ $account }}</td>
                            <td></td>
                            @foreach($forecastYears as $yearIdx=>$year)
                                <td id="end{{ str_replace(' ', '', $account) }}{{$year}}">{{ number_format((float)$accountValues[$acctIdx][$yearIdx]) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                    <!-- subtotal -->
                    <tr>
                        <td style="background-color: lightblue;">Sub-total:</td>
                        <td style="background-color: lightblue;"></td>
                        <td style="background-color: lightblue;"></td>
                        @foreach($forecastYears as $year)
                            <td style="background-color: lightblue;">(calc)</td>
                        @endforeach                        
                    </tr>

                    <!-- break -->
                    <tr>
                        <td style="background-color: #36454F; height: 10px;"></td>
                        <td style="background-color: #36454F;"></td>
                        <td style="background-color: #36454F;"></td>
                        @foreach($forecastYears as $year)
                            <td style="background-color: #36454F;"></td>
                        @endforeach
                    </tr>
                    
                    <!-- misc (LTC, etc) -->
                    @php 
                        $accountNames = ["LTC", "House"];
                        $accountValues = [
                            [$initLTCBal, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111 ],
                            [500000, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111 ]
                        ];
                    @endphp
                    <tr>
                        <td style="background-color: purple; color: white;">Misc Balances</td>
                        <td style="background-color: purple; color: white;"></td>
                        <td style="background-color: purple; color: white;"></td>
                        @foreach($forecastYears as $idxYear => $year)
                            @if($idxYear == 0)
                            <td style="background-color: purple; color: white;">{{ $date }}</td>
                            @else
                            <td style="background-color: purple; color: white;">Dec 31</td>
                            @endif
                        @endforeach
                    </tr>

                    <!-- LTC goal amts -->
                    <tr>
                        <td></td>
                        <td  data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="Goal amt at year end assuming $7500 annual contrib beginning 2021 at 5% interest">
                            LTC goal
                        </td>
                        <td></td>
                        @foreach($forecastYears as $yearIdx=>$year)
                            <td id="LTCgoal{{$year}}"></td>
                        @endforeach
                    </tr>
                    @foreach($accountNames as $acctIdx=>$account)
                        <tr>
                            <td></td>
                            @if($account == 'LTC')
                            <td  data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="Inherited IRA, Discover LTC, rest in WF Trad IRA">{{ $account }}
                            </td>
                            @else
                            <td>{{ $account }}</td>
                            @endif
                            <td></td>
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
                <li>Income from Inherited IRA should be earmarked for LTC</li>
                <li>Health care inflation assumed to be 5% (in budget table)
                    <br> - US Health Care Inflation Rate is at 3.05%, compared to 3.28% last month and 3.08% last year. This is lower than the long term average of 5.09%.
                    <br> - Source: https://ycharts.com/indicators/us_health_care_inflation_rate#:~:text=Basic%20Info,the%20US%20Consumer%20Price%20Index.
                </li>
                <li>See 
                    <br> - https://docs.google.com/spreadsheets/d/10UFYi7Hiqd_y4q02vT85QjEXc1MJep27Kw3PYU7lmns/edit?gid=1813417080#gid=1813417080
                    <br> for details on future "Doctor" estimates
                <li>Assume "Irregular Big" expenses are spent, so don't keep track of balance</li>
                <li>Spending:
                    <ul>
                        <li>Savings (Big Bills)</li>
                        <li>Checking</li>
                        <li>subtract CC (Disc & VISA) balances - I'm not doing this yet (1/15/26)</li>
                    </ul>
                </li>
                <li>Not included:
                    <ul>
                        <li>M&M Spending accts</li>
                        <li>Security Deposits (not ours)</li>
                        <li>Irreg Big Bills</li>
                        <li>Prudential LI</li>
                    </ul>
                </li>
                <li>Expenses for current year are the max of the budget vs. actual expenses between the first of the current month through the end of the year.</li>
            </ul>
        </div>

        
        <script>
            // needed for tooltips
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            // const tooltipList = [...tooltipTriggerList].map(el => new bootstrap.Tooltip(tooltipTriggerEl));

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $(document).ready(function() {

                // calculate future forecasted expenses
                function calcFutureExpenses(forecastYears, expenseCategoriesWithSummaryCats, sumCategoriesWithDetailCategories, expectedExpensesForThisYearByCategory, inflationFactors, defaultInflationFactor, incomeValues, retirementParameters) {

                    function calcIncomeRelatedExpense(year, currentYear, lastYearsExpense, inflationFactor, incomeValue) {
                            
                        var yearIdx = year-currentYear;
                        var thisYearsExpense = 0;
                        incomeValue = JSON.parse(incomeValue);

                        // if no income, thisYearsExpense is 0
                        if(incomeValue[yearIdx] == 0 ) thisYearsExpense = 0;
                        // if there is income, increase Expense by inflation factor
                        else {
                            thisYearsExpense = Math.round(lastYearsExpense * (1 + inflationFactor/100));
                        }
                        return thisYearsExpense;
                    }   // end of function calcIncomeRelatedExpense

                    function getDoctorExpense(year, lastYearsExpense, inflationFactor, retirementParameters) {

                        // use estimates in retirementdata if they exist;
                        //  otherwise bump up by inflation factor
                        if(typeof retirementParameters['Doctor' + year] != 'undefined') {
                            thisYearsExpense =  -parseInt(retirementParameters['Doctor' + year]);
                        } else {
                            thisYearsExpense = Math.round(lastYearsExpense * (1 + inflationFactor/100));
                        }

                        return thisYearsExpense;
                    }   // end of function getDoctorExpense

                    // last year expenses set to current year expenses by category
                    var lastYearsExpenses = expectedExpensesForThisYearByCategory;

                    // categories with different (non-default) inflation factors
                    var inflationFactorCategories = Object.keys(inflationFactors);

                    console.log("expectedExpensesForThisYearByCategory:", expectedExpensesForThisYearByCategory);
                    // for each year starting with next year
                    forecastYears.shift();

                    var futureExpenses = [];
                    var futureExpensesSummary = [];
                    var futureExpensesYearlyTotal = [];
                    var summaryCategories = [];

                    const currentYear = $('#currentYear').text();
                    console.log("currentYear: ", currentYear);
                    
                    forecastYears.forEach(year => {
                        console.log("------------ Year: ",year," ----------------");
                        futureExpenses[year] = [];
                        futureExpensesSummary[year] = [];
                        futureExpensesYearlyTotal[year] = 0;
                        
                        // init this year's expenses by category & summary category
                        futureExpenses[year] = [];
                        futureExpensesSummary[year] = [];
                        expenseCategoriesWithSummaryCats.forEach( summary => {
                            const category = summary['name'];
                            const summaryCategory = summary['summaryCategory'];
                            futureExpenses[year][category] = 0;
                            if(typeof futureExpensesSummary[year][summaryCategory] == 'undefined') {
                                futureExpensesSummary[year][summaryCategory] = 0;
                                summaryCategories.push(summaryCategory);
                            }
                        });

                        // for each catagory, 
                        expenseCategoriesWithSummaryCats.forEach( summary => {
                            const category = summary['name'];
                            console.log("category: ", category);
                            const summaryCategory = summary['summaryCategory'];

                            // get inflation factor
                            if(inflationFactorCategories.includes(category)) {
                                inflationFactor = inflationFactors[category];
                            } else {
                                inflationFactor = defaultInflationFactor;
                            }

                            // put inflation factor on page, highlighing non-default values
                            if(inflationFactor == defaultInflationFactor) {
                                $('#' + category + 'INF').text(inflationFactor);
                            } else {
                                $('#' + category + 'INF').text(inflationFactor).css('background-color', 'yellow');
                            }
                            // handle special cases separately
                            // rental expense and work expense (only when there's rental income or earned income)
                            if(category == 'RentalExpense' || category == 'WorkExpense') {
                                // RentalIncome is index 2 in incomeValues; WorkExpense based on IncomePaycheck existing - index 0
                                if(category == 'RentalExpense') catIdx = 2;
                                else if(category == 'WorkExpense') catIdx = 0;
                                futureExpenses[year][category] = calcIncomeRelatedExpense(year, currentYear, lastYearsExpenses[category], inflationFactor, incomeValues[catIdx]);
                            // use doctor estimates from retirementdata table or input
                            } else if(category == 'Doctor') {
                                futureExpenses[year][category] = getDoctorExpense(year, lastYearsExpenses[category], inflationFactor, retirementParameters);
                            } else {
                                // increase expense by inflation.  Use category's inflation factor, or default
                                futureExpenses[year][category] = Math.round(lastYearsExpenses[category] * (1 + inflationFactor/100));
                            }

                            // add to summary category
                            futureExpensesSummary[year][summaryCategory] += futureExpenses[year][category];

                            // put detail on page
                            $("#" + category + year).text(futureExpenses[year][category]);

                            //      add new amt to year total
                            futureExpensesYearlyTotal[year] += futureExpenses[year][category];

                        });                        
                        
                        // write this year's expenses to page
                        summaryCategories.forEach( summaryCategory => {
                            $("#" + summaryCategory + year + "SUM").text(futureExpensesSummary[year][summaryCategory]);
                        });
                        $("#expenses" + year).text(futureExpensesYearlyTotal[year]);

                        // set this year's expenses to last year's in preparation for the next year's calculations
                        lastYearsExpenses =futureExpenses[year];
                    }); // end of foreach year

                }   // end function calcFutureExpenses


                // calc interest for amt since transferred date with interest rate given to nearest dollar
                function calcInterest(thisLTCamt, dateTransferred, interestRate) {
                    // calc days since deposited
                    var today = new Date();
                    var dateTransferred = new Date('20'+ dateTransferred.substring(4, 6), dateTransferred.substring(0, 2), dateTransferred.substring(2, 2));
                
                    // same unit of measure (milliseconds)
                    today = Date.UTC(today.getFullYear(), today.getMonth(), today.getDate());
                    dateTransferred = Date.UTC(dateTransferred.getFullYear(), dateTransferred.getMonth(), dateTransferred.getDate());

                    // get diff and convert to days
                    const term = Math.abs((dateTransferred.valueOf() - today.valueOf()) / (24 * 60 * 60 * 1000));

                    // interest for that length term, rounded to the nearest dollar
                    var interest = Math.round(thisLTCamt * interestRate/100 * (term/365));

                    return interest;
                }


                // calculate future retirement income
                function calcRetirementIncome(year, retirementParameters, lastYearTaxableRetIncome, lastYearNonTaxableRetIncome) {
                    console.log("in calcRetirementIncome");
                    // when to start taking retirement funds
                    const twoDigitYearStart = retirementParameters['RetDistribBegin'].substring(4, 6);

                    console.log(" --- ", year, " --- ");
                    const twoDigitIteratedYear = year-2000;

                    // if not getting retirement yet, change the retirement income values to 0 for the year
                    if(twoDigitYearStart > twoDigitIteratedYear) {
                        console.log(" - no retirement yet");
                        $('#TaxRetire' + year).text('0');
                        $('#Non-TaxRetire' + year).text('0');

                        // set ret income to use in future
                        lastYearTaxableRetIncome = 0;
                        lastYearNonTaxableRetIncome = 0;
                    
                    // else if beginning distributions this year
                    } else if(twoDigitYearStart == twoDigitIteratedYear) {
                        console.log(" - start this year");
                        // Get current retirement account values (LTC funds should already be reported under LTC balance at bottom of page, not here)
                        // get from retirementforecast page (this one) under Beginning Balances for this year

                        WFIRATaxableTrad = $("#TaxableRetirement20" + twoDigitIteratedYear).text();
                        console.log(" - Taxable Ret 20" + twoDigitIteratedYear + ": " + WFIRATaxableTrad);

                        nonTaxableRothBal = $("#TaxFreeRetirement20" + twoDigitIteratedYear).text();
                        console.log(" - NON Taxable Ret 20" + twoDigitIteratedYear + ": " + nonTaxableRothBal);

                        // Distributions from Trad and Roth are proportional to initial balances
                        // Determine proportions (TradProportion, RothProportion)
                        var tradProportion = WFIRATaxableTrad/(WFIRATaxableTrad + nonTaxableRothBal);
                        var rothProportion = nonTaxableRothBal/(WFIRATaxableTrad + nonTaxableRothBal);
                        // console.log("tradProportion: ", tradProportion);
                        // console.log("rothProportion: ", rothProportion);

                        // totalDistribution = InvWD/100 * (WFIRATaxableTrad + WF-IRA-non-taxable-Roth)
                        const totalDistribution = retirementParameters['InvWD']/100 * (WFIRATaxableTrad + nonTaxableRothBal);
                        const taxableDist = Math.round(totalDistribution * tradProportion);
                        const nonTaxableDist = Math.round(totalDistribution *  rothProportion);

                        // put distribution values on the page 
                        $('#TaxRetire20' + twoDigitIteratedYear).text(taxableDist);                      
                        $('#Non-TaxRetire20' + twoDigitIteratedYear).text(nonTaxableDist);                      

                        // set ret income to use in future
                        lastYearTaxableRetIncome = taxableDist;
                        lastYearNonTaxableRetIncome = nonTaxableDist;

                    } else {
                        console.log(" - bumped ret income");
                    // ELSE  ... just bump up last year's values by defaultInflationFactor
                    //  and put on page
                        taxableDist = Math.round((1 + Number(retirementParameters['InvGrowth'])/100) * lastYearTaxableRetIncome);
                        nonTaxableDist = Math.round((1 + Number(retirementParameters['InvGrowth'])/100) * lastYearNonTaxableRetIncome);
                        $('#TaxRetire' + year).text(taxableDist);
                        $('#Non-TaxRetire' + year).text(nonTaxableDist);
                        console.log("taxableDist: ", taxableDist);
                        console.log("nonTaxableDist: ", nonTaxableDist);

                        // set ret income to use in future
                        lastYearTaxableRetIncome = taxableDist;
                        lastYearNonTaxableRetIncome = nonTaxableDist;
                    }

                    return [lastYearTaxableRetIncome, lastYearNonTaxableRetIncome];

                }   // end function calcRetirementIncome


                // calc values dependent on previous year:
                //      beginning balances after first forecast year, 
                //      retirement income (tax and non-tax)
                //      income taxes,
                //      incomeOtherWH,
                //      ending balances
                function calcYearByYear(forecastYears, retirementParameters, lastYearRetirementIncome, date) {

                    var lastYearNonTaxableRetIncome, lastYearTaxableRetIncome;

                    const balanceCategories = JSON.parse($("#balanceCategories").text());
                    console.log("balanceCategories: ", balanceCategories);

                    const InvGrowth = Number(retirementParameters['InvGrowth'])/100;
                    console.log("InvGrowth: ", InvGrowth);

                    // add this year to begining of forecastYears array to calc this year's numbers, too
                    const today = new Date();
                    const currentYear = today.getFullYear();
                    forecastYears.unshift(currentYear);

                    forecastYears.forEach( (year, yrIdx) => {
                        console.log(" --- year by year ", yrIdx, ": ", year, "; last year: ", (year - 1));

                        // copy last year's ending balances to this year's beginning balances
                        // no need to do it for current year
                        if(yrIdx != 0) {
                            balanceCategories.forEach(category => {
                                console.log("category", category, "; last year's balance: ", $('#end' + category + (year-1)).text());
                                $('#' + category + year).text( $('#end' + category + (year-1)).text() );
                            });
                        }

                        // figure this year's retirement income
                        if(yrIdx == 0 && year > 2025) {
                            // Retirement income last year...
                            //  if year > 2025
                            //      and category = "IncomeRetirement"
                            //      and amount > 0
                            //      and toFrom in 'WF', 'WF-IRA', 'TIAA', 'DiscRet'
                            //      notes should indicate if income is non-taxable (from Roth)
                            //  I may not remember, so throw a message so I'll check
                            var retirementIncomeMsg = 'If this is not correct, FIX IT!!  Retirement income for ' + (year - 1) + "\n";
                            lastYearNonTaxableRetIncome = 0;
                            lastYearTaxableRetIncome = 0;
                            lastYearRetirementIncome.forEach( retIncome => {
                                console.log("---- begin loop: ", retIncome);
                                retirementIncomeMsg += " - " + JSON.stringify(retIncome) + "\n";
                                if(retIncome['notes'].includes('nontaxable') || retIncome['notes'].includes('non-taxable')) {
                                    lastYearNonTaxableRetIncome += Number(retIncome['amount']);
                                    console.log("lastYearNonTaxableRetIncome: ", lastYearNonTaxableRetIncome);
                                } else {
                                    lastYearTaxableRetIncome += Number(retIncome['amount']);
                                    console.log("lastYearTaxableRetIncome: ", lastYearTaxableRetIncome);
                                }
                            });

                            retirementIncomeMsg += "Non Taxable total: " + lastYearNonTaxableRetIncome + "\n" +
                                "Taxable total: " + lastYearTaxableRetIncome;
                            alert(retirementIncomeMsg);

                        }
                        [lastYearTaxableRetIncome, lastYearNonTaxableRetIncome] = calcRetirementIncome(year, retirementParameters, lastYearTaxableRetIncome, lastYearNonTaxableRetIncome);

                        // figure this year's investment growth based on average balances
                        // some interest already earned in first year
                        if(yrIdx == 0) {
                            console.log("date: ", date);
                            // assume growth happened so far at expected rate, and add growth till end of year
                            const month = Number(date.substring(5, 7));
                            // number of months interest already earned
                            const numMonthsToDate = month - 1;
                            // number of months left to earn interest
                            const numMonthsLeft = 12 - numMonthsToDate;
                            console.log("month: ", month, "; numMonthsToDate: ", numMonthsToDate, "; numMonthsLeft: ", numMonthsLeft);
                            // beginning_balance = balance_w_growth / ((interest_rate * months_interest_already_earned) + 1)
                            //      derived from:  balance_w_growth = ((beginning_balance * interest_rate) * months_interest_already_earned) + beginning_balance
                            //    where:
                            //          origEst = beginning_balance
                            //          $('#Investment' + year).text()  =  balance_w_growth
                            //          InvGrowth   =   interest_rate
                            //          numMonthsToDate = months_interest_already_earned
                            const origEst = Number($('#Investment' + year).text().replaceAll(',', '')) / ((InvGrowth * numMonthsToDate) + 1);
                            // apply growth to original balance for number of months left
                            const growthLeft = Math.round((origEst/12 * numMonthsLeft) * InvGrowth);
                            $('#InvestmentGrowth' + year).text(growthLeft);
                        } else {
                            const beginBalance = $('#Investment' + year).text();
                            const growth = Math.round(beginBalance * InvGrowth);
                            $('#InvestmentGrowth' + year).text(growth);
                        }

                        // estimate income taxes and IncomeOtherWH (Medicare, SS)
                        // left off here -- for current year, take into account what's already been withheld
                        //      note: only earned income is subject to Medicare and SS
                        //              GB limo tips are deductible up to $25,000 thru 2028

                        // extraSpending is based on GBLimo income
                        // left off here -- GBLimo income based

                        // LTC only an expense if LTC balance is below the goal
                        // left off here -- fix LTC expense

                        // left off here -- DO THIS NEXT
                        // NOTE: Need two more lines for tax retirement growth & non-tax retirement growth

                        // update Ending balances
                        // left off here -- ending balances
                        // end spending = beginning spending + income (except retirement and inv growth) - expenses
                        // end investments = begining investments + inv growth - needed for spending (if end spending is negative)
                        // end tax ret = beginning tax ret + tax ret inv growth - tax retirement income
                        // end non-tax ret = beginning non-tax ret + non-tax ret inv growth - non-tax retirement income



                        
                    });
                }
                            

                // calc LTC goals per year & put on retirementforecast page
                function calcLTCgoals(yearlyContrib, interestRate, yearFirstContrib, forecastYears) {
                    
                    var LTCbalance = 0;
                    var interest, avgBal;
                    
                    // iterate through each year starting when first contrib made to LTC
                    //      and ending with last forecast year
                    const finalForecastYear = forecastYears[forecastYears.length-1];
                    for(var year = yearFirstContrib; year <=finalForecastYear; year++) {

                        // interest assumes yearlyContrib added throughout the year - so interest on average balance
                        avgBal = LTCbalance + yearlyContrib/2;
                        interest = avgBal * interestRate;
                        LTCbalance += yearlyContrib + interest;
                        LTCbalance = Math.round(LTCbalance * 100)/100;  // round to nearest cent

                        // if within forecastYears, put on page (rounding to nearest dollar on page, but not in calculations)
                        if(forecastYears.includes(year)) {
                            $('#LTCgoal' + year).text(Math.round(LTCbalance));
                        }
                    }
                }   // end of function calcLTCgoals


                // get inflationFactors from page
                const inflationFactors = JSON.parse($("#inflationFactors").text());
                console.log(inflationFactors);

                // get forecastYears from page
                const forecastYears = JSON.parse($("#forecastYears").text());
                console.log(forecastYears);

                // get expenses for current year by category
                const expectedExpensesForThisYearByCategory = JSON.parse($("#expectedExpensesForThisYearByCategory").text());
                
                // get expense Categories With Summary Cats
                const expenseCategoriesWithSummaryCats = JSON.parse($("#expenseCategoriesWithSummaryCats").text());
                console.log("expenseCategoriesWithSummaryCats: " , expenseCategoriesWithSummaryCats);

                // get summary Categories With detail Cats (same data as expenseCategoriesWithSummaryCats above structured differently)
                const sumCategoriesWithDetailCategories = JSON.parse($("#sumCategoriesWithDetailCategories").text());
                console.log("sumCategoriesWithDetailCategories: " , sumCategoriesWithDetailCategories);
                
                // get default inflation factor
                const defaultInflationFactor = $("#defaultInflationFactor").text();
                console.log("defaultInflationFactor: ", defaultInflationFactor, " (", typeof defaultInflationFactor, ")");
                
                // get date (first of month) for forecast
                const date = $('#date').text();
                console.log("date: ", date);

                // get incomeValues
                const incomeValues = JSON.parse($("#incomeValues").text());

                var retirementParameters = $("#retirementParameters").text();
                retirementParameters = JSON.parse(retirementParameters);
                console.log("retirementParameters: ", retirementParameters);
                console.log(" invWD: ", retirementParameters['InvWD']);

                var lastYearRetirementIncome = $("#lastYearRetirementIncome").text();
                lastYearRetirementIncome = JSON.parse(lastYearRetirementIncome);
                console.log("lastYearRetirementIncome: ", lastYearRetirementIncome);

                // need current year expenses by category and summary category
                calcFutureExpenses(forecastYears, expenseCategoriesWithSummaryCats, sumCategoriesWithDetailCategories, expectedExpensesForThisYearByCategory, inflationFactors, defaultInflationFactor, incomeValues, retirementParameters);

                // calc values dependent on previous year:
                //      beginning balances after first forecast year, 
                //      retirement income (tax and non-tax)
                //      income taxes,
                //      incomeOtherWH,
                //      ending balances
                calcYearByYear(forecastYears, retirementParameters, lastYearRetirementIncome, date);

                // calc LTC goal per year & put on page
                // assume contrib $7500 per year beginning in 2021 at 5% interest
                calcLTCgoals(7500, 0.05, 2021, forecastYears);

            });



        </script>
    </body>

</html>