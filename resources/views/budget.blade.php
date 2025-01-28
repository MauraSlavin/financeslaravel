<html>
    <head>
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </head>

    <body>
    <h1>Budget</h1>
    <h6>Year: {{ $thisYear }}</h6>

    <!-- hidden fields -->
    <input type="hidden" id="budgetData"  name="budgetData"  value={{ json_encode($budgetData) }}>
    <input type="hidden" id="incomeCategories"  name="incomeCategories"  value={{ json_encode($incomeCategories) }}>
    <input type="hidden" id="expenseCategories"  name="expenseCategories"  value={{ json_encode($expenseCategories) }}>

    <table>
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

            <!-- income -->
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
            @foreach($budgetData as $category)
                @if(in_array($category->category, $incomeCategories))
                    <tr>
                        <td class="text-end" style="width: 100px;">{{ $category->category }}</td>
                        <td class="text-end" style="width: 100px;">{{ $category->january }}</td>
                        <td class="text-end" style="width: 100px;">{{ $category->february }}</td>
                        <td class="text-end" style="width: 100px;">{{ $category->march }}</td>
                        <td class="text-end" style="width: 100px;">{{ $category->april }}</td>
                        <td class="text-end" style="width: 100px;">{{ $category->may }}</td>
                        <td class="text-end" style="width: 100px;">{{ $category->june }}</td>
                        <td class="text-end" style="width: 100px;">{{ $category->july }}</td>
                        <td class="text-end" style="width: 100px;">{{ $category->august }}</td>
                        <td class="text-end" style="width: 100px;">{{ $category->september }}</td>
                        <td class="text-end" style="width: 100px;">{{ $category->october }}</td>
                        <td class="text-end" style="width: 100px;">{{ $category->november }}</td>
                        <td class="text-end" style="width: 100px;">{{ $category->december }}</td>
                        <td class="text-end" style="width: 100px;" id="totalIncome">{{ $category->total }}</td>
                    </tr>
                @endif
            @endforeach

            <!-- Income total line -->
            <tr  style="background-color: #0096FF; color: white;">
                <td class="text-end" style="width: 100px;">Income Total</td>
                <td class="text-end" style="width: 100px;" id="januaryIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="februaryIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="marchIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="aprilIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="mayIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="juneIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="julyIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="augustIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="septemberIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="octoberIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="novemberIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="decemberIncomeTotal"></td> 
                <td class="text-end" style="width: 100px;" id="IncomeTotal"></td>
            </tr>

            <!-- expense -->
            <tr style="background-color: blue; color: white;">
                <td>Expense</td>
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
                    <tr>
                        <td class="text-end" style="width: 100px;">{{ $category->category }}</td>
                        <td class="text-end" style="width: 100px;">{{ $category->january }}</td>
                        <td class="text-end" style="width: 100px;">{{ $category->february }}</td>
                        <td class="text-end" style="width: 100px;">{{ $category->march }}</td>
                        <td class="text-end" style="width: 100px;">{{ $category->april }}</td>
                        <td class="text-end" style="width: 100px;">{{ $category->may }}</td>
                        <td class="text-end" style="width: 100px;">{{ $category->june }}</td>
                        <td class="text-end" style="width: 100px;">{{ $category->july }}</td>
                        <td class="text-end" style="width: 100px;">{{ $category->august }}</td>
                        <td class="text-end" style="width: 100px;">{{ $category->september }}</td>
                        <td class="text-end" style="width: 100px;">{{ $category->october }}</td>
                        <td class="text-end" style="width: 100px;">{{ $category->november }}</td>
                        <td class="text-end" style="width: 100px;">{{ $category->december }}</td> 
                        <td class="text-end" style="width: 100px;" id="totalExpense">{{ $category->total }}</td>
                    </tr>
                @endif
            @endforeach

            <!-- Expense total line -->
            <tr  style="background-color: #0096FF; color: white;">
                <td class="text-end" style="width: 100px;">Expense Total</td>
                <td class="text-end" style="width: 100px;" id="januaryExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="februaryExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="marchExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="aprilExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="mayExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="juneExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="julyExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="augustExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="septemberExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="octoberExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="novemberExpenseTotal"></td>
                <td class="text-end" style="width: 100px;" id="decemberExpenseTotal"></td> 
                <td class="text-end" style="width: 100px;" id="ExpenseTotal"></td>
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
            var incomeTotal = 0;
            var expenseTotal = 0;

            var incomeTotals = {
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

            var expenseTotals = {
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
                if(incomeCategories.includes(data['category'])) {
                    months.forEach(month => {
                        incomeTotals[month] += parseFloat(data[month]);
                        incomeTotal += parseFloat(data[month]);
                    });
                } else if(expenseCategories.includes(data['category'])) {
                    months.forEach(month => {
                        expenseTotals[month] += parseFloat(data[month]);
                        expenseTotal += parseFloat(data[month]);
                    });
                }
            });

            // put monthly income and expense totals on the page
            months.forEach( month => {
                var incomeTotal = incomeTotals[month].toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                $("#" + month + "IncomeTotal").text(incomeTotal);
                var expenseTotal = expenseTotals[month].toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                $("#" + month + "ExpenseTotal").text(expenseTotal);
                var total = (incomeTotals[month] + expenseTotals[month]).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                $("#" + month + "Total").text(total);
            });

            // put total income, expense and grand total on the page
            var total = incomeTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            $("#IncomeTotal").text(total);

            var total = expenseTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            $("#ExpenseTotal").text(total);
            
            console.log("incomeTotal: ", incomeTotal);
            console.log("expenseTotal: ", expenseTotal);
            console.log("Total: ", (incomeTotal + expenseTotal));
            var total = (incomeTotal + expenseTotal).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            $("#grandTotal").text(total);


        });

    </script>

    </body>
</html>