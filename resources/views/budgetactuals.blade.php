<html>
    <head>
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </head>

    <body>
    <h1>Budget vs Actuals</h1>
    <h6>Year: {{ $thisYear }}</h6>
    <button type="button" id="budget" class="btn btn-success">Budget</button>
    <button type="button" id="actuals" class="btn btn-primary">Actuals</button>

    <!-- hidden fields -->
    <input type="hidden" id="budgetData"  name="budgetData"  value={{ json_encode($budgetData) }}>
    <input type="hidden" id="incomeCategories"  name="incomeCategories"  value={{ json_encode($incomeCategories) }}>
    <input type="hidden" id="expenseCategories"  name="expenseCategories"  value={{ json_encode($expenseCategories) }}>

    <table>
        <!-- Headers -->
        <thead>
            <tr>
                <th style="width: 100px;">Category</th>
                <th>Jan</th>
                <th>Feb</th>
                <th>Mar</th>
                <th>Apr</th>
                <th>May</th>
                <th>Jun</th>
                <th>Jul</th>
                <th>Aug</th>
                <th>Sep</th>
                <th>Oct</th>
                <th>Nov</th>
                <th>Dec</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>

            <!-- income sub-header -->
            <tr style="background-color: blue; color: white;">
                <td>Income</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            @foreach($budgetData as $catIdx=>$category)
                @if(in_array($category->category, $incomeCategories))
                    <!-- Budget data -->
                    <tr>
                        <td class="budget category">{{ $category->category }}</td>
                        <td class="budget">{{ $category->january }}</td>
                        <td class="budget">{{ $category->february }}</td>
                        <td class="budget">{{ $category->march }}</td>
                        <td class="budget">{{ $category->april }}</td>
                        <td class="budget">{{ $category->may }}</td>
                        <td class="budget">{{ $category->june }}</td>
                        <td class="budget">{{ $category->july }}</td>
                        <td class="budget">{{ $category->august }}</td>
                        <td class="budget">{{ $category->september }}</td>
                        <td class="budget">{{ $category->october }}</td>
                        <td class="budget">{{ $category->november }}</td>
                        <td class="budget">{{ $category->december }}</td>
                        <td class="budget" id="totalIncome">{{ $category->total }}</td>
                    </tr>
                    <!-- actual data -->
                    <tr>
                        <td class="actual">actual</td>
                        <td class="actual">{{ $actualIncomeData[$category->category]['january'] }}</td>
                        <td class="actual">{{ $actualIncomeData[$category->category]['february'] }}</td>
                        <td class="actual">{{ $actualIncomeData[$category->category]['march'] }}</td>
                        <td class="actual">{{ $actualIncomeData[$category->category]['april'] }}</td>
                        <td class="actual">{{ $actualIncomeData[$category->category]['may'] }}</td>
                        <td class="actual">{{ $actualIncomeData[$category->category]['june'] }}</td>
                        <td class="actual">{{ $actualIncomeData[$category->category]['july'] }}</td>
                        <td class="actual">{{ $actualIncomeData[$category->category]['august'] }}</td>
                        <td class="actual">{{ $actualIncomeData[$category->category]['september'] }}</td>
                        <td class="actual">{{ $actualIncomeData[$category->category]['october'] }}</td>
                        <td class="actual">{{ $actualIncomeData[$category->category]['november'] }}</td>
                        <td class="actual">{{ $actualIncomeData[$category->category]['december'] }}</td>
                        <td class="actual" id="totalIncome">{{ $actualIncomeData[$category->category]['total'] }}</td>
                    </tr>
                    <!-- difference -->
                    <tr style="border-bottom-width: thick;">
                        <td class="diff">difference</td>
                        <td class="diff">{{ number_format((float)str_replace(",", "", $category->january) - (float)str_replace(",", "", $actualIncomeData[$category->category]['january']), 2) }}</td>
                        <td class="diff">{{ number_format((float)str_replace(",", "", $category->february) - (float)str_replace(",", "", $actualIncomeData[$category->category]['february']), 2) }}</td>
                        <td class="diff">{{ number_format((float)str_replace(",", "", $category->march) - (float)str_replace(",", "", $actualIncomeData[$category->category]['march']), 2) }}</td>
                        <td class="diff">{{ number_format((float)str_replace(",", "", $category->april) - (float)str_replace(",", "", $actualIncomeData[$category->category]['april']), 2) }}</td>
                        <td class="diff">{{ number_format((float)str_replace(",", "", $category->may) - (float)str_replace(",", "", $actualIncomeData[$category->category]['may']), 2) }}</td>
                        <td class="diff">{{ number_format((float)str_replace(",", "", $category->june) - (float)str_replace(",", "", $actualIncomeData[$category->category]['june']), 2) }}</td>
                        <td class="diff">{{ number_format((float)str_replace(",", "", $category->july) - (float)str_replace(",", "", $actualIncomeData[$category->category]['july']), 2) }}</td>
                        <td class="diff">{{ number_format((float)str_replace(",", "", $category->august) - (float)str_replace(",", "", $actualIncomeData[$category->category]['august']), 2) }}</td>
                        <td class="diff">{{ number_format((float)str_replace(",", "", $category->september) - (float)str_replace(",", "", $actualIncomeData[$category->category]['september']), 2) }}</td>
                        <td class="diff">{{ number_format((float)str_replace(",", "", $category->october) - (float)str_replace(",", "", $actualIncomeData[$category->category]['october']), 2) }}</td>
                        <td class="diff">{{ number_format((float)str_replace(",", "", $category->november) - (float)str_replace(",", "", $actualIncomeData[$category->category]['november']), 2) }}</td>
                        <td class="diff">{{ number_format((float)str_replace(",", "", $category->december) - (float)str_replace(",", "", $actualIncomeData[$category->category]['december']), 2) }}</td>
                        <td class="diff" id="totalIncome">{{ number_format((float)str_replace(",", "", $category->total) - (float)str_replace(",", "", $actualIncomeData[$category->category]['total']), 2) }}</td>
                    </tr>
                @endif
            @endforeach

            <!-- Income total line -->
            <tr  style="background-color: #0096FF; color: white;">
                <td class="text-end" style="width: 100px;">Income Total</td>
                <td class="text-end" style="width: 100px;" id="januaryIncomeExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="februaryIncomeExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="marchIncomeExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="aprilIncomeExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="mayIncomeExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="juneIncomeExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="julyIncomeExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="augustIncomeExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="septemberIncomeExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="octoberIncomeExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="novemberIncomeExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="decemberIncomeExpenseTotal"></td> 
                <td class="text-end" style="width: 100px;" id="incomeExpenseTotal"></td>
            </tr>

            <!-- expense -->
            <tr style="background-color: blue; color: white;">
                <td>Expenses</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            @foreach($budgetData as $category)
                @if(in_array($category->category, $expenseCategories))
                    <!-- Budget data -->
                    <tr>
                        <td class="budget category">{{ $category->category }}</td>
                        <td class="budget">{{ $category->january }}</td>
                        <td class="budget">{{ $category->february }}</td>
                        <td class="budget">{{ $category->march }}</td>
                        <td class="budget">{{ $category->april }}</td>
                        <td class="budget">{{ $category->may }}</td>
                        <td class="budget">{{ $category->june }}</td>
                        <td class="budget">{{ $category->july }}</td>
                        <td class="budget">{{ $category->august }}</td>
                        <td class="budget">{{ $category->september }}</td>
                        <td class="budget">{{ $category->october }}</td>
                        <td class="budget">{{ $category->november }}</td>
                        <td class="budget">{{ $category->december }}</td> 
                        <td class="budget" id="totalExpense">{{ $category->total }}</td>
                    </tr>
                    <!-- actual data -->
                    <tr>
                        <td class="actual">actual</td>
                        <td class="actual">{{ $actualExpenseData[$category->category]['january'] }}</td>
                        <td class="actual">{{ $actualExpenseData[$category->category]['february'] }}</td>
                        <td class="actual">{{ $actualExpenseData[$category->category]['march'] }}</td>
                        <td class="actual">{{ $actualExpenseData[$category->category]['april'] }}</td>
                        <td class="actual">{{ $actualExpenseData[$category->category]['may'] }}</td>
                        <td class="actual">{{ $actualExpenseData[$category->category]['june'] }}</td>
                        <td class="actual">{{ $actualExpenseData[$category->category]['july'] }}</td>
                        <td class="actual">{{ $actualExpenseData[$category->category]['august'] }}</td>
                        <td class="actual">{{ $actualExpenseData[$category->category]['september'] }}</td>
                        <td class="actual">{{ $actualExpenseData[$category->category]['october'] }}</td>
                        <td class="actual">{{ $actualExpenseData[$category->category]['november'] }}</td>
                        <td class="actual">{{ $actualExpenseData[$category->category]['december'] }}</td>
                        <td class="actual" id="totalExpense">{{ $actualExpenseData[$category->category]['total'] }}</td>
                    </tr>
                    <!-- difference -->
                    <!-- NOTE: subtraction is reversed since the budget is a negative number, so + means $ left in the budget, and - means overspent -->
                    <tr style="border-bottom-width: thick;">
                        <td class="diff">difference</td>
                        <td class="diff">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category->category]['january']) - (float)str_replace(",", "", $category->january), 2) }}</td>
                        <td class="diff">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category->category]['february']) - (float)str_replace(",", "", $category->february), 2) }}</td>
                        <td class="diff">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category->category]['march']) - (float)str_replace(",", "", $category->march), 2) }}</td>
                        <td class="diff">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category->category]['april']) - (float)str_replace(",", "", $category->april), 2) }}</td>
                        <td class="diff">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category->category]['may']) - (float)str_replace(",", "", $category->may), 2) }}</td>
                        <td class="diff">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category->category]['june']) - (float)str_replace(",", "", $category->june), 2) }}</td>
                        <td class="diff">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category->category]['july']) - (float)str_replace(",", "", $category->july), 2) }}</td>
                        <td class="diff">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category->category]['august']) - (float)str_replace(",", "", $category->august), 2) }}</td>
                        <td class="diff">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category->category]['september']) - (float)str_replace(",", "", $category->september), 2) }}</td>
                        <td class="diff">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category->category]['october']) - (float)str_replace(",", "", $category->october), 2) }}</td>
                        <td class="diff">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category->category]['november']) - (float)str_replace(",", "", $category->november), 2) }}</td>
                        <td class="diff">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category->category]['december']) - (float)str_replace(",", "", $category->december), 2) }}</td>
                        <td class="diff" id="totalExpense">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category->category]['total']) - (float)str_replace(",", "", $category->total), 2) }}</td>
                    </tr>                    
                @endif
            @endforeach

            <!-- Budget Expense total line -->
            <tr  style="background-color: #0096FF; color: white;">
                <td class="text-end category" style="width: 100px;">Budget Expense Total</td>
                <td class="text-end" style="width: 100px;" id="januaryBudgetExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="februaryBudgetExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="marchBudgetExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="aprilBudgetExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="mayBudgetExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="juneBudgetExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="julyBudgetExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="augustBudgetExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="septemberBudgetExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="octoberBudgetExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="novemberBudgetExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="decemberBudgetExpenseTotal"></td> 
                <td class="text-end" style="width: 100px;" id="budgetExpenseTotal"></td>
            </tr>

            <!-- Actual Expense total line -->
            <tr  style="background-color: #0096FF; color: white;">
                <td class="text-end" style="width: 100px;">Actual Expense Total</td>
                <td class="text-end" style="width: 100px;" id="januaryActualExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="februaryActualExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="marchActualExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="aprilActualExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="mayActualExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="juneActualExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="julyActualExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="augustActualExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="septemberActualExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="octoberActualExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="novemberActualExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="decemberActualExpenseTotal"></td> 
                <td class="text-end" style="width: 100px;" id="actualExpenseTotal"></td>
            </tr>

            <!-- diff Expense total line -->
            <tr  style="background-color: #0096FF; color: white;">
                <td class="text-end" style="width: 100px;">Diff Expense Total</td>
                <td class="text-end" style="width: 100px;" id="januaryDiffExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="februaryDiffExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="marchDiffExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="aprilDiffExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="mayDiffExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="juneDiffExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="julyDiffExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="augustDiffExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="septemberDiffExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="octoberDiffExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="novemberDiffExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="decemberDiffExpenseTotal"></td> 
                <td class="text-end" style="width: 100px;" id="diffExpenseTotal"></td>
            </tr>

            <!-- Grand total line -->
            <tr  style="background-color: blue; color: white;">
                <td class="text-end" style="width: 100px;">Total</td>
                <td class="text-end" style="width: 100px;" id="januaryTotal"></td>
                <td class="text-end" style="width: 100px;" id="februaryTotal"></td>
                <td class="text-end" style="width: 100px;" id="marchTotal"></td>
                <td class="text-end" style="width: 100px;" id="aprilTotal"></td>
                <td class="text-end" style="width: 100px;" id="mayTotal"></td>
                <td class="text-end" style="width: 100px;" id="juneTotal"></td>
                <td class="text-end" style="width: 100px;" id="julyTotal"></td>
                <td class="text-end" style="width: 100px;" id="augustTotal"></td>
                <td class="text-end" style="width: 100px;" id="septemberTotal"></td>
                <td class="text-end" style="width: 100px;" id="octoberTotal"></td>
                <td class="text-end" style="width: 100px;" id="novemberTotal"></td>
                <td class="text-end" style="width: 100px;" id="decemberTotal"></td> 
                <td class="text-end" style="width: 100px;" id="grandTotal"></td>
            </tr>
        </tbody>
    </table>

    <script>

            
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(document).ready(function() {

            // months
            const months = [
                'january',
                'february',
                'march',
                'april',
                'may',
                'june',
                'july',
                'august',
                'september',
                'october',
                'november',
                'december'
            ];

            // calculate totals and subtotals
            // get hidden fields
            // - budgetData
            var budgetData = $("#budgetData").val();
            budgetData = JSON.parse(budgetData);

            // - incomeCategories
            var incomeCategories = $("#incomeCategories").val();
            incomeCategories = JSON.parse(incomeCategories);

            // - expenseCategories
            var expenseCategories = $("#expenseCategories").val();
            expenseCategories = JSON.parse(expenseCategories);

            // init total vars
            var incomeExpenseTotal = 0;
            var budgetExpenseTotal = 0;

            var incomeExpenseTotals = {
                'january': 0,
                'february': 0,
                'march': 0,
                'april': 0,
                'may': 0,
                'june': 0,
                'july': 0,
                'august': 0,
                'september': 0,
                'october': 0,
                'november': 0,
                'december': 0
            };

            var budgetExpenseTotals = {
                'january': 0,
                'february': 0,
                'march': 0,
                'april': 0,
                'may': 0,
                'june': 0,
                'july': 0,
                'august': 0,
                'september': 0,
                'october': 0,
                'november': 0,
                'december': 0
            };

            // calc income and expense totals for each month
            budgetData.forEach(data => {
                console.log("----- category: " + data['category'] + " -----");
                if(incomeCategories.includes(data['category'])) {
                    months.forEach(month => {
                        var amt = parseFloat(data[month].replace(",", ""));
                        console.log(" - income for " + month + ": " + amt.toFixed(2));
                        console.log(" - old " + month + " total: " + incomeExpenseTotals[month]);
                        incomeExpenseTotals[month] += amt;
                        incomeExpenseTotal += amt;
                        console.log(" - new " + month + " total: " + incomeExpenseTotals[month]);
                    });
                } else if(expenseCategories.includes(data['category'])) {
                    months.forEach(month => {
                        var amt = parseFloat(data[month].replace(",", ""));
                        console.log(" - expense for " + month + ": " + amt.toFixed(2));
                        console.log(" - old " + month + " total: " + budgetExpenseTotals[month]);
                        budgetExpenseTotals[month] += amt;
                        budgetExpenseTotal += amt;
                        console.log(" - new " + month + " total: " + budgetExpenseTotals[month]);
                    });
                }
            });

            // put monthly income and expense totals on the page
            months.forEach( month => {
                var incomeExpenseTotal = incomeExpenseTotals[month].toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                $("#" + month + "IncomeExpenseTotal").text(incomeExpenseTotal);
                var budgetExpenseTotal = budgetExpenseTotals[month].toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                $("#" + month + "BudgetExpenseTotal").text(budgetExpenseTotal);
                var total = (incomeExpenseTotals[month] + budgetExpenseTotals[month]).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                $("#" + month + "Total").text(total);
            });

            // put total income, expense and grand total on the page
            var total = incomeExpenseTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            $("#incomeExpenseTotal").text(total);

            var total = budgetExpenseTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            $("#budgetExpenseTotal").text(total);
            
            var total = (incomeExpenseTotal + budgetExpenseTotal).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            $("#grandTotal").text(total);

            // listener for budget button
            $('#budget').on('click', function(e) {
                e.preventDefault();

                const url = '/accounts/budget';
                window.location.href = url;
            });

            // listener for actuals button
            $('#actuals').on('click', function(e) {
                e.preventDefault();

                const url = '/accounts/actuals';
                window.location.href = url;
            });
        });

    </script>

    </body>
</html>