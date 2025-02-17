<html>
    <head>
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </head>

    <body>
    <h1>Budget vs Actuals</h1>

    <h2>Year: <span id="year">{{ $year }}</span> </h2>
    <form action="{{ route('budgetactuals', $year ?? session('selected_year')) }}" method="GET">
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
    <button type="button" id="actuals" class="btn btn-primary">Actuals</button>

    <!-- hidden fields -->
    <input type="hidden" id="budgetData"  name="budgetData"  value={{ json_encode($budgetData) }}>
    <!-- <input type="hidden" id="actualIncomeData"  name="actualIncomeData"  value={{ json_encode($actualIncomeData) }}> -->
    <!-- <input type="hidden" id="actualExpenseData"  name="actualExpenseData"  value={{ json_encode($actualExpenseData) }}> -->
    <input type="hidden" id="actualIncomeTotals"  name="actualIncomeTotals"  value={{ json_encode($actualIncomeTotals) }}>
    <input type="hidden" id="actualExpenseTotals"  name="actualExpenseTotals"  value={{ json_encode($actualExpenseTotals) }}>
    <input type="hidden" id="actualGrandTotals"  name="actualGrandTotals"  value={{ json_encode($actualGrandTotals) }}>
    <input type="hidden" id="incomeCategories"  name="incomeCategories"  value={{ json_encode($incomeCategories) }}>
    <input type="hidden" id="expenseCategories"  name="expenseCategories"  value={{ json_encode($expenseCategories) }}>

    <table>
        <!-- Headers -->
        <thead>
            <tr>
                <th style="width: 200px;">Category</th>
                <th id="jan">Jan</th>
                <th id="feb">Feb</th>
                <th id="mar">Mar</th>
                <th id="apr">Apr</th>
                <th id="may">May</th>
                <th id="jun">Jun</th>
                <th id="jul">Jul</th>
                <th id="aug">Aug</th>
                <th id="sep">Sep</th>
                <th id="oct">Oct</th>
                <th id="nov">Nov</th>
                <th id="dec">Dec</th>
                <th id="total">Total</th>
            </tr>
        </thead>
        <tbody>

            <!-- income sub-header -->
            <tr style="background-color: blue; color: white; border-top-width: thick; border-bottom-width: medium;">
                <td id="income" style="font-size: 1.3em;">Income</td>
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
            @foreach($budgetData as $category=>$budgetRecord)
                @if(in_array($category, $incomeCategories))
                    <!-- Budget data -->
                    <tr>
                        <td class="budget category" id="{{$category}}Budget">{{ $category }} bdgt</td>
                        <td class="budget" id="jan{{$category}}Budget">{{ $budgetRecord['january'] }}</td>
                        <td class="budget" id="feb{{$category}}Budget">{{ $budgetRecord['february'] }}</td>
                        <td class="budget" id="mar{{$category}}Budget">{{ $budgetRecord['march'] }}</td>
                        <td class="budget" id="apr{{$category}}Budget">{{ $budgetRecord['april'] }}</td>
                        <td class="budget" id="may{{$category}}Budget">{{ $budgetRecord['may'] }}</td>
                        <td class="budget" id="jun{{$category}}Budget">{{ $budgetRecord['june'] }}</td>
                        <td class="budget" id="jul{{$category}}Budget">{{ $budgetRecord['july'] }}</td>
                        <td class="budget" id="aug{{$category}}Budget">{{ $budgetRecord['august'] }}</td>
                        <td class="budget" id="sep{{$category}}Budget">{{ $budgetRecord['september'] }}</td>
                        <td class="budget" id="oct{{$category}}Budget">{{ $budgetRecord['october'] }}</td>
                        <td class="budget" id="nov{{$category}}Budget">{{ $budgetRecord['november'] }}</td>
                        <td class="budget" id="dec{{$category}}Budget">{{ $budgetRecord['december'] }}</td>
                        <td class="budget" id="totalBudgetIncome">{{ $budgetRecord['total'] }}</td>
                    </tr>
                    <!-- actual data -->
                    <tr>
                        <td class="actual" id="{{$category}}Actual">actual</td>
                        <td class="actual" id="jan{{$category}}Actual">{{ $actualIncomeData[$category]['january'] }}</td>
                        <td class="actual" id="feb{{$category}}Actual">{{ $actualIncomeData[$category]['february'] }}</td>
                        <td class="actual" id="mar{{$category}}Actual">{{ $actualIncomeData[$category]['march'] }}</td>
                        <td class="actual" id="apr{{$category}}Actual">{{ $actualIncomeData[$category]['april'] }}</td>
                        <td class="actual" id="may{{$category}}Actual">{{ $actualIncomeData[$category]['may'] }}</td>
                        <td class="actual" id="jun{{$category}}Actual">{{ $actualIncomeData[$category]['june'] }}</td>
                        <td class="actual" id="jul{{$category}}Actual">{{ $actualIncomeData[$category]['july'] }}</td>
                        <td class="actual" id="aug{{$category}}Actual">{{ $actualIncomeData[$category]['august'] }}</td>
                        <td class="actual" id="sep{{$category}}Actual">{{ $actualIncomeData[$category]['september'] }}</td>
                        <td class="actual" id="oct{{$category}}Actual">{{ $actualIncomeData[$category]['october'] }}</td>
                        <td class="actual" id="nov{{$category}}Actual">{{ $actualIncomeData[$category]['november'] }}</td>
                        <td class="actual" id="dec{{$category}}Actual">{{ $actualIncomeData[$category]['december'] }}</td>
                        <td class="actual" id="totalActualIncome">{{ $actualIncomeData[$category]['total'] }}</td>
                    </tr>
                    <!-- difference -->
                    <tr style="border-bottom-width: thick;">
                        <td class="diff" id="{{$category}}Diff">difference<br>(+: made extra)</td>
                        <td class="diff" id="jan{{$category}}Diff">{{ number_format((float)str_replace(",", "", $actualIncomeData[$category]['january']) - (float)str_replace(",", "", $budgetRecord['january']), 2) }}</td>
                        <td class="diff" id="feb{{$category}}Diff">{{ number_format((float)str_replace(",", "", $actualIncomeData[$category]['february']) - (float)str_replace(",", "", $budgetRecord['february']), 2) }}</td>
                        <td class="diff" id="mar{{$category}}Diff">{{ number_format((float)str_replace(",", "", $actualIncomeData[$category]['march']) - (float)str_replace(",", "", $budgetRecord['march']), 2) }}</td>
                        <td class="diff" id="apr{{$category}}Diff">{{ number_format((float)str_replace(",", "", $actualIncomeData[$category]['april']) - (float)str_replace(",", "", $budgetRecord['april']), 2) }}</td>
                        <td class="diff" id="may{{$category}}Diff">{{ number_format((float)str_replace(",", "", $actualIncomeData[$category]['may']) - (float)str_replace(",", "", $budgetRecord['may']), 2) }}</td>
                        <td class="diff" id="jun{{$category}}Diff">{{ number_format((float)str_replace(",", "", $actualIncomeData[$category]['june']) - (float)str_replace(",", "", $budgetRecord['june']), 2) }}</td>
                        <td class="diff" id="jul{{$category}}Diff">{{ number_format((float)str_replace(",", "", $actualIncomeData[$category]['july']) - (float)str_replace(",", "", $budgetRecord['july']), 2) }}</td>
                        <td class="diff" id="aug{{$category}}Diff">{{ number_format((float)str_replace(",", "", $actualIncomeData[$category]['august']) - (float)str_replace(",", "", $budgetRecord['august']), 2) }}</td>
                        <td class="diff" id="sep{{$category}}Diff">{{ number_format((float)str_replace(",", "", $actualIncomeData[$category]['september']) - (float)str_replace(",", "", $budgetRecord['september']), 2) }}</td>
                        <td class="diff" id="oct{{$category}}Diff">{{ number_format((float)str_replace(",", "", $actualIncomeData[$category]['october']) - (float)str_replace(",", "", $budgetRecord['october']), 2) }}</td>
                        <td class="diff" id="nov{{$category}}Diff">{{ number_format((float)str_replace(",", "", $actualIncomeData[$category]['november']) - (float)str_replace(",", "", $budgetRecord['november']), 2) }}</td>
                        <td class="diff" id="dec{{$category}}Diff">{{ number_format((float)str_replace(",", "", $actualIncomeData[$category]['december']) - (float)str_replace(",", "", $budgetRecord['december']), 2) }}</td>
                        <td class="diff" id="diffIncome">{{ number_format((float)str_replace(",", "", $actualIncomeData[$category]['total']) - (float)str_replace(",", "", $budgetRecord['total']), 2) }}</td>
                    </tr>
                @endif
            @endforeach

            <!-- Income total line -->
            <tr  style="background-color: #0096FF; color: white;">
                <td class="text-end" style="width: 100px;">Budget Income Total</td>
                <td class="text-end" style="width: 100px;" id="januaryBudgetIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="februaryBudgetIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="marchBudgetIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="aprilBudgetIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="mayBudgetIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="juneBudgetIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="julyBudgetIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="augustBudgetIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="septemberBudgetIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="octoberBudgetIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="novemberBudgetIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="decemberBudgetIncomeTotal"></td> 
                <td class="text-end" style="width: 100px;" id="budgetIncomeTotal"></td>
            </tr>

            <!-- Actual Income total line -->
            <tr  style="background-color: #a2e897; color: white;">
                <td class="text-end" style="width: 100px;">Actual Income Total</td>
                <td class="text-end" style="width: 100px;" id="januaryActualIncomeTotal">{{ $actualIncomeTotals['january'] }}</td>
                <td class="text-end" style="width: 100px;" id="februaryActualIncomeTotal">{{ $actualIncomeTotals['february'] }}</td>
                <td class="text-end" style="width: 100px;" id="marchActualIncomeTotal">{{ $actualIncomeTotals['march'] }}</td>
                <td class="text-end" style="width: 100px;" id="aprilActualIncomeTotal">{{ $actualIncomeTotals['april'] }}</td>
                <td class="text-end" style="width: 100px;" id="mayActualIncomeTotal">{{ $actualIncomeTotals['may'] }}</td>
                <td class="text-end" style="width: 100px;" id="juneActualIncomeTotal">{{ $actualIncomeTotals['june'] }}</td>
                <td class="text-end" style="width: 100px;" id="julyActualIncomeTotal">{{ $actualIncomeTotals['july'] }}</td>
                <td class="text-end" style="width: 100px;" id="augustActualIncomeTotal">{{ $actualIncomeTotals['august'] }}</td>
                <td class="text-end" style="width: 100px;" id="septemberActualIncomeTotal">{{ $actualIncomeTotals['september'] }}</td>
                <td class="text-end" style="width: 100px;" id="octoberActualIncomeTotal">{{ $actualIncomeTotals['october'] }}</td>
                <td class="text-end" style="width: 100px;" id="novemberActualIncomeTotal">{{ $actualIncomeTotals['november'] }}</td>
                <td class="text-end" style="width: 100px;" id="decemberActualIncomeTotal">{{ $actualIncomeTotals['december'] }}</td> 
                <td class="text-end" style="width: 100px;" id="actualIncomeTotal">{{ $actualIncomeTotals['total'] }}</td>
            </tr>

            <!-- diff Income total line -->
            <tr class="text-end" style="background-color: goldenrod; color: white;">
                <td class="text-end" style="width: 100px;">Diff Income Total<br>(+: made extra)</td>
                <td class="text-end" style="width: 100px;" id="januaryDiffIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="februaryDiffIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="marchDiffIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="aprilDiffIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="mayDiffIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="juneDiffIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="julyDiffIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="augustDiffIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="septemberDiffIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="octoberDiffIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="novemberDiffIncomeTotal"></td>
                <td class="text-end" style="width: 100px;" id="decemberDiffIncomeTotal"></td> 
                <td class="text-end" style="width: 100px;" id="diffIncomeTotal"></td>
            </tr>

            <!-- expense -->
            <tr style="background-color: blue; color: white; border-top-width: thick; border-bottom-width: medium;">
                <td id="expense" style="font-size: 1.3em;">Expenses</td>
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
            @foreach($budgetData as $category=>$budgetRecord)
                @if(in_array($category, $expenseCategories))
                    <!-- Budget data -->
                    <tr>
                        <td class="budget category">{{ $category }} bdgt</td>
                        <td class="budget" id="jan{{$category}}Budget">{{ $budgetRecord['january'] }}</td>
                        <td class="budget" id="feb{{$category}}Budget">{{ $budgetRecord['february'] }}</td>
                        <td class="budget" id="mar{{$category}}Budget">{{ $budgetRecord['march'] }}</td>
                        <td class="budget" id="apr{{$category}}Budget">{{ $budgetRecord['april'] }}</td>
                        <td class="budget" id="may{{$category}}Budget">{{ $budgetRecord['may'] }}</td>
                        <td class="budget" id="jun{{$category}}Budget">{{ $budgetRecord['june'] }}</td>
                        <td class="budget" id="jul{{$category}}Budget">{{ $budgetRecord['july'] }}</td>
                        <td class="budget" id="aug{{$category}}Budget">{{ $budgetRecord['august'] }}</td>
                        <td class="budget" id="sep{{$category}}Budget">{{ $budgetRecord['september'] }}</td>
                        <td class="budget" id="oct{{$category}}Budget">{{ $budgetRecord['october'] }}</td>
                        <td class="budget" id="nov{{$category}}Budget">{{ $budgetRecord['november'] }}</td>
                        <td class="budget" id="dec{{$category}}Budget">{{ $budgetRecord['december'] }}</td> 
                        <td class="budget" id="totalExpense">{{ $budgetRecord['total'] }}</td>
                    </tr>
                    <!-- actual data -->
                    <tr>
                        <td class="actual">actual</td>
                        <td class="actual" id="jan{{$category}}Actual">{{ $actualExpenseData[$category]['january'] }}</td>
                        <td class="actual" id="feb{{$category}}Actual">{{ $actualExpenseData[$category]['february'] }}</td>
                        <td class="actual" id="mar{{$category}}Actual">{{ $actualExpenseData[$category]['march'] }}</td>
                        <td class="actual" id="apr{{$category}}Actual">{{ $actualExpenseData[$category]['april'] }}</td>
                        <td class="actual" id="may{{$category}}Actual">{{ $actualExpenseData[$category]['may'] }}</td>
                        <td class="actual" id="jun{{$category}}Actual">{{ $actualExpenseData[$category]['june'] }}</td>
                        <td class="actual" id="jul{{$category}}Actual">{{ $actualExpenseData[$category]['july'] }}</td>
                        <td class="actual" id="aug{{$category}}Actual">{{ $actualExpenseData[$category]['august'] }}</td>
                        <td class="actual" id="sep{{$category}}Actual">{{ $actualExpenseData[$category]['september'] }}</td>
                        <td class="actual" id="oct{{$category}}Actual">{{ $actualExpenseData[$category]['october'] }}</td>
                        <td class="actual" id="nov{{$category}}Actual">{{ $actualExpenseData[$category]['november'] }}</td>
                        <td class="actual" id="dec{{$category}}Actual">{{ $actualExpenseData[$category]['december'] }}</td>
                        <td class="actual" id="totalExpense">{{ $actualExpenseData[$category]['total'] }}</td>
                    </tr>
                    <!-- difference -->
                    <!-- NOTE: subtraction is reversed since the budget is a negative number, so + means $ left in the budget, and - means overspent -->
                    <tr style="border-bottom-width: thick;">
                        <td class="diff">difference<br>(-: overspent)</td>
                        <td class="diff" id="jan{{$category}}Diff">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category]['january']) - (float)str_replace(",", "", $budgetRecord['january']), 2) }}</td>
                        <td class="diff" id="feb{{$category}}Diff">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category]['february']) - (float)str_replace(",", "", $budgetRecord['february']), 2) }}</td>
                        <td class="diff" id="mar{{$category}}Diff">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category]['march']) - (float)str_replace(",", "", $budgetRecord['march']), 2) }}</td>
                        <td class="diff" id="apr{{$category}}Diff">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category]['april']) - (float)str_replace(",", "", $budgetRecord['april']), 2) }}</td>
                        <td class="diff" id="may{{$category}}Diff">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category]['may']) - (float)str_replace(",", "", $budgetRecord['may']), 2) }}</td>
                        <td class="diff" id="jun{{$category}}Diff">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category]['june']) - (float)str_replace(",", "", $budgetRecord['june']), 2) }}</td>
                        <td class="diff" id="jul{{$category}}Diff">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category]['july']) - (float)str_replace(",", "", $budgetRecord['july']), 2) }}</td>
                        <td class="diff" id="aug{{$category}}Diff">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category]['august']) - (float)str_replace(",", "", $budgetRecord['august']), 2) }}</td>
                        <td class="diff" id="sep{{$category}}Diff">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category]['september']) - (float)str_replace(",", "", $budgetRecord['september']), 2) }}</td>
                        <td class="diff" id="oct{{$category}}Diff">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category]['october']) - (float)str_replace(",", "", $budgetRecord['october']), 2) }}</td>
                        <td class="diff" id="nov{{$category}}Diff">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category]['november']) - (float)str_replace(",", "", $budgetRecord['november']), 2) }}</td>
                        <td class="diff" id="dec{{$category}}Diff">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category]['december']) - (float)str_replace(",", "", $budgetRecord['december']), 2) }}</td>
                        <td class="diff" id="totalExpense">{{ number_format( (float)str_replace(",", "", $actualExpenseData[$category]['total']) - (float)str_replace(",", "", $budgetRecord['total']), 2) }}</td>
                    </tr>                    
                @endif
            @endforeach

            <!-- Budget Expense total line -->
            <tr  style="background-color: #0096FF; color: white;">
                <td class="text-end" style="width: 100px;">Budget Expense Total</td>
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
            <tr  style="background-color: #a2e897; color: white;">
                <td class="text-end" style="width: 100px;">Actual Expense Total</td>
                <td class="text-end" style="width: 100px;" id="januaryActualExpenseTotal">{{ $actualExpenseTotals['january'] }}</td>
                <td class="text-end" style="width: 100px;" id="februaryActualExpenseTotal">{{ $actualExpenseTotals['february'] }}</td>
                <td class="text-end" style="width: 100px;" id="marchActualExpenseTotal">{{ $actualExpenseTotals['march'] }}</td>
                <td class="text-end" style="width: 100px;" id="aprilActualExpenseTotal">{{ $actualExpenseTotals['april'] }}</td>
                <td class="text-end" style="width: 100px;" id="mayActualExpenseTotal">{{ $actualExpenseTotals['may'] }}</td>
                <td class="text-end" style="width: 100px;" id="juneActualExpenseTotal">{{ $actualExpenseTotals['june'] }}</td>
                <td class="text-end" style="width: 100px;" id="julyActualExpenseTotal">{{ $actualExpenseTotals['july'] }}</td>
                <td class="text-end" style="width: 100px;" id="augustActualExpenseTotal">{{ $actualExpenseTotals['august'] }}</td>
                <td class="text-end" style="width: 100px;" id="septemberActualExpenseTotal">{{ $actualExpenseTotals['september'] }}</td>
                <td class="text-end" style="width: 100px;" id="octoberActualExpenseTotal">{{ $actualExpenseTotals['october'] }}</td>
                <td class="text-end" style="width: 100px;" id="novemberActualExpenseTotal">{{ $actualExpenseTotals['november'] }}</td>
                <td class="text-end" style="width: 100px;" id="decemberActualExpenseTotal">{{ $actualExpenseTotals['december'] }}</td> 
                <td class="text-end" style="width: 100px;" id="actualExpenseTotal">{{ $actualExpenseTotals['total'] }}</td>
            </tr>

            <!-- diff Expense total line -->
            <tr class="text-end" style="background-color: goldenrod; color: white; border-bottom-width: thick;">
                <td class="text-end" style="width: 100px;">Diff Expense Total<br>(-: overspent)</td>
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

            <!-- Budget Grand total line -->
            <tr  style="background-color: blue; color: white; font-weight: bold;">
                <td class="text-end" style="width: 100px;">Budget Grand Total</td>
                <td class="text-end" style="width: 100px;" id="januaryBudgetTotal"></td>
                <td class="text-end" style="width: 100px;" id="februaryBudgetTotal"></td>
                <td class="text-end" style="width: 100px;" id="marchBudgetTotal"></td>
                <td class="text-end" style="width: 100px;" id="aprilBudgetTotal"></td>
                <td class="text-end" style="width: 100px;" id="mayBudgetTotal"></td>
                <td class="text-end" style="width: 100px;" id="juneBudgetTotal"></td>
                <td class="text-end" style="width: 100px;" id="julyBudgetTotal"></td>
                <td class="text-end" style="width: 100px;" id="augustBudgetTotal"></td>
                <td class="text-end" style="width: 100px;" id="septemberBudgetTotal"></td>
                <td class="text-end" style="width: 100px;" id="octoberBudgetTotal"></td>
                <td class="text-end" style="width: 100px;" id="novemberBudgetTotal"></td>
                <td class="text-end" style="width: 100px;" id="decemberBudgetTotal"></td> 
                <td class="text-end" style="width: 100px;" id="grandBudgetTotal"></td>
            </tr>

            <!-- Actual Grand total line -->
            <tr  style="background-color: mediumspringgreen; color: white; font-weight: bold;">
                <td class="text-end" style="width: 100px;">Actual Grand Total</td>
                <td class="text-end" style="width: 100px;" id="januaryActualTotal"></td>
                <td class="text-end" style="width: 100px;" id="februaryActualTotal"></td>
                <td class="text-end" style="width: 100px;" id="marchActualTotal"></td>
                <td class="text-end" style="width: 100px;" id="aprilActualTotal"></td>
                <td class="text-end" style="width: 100px;" id="mayActualTotal"></td>
                <td class="text-end" style="width: 100px;" id="juneActualTotal"></td>
                <td class="text-end" style="width: 100px;" id="julyActualTotal"></td>
                <td class="text-end" style="width: 100px;" id="augustActualTotal"></td>
                <td class="text-end" style="width: 100px;" id="septemberActualTotal"></td>
                <td class="text-end" style="width: 100px;" id="octoberActualTotal"></td>
                <td class="text-end" style="width: 100px;" id="novemberActualTotal"></td>
                <td class="text-end" style="width: 100px;" id="decemberActualTotal"></td> 
                <td class="text-end" style="width: 100px;" id="grandActualTotal"></td>
            </tr>

            <!-- Diff Grand total line -->
            <tr  style="background-color: darkorange; color: white; font-weight: bold;">
                <td class="text-end" style="width: 100px;">Diff Grand Total</td>
                <td class="text-end" style="width: 100px;" id="januaryDiffTotal"></td>
                <td class="text-end" style="width: 100px;" id="februaryDiffTotal"></td>
                <td class="text-end" style="width: 100px;" id="marchDiffTotal"></td>
                <td class="text-end" style="width: 100px;" id="aprilDiffTotal"></td>
                <td class="text-end" style="width: 100px;" id="mayDiffTotal"></td>
                <td class="text-end" style="width: 100px;" id="juneDiffTotal"></td>
                <td class="text-end" style="width: 100px;" id="julyDiffTotal"></td>
                <td class="text-end" style="width: 100px;" id="augustDiffTotal"></td>
                <td class="text-end" style="width: 100px;" id="septemberDiffTotal"></td>
                <td class="text-end" style="width: 100px;" id="octoberDiffTotal"></td>
                <td class="text-end" style="width: 100px;" id="novemberDiffTotal"></td>
                <td class="text-end" style="width: 100px;" id="decemberDiffTotal"></td> 
                <td class="text-end" style="width: 100px;" id="grandDiffTotal"></td>
            </tr>

            
        </tbody>
    </table>

    <script>

            
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        function initArrayWithMonthKeys() {
            return {
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
                'december': 0,
                'total': 0
            };
        }

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

            // - actualIncomeTotals
            var actualIncomeTotals = $("#actualIncomeTotals").val();
            actualIncomeTotals = JSON.parse(actualIncomeTotals);

            // - actualExpenseTotals
            var actualExpenseTotals = $("#actualExpenseTotals").val();
            actualExpenseTotals = JSON.parse(actualExpenseTotals);

            // - incomeCategories
            var incomeCategories = $("#incomeCategories").val();
            incomeCategories = JSON.parse(incomeCategories);

            // - expenseCategories
            var expenseCategories = $("#expenseCategories").val();
            expenseCategories = JSON.parse(expenseCategories);

            // init total vars       
            var budgetIncomeTotals = initArrayWithMonthKeys();
            var budgetExpenseTotals = initArrayWithMonthKeys();
            var diffIncomeTotals = initArrayWithMonthKeys();
            var diffExpenseTotals = initArrayWithMonthKeys();

            // calc income and expense totals for each month

            // budget income/expense totals
            for (const category in budgetData) {
                if(incomeCategories.includes(category)) {
                    months.forEach(month => {
                        var amt = parseFloat(budgetData[category][month].replace(",", ""));
                        budgetIncomeTotals[month] += amt;
                        budgetIncomeTotals['total'] += amt;
                    });
                } else if(expenseCategories.includes(category)) {
                    months.forEach(month => {
                        var amt = parseFloat(budgetData[category][month].replace(",", ""));
                        budgetExpenseTotals[month] += amt;
                        budgetExpenseTotals['total'] += amt;
                        // diffExpenseTotals[month] = amt - actualExpenseTotals[month];
                    });
                }
            };

            // put monthly income, expense, and diff totals on the page
            months.forEach( month => {
                // budget income, expense, total
                var budgetIncomeTotal = budgetIncomeTotals[month].toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                $("#" + month + "BudgetIncomeTotal").text(budgetIncomeTotal);
                var budgetExpenseTotal = budgetExpenseTotals[month].toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                $("#" + month + "BudgetExpenseTotal").text(budgetExpenseTotal);
                var total = (budgetIncomeTotals[month] + budgetExpenseTotals[month]).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                $("#" + month + "BudgetTotal").text(total);

                // actual income, expense, total
                var actualIncomeTotal = actualIncomeTotals[month].toLocaleString(undefined, { minimumFractionDigits: 2, maximumFracctionDigits: 2});
                $("#" + month + "ActualIncomeTotal").text(actualIncomeTotal);
                var actualExpenseTotal = actualExpenseTotals[month].toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                $("#" + month + "ActualExpenseTotal").text(actualExpenseTotal);
                var total = (parseFloat(actualIncomeTotals[month].replaceAll(",", "")) + parseFloat(actualExpenseTotals[month].replaceAll(",", ""))).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                $("#" + month + "ActualTotal").text(total);

                // strip commas from string monthly totals
                var incomeBudget = budgetIncomeTotal.replaceAll(",", "");
                var incomeActual = actualIncomeTotals[month].replaceAll(",", "");
                var expenseBudget = budgetExpenseTotal.replaceAll(",", "");
                var expenseActual = actualExpenseTotals[month].replaceAll(",", "");

                // convert to 2 decimal floats
                incomeBudget = parseFloat(parseFloat(incomeBudget).toFixed(2));
                incomeActual = parseFloat(parseFloat(incomeActual).toFixed(2));
                expenseBudget = parseFloat(parseFloat(expenseBudget).toFixed(2));
                expenseActual = parseFloat(parseFloat(expenseActual).toFixed(2));

                // get differences
                var diffIncomeTotal = (incomeActual - incomeBudget);
                var diffExpenseTotal = (expenseActual - expenseBudget);
                var diffTotal = (diffIncomeTotal + diffExpenseTotal).toFixed(2);
                // change diffIncomeTotal and diffExpenseTotal to strings
                diffIncomeTotal = diffIncomeTotal.toFixed(2);
                diffExpenseTotal = diffExpenseTotal.toFixed(2);

                // put differences on page
                $("#" + month + "DiffIncomeTotal").text(diffIncomeTotal);
                $("#" + month + "DiffExpenseTotal").text(diffExpenseTotal);
                $("#" + month + "DiffTotal").text(diffTotal);
            });

            // put totals on the page

            // total budget income
            var total = budgetIncomeTotals['total'].toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            $("#budgetIncomeTotal").text(total);
            
            // total budget expense
            var total = budgetExpenseTotals['total'].toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            $("#budgetExpenseTotal").text(total);

            // total income difference
            var actual = actualIncomeTotals['total'].replaceAll(",", "");
            actual = parseFloat(actual).toFixed(2);
            var total = (actual - budgetIncomeTotals['total']).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            $("#diffIncomeTotal").text(total);

            // total expense difference
            var actual = actualExpenseTotals['total'].replaceAll(",", "");
            actual = parseFloat(actual).toFixed(2);
            var total = (actual - budgetExpenseTotals['total']).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            $("#diffExpenseTotal").text(total);

            // total budget I+E
            var grandBudgetTotalNum = budgetIncomeTotals['total'] + budgetExpenseTotals['total'];
            var grandBudgetTotal = grandBudgetTotalNum.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            $("#grandBudgetTotal").text(grandBudgetTotal);

            // total actual I+E
            var actual = parseFloat(actualIncomeTotals["total"].replaceAll(",", ""));
            var expense = parseFloat(actualExpenseTotals["total"].replaceAll(",", ""));
            var grandActualTotalNum = actual + expense;
            var grandActualTotal = grandActualTotalNum.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            $("#grandActualTotal").text(grandActualTotal);

            // total diff I+E
            var grandDiff = grandActualTotalNum - grandBudgetTotalNum;
            grandDiff = grandDiff.toFixed(2);
            $("#grandDiffTotal").text(grandDiff);

            // add ytd column
            
            // get current month
            const date = new Date();
            const monthIdx = date.getMonth();
            const month = months[monthIdx];
            const monthAbbr = (month.toLowerCase()).substring(0, 3);
            
            // skip this if it's december
            if(monthAbbr != "dec") {
                // add the new column header YTD
                $("#" + monthAbbr).after('<th>YTD</th>');
                // add another <tr> to the Income row
                $("#income").after('<tr></tr>');
                // add another <tr> to the Expense row
                $("#expense").after('<tr></tr>');

                // add another <tr> to each income row (budget, actual, and diff)
                incomeCategories.forEach(category => {
                    console.log("Income cat: ", category);
                    ['Budget', 'Actual', 'Diff'].forEach( typeOfData => {
                        $("#" + monthAbbr + category + typeOfData).after('<td id="ytd'+category+'Budget">new' + category + typeOfData + '</td>');
                    });
                });
                
                // add another <tr> to each expense row (budget, actual, and diff)
                expenseCategories.forEach(category => {
                    console.log("Expense cat: ", category);
                    ['Budget', 'Actual', 'Diff'].forEach( typeOfData => {
                        $("#" + monthAbbr + category + typeOfData).after('<td id="ytd'+category+'Budget">new' + category + typeOfData + '</td>');
                    });
                });
                // left off here
                // need to add td element for subtotals and totals
                // need to fill in with real data



            }   // skip YTD column for december



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