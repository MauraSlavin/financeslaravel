<html>
    <head>
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
        <!-- Favicon -->
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/favicon/apple-touch-icon.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/favicon/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/img/favicon/favicon-16x16.png') }}">
        <link rel="shortcut icon" href="{{ asset('assets/img/favicon/favicon.ico') }}">
        <link rel="manifest" href="{{ asset('assets/img/favicon/site.webmanifest') }}">
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
        <div hidden id="budgetedExpensesForThisFullYearByCategory">{{ json_encode($budgetedExpensesForThisFullYearByCategory) }}</div>
        <div hidden id="expenseCategoriesWithSummaryCats">{{ json_encode($expenseCategoriesWithSummaryCats) }}</div>
        <div hidden id="sumCategoriesWithDetailCategories">{{ json_encode($sumCategoriesWithDetailCategories) }}</div>
        <div hidden id="defaultInflationFactor">{{ $defaultInflationFactor }}</div>
        <div hidden id="incomeValues">{{ json_encode($incomeValues) }}</div>
        <div hidden id="retirementParameters">{{ json_encode($retirementParameters) }}</div>
        @php 
            $currentYear = date("Y");
            $forecastLength = 2062 - $currentYear;
            $forecastYears = range($currentYear, $currentYear + $forecastLength);
        @endphp
        <div hidden id="forecastYears">{{ json_encode($forecastYears) }}</div>
        <div hidden id="currentYear">{{ $currentYear }}</div>
        <div hidden id="firstOfThisMonth">{{ $firstOfThisMonth }}</div>

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
                        $spendingAccountNames = ["Spending", "Credit Card Debt", "Investment", "Taxable Retirement", "Tax Free Retirement"];
                        $accountValues = [
                            $spending, 
                            $ccdebt,
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
                            <td style="background-color: blue; color: white;">{{ $firstOfThisMonth }}</td>
                            @else
                            <td style="background-color: blue; color: white;">Jan 1, {{ $year }}</td>
                            @endif
                        @endforeach
                    </tr>
                    @php 
                        $acctsIncludedArray = [
                            $spendingAccts,   // Spending
                            $ccAccts,         // CC debt
                            $invAccts,        // Investment
                            $retTaxAccts,     // taxable retirement accts
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
                            <td id="begSubTot{{$year}}" style="background-color: lightblue;">{{ number_format((float)$beginBalances[$idxSubTot]) }}</td>
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
                        $accountNames = ["Town of Durham", "GB Limo", "Rental", "NH Retirement", "Mike IBM", "Mike SS", "Maura IBM", "Maura SS", "Tax Retire", "Non Tax Retire", "Investment Growth", "Taxable Retirement Growth", "Tax Free Retirement Growth"];
                        // NO inherited IRA - income from that goes to LTC
                    @endphp
                    <tr id="incomeForecast">
                        <td style="background-color: green; color: white;">Income</td>
                        <td style="background-color: green; color: white;"></td>
                        <td style="background-color: green; color: white;"></td>
                        @foreach($forecastYears as $idxYear => $year)
                            @if($idxYear == 0)
                            <td style="background-color: green; color: white;">After {{ $firstOfThisMonth }}</td>
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
                                $incomeValueArray = json_decode($incomeValues[$acctIdx] ?? "[]");
                                $htmlId = str_replace(' ', '', $account) . $year;
                            @endphp
                            <td id="{{ $htmlId }}">{{ number_format((float)($incomeValueArray[$yearIdx] ?? 0)) }}</td>
                        @endforeach
                    </tr>
                    @endforeach
                    <!-- subtotal -->
                    <tr>
                        <td style="background-color: lightgreen;">Sub-total:</td>
                        <td style="background-color: lightgreen;"></td>
                        <td style="background-color: lightgreen;"></td>
                        @foreach($forecastYears as $idxSubTot=>$year)
                            <td id="income{{ $year }}" style="background-color: lightgreen;"></td>
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

                    <!-- Expenses --> 
                    @php 
                        $sumCatNames = array_keys($restOfYearBudgetBySUMMARYCategory);
                        // NO inherited IRA - income from that goes to LTC (no longer true)
                    @endphp
                    <!-- header row for expenses, with first of month date on first column -->
                    <tr id="expenseForecast">
                        <td style="background-color: red; color: white;">Expenses</td>
                        <td style="background-color: red; color: white;"></td>
                        <td style="background-color: red; color: white;"></td>
                        @foreach($forecastYears as $idxYear => $year)
                            @if($idxYear == 0)
                            <td style="background-color: red; color: white;">After {{ $firstOfThisMonth }}</td>
                            @else
                            <td style="background-color: red; color: white;">{{ $year }}</td>
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
                            <td style="background-color: lightpink;">{{ $restOfYearBudgetBySUMMARYCategory[$sumcat] }}</td>
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
                                    <td id="{{ $detailCategory }}{{ $year }}">{{ round($restOfYearBudgetByCategory[$detailCategory] ?? 0 ) }}</td>
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
                        <td style="background-color: pink;"></td>
                        <td id="expenses{{ $forecastYears[0] }}" style="background-color: pink;"></td>  <!-- current year -->
                        <!-- add sub totals here --> 
                        @foreach($forecastYears as $idxYear => $year)
                            @if($idxYear != 0)   <!-- first column is already done -->
                                <td id="expenses{{ $year }}" style="background-color: pink;"></td>  <!-- current year -->
                            @endif
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

                    <!-- Ending Balances --> 
                    @php 
                        $accountValues = [
                            [10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110, 120, 130, 140, 150, 160, 170, 180, 190, 200, 210, 220, 230, 240, 250, 260, 270, 280, 290, 300, 10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110, 120, 130, 140, 150, 160, 170, 180, 190, 200, 210, 220, 230, 240, 250, 260, 270, 280, 290, 300, 10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110, 120, 130, 140, 150, 160, 170, 180, 190, 200, 210, 220, 230, 240, 250, 260, 270, 280, 290, 300, 10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110 ],
                            [ 0,  0,  0,  0,  0,  0,  0,  0,  0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,  0,  0,  0,  0,  0,  0,  0,  0,  0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,  0,  0,  0,  0,  0,  0,  0,  0,  0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,  0,  0,  0,  0,  0,  0,  0,  0,  0,   0,   0 ],
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
                            <td id="ending{{ $year }}" style="background-color: lightblue;"></td>
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
                        $accountNames = ["LTC", "House value"];
                        $accountValues = [
                            [$initLTCBal, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111 ],
                            [11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111 ]
                        ];
                    @endphp
                    <tr>
                        <td style="background-color: purple; color: white;">Misc Balances</td>
                        <td style="background-color: purple; color: white;"></td>
                        <td style="background-color: purple; color: white;"></td>
                        @foreach($forecastYears as $idxYear => $year)
                            @if($idxYear == 0)
                            <td style="background-color: purple; color: white;">{{ $firstOfThisMonth }}</td>
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
                            title="Goal amt at year end assuming $7500 annual contrib beginning 2021 at <LTCInvGrowth> interest">
                            LTC goal
                        </td>
                        <td></td>
                        @foreach($forecastYears as $yearIdx=>$year)
                            <td id="LTCgoal{{$year}}"></td>
                        @endforeach
                    </tr>
                    <!-- LTC balances -->
                    @foreach($accountNames as $acctIdx=>$account)
                        @php
                            if($account == 'LTC') $idPrefix = 'LTCBal';
                            elseif($account == 'House value') $idPrefix = 'HouseValue';
                            else $idPrefix = '';
                        @endphp
                        <tr>
                            <td></td>
                            @if($account == 'LTC')
                                <td  data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="Inherited IRA, Discover LTC, rest in WF Trad IRA">{{ $account }} balance
                                </td>
                                <td></td>
                            @else
                                <td>{{ $account }}</td>
                                <td id="HouseGrowth"></td>
                            @endif
                            @foreach($forecastYears as $yearIdx=>$year)
                                <td id="{{$idPrefix}}{{$year}}">{{ number_format((float)$accountValues[$acctIdx][$yearIdx]) }}</td>
                            @endforeach
                        </tr>
                    @endforeach             

                </tbody>
            </table>

            NOTES:
            <ul>
                <li>IRA/Retirement distributions go to Spending</li>
                <li>Income from Inherited IRA should be earmarked for LTC</li>
                <li>LTC goals are based on $7500 (hard-coded) contributed each year beginning in 2021. Growth is input as LTCInvGrowth on previous page.</li>
                <li>Health care inflation assumed to be 5% (in budget table)
                    <br> - US Health Care Inflation Rate is at 3.05%, compared to 3.28% last month and 3.08% last year. This is lower than the long term average of 5.09%.
                    <br> - Source: https://ycharts.com/indicators/us_health_care_inflation_rate#:~:text=Basic%20Info,the%20US%20Consumer%20Price%20Index.
                </li>
                <li>See 
                    <br> - https://docs.google.com/spreadsheets/d/10UFYi7Hiqd_y4q02vT85QjEXc1MJep27Kw3PYU7lmns/edit?gid=1813417080#gid=1813417080
                    <br> for details on future "Doctor" estimates
                </li>
                <li>Food (Groceries) inflation worksheet:
                    <br>https://docs.google.com/spreadsheets/d/1A41Xq_W51dHUSzA9vPjcqlHlvD3f7E7cKkTwOeeqzT8/edit?gid=260114027#gid=260114027
                </li>
                <li>ExtraSpending is what's left of GB Limo Income after deductions are made for:
                    <ul>
                        <li>Withholdings for SS and Medicare</li>
                        <li>Federal taxes 
                            <ul>
                                <li>Used 1/2 of income (about half are tips)</li>
                                <li>Tips are not taxed through 2028 up to $25,000
                                    <br>- assuming GB Limo income won't continue past that, and
                                    <br>- tips won't exceed $25,000
                                </li>
                                <li>BUT the application doesn't check for income limit and past 2028.</li>
                            </ul>
                        </li>
                        <li>Trips to N Hampton (assuming $1000 based on 2025 costs)</li>
                        <li>Household (percent is in Retirement Input page)</li>
                    </ul>
                </li>
                <li>Budget for IncomeTaxes is estimated in
                    <br>- https://docs.google.com/spreadsheets/d/1Uyk-zKjbBLCmaV5GXPKeF2JYaNq-dasulqhCff7aEqA/edit?gid=0#gid=0
                <li>Assume "Irregular Big" expenses are spent, so don't keep track of balance</li>
                <li>Assume raises from earned income = COLA</li>
                <li>IncomeOtherWH is Medicare and SS withholdings. Earned income used to calculate this are Town of Durham and GB Limo income.</li>
                <li>Default annual increase in value of house from 2004 to 2026 is about 3.75%.</li>
                <li>Spending:
                    <ul>
                        <li>Savings (Big Bills)</li>
                        <li>Checking</li>
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
                function calcFutureExpenses(forecastYears, expenseCategoriesWithSummaryCats, sumCategoriesWithDetailCategories, budgetedExpensesForThisFullYearByCategory, inflationFactors, defaultInflationFactor, incomeValues, retirementParameters) {

                    // for rental or work expenses
                    function getIncomeRelatedExpense(year, currentYear, lastYearsExpense, inflationFactor, incomeValue) {
                            
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
                    }   // end of function getIncomeRelatedExpense

                    function getDoctorExpense(year, lastYearsExpense, inflationFactor, retirementParameters) {

                        // use estimates in retirementdata if they exist;
                        //  otherwise bump up by inflation factor
                        if(typeof retirementParameters['Doctor' + year] != 'undefined') {
                            thisYearsExpense = -parseInt(retirementParameters['Doctor' + year]);
                        } else {
                            thisYearsExpense = Math.round(lastYearsExpense * (1 + inflationFactor/100));
                        }

                        return thisYearsExpense;
                    }   // end of function getDoctorExpense

                    function getIncomeOtherWHExpense(year, percentWithheld) {

                        // get earned income, convert to numbers and add
                        const townIncome = Number($('#TownofDurham' + year).text().replaceAll(",", ""));
                        // ~ 1/2 GB Limo is tips and not taxed
                        const GBLimoIncome = .5 * Number($('#GBLimo' + year).text().replaceAll(",", ""));
                        const earnedIncome = townIncome + GBLimoIncome;

                        // calc amount withheld
                        const withheld = Math.round(percentWithheld/100 * earnedIncome);

                        // return as a negative number (expense)
                        return -withheld;

                    }   // end of function getIncomeOtherWHExpense

                    function getExtraSpendingExpense(year, IncomeOtherWH, percentWithheld, GBLimoForExpenses) {

                        // extra spending is what's left after other stuff taken from GB Limo income
                        //  minus SS and Medicare withheld
                        //  minus fed taxes
                        //  minus $ for trips to N Hampton
                        //  minus toward household
                        // rest is ExtraSpending

                        // get GBLimo income to start
                        const GBLimoIncome = Number($('#GBLimo' + year).text().replaceAll(",", ""));

                        // extra spending is 0 if no GB Limo income
                        if(GBLimoIncome == 0) return 0; 

                        // has ss & medicare wh been calc'd yet?  If not, get it.
                        var withheld = IncomeOtherWH;
                        if(IncomeOtherWH != 0) withheld = getIncomeOtherWHExpense(year, percentWithheld);

                        // estimate federal income taxes.  Half of GB Limo is tips, which aren't taxable thru 2028 up to $25,000
                        // assume 22% tax rate
                        const taxes = (GBLimoIncome / 2) * .22;

                        // trips to N Hampton (based on 2025)
                        const trips = 1000;

                        // towards household
                        const household = GBLimoIncome * GBLimoForExpenses/100;

                        // extra spending is what's left after everything else subtracted
                        const extraSpending = Math.round(GBLimoIncome - (withheld + taxes + trips + household));

                        // return as a negative number (expense)
                        return -extraSpending;

                    }   // end of function getExtraSpendingExpense

                    function getIncomeTaxesExpense(year, retirementParameters) {

                        // estimate the Income Taxes for the year

                        // get incomes needed
                        const DurhamIncome = Number($('#TownofDurham' + year).text().replaceAll(",", ""));
                        // only about 1/2 of GB Limo is taxable (tips are not taxable)
                        const GBLimoIncome = .5 * Number($('#GBLimo' + year).text().replaceAll(",", ""));
                        const rentalIncome = Number($('#Rental' + year).text().replaceAll(",", ""));
                        const NHRetIncome = Number($('#NHRetirement' + year).text().replaceAll(",", ""));
                        const MikeIBMIncome = Number($('#MikeIBM' + year).text().replaceAll(",", ""));
                        const MikeSSIncome = Number($('#MikeSS' + year).text().replaceAll(",", ""));
                        const MauraIBMIncome = Number($('#MauraIBM' + year).text().replaceAll(",", ""));
                        const MauraSSIncome = Number($('#MauraSS' + year).text().replaceAll(",", ""));
                        const TaxRetireIncome = Number($('#TaxRetire' + year).text().replaceAll(",", ""));

                        // total taxable income
                        const totalTaxableIncome = DurhamIncome + GBLimoIncome + rentalIncome + NHRetIncome + MikeIBMIncome + MikeSSIncome + MauraIBMIncome + MauraSSIncome + TaxRetireIncome;

                        // get estimated tax rate (on taxable income)
                        const taxRate = Number(retirementParameters['EstTaxRateOnTotalTaxInc']);

                        // calc taxes & return
                        const taxes = Math.round(totalTaxableIncome * taxRate/100);

                        // return as negative number since it's an expense
                        return -taxes;

                    }   // end of function getIncomeTaxesExpense

                    // last year expenses set to current year expenses by category
                    var lastYearsExpenses = budgetedExpensesForThisFullYearByCategory;

                    // categories with different (non-default) inflation factors
                    var inflationFactorCategories = Object.keys(inflationFactors);

                    // for each year starting with next year
                    forecastYears.shift();

                    var futureExpenses = [];
                    var futureExpensesSummary = [];
                    var futureExpensesYearlyTotal = [];
                    var summaryCategories = [];

                    const currentYear = $('#currentYear').text();
                    
                    forecastYears.forEach( year => {
                        futureExpenses[year] = [];
                        futureExpensesSummary[year] = [];
                        futureExpensesYearlyTotal[year] = 0;
                        
                        // init this year's expenses by category & summary category
                        futureExpenses[year] = [];
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

                            const summaryCategory = summary['summaryCategory'];

                            var inflationFactor;
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
                                futureExpenses[year][category] = getIncomeRelatedExpense(year, currentYear, lastYearsExpenses[category], inflationFactor, incomeValues[catIdx]);
                            // use doctor estimates from retirementdata table or input
                            } else if(category == 'Doctor') {
                                futureExpenses[year][category] = getDoctorExpense(year, lastYearsExpenses[category], inflationFactor, retirementParameters);
                            } else if(category == 'IncomeOtherWH') {
                                futureExpenses[year][category] = getIncomeOtherWHExpense(year, retirementParameters['SS-Med-WHs']); 
                            } else if(category == 'ExtraSpending') {
                                futureExpenses[year][category] = getExtraSpendingExpense(year, futureExpenses[year]['IncomeOtherWH'], retirementParameters['SS-Med-WHs'], retirementParameters['GBLimoForExpenses']);
                            } else if(category == 'IncomeTaxes') {
                                // do later when have all data needed
                                futureExpenses[year][category] = 0;
                            } else {
                                // increase expense by inflation.  Use category's inflation factor, or default
                                futureExpenses[year][category] = Math.round(lastYearsExpenses[category] * (1 + inflationFactor/100));
                            }

                            // add to summary category
                            futureExpensesSummary[year][summaryCategory] += futureExpenses[year][category];

                            // put detail on page
                            $("#" + category + year).text(futureExpenses[year][category].toLocaleString());

                            //      add new amt to year total
                            futureExpensesYearlyTotal[year] += futureExpenses[year][category];

                        });   // end of each category
                        
                        // do IncomeTaxes last
                        futureExpenses[year]['IncomeTaxes'] = getIncomeTaxesExpense(year, retirementParameters);
                        // put IncomeTaxes on page
                        $("#IncomeTaxes" + year).text(futureExpenses[year]['IncomeTaxes'].toLocaleString());
                        // add IncomeTaxes to category summary & expenses subtot for Taxes
                        futureExpensesSummary[year]["Taxes"] += futureExpenses[year]['IncomeTaxes'];
                        futureExpensesYearlyTotal[year] += futureExpenses[year]['IncomeTaxes'];

                        // write this year's expenses to page
                        summaryCategories.forEach( summaryCategory => {
                            $("#" + summaryCategory + year + "SUM").text(futureExpensesSummary[year][summaryCategory].toLocaleString());
                        });
                        $("#expenses" + year).text(futureExpensesYearlyTotal[year].toLocaleString());

                        // set this year's expenses to last year's in preparation for the next year's calculations
                        lastYearsExpenses = futureExpenses[year];
                    }); // end of foreach year

                    return [futureExpenses, futureExpensesSummary, futureExpensesYearlyTotal, summaryCategories];
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
                function getRetirementIncome(year, retirementParameters, lastYearTaxableRetIncome, lastYearNonTaxableRetIncome) {
                    // when to start taking retirement funds
                    const twoDigitYearStart = retirementParameters['RetDistribBegin'].substring(4, 6);
                    const twoDigitIteratedYear = year-2000;

                    // if not getting retirement yet, change the retirement income values to 0 for the year
                    if(twoDigitYearStart > twoDigitIteratedYear) {
                        $('#TaxRetire' + year).text('0'.toLocaleString());
                        $('#NonTaxRetire' + year).text('0'.toLocaleString());

                        // set ret income to use in future
                        lastYearTaxableRetIncome = 0;
                        lastYearNonTaxableRetIncome = 0;
                    
                    // else if beginning distributions this year
                    } else if(twoDigitYearStart == twoDigitIteratedYear) {
                        // Get current retirement account values (LTC funds should already be reported under LTC balance at bottom of page, not here)
                        // get from retirementforecast page (this one) under Beginning Balances for this year
                        WFIRATaxableTrad = Number($("#TaxableRetirement20" + twoDigitIteratedYear).text().replaceAll(",", ""));
                        nonTaxableRothBal = Number($("#TaxFreeRetirement20" + twoDigitIteratedYear).text().replaceAll(",", ""));

                        // Distributions from Trad and Roth are proportional to initial balances
                        // Determine proportions (TradProportion, RothProportion)
                        var tradProportion = WFIRATaxableTrad/(WFIRATaxableTrad + nonTaxableRothBal);
                        var rothProportion = nonTaxableRothBal/(WFIRATaxableTrad + nonTaxableRothBal);

                        // totalDistribution = InvWD/100 * (WFIRATaxableTrad + WF-IRA-non-taxable-Roth)
                        const totalDistribution = retirementParameters['InvWD']/100 * (WFIRATaxableTrad + nonTaxableRothBal);
                        const taxableDist = Math.round(totalDistribution * tradProportion);
                        const nonTaxableDist = Math.round(totalDistribution *  rothProportion);
                        // adjustments needed if taxableDist or nonTaxableDist is negative  left off here mms maura

                        // put distribution values on the page 
                        $('#TaxRetire20' + twoDigitIteratedYear).text(taxableDist.toLocaleString());                      
                        $('#NonTaxRetire20' + twoDigitIteratedYear).text(nonTaxableDist.toLocaleString());                      

                        // set ret income to use in future
                        lastYearTaxableRetIncome = taxableDist;
                        lastYearNonTaxableRetIncome = nonTaxableDist;

                    } else {
                    // ELSE  ... just bump up last year's values by defaultInflationFactor
                    //  and put on page
                        taxableDist = Math.round((1 + Number(retirementParameters['InvGrowth'])/100) * lastYearTaxableRetIncome);
                        nonTaxableDist = Math.round((1 + Number(retirementParameters['InvGrowth'])/100) * lastYearNonTaxableRetIncome);

                        // make sure distribution is not greater than the balance of the retirement accounts
                        //  taxable & tax free    mms maura
                        if(taxableDist > $('#endTaxableRetirement'+ (year-1)).text()) {
                            taxableDist = Number($('#endTaxableRetirement'+ (year-1)).text().replaceAll(",", ""));
                        }
                        if(nonTaxableDist > $('#endTaxFreeRetirement'+ (year-1)).text()) {
                            nonTaxableDist = Number($('#endTaxFreeRetirement'+ (year-1)).text().replaceAll(",", ""));
                        }
                        $('#TaxRetire' + year).text(taxableDist.toLocaleString());
                        $('#NonTaxRetire' + year).text(nonTaxableDist.toLocaleString());

                        // set ret income to use in future
                        lastYearTaxableRetIncome = taxableDist;
                        lastYearNonTaxableRetIncome = nonTaxableDist;
                    }

                    return [lastYearTaxableRetIncome, lastYearNonTaxableRetIncome];

                }   // end function getRetirementIncome


                // calc income sub-totals for the year
                function updateIncomeSubTotal(year) {

                    const incomeSelectorPrefixes = ['#TownofDurham', '#GBLimo', '#Rental', '#NHRetirement', '#MikeIBM', '#MikeSS', '#MauraIBM', '#MauraSS', '#TaxRetire', '#NonTaxRetire', '#InvestmentGrowth', '#TaxableRetirementGrowth', '#TaxFreeRetirementGrowth'];
                    var currValue;  // to hold current income being added

                    incomeSubTotal = 0;    // for Sub-total

                    // calc income subtotal
                    incomeSelectorPrefixes.forEach( selectorPrefix => {

                        // get value for this prefix
                        currValue = Number($(selectorPrefix + year).text().replaceAll(",", ""));

                        // add to subtotal
                        incomeSubTotal += currValue;

                    });

                    // put subtotal on page
                    $('#income' + year).text(incomeSubTotal.toLocaleString());

                    return;

                }   // end function updateIncomeSubTotal


                // end spending = beginning spending + income (except retirement growth and inv growth) - expenses
                // end investments = begining investments + inv growth - needed for spending (if end spending is negative)
                // end tax ret = beginning tax ret + tax ret inv growth - tax retirement income
                // end non-tax ret = beginning non-tax ret + non-tax ret inv growth - non-tax retirement income
                function updateEndingBalances(year) {

                    endingSubTotal = 0;    // for Sub-total

                    summaryCategories = ['Spending', 'CreditCardDebt', 'Investment', 'TaxableRetirement', 'TaxFreeRetirement'];

                    // adjustments are needed to make sure balances don't fall below 0.
                    // set all to 0 to start
                    var adjustments = [];
                    summaryCategories.forEach(category => {
                        adjustments[category] = 0;
                    });

                    selectorPrefixesToAdd = [];
                    selectorPrefixesToSubtract = [];
                    
                    // spending
                    // left off here -- expenses aren't calc'd yet!!
                    selectorPrefixesToAdd['Spending'] = ['income', 'CreditCardDebt', 'expenses'];     // cc debt & expenses "added" because it's a negative number on the page
                    // selectorPrefixesToAdd['Spending'] = ['income', 'expenses'];     // expenses "added" because it's a negative number on the page
                    selectorPrefixesToSubtract['Spending'] = ['InvestmentGrowth', 'TaxableRetirementGrowth', 'TaxFreeRetirementGrowth'];

                    // investments
                    selectorPrefixesToAdd['Investment'] = ['InvestmentGrowth'];
                    selectorPrefixesToSubtract['Investment'] = [];
                    
                    // taxable retirement
                    selectorPrefixesToAdd['TaxableRetirement'] = ['TaxableRetirementGrowth'];
                    selectorPrefixesToSubtract['TaxableRetirement'] = ['TaxRetire'];

                    // investments
                    selectorPrefixesToAdd['TaxFreeRetirement'] = ['TaxFreeRetirementGrowth'];
                    selectorPrefixesToSubtract['TaxFreeRetirement'] = ['NonTaxRetire'];

                    // calc each ending balance
                    summaryCategories.forEach( summaryCategory => {
                        // start with beginning balance for the year
                        // get value from page
                        var endingBalance = $('#' + summaryCategory + year).text();

                        // strip commas and make it a number
                        endingBalance = Number(endingBalance.replaceAll(',', ''));
                        
                        // add incomes for this summary category
                        selectorPrefixesToAdd[summaryCategory].forEach( addPrefix => {
                            // get value from page
                            var income = $('#' + addPrefix + year).text();

                            // strip commas and make it a number
                            income = Number(income.replaceAll(',', ''));

                            // add to balance
                            endingBalance += income;

                        });

                        // subtract expenses for this summary category
                        selectorPrefixesToSubtract[summaryCategory].forEach( subPrefix => {
                            // get value from page
                            var expense = $('#' + subPrefix + year).text();

                            // strip commas and make it a number
                            expense = Number(expense.replaceAll(',', ''));

                            // subtract from balance
                            endingBalance -= expense;
                        });

                        // if balance is below 0, need to adjust; or highlight in red where it goes negative
                        if(endingBalance < 0 && summaryCategory == 'Spending') {
                            const adj = -endingBalance + 2000;  // Random $2000 buffer
                            adjustments['Spending'] += adj;
                            adjustments['Investment'] += -adj;
                        }
                        
                        // Adjust endingBalance as needed; highlight if below 0
                        endingBalance += adjustments[summaryCategory];
                        if(endingBalance < 0) $('#end' + summaryCategory + year).css('background-color', 'red');

                        // put result on page
                        $('#end' + summaryCategory + year).text(endingBalance.toLocaleString());

                        // add to subTotal
                        endingSubTotal += endingBalance;                        

                    });

                    // put subtotal on page
                    $('#ending' + year).text(endingSubTotal.toLocaleString());

                    return;
                }   // end function updateEndingBalances

                // calc values dependent on previous year
                function calcYearByYear(forecastYears, thisYear, expenseCategoriesWithSummaryCats, retirementParameters, budgetedExpensesForThisFullYearByCategory) {

                    // get inflationFactors and put on page
                    function processInflationFactors(expenseCategoriesWithSummaryCats, defaultInflationFactor, inflationFactors, inflationFactorCategories) {
        
                        var inflationFactor =0;
                        // for each catagory, 
                        expenseCategoriesWithSummaryCats.forEach( summary => {
                            const category = summary['name'];

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

                        });
                    }   // end processInflationFactors

                    // calc expenses sub-total
                    function calcExpensesSubTot(year, expenseCategoriesWithSummaryCats) {

                        // init sub total
                        var expensesSubTot = 0;
                        var amount;

                        // add each expense category
                        expenseCategoriesWithSummaryCats.forEach(cat => {
                            amount = Number($('#' + cat['name'] + year).text().replaceAll(",", ""));
                            expensesSubTot += amount;
                        });

                        // put sub total on page
                        $('#expenses' + year).text(expensesSubTot.toLocaleString());

                    }   // end calcExpensesSubTot

                    // calc inv growth
                    function calcInitInvGrowth(year, firstOfThisMonth, growthSelectorPrefixes, InvGrowth, budgetedExpensesForThisFullYearByCategory) {

                            // assume growth happened so far at expected rate, and add growth till end of year
                            const month = Number(firstOfThisMonth.substring(5, 7));

                            // number of months interest already earned
                            const numMonthsToDate = month - 1;

                            // number of months left to earn interest
                            const numMonthsLeft = 12 - numMonthsToDate;

                            //      for investments, tax retire, non tax retire
                            growthSelectorPrefixes.forEach (selectorPrefix => {
                                // const origEst = Number($('#Investment' + year).text().replaceAll(',', '')) / ((InvGrowth * numMonthsToDate) + 1);
                                const origEst = Number($('#' + selectorPrefix + year).text().replaceAll(',', '')) / ((InvGrowth * numMonthsToDate) + 1);
                                
                                // apply growth to original balance for number of months left
                                const growthLeft = Math.round((origEst/12 * numMonthsLeft) * InvGrowth);

                                // put on page
                                $('#' + selectorPrefix + 'Growth' + year).text(growthLeft.toLocaleString());

                            });
                    }   // end calcInitInvGrowth

                    // calc income subtotal on page
                    function calcInitIncomeSubtot(year, growthSelectorPrefixes) {

                        // get incomeValues
                        const incomeValues = JSON.parse($("#incomeValues").text());
                        
                        // sum first element of incomeValues to get subtotal
                        var incomeSubTot = 0;
                        incomeValues.forEach( incomeItem => {
                            incomeItem = JSON.parse(incomeItem)[0];
                            incomeSubTot += incomeItem;
                        });

                        // include growth 
                        var incomeValue;
                        growthSelectorPrefixes.forEach( prefix => {
                            incomeValue = Number($('#' + prefix + "Growth" + year).text().replaceAll(",", ""));
                            incomeSubTot += incomeValue;
                        });

                        // put rounded value on page
                        $('#income'+year).text(Math.round(incomeSubTot).toLocaleString());

                    }   // end calcInitIncomeSubtot
                    
                    // calc ending balances on page
                    function calcEndingBalances(year, balanceCategories) {
                        
                        // categories to add/subtract for each balance category
                        var categoriesToAdd = [];
                        var categoriesToSubtract = [];
                        
                        // set items to add or subtract for each balance category
                        // NOTE:  cc debt will always assume to be 0 since it's paid off monthly

                        // cc debt and expenses are negative on the page, so "add" them
                        categoriesToAdd['Spending'] = ['Spending', 'CreditCardDebt', 'income', 'expenses'];
                        categoriesToSubtract['Spending'] = ['InvestmentGrowth', 'TaxableRetirementGrowth', 'TaxFreeRetirementGrowth'];

                        categoriesToAdd['CreditCardDebt'] = [];
                        categoriesToSubtract['CreditCardDebt'] = [];

                        categoriesToAdd['Investment'] = ['Investment', 'InvestmentGrowth'];
                        categoriesToSubtract['Investment'] = [];

                        categoriesToAdd['TaxableRetirement'] = ['TaxableRetirement', 'TaxableRetirementGrowth'];
                        categoriesToSubtract['TaxableRetirement'] = ['TaxRetire'];

                        categoriesToAdd['TaxFreeRetirement'] = ['TaxFreeRetirement', 'TaxFreeRetirementGrowth'];
                        categoriesToSubtract['TaxFreeRetirement'] = ['NonTaxRetire'];

                        // calc new spending
                        var spending = 0;
                        var amount = 0;
                        var endingBalanceSubTot = 0;
                        balanceCategories.forEach( balanceCategory => {
                            // init new ending balance
                            var endingBalance = 0;

                            // sum items to add
                            categoriesToAdd[balanceCategory].forEach( addCategory => {
                                amount = Number($('#' + addCategory + year).text().replaceAll(",", ""));
                                endingBalance += amount;
                            });

                            // subtract items to subtract
                            categoriesToSubtract[balanceCategory].forEach (subtractCategory => {
                                amount = Number($('#' + subtractCategory + year).text().replaceAll(",", ""));
                                endingBalance -= amount;
                            });

                            // put on page
                            $('#end' + balanceCategory + year).text(endingBalance.toLocaleString());
                            endingBalanceSubTot += endingBalance;
                        });

                        // put ending balance subtotal on page
                        $("#ending"+year).text(endingBalanceSubTot);

                    }   // end calcEndingBalances

                    // copy ending balances from previous year to given year on page
                    function copyBeginBalances(year, balanceCategories) {
                        const prevYear = year-1;
                        var beginBalance = 0;

                        // copy each balance from end of last year to begin of this year
                        balanceCategories.forEach( balanceCat => {
                            beginBalance = Number($('#end' + balanceCat + prevYear).text().replaceAll(",", ""));
                            $('#' + balanceCat + year).text(beginBalance.toLocaleString());
                        });

                        // copy sub total
                        beginBalance = $('#ending' + prevYear).text();
                        $('#begSubTot' + year).text(beginBalance.toLocaleString());

                    }   // end copyBeginBalances

                    function calcHouseValues(year, retirementParameters) {

                        // get current year, initial house value & expected house growth
                        // const currentYear = Number($('#currentYear').text());
                        const lastYearHouseValue = Number($('#HouseValue' + (year-1)).text().replaceAll(",", ""));
                        var houseGrowth = retirementParameters['HouseGrowth'];

                        // previous year's values (to start)
                        // var previousYear = currentYear - 1;
                        // var previousYearValue = initialHouseValue;

                        // put house growth on page
                        // $("#HouseGrowth").text(houseGrowth);
                        houseGrowth = houseGrowth/100;  // for calculations

                        // increase house value for each year by houseGrowth
                        var newHouseValue;
                        // calc new value
                        newHouseValue = Math.round(lastYearHouseValue * (1 + houseGrowth));

                        // put on page
                        $("#HouseValue" + year).text(newHouseValue.toLocaleString());

                    }   // end calcHouseValues

                    function calcSimpleExpenseIncreases(year, thisYear, simpleToPredictExpenses, budgetedExpensesForThisFullYearByCategory) {
                        // init variables
                        var lastYearExpense = 0;
                        var thisYearExpense = 0;
                        var inflationFactor = 0;

                        // for each simple to predict expense..
                        simpleToPredictExpenses.forEach (expense => {
                            // get last year's expense
                            // if first full year, previous year is not a full year.  Need to get full year for basis.
                            if(year == Number(thisYear) + 1) {
                                lastYearExpense = budgetedExpensesForThisFullYearByCategory[expense];
                            } else {
                                lastYearExpense = Number($('#' + expense + (year-1)).text().replaceAll(",", ""));
                            }
                            // get the inflation factor for this expense
                            inflationFactor = Number($('#' + expense + 'INF').text());
                            // increase last year's expense by the inflation factor to get this year's expense
                            thisYearExpense = Math.round(lastYearExpense * (inflationFactor/100 + 1));
                            // put the result on the page
                            $('#' + expense + year).text(thisYearExpense.toLocaleString());
                        });

                    }   // end calcSimpleExpenseIncreases

                    function calcDoctorExpense(year, retirementParameters) {

                        // use estimates in retirementData (retirementParameters) if they exist
                        //  otherwise increase by inflation factor from last year

                        var thisYearExpense = 0;
                        // look for this year Doctor in retirementParameters
                        if(typeof retirementParameters['Doctor' + year] != 'undefined') {
                            // if found, use it
                            thisYearExpense = -Number(retirementParameters['Doctor' + year]);
                        } else {
                        // if not found, increase by inflation factor
                            // get last year's expense
                            const lastYearExpense = Number($('#Doctor' + (year-1)).text().replaceAll(",", ""));
                            // get inflationFactor
                            const inflationFactor = Number($('#DoctorINF').text());
                            // increase last year's expense by inflation factor
                            thisYearExpense = Math.round(lastYearExpense * (inflationFactor/100 + 1));
                        }

                        // put the result on the page
                        $('#Doctor' + year).text(thisYearExpense.toLocaleString());

                    }   // end calcDoctorExpense

                    function calcIncomeOtherWHExpense(year, retirementParameters) {

                        // get earned income, convert to numbers and add
                        const townIncome = Number($('#TownofDurham' + year).text().replaceAll(",", ""));
                        // ~ 1/2 GB Limo is tips and not taxed
                        const GBLimoIncome = .5 * Number($('#GBLimo' + year).text().replaceAll(",", ""));
                        const earnedIncome = townIncome + GBLimoIncome;

                        // calc amount withheld
                        // need percentWithheld
                        const percentWithheld = retirementParameters['SS-Med-WHs'];
                        const withheld = -Math.round(percentWithheld/100 * earnedIncome);

                        // put on pate
                        $('#IncomeOtherWH' + year).text(withheld.toLocaleString());

                    }   // end calcIncomeOtherWHExpense

                    function calcIncomeRelatedExpense(year, expenseCategory, relatedIncomeCategories, inflationFactors, budgetedExpensesForThisFullYearByCategory) {
                        // if income is 0, expense is 0
                        // else income is inflationFactor more than last year's income

                        var lastYearExpense, thisYearExpense, inflationFactor;
                        const currentYear = Number($('#currentYear').text());

                        // get sum of income related categories that expense is based on
                        var income = 0;
                        if(year == currentYear) {
                            relatedIncomeCategories.forEach( incomeCat => {
                                income += Number(budgetedExpensesForThisFullYearByCategory[incomeCat + 'Expense'].replaceAll(",", ""));
                            });
                        } else {
                            relatedIncomeCategories.forEach( incomeCat => {
                                income += Number($('#' + incomeCat + year).text().replaceAll(",", "").replaceAll(",", ""));
                            });
                        }

                        if(income == 0) {
                            thisYearExpense = 0;
                        } else {
                            // get last year's expense
                            if(year == currentYear + 1) {
                                lastYearExpense = Number(budgetedExpensesForThisFullYearByCategory[expenseCategory + 'Expense'].replaceAll(",", ""));
                            } else {
                                lastYearExpense = Number($('#' + expenseCategory + 'Expense' + (year-1)).text().replaceAll(",", ""));
                            }
                            // get inflationFactor
                            inflationFactor = Number($('#' + expenseCategory + 'ExpenseINF').text());
                            // increase last year's expense by inflation factor
                            thisYearExpense = Math.round(lastYearExpense * (inflationFactor/100 + 1)); 
                        }

                        // put on page
                        $('#' + expenseCategory + 'Expense' + year).text(thisYearExpense.toLocaleString());

                    }; // end calcIncomeRelatedExpense

                    function calcExtraSpendingExpense(year, retirementParameters) {

                        // extra spending is what's left after other stuff taken from GB Limo income
                        //  minus SS and Medicare withheld
                        //  minus fed taxes
                        //  minus $ for trips to N Hampton
                        //  minus toward household
                        // rest is ExtraSpending

                        var extraSpending;

                        // get GBLimo income to start
                        const GBLimoIncome = Number($('#GBLimo' + year).text().replaceAll(",", ""));

                        // extra spending is 0 if no GB Limo income
                        if(GBLimoIncome == 0) {
                            extraSpending = 0;
                        } else {
                        // has ss & medicare wh been calc'd yet?  If not, get it.
                            const whPercent = retirementParameters['SS-Med-WHs'];
                            const withheld = Math.round(GBLimoIncome * whPercent/100);

                            // estimate federal income taxes.  Half of GB Limo is tips, which aren't taxable thru 2028 up to $25,000
                            // assume 22% tax rate
                            const taxes = (GBLimoIncome / 2) * .22;

                            // trips to N Hampton (based on 2025)
                            const trips = 1000;

                            // towards household
                            const GBLimoForExpenses = retirementParameters['GBLimoForExpenses'];
                            const household = GBLimoIncome * GBLimoForExpenses/100;

                            // extra spending is what's left after everything else subtracted
                            extraSpending = -Math.round(GBLimoIncome - (withheld + taxes + trips + household));
    
                        }  
                        
                        // put extraSpending on the page
                        $('#ExtraSpending' + year).text(extraSpending.toLocaleString());                         
                    }   // end of calcExtraSpendingExpense

                    // calc LTC goals per year & put on retirementforecast page
                    function calcLTCGoals(forecastYears, yearlyContrib, yearFirstContrib, retirementParameters) {

                        const LTCInvGrowth = retirementParameters['LTCInvGrowth'];
                        const interestRate = LTCInvGrowth/100;

                        var LTCbalance = 0;
                        var interest, avgBal;
                        
                        // iterate through each year starting when first contrib made to LTC
                        //      and ending with last forecast year
                        const finalForecastYear = forecastYears[forecastYears.length-1];
                        for(var year = yearFirstContrib; year <= finalForecastYear; year++) {

                            // interest assumes yearlyContrib added throughout the year - so interest on average balance
                            avgBal = LTCbalance + yearlyContrib/2;
                            interest = avgBal * interestRate;
                            LTCbalance += yearlyContrib + interest;
                            LTCbalance = Math.round(LTCbalance * 100)/100;  // round to nearest cent

                            // if within forecastYears, put on page (rounding to nearest dollar on page, but not in calculations)
                            if(forecastYears.includes(year)) {
                                $('#LTCgoal' + year).text(Math.round(LTCbalance).toLocaleString());
                            }
                        }

                    }   // end of function calcLTCGoals

                    // calc LTC contributions & new LTC balances for the year
                    function calcLTCContribsAndNewBalances(year, yearlyContrib, retirementParameters) {
                        var LTCgoal, OldLTCbalance, NewLTCbalance, contrib;
                        const lastYear = Number(year-1);
                        const LTCInvGrowth = retirementParameters['LTCInvGrowth']/100;
                        const currentYear = Number($('#currentYear').text());
                        const currMonth = Number($('#firstOfThisMonth').text().substr(5, 2));

                        // get LTC goal and LTC balance
                        LTCgoal = Number($('#LTCgoal'+year).text().replaceAll(",",""));
                        OldLTCbalance = Number($('#LTCBal'+lastYear).text().replaceAll(",",""));
                        // if one year past the current year, adjust the old balance to an estimate of what it was at the beginning of the year
                        if(year == currentYear + 1) {
                            // need to rewind the balance.  Principle = balance / (1 + rate * time).
                            var time = (currMonth * 30)/365;    // assume 30 day months
                            OldLTCbalance = OldLTCbalance / (1 + LTCInvGrowth*time);
                        }
                        NewLTCbalance = Math.round(OldLTCbalance * (1+LTCInvGrowth));

                        // if balance is more than goal, contrib is 0
                        if(NewLTCbalance >= LTCgoal) {
                            // put 0 LTC contribution on the page
                            contrib = 0;    // needed later on

                        // else contribution is difference, up to $7500 (yearlyContrib)
                        } else {
                            // needs to be displayed as a negative (expense) number
                            contrib = -Math.round(Math.min(yearlyContrib, LTCgoal - NewLTCbalance));
                            // put LTC contribution on the page as an expense
                        }

                        $("#LTC"+year).text(contrib.toLocaleString());

                        // update LTC balance
                        var LTCgrowth;

                        // handle as a positive number here
                        contrib = -contrib;
                        
                        // estimate growth expected
                        LTCgrowth = (OldLTCbalance + contrib)*LTCInvGrowth;
                        
                        // estimate new LTC balance
                        NewLTCbalance = Math.round(OldLTCbalance + contrib + LTCgrowth);

                        // put new LTC balance on the page
                        $("#LTCBal"+year).text(NewLTCbalance.toLocaleString());

                    }   // end of function calcLTCContribsAndNewBalances

                    function calcRetirementInvestmentAmounts(year, retirementParameters) {
                        // when to start taking retirement funds
                        const currentYear = Number($('#currentYear').text());
                        const lastYear = year-1;
                        const currMonth = Number($('#firstOfThisMonth').text().substr(5, 2));
                        const twoDigitYearStart = retirementParameters['RetDistribBegin'].substring(4, 6);
                        const twoDigitIteratedYear = year-2000;
                        const growth = Number(retirementParameters['InvGrowth'])/100;
                        const fractionOfYearInvested = (currMonth * 30)/365;    // assume 30 day months; for rolling back growth

                        var taxableRetGrowthAmount, taxFREERetGrowthAmount, investmentGrowthAmount, tradProportion, rothProportion,
                            totalDistribution, taxableDist, nonTaxableDist, investmentDist, 
                            newTaxableRetBalance, newTaxFREERetBalance, newInvestmentBalance, 
                            taxableGrowth, taxFREEGrowth, investementGrowth;
                        
                        // get last year's data from page
                        // var lastYearTaxableRetIncome = Number($('#TaxRetire' + lastYear).text().replaceAll("," ,""));
                        // var lastYearTaxFREERetIncome = Number($('#NonTaxRetire' + lastYear).text().replaceAll("," ,""));
                        var beginYearTaxableRetBalance = Number($('#TaxableRetirement' + year).text().replaceAll("," ,""));
                        var beginYearTaxFREERetBalance = Number($('#TaxFreeRetirement' + year).text().replaceAll("," ,""));
                        var beginYearInvestmentBalance = Number($('#Investment' + year).text().replaceAll("," ,""));

                        // // rewind interest to beginning of year,
                        // // if processing year after current actual year 
                        // // (where some interest has been earned)
                        // if(year == currentYear + 1) {
                        //     // need to rewind the balance.  Principle = balance / (1 + rate * fractionOfYearInvested).
                        //     beginYearTaxableRetBalance = lastYearTaxableRetBalance / (1 + growth * fractionOfYearInvested);
                        //     beginYearTaxFREERetBalance = lastYearTaxFREERetBalance / (1 + growth * fractionOfYearInvested);
                        //     beginYearInvestmentBalance = lastYearInvestmentBalance / (1 + growth * fractionOfYearInvested);
                        //     // console.log("rewound taxable retirement bal: ", beginYearTaxableRetBalance);
                        //     // console.log("rewound tax free retirement bal: ", beginYearTaxFREERetBalance);
                        // }  

                        // if not getting retirement yet, change the retirement income values to 0 for the year
                        if(twoDigitYearStart > twoDigitIteratedYear) {
                            $('#TaxRetire' + year).text('0');
                            $('#NonTaxRetire' + year).text('0');

                            // calc growth
                            taxableGrowth = Math.round(beginYearTaxableRetBalance * growth);
                            taxFREEGrowth = Math.round(beginYearTaxFREERetBalance * growth);
                            investmentGrowth = Math.round(beginYearInvestmentBalance * growth);

                            // put on page
                            $('#TaxableRetirementGrowth' + year).text(taxableGrowth.toLocaleString());
                            $('#TaxFreeRetirementGrowth' + year).text(taxFREEGrowth.toLocaleString());
                            $('#InvestmentGrowth' + year).text(investmentGrowth.toLocaleString());

                            // new balances just add investment growth
                            newTaxableRetBalance = Math.round(beginYearTaxableRetBalance + taxableGrowth);
                            newTaxFREERetBalance = Math.round(beginYearTaxFREERetBalance + taxFREEGrowth);
                            newInvestmentBalance = Math.round(beginYearInvestmentBalance + investmentGrowth);

                            // put on page
                            $('#endTaxableRetirement' + year).text(newTaxableRetBalance.toLocaleString());
                            $('#endTaxFreeRetirement' + year).text(newTaxFREERetBalance.toLocaleString());
                            $('#endInvestment' + year).text(newInvestmentBalance.toLocaleString());
                        
                        // else if beginning distributions this year
                        } else if(twoDigitYearStart == twoDigitIteratedYear) {

                            // calc growth
                            taxableGrowth = Math.round(beginYearTaxableRetBalance * growth);
                            taxFREEGrowth = Math.round(beginYearTaxFREERetBalance * growth);
                            investmentGrowth = Math.round(beginYearInvestmentBalance * growth);

                            // put on page
                            $('#TaxableRetirementGrowth' + year).text(taxableGrowth.toLocaleString());
                            $('#TaxFreeRetirementGrowth' + year).text(taxFREEGrowth.toLocaleString());
                            $('#InvestmentGrowth' + year).text(investmentGrowth.toLocaleString());

                            // Distributions from Trad and Roth are proportional to initial balances
                            // Determine proportions (TradProportion, RothProportion)
                            tradProportion = beginYearTaxableRetBalance/(beginYearTaxableRetBalance + beginYearTaxFREERetBalance);
                            rothProportion = beginYearTaxFREERetBalance/(beginYearTaxableRetBalance + beginYearTaxFREERetBalance);

                            // calc distributions from taxable (Trad) and tax free (Roth) retirement accounts
                            // totalDistribution = InvWD/100 * (beginYearTaxableRetBalance + WF-IRA-non-taxable-Roth)
                            totalDistribution = retirementParameters['InvWD']/100 * (beginYearTaxableRetBalance + beginYearTaxFREERetBalance);
                            taxableDist = Math.round(totalDistribution * tradProportion);
                            nonTaxableDist = Math.round(totalDistribution *  rothProportion);

                            // taxableDist can't be more than what's there
                            // if not enough in taxableDist, reduce distribution
                            var diff = beginYearTaxableRetBalance-taxableDist;
                            if(diff < 0) {
                                // take all of taxable balance
                                taxableDist = beginYearTaxableRetBalance;
                                totalDistribution -= -diff; 
                            
                                // update balance
                                beginYearTaxableRetBalance = 0;
                            } else {
                                beginYearTaxableRetBalance -= taxableDist;
                            }
                            
                            // nonTaxableDist can't be more than what's there
                            // if not enough in nonTaxableDist, reduce distribution
                            diff = beginYearTaxFREERetBalance - nonTaxableDist;
                            if(diff < 0) {
                                // take all of nontaxable balance
                                nonTaxableDist = beginYearTaxFREERetBalance;
                                totalDistribution -= -diff; 
                            
                                // update balance
                                beginYearTaxFREERetBalance = 0;
                            } else {
                                beginYearTaxFREERetBalance -= nonTaxableDist;
                            }

                            // put distribution values on the page 
                            $('#TaxRetire20' + twoDigitIteratedYear).text(taxableDist.toLocaleString());                      
                            $('#NonTaxRetire20' + twoDigitIteratedYear).text(nonTaxableDist.toLocaleString());                      

                            newTaxableRetBalance = Math.round(beginYearTaxableRetBalance + taxableGrowth);
                            newTaxFREERetBalance = Math.round(beginYearTaxFREERetBalance + taxFREEGrowth);
                            newInvestmentBalance = Math.round(beginYearInvestmentBalance + investmentGrowth);

                            // put new balances on page
                            $('#endTaxableRetirement' + year).text(newTaxableRetBalance.toLocaleString());
                            $('#endTaxFreeRetirement' + year).text(newTaxFREERetBalance.toLocaleString());
                            $('#endInvestment' + year).text(newInvestmentBalance.toLocaleString());

                        } else {
                        // ELSE  ... just bump up last year's values by retirement growth
                        //  and put on page

                            // calc growth
                            taxableGrowth = Math.round(beginYearTaxableRetBalance * growth);
                            taxFREEGrowth = Math.round(beginYearTaxFREERetBalance * growth);
                            investmentGrowth = Math.round(beginYearInvestmentBalance * growth);

                            // put on page
                            $('#TaxableRetirementGrowth' + year).text(taxableGrowth.toLocaleString());
                            $('#TaxFreeRetirementGrowth' + year).text(taxFREEGrowth.toLocaleString());
                            $('#InvestmentGrowth' + year).text(investmentGrowth.toLocaleString());

                            // Distributions from Trad and Roth are proportional to initial balances
                            // Determine proportions (TradProportion, RothProportion)
                            tradProportion = beginYearTaxableRetBalance/(beginYearTaxableRetBalance + beginYearTaxFREERetBalance);
                            rothProportion = beginYearTaxFREERetBalance/(beginYearTaxableRetBalance + beginYearTaxFREERetBalance);

                            // calc distributions from taxable (Trad) and tax free (Roth) retirement accounts
                            // totalDistribution = InvWD/100 * (beginYearTaxableRetBalance + WF-IRA-non-taxable-Roth)
                            totalDistribution = retirementParameters['InvWD']/100 * (beginYearTaxableRetBalance + beginYearTaxFREERetBalance);
                            taxableDist = Math.round(totalDistribution * tradProportion);
                            nonTaxableDist = Math.round(totalDistribution *  rothProportion);

                            // taxableDist can't be more than what's there
                            // if not enough in taxableDist, reduce distribution
                            var diff = beginYearTaxableRetBalance-taxableDist;
                            if(diff < 0) {
                                // take all of taxable balance
                                taxableDist = beginYearTaxableRetBalance;
                                totalDistribution -= -diff; 
                            
                                // update balance
                                beginYearTaxableRetBalance = 0;
                            } else {
                                beginYearTaxableRetBalance -= taxableDist;
                            }
                            
                            // nonTaxableDist can't be more than what's there
                            // if not enough in nonTaxableDist, reduce distribution
                            diff = beginYearTaxFREERetBalance - nonTaxableDist;
                            if(diff < 0) {
                                // take all of nontaxable balance
                                nonTaxableDist = beginYearTaxFREERetBalance;
                                totalDistribution -= -diff; 
                            
                                // update balance
                                beginYearTaxFREERetBalance = 0;
                            } else {
                                beginYearTaxFREERetBalance -= nonTaxableDist;
                            }

                            // put distribution values on the page 
                            $('#TaxRetire20' + twoDigitIteratedYear).text(taxableDist.toLocaleString());                      
                            $('#NonTaxRetire20' + twoDigitIteratedYear).text(nonTaxableDist.toLocaleString());                      

                            newTaxableRetBalance = Math.round(beginYearTaxableRetBalance + taxableGrowth);
                            newTaxFREERetBalance = Math.round(beginYearTaxFREERetBalance + taxFREEGrowth);
                            newInvestmentBalance = Math.round(beginYearInvestmentBalance + investmentGrowth);

                            // put new balances on page
                            $('#endTaxableRetirement' + year).text(newTaxableRetBalance.toLocaleString());
                            $('#endTaxFreeRetirement' + year).text(newTaxFREERetBalance.toLocaleString());
                            $('#endInvestment' + year).text(newInvestmentBalance.toLocaleString());
                        }
                    }   // end calcRetirementInvestmentAmounts

                    function calcIncomeTaxExpense(year, retirementParameters) {
                        // estimate the Income Taxes for the year

                        // get incomes needed
                        const DurhamIncome = Number($('#TownofDurham' + year).text().replaceAll(",", ""));
                        // only about 1/2 of GB Limo is taxable (tips are not taxable)
                        const GBLimoIncome = .5 * Number($('#GBLimo' + year).text().replaceAll(",", ""));
                        const rentalIncome = Number($('#Rental' + year).text().replaceAll(",", ""));
                        const NHRetIncome = Number($('#NHRetirement' + year).text().replaceAll(",", ""));
                        const MikeIBMIncome = Number($('#MikeIBM' + year).text().replaceAll(",", ""));
                        const MikeSSIncome = Number($('#MikeSS' + year).text().replaceAll(",", ""));
                        const MauraIBMIncome = Number($('#MauraIBM' + year).text().replaceAll(",", ""));
                        const MauraSSIncome = Number($('#MauraSS' + year).text().replaceAll(",", ""));
                        const TaxRetireIncome = Number($('#TaxRetire' + year).text().replaceAll(",", ""));
                        // left off here mms mms -- include investment distributions (non-retirement)

                        // total taxable income
                        const totalTaxableIncome = DurhamIncome + GBLimoIncome + rentalIncome + NHRetIncome + MikeIBMIncome + MikeSSIncome + MauraIBMIncome + MauraSSIncome + TaxRetireIncome;

                        // get estimated tax rate (on taxable income)
                        const taxRate = Number(retirementParameters['EstTaxRateOnTotalTaxInc']);

                        // calc taxes & return
                        const taxes = Math.round(totalTaxableIncome * taxRate/100);

                        // put on page (taxes is positive, and neg number is expected on page for expenses, thus "-taxes")
                        $('#IncomeTaxes' + year).text((-taxes).toLocaleString());

                    }   // end calcIncomeTaxExpense

                    function calcSummarySubTotals(year) {

                        // get sumCategoriesWithDetailCategories...
                        //      summary categories w/detail categories included in each
                        const sumCategoriesWithDetailCategories = JSON.parse($('#sumCategoriesWithDetailCategories').text());

                        var summaryTotal;
                        var expenseTotal = 0;
                        // iterate over summary categories, adding detail amounts to get the summary for the summary category
                        for (const [summaryCategory, detailCategories] of Object.entries(sumCategoriesWithDetailCategories)) {
                            summaryTotal = 0;
                            detailCategories.forEach( detailCategory => {
                                summaryTotal += Number($('#' + detailCategory + year).text().replaceAll(",", ""));
                            });

                            // put it on the page
                            $('#' + summaryCategory + year + "SUM").text(Math.round(summaryTotal).toLocaleString());
                            expenseTotal += summaryTotal;
                        }

                        // put total expenses on page
                        $('#expenses' + year).text(expenseTotal.toLocaleString());

                    }   // end calcSummarySubTotals

                    function calcEndingSpending(year) {

                        const beginSpending = Number($('#Spending' + year).text().replaceAll(",", ""));
                        const creditCard = Number($('#CreditCardDebt' + year).text().replaceAll(",", ""));
                        const income = Number($('#income' + year).text().replaceAll(",", ""));
                        const expenses = Number($('#expenses' + year).text().replaceAll(",", ""));
                        // don't include investment or tetirment account growth; they're accounted for separately
                        const investmentGrowth = Number($('#InvestmentGrowth' + year).text().replaceAll(",", ""));
                        const taxRetGrowth = Number($('#TaxableRetirementGrowth' + year).text().replaceAll(",", ""));
                        const taxFreeRetGrowth = Number($('#TaxFreeRetirementGrowth' + year).text().replaceAll(",", ""));

                        // sum everything up (expenses is a negative number, so add it)
                        const endingSpending = beginSpending + creditCard + income + expenses - (investmentGrowth + taxRetGrowth + taxFreeRetGrowth);

                        // put on page
                        $('#endSpending' + year).text(Math.round(endingSpending).toLocaleString());
                    }   // end calcEndingSpending

                    function calcSubTotals(year, type) {

                        var categories, subTotPrefix;

                        switch(type) {
                            // --- Sub total for BEGINNING BALANCES
                            case 'begin':
                                categories = ['Spending', 'CreditCardDebt', 'Investment', 'TaxableRetirement', 'TaxFreeRetirement'];
                                subTotPrefix = 'begSubTot';
                                break;

                            // --- Sub total for INCOME (expenses done in calcSummarySubTotals)
                            case 'income':
                                categories = ['TownofDurham', 'GBLimo', 'Rental', 'NHRetirement', 'MikeIBM', 'MikeSS', 'MauraIBM', 'MauraSS', 
                                'TaxRetire', 'NonTaxRetire', 'InvestmentGrowth', 'TaxableRetirementGrowth', 'TaxFreeRetirementGrowth'];
                                subTotPrefix = 'income';
                                break;

                            // --- Sub total for ENDING BALANCES ("end" concatenated to the front of elements in beginBalanceCategories)
                            case 'end':
                                categories = ['endSpending', 'endCreditCardDebt', 'endInvestment', 'endTaxableRetirement', 'endTaxFreeRetirement'];
                                subTotPrefix = 'ending';
                                break;
                        }
                        var subtotal = 0;
                        categories.forEach( detailCategory => {
                            subtotal += Number($('#' + detailCategory + year).text().replaceAll(",", ""));
                        });

                        // put it on the page
                        $('#' + subTotPrefix + year).text(Math.round(subtotal).toLocaleString());

                    }   // end calcSubTotals


                    // get default inflation factor
                    const defaultInflationFactor = $("#defaultInflationFactor").text();
                
                    // get inflationFactors from page
                    const inflationFactors = JSON.parse($("#inflationFactors").text());
                    
                    // categories with different (non-default) inflation factors
                    var inflationFactorCategories = Object.keys(inflationFactors);

                    // put inflationFactors on page
                    processInflationFactors(expenseCategoriesWithSummaryCats, defaultInflationFactor, inflationFactors, inflationFactorCategories);

                    // get InvGrowth from retirementParameters
                    const InvGrowth = Number(retirementParameters['InvGrowth'])/100;

                    // categories for beginning and ending balances
                    const balanceCategories = JSON.parse($("#balanceCategories").text());

                    // calc init LTC goal for each year
                    // LTC constants
                    const yearlyContrib = 7500;
                    const yearFirstContrib = 2021;
                    calcLTCGoals(forecastYears, yearlyContrib, yearFirstContrib, retirementParameters);

                    // growth selector prefixes
                    growthSelectorPrefixes = ["Investment", "TaxableRetirement", "TaxFreeRetirement"];
                    // Note: income passed in for everything except Investment Growth, Taxable Retirement Growth, Tax Free Retirement Growth
                    forecastYears.forEach( (year, idx) => {

                        // first year is a little different - many of the numbers were passed in
                        if(idx == 0) {
                            calcExpensesSubTot(year, expenseCategoriesWithSummaryCats);
                            calcInitInvGrowth(year, firstOfThisMonth, growthSelectorPrefixes, InvGrowth);
                            calcInitIncomeSubtot(year, growthSelectorPrefixes);
                            calcEndingBalances(year, balanceCategories);
                            $('#HouseValue' + year).text(Number(retirementParameters['House'].replaceAll(",", "")).toLocaleString());

                        } else {
                        // figure rest of worksheet
                            copyBeginBalances(year, balanceCategories, inflationFactors);

                            // calc house values
                            calcHouseValues(year, retirementParameters);

                            // calc expenses that are just based on inflationFactor increase over previous year
                            // all expenses except Doctor, IncomeTaxes, IncomeOtherWH, RentalExpense, WorkExpense, ExtraSpending, LTC
                            simpleToPredictExpenses = ['BigExpenses', 'Bolt', 'Charity', 'College', 'CRZ', 'Dentist', 
                                'Eyecare', 'Gift', 'Groceries', 'Holiday', 'Home', 'HomeInsurance', 'Kids', 'LifeInsurance', 
                                'Loan', 'LoanPaid', 'MarinasMiles', 'MauraSpending', 'MikeSpending', 'MiscExpense', 
                                'Prescriptions', 'PropertyTax', 'RetContribOut', 'Utilities', 'Vacation'];                              
                            calcSimpleExpenseIncreases(year, thisYear, simpleToPredictExpenses, budgetedExpensesForThisFullYearByCategory);
                                
                            // calc more complicated dependencies (doctor, withholdings, rental expense, Work expense, extraspending, ltc)
                            // get doctor expense for the year
                            calcDoctorExpense(year, retirementParameters);

                            // get IncomeOtherWH expense for the year
                            calcIncomeOtherWHExpense(year, retirementParameters);

                            // get RentalExpense expense for the year
                            calcIncomeRelatedExpense(year, 'Rental', ['Rental'], inflationFactors, budgetedExpensesForThisFullYearByCategory);
                            
                            // get WorkExpense expense for the year
                            calcIncomeRelatedExpense(year, 'Work', ['GBLimo', 'TownofDurham'], inflationFactors, budgetedExpensesForThisFullYearByCategory);

                            // get ExtraSpending expense for the year
                            calcExtraSpendingExpense(year, retirementParameters);

                            // get LTC contribs & new LTC balancesfor the year
                            calcLTCContribsAndNewBalances(year, yearlyContrib, retirementParameters); 

                            // calc retirement income
                            calcRetirementInvestmentAmounts(year, retirementParameters);

                            // calc income tax
                            calcIncomeTaxExpense(year, retirementParameters);
                            
                            // calc summary totals and sub totals
                            // handle when spending accounts go below 0 (take from investments)
                            calcSummarySubTotals(year);

                            // calc begin subtotals
                            calcSubTotals(year, 'begin');
                            // calc income subtotals
                            calcSubTotals(year, 'income');

                            // update ending spending;
                            //      including handling when it goes negative (take from investments, update income tax, etc)
                            calcEndingSpending(year);

                            // calc ending balances
                            calcSubTotals(year, 'end');

                            // left off here mms mms
                            //      handle when spending falls below 0 (take from investment acct)
                            //          adjust income taxes if money taken from investements
                            //
                            // figure sub-totals


                        }
                    });

                }   // end calcYearByYear
                          
                // calc estimated house values
                function getHouseValues(forecastYears, retirementParameters) {
                    // get current year, initial house value & expected house growth
                    const currentYear = Number($('#currentYear').text());
                    const initialHouseValue = Math.round(retirementParameters['House']);
                    const houseGrowth = retirementParameters['HouseGrowth'];

                    // previous year's values (to start)
                    var previousYear = currentYear - 1;
                    var previousYearValue = initialHouseValue;

                    // put house growth on page
                    $("#HouseGrowth").text(houseGrowth.toLocaleString());

                    // increase house value for each year by houseGrowth
                    var newHouseValue;
                    forecastYears.forEach( year => {
                        if(year != currentYear) {
                            // calc new value
                            newHouseValue = Math.round(previousYearValue * (1 + houseGrowth/100));

                            // put on page
                            $("#HouseValue" + year).text(newHouseValue.toLocaleString());

                            // set up for next year
                            previousYear = year;
                            previousYearValue = newHouseValue;
                        } else {
                            // set initial house value
                            $("#HouseValue" + currentYear).text(initialHouseValue.toLocaleString());
                        }
                    });
                   
                } // end of function getHouseValues

                
                // get forecastYears from page
                var forecastYears = JSON.parse($("#forecastYears").text());

                // get retirement parameters
                var retirementParameters = $("#retirementParameters").text();
                retirementParameters = JSON.parse(retirementParameters);

                // get expense Categories With Summary Cats
                var expenseCategoriesWithSummaryCats = JSON.parse($("#expenseCategoriesWithSummaryCats").text()); 
                
                // get expenses for current year by category
                var budgetedExpensesForThisFullYearByCategory = JSON.parse($("#budgetedExpensesForThisFullYearByCategory").text());

                // get date (first of month) for forecast
                const firstOfThisMonth = $('#firstOfThisMonth').text();

                // get this year and the expense categories
                var thisYear = firstOfThisMonth.substr(0, 4);

                calcYearByYear(forecastYears, thisYear, expenseCategoriesWithSummaryCats, retirementParameters, budgetedExpensesForThisFullYearByCategory);
                
            });  // end of document ready



        </script>
    </body>

</html>