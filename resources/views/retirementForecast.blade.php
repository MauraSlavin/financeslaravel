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
        @php 
        $currentYear = date("Y");
        $forecastLength = 2062 - $currentYear;
        $forecastYears = range($currentYear, $currentYear + $forecastLength);
        @endphp
        <div hidden id="forecastYears">{{ json_encode($forecastYears) }}</div>
        <div hidden id="currentYear">{{ $currentYear }}</div>

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
                    @foreach($accountNames as $acctIdx=>$account)
                        <tr>
                            <td></td>
                            <td data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="{{ $acctsIncludedArray[$acctIdx] }}">
                                {{ $account }}
                            </td>
                            <td></td>
                            @foreach($forecastYears as $yearIdx=>$year)
                                <td>{{ number_format((float)$accountValues[$acctIdx][$yearIdx]) }}</td>
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
                            @endphp
                            <td>{{ number_format((float)$incomeValueArray[$yearIdx]) }}</td>
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
                        <td style="background-color: blue; color: white;"></td>
                        @foreach($forecastYears as $year)
                            <td style="background-color: blue; color: white;">Dec 31</td>
                        @endforeach
                    </tr>
                    @foreach($accountNames as $acctIdx=>$account)
                        <tr>
                            <td></td>
                            <td>{{ $account }}</td>
                            <td></td>
                            @foreach($forecastYears as $yearIdx=>$year)
                                <td>{{ number_format((float)$accountValues[$acctIdx][$yearIdx]) }}</td>
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
                            [11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111 ],
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
                    @foreach($accountNames as $acctIdx=>$account)
                        <tr>
                            <td></td>
                            <td>{{ $account }}</td>
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
                <li>LTC in budget goes to LTC in Misc Balances</li>
                <li>Income from Inherited IRA intended for LTC</li>
                <li>Investment Growth: (end bal before growth - begin bal)/2 * growth
                    <br>or (begin bal - w/ds) * growth
                </li>
                <li>Health care inflation assumed to be 5% (in budget table)
                    <br>US Health Care Inflation Rate is at 3.05%, compared to 3.28% last month and 3.08% last year. This is lower than the long term average of 5.09%.</br>
                    <br>Source: https://ycharts.com/indicators/us_health_care_inflation_rate#:~:text=Basic%20Info,the%20US%20Consumer%20Price%20Index.</br>
                </li>
                <li>Assume "Irregular Big" expenses are spent, so don't keep track of balance</li>
                <li>Spending:
                    <ul>
                        <li>Savings (Big Bills)</li>
                        <li>Checking</li>
                        <li>subtract CC (Disc & VISA) balances - I'm not doing this yet (12/25/25)</li>
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

                // left off here ...
                function calcFutureExpenses(forecastYears, expenseCategoriesWithSummaryCats, sumCategoriesWithDetailCategories, expectedExpensesForThisYearByCategory, inflationFactors, defaultInflationFactor, incomeValues) {

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
                    }

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
                            if(category == 'RentalExpense' || category == 'WorkExpense') {
                                // RentalIncome is index 2 in incomeValues; WorkExpense based on IncomePaycheck existing - index 0
                                if(category == 'RentalExpense') catIdx = 2;
                                else if(category == 'WorkExpense') catIdx = 0;
                                futureExpenses[year][category] = calcIncomeRelatedExpense(year, currentYear, lastYearsExpenses[category], inflationFactor, incomeValues[catIdx]);
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

                }   // end function futureExpenses

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
                
                // get incomeValues
                const incomeValues = JSON.parse($("#incomeValues").text());
 
                // need current year expenses by category and summary category
                calcFutureExpenses(forecastYears, expenseCategoriesWithSummaryCats, sumCategoriesWithDetailCategories, expectedExpensesForThisYearByCategory, inflationFactors, defaultInflationFactor, incomeValues);


            });



        </script>
    </body>

</html>