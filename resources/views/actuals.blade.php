<html>
    <head>
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </head>

    <body>
    <h1>Actuals</h1>

    <h2>Year: <span id="year">{{ $year }}</span> </h2>
    <form action="{{ route('actuals', $year ?? session('selected_year')) }}" method="GET">
        <select name="year">
            @foreach(range('2022', date('Y')) as $y)
                <option value="{{ $y }}" {{ $y == ($year ?? session('selected_year')) ? 'selected' : '' }}>
                    {{ $y }}
                </option>
            @endforeach
        </select>
        <button type="submit">Update</button>
    </form>

    <button type="button" id="budget" class="btn btn-success">Budget</button>
    <button type="button" id="budgetactuals" class="btn btn-warning">Budget vs Actuals</button>

    <!-- hidden fields -->
    <input type="hidden" id="incomeCategories"  name="incomeCategories"  value={{ json_encode($incomeCategories) }}>
    <input type="hidden" id="expenseCategories"  name="expenseCategories"  value={{ json_encode($expenseCategories) }}>
    <input type="hidden" id="actualIncomeData"  name="actualIncomeData"  value={{ json_encode($actualIncomeData) }}>
    <input type="hidden" id="actualExpenseData"  name="actualExpenseData"  value={{ json_encode($actualExpenseData) }}>

    <table id="actualsTable">
        <thead>
            <tr>
                <th style="width: 100px; background-color: #50C878; color: black;">Category</th>
                <th style="background-color: #50C878; color: black;">Jan</th>
                <th style="background-color: #50C878; color: black;">Feb</th>
                <th style="background-color: #50C878; color: black;">Mar</th>
                <th style="background-color: #50C878; color: black;">Apr</th>
                <th style="background-color: #50C878; color: black;">May</th>
                <th style="background-color: #50C878; color: black;">Jun</th>
                <th style="background-color: #50C878; color: black;">Jul</th>
                <th style="background-color: #50C878; color: black;">Aug</th>
                <th style="background-color: #50C878; color: black;">Sep</th>
                <th style="background-color: #50C878; color: black;">Oct</th>
                <th style="background-color: #50C878; color: black;">Nov</th>
                <th style="background-color: #50C878; color: black;">Dec</th>
                <th style="background-color: #50C878; color: black;">Total</th>
            </tr>
        </thead>
        <tbody>

            <!-- income -->
            <tr style="background-color: green; color: white;">
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
            @foreach($actualIncomeData as $key=>$category)
                <tr>
                    <td class="text-end" style="width: 100px;">{{ $key }}</td>
                    <td class="text-end" style="width: 100px;">{{ $category['january'] }}</td>
                    <td class="text-end" style="width: 100px;">{{ $category['february'] }}</td>
                    <td class="text-end" style="width: 100px;">{{ $category['march'] }}</td>
                    <td class="text-end" style="width: 100px;">{{ $category['april'] }}</td>
                    <td class="text-end" style="width: 100px;">{{ $category['may'] }}</td>
                    <td class="text-end" style="width: 100px;">{{ $category['june'] }}</td>
                    <td class="text-end" style="width: 100px;">{{ $category['july'] }}</td>
                    <td class="text-end" style="width: 100px;">{{ $category['august'] }}</td>
                    <td class="text-end" style="width: 100px;">{{ $category['september'] }}</td>
                    <td class="text-end" style="width: 100px;">{{ $category['october'] }}</td>
                    <td class="text-end" style="width: 100px;">{{ $category['november'] }}</td>
                    <td class="text-end" style="width: 100px;">{{ $category['december'] }}</td>
                    <td class="text-end" style="width: 100px;" id="totalIncome">{{ $category['total'] }}</td>
                </tr>
            @endforeach

            <!-- Income total line -->
            <tr  style="background-color: #81eb71;">
                <td class="text-end" style="width: 100px;">Income Total</td>
                <td class="text-end" style="width: 100px;" id="januaryIncomeTotal">{{ $incomeTotals['january'] }}</td>
                <td class="text-end" style="width: 100px;" id="februaryIncomeTotal">{{ $incomeTotals['february'] }}</td>
                <td class="text-end" style="width: 100px;" id="marchIncomeTotal">{{ $incomeTotals['march'] }}</td>
                <td class="text-end" style="width: 100px;" id="aprilIncomeTotal">{{ $incomeTotals['april'] }}</td>
                <td class="text-end" style="width: 100px;" id="mayIncomeTotal">{{ $incomeTotals['may'] }}</td>
                <td class="text-end" style="width: 100px;" id="juneIncomeTotal">{{ $incomeTotals['june'] }}</td>
                <td class="text-end" style="width: 100px;" id="julyIncomeTotal">{{ $incomeTotals['july'] }}</td>
                <td class="text-end" style="width: 100px;" id="augustIncomeTotal">{{ $incomeTotals['august'] }}</td>
                <td class="text-end" style="width: 100px;" id="septemberIncomeTotal">{{ $incomeTotals['september'] }}</td>
                <td class="text-end" style="width: 100px;" id="octoberIncomeTotal">{{ $incomeTotals['october'] }}</td>
                <td class="text-end" style="width: 100px;" id="novemberIncomeTotal">{{ $incomeTotals['november'] }}</td>
                <td class="text-end" style="width: 100px;" id="decemberIncomeTotal">{{ $incomeTotals['december'] }}</td> 
                <td class="text-end" style="width: 100px;" id="IncomeTotal">{{ $incomeTotals['total'] }}</td>
            </tr>

            <!-- expense -->
            <tr style="background-color: green; color: white;">
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
            @foreach($actualExpenseData as $key=>$category)
                <tr>
                    <td class="text-end" style="width: 100px;">{{ $key }}</td>
                    <td class="text-end" style="width: 100px;">{{ $category['january'] }}</td>
                    <td class="text-end" style="width: 100px;">{{ $category['february'] }}</td>
                    <td class="text-end" style="width: 100px;">{{ $category['march'] }}</td>
                    <td class="text-end" style="width: 100px;">{{ $category['april'] }}</td>
                    <td class="text-end" style="width: 100px;">{{ $category['may'] }}</td>
                    <td class="text-end" style="width: 100px;">{{ $category['june'] }}</td>
                    <td class="text-end" style="width: 100px;">{{ $category['july'] }}</td>
                    <td class="text-end" style="width: 100px;">{{ $category['august'] }}</td>
                    <td class="text-end" style="width: 100px;">{{ $category['september'] }}</td>
                    <td class="text-end" style="width: 100px;">{{ $category['october'] }}</td>
                    <td class="text-end" style="width: 100px;">{{ $category['november'] }}</td>
                    <td class="text-end" style="width: 100px;">{{ $category['december']}}</td> 
                    <td class="text-end" style="width: 100px;" id="totalExpense">{{ $category['total'] }}</td>
                </tr>
            @endforeach

            <!-- Expense total line -->
            <tr  style="background-color: #81eb71;">
                <td class="text-end" style="width: 100px;">Expense Total</td>
                <td class="text-end" style="width: 100px;" id="januaryExpenseTotal">{{ $expenseTotals['january'] }}</td>
                <td class="text-end" style="width: 100px;" id="februaryExpenseTotal">{{ $expenseTotals['february'] }}</td>
                <td class="text-end" style="width: 100px;" id="marchExpenseTotal">{{ $expenseTotals['march'] }}</td>
                <td class="text-end" style="width: 100px;" id="aprilExpenseTotal">{{ $expenseTotals['april'] }}</td>
                <td class="text-end" style="width: 100px;" id="mayExpenseTotal">{{ $expenseTotals['may'] }}</td>
                <td class="text-end" style="width: 100px;" id="juneExpenseTotal">{{ $expenseTotals['june'] }}</td>
                <td class="text-end" style="width: 100px;" id="julyExpenseTotal">{{ $expenseTotals['july'] }}</td>
                <td class="text-end" style="width: 100px;" id="augustExpenseTotal">{{ $expenseTotals['august'] }}</td>
                <td class="text-end" style="width: 100px;" id="septemberExpenseTotal">{{ $expenseTotals['september'] }}</td>
                <td class="text-end" style="width: 100px;" id="octoberExpenseTotal">{{ $expenseTotals['october'] }}</td>
                <td class="text-end" style="width: 100px;" id="novemberExpenseTotal">{{ $expenseTotals['november'] }}</td>
                <td class="text-end" style="width: 100px;" id="decemberExpenseTotal">{{ $expenseTotals['december'] }}</td> 
                <td class="text-end" style="width: 100px;" id="ExpenseTotal">{{ $expenseTotals['total'] }}</td>
            </tr>

            <!-- Grand total line -->
            <tr  style="background-color: green; color: white;">
                <td class="text-end" style="width: 100px;">Total</td>
                <td class="text-end" style="width: 100px;" id="januaryTotal">{{ $grandTotals['january'] }}</td>
                <td class="text-end" style="width: 100px;" id="februaryTotal">{{ $grandTotals['february'] }}</td>
                <td class="text-end" style="width: 100px;" id="marchTotal">{{ $grandTotals['march'] }}</td>
                <td class="text-end" style="width: 100px;" id="aprilTotal">{{ $grandTotals['april'] }}</td>
                <td class="text-end" style="width: 100px;" id="mayTotal">{{ $grandTotals['may'] }}</td>
                <td class="text-end" style="width: 100px;" id="juneTotal">{{ $grandTotals['june'] }}</td>
                <td class="text-end" style="width: 100px;" id="julyTotal">{{ $grandTotals['july'] }}</td>
                <td class="text-end" style="width: 100px;" id="augustTotal">{{ $grandTotals['august'] }}</td>
                <td class="text-end" style="width: 100px;" id="septemberTotal">{{ $grandTotals['september'] }}</td>
                <td class="text-end" style="width: 100px;" id="octoberTotal">{{ $grandTotals['october'] }}</td>
                <td class="text-end" style="width: 100px;" id="novemberTotal">{{ $grandTotals['november'] }}</td>
                <td class="text-end" style="width: 100px;" id="decemberTotal">{{ $grandTotals['december'] }}</td> 
                <td class="text-end" style="width: 100px;" id="grandTotal">{{ $grandTotals['total'] }}</td>
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
            // - actualIncomeData
            var actualIncomeData = $("#actualIncomeData").val();
            actualIncomeData = JSON.parse(actualIncomeData);
            
            // - actualExpenseData
            var actualExpenseData = $("#actualExpenseData").val();
            actualExpenseData = JSON.parse(actualExpenseData);

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
            for (let category in actualIncomeData) {
                months.forEach(month => {
                    var amt = actualIncomeData[category][month];
                    // remove commas and convert to float
                    amt = parseFloat(amt.split(',').join(''));
                    incomeTotals[month] += amt;
                    incomeTotal += amt;
                });
            };
            for (let category in actualExpenseData) {
                months.forEach(month => {
                    var amt = actualExpenseData[category][month];
                    // remove commas and convert to float
                    amt = parseFloat(amt.split(',').join(''));
                    expenseTotals[month] += amt;
                    expenseTotal += amt;
                });
            };

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
            
            var total = (incomeTotal + expenseTotal).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            $("#grandTotal").text(total);

            // listener for budget button
            $('#budget').on('click', function(e) {
                e.preventDefault();

                const url = '/accounts/budget';
                window.location.href = url;
            });

            // listener for budget vs actuals button
            $('#budgetactuals').on('click', function(e) {
                e.preventDefault();

                const url = '/accounts/budgetactuals';
                window.location.href = url;
            });
        });

    </script>

    </body>
</html>