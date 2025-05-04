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
        <h1>Monthly transactions</h1> 

        <p id="errorMsg"></p>
        <!-- table -->
        <table id="editMonthliesTable">

            <!-- table headers -->
            <thead>
                <tr>
                    <th style="display: none; width: 100px; word-break: break-word;">id</th>
                    <th style="width: 70px; word-break: break-word;">reg date</th>
                    <th style="width: 100px; word-break: break-word;">date sched or done</th>
                    <th style="width: 100px; word-break: break-word;">status</th>
                    <!-- <th style="width: 100px; word-break: break-word;">clear_date</th> -->
                    <th style="width: 100px; word-break: break-word;">account</th>
                    <!-- <th style="display: none;">id</th> -->
                    <th style="width: 100px; word-break: break-word;">toFrom</th>
                    <th style="width: 100px; word-break: break-word;">amount</th>
                    <th style="width: 100px; word-break: break-word;">category</th>
                    <th style="width: 100px; word-break: break-word;">bucket</th>
                    <th style="width: 100px; word-break: break-word;">notes</th>
                    <th style="width: 100px; word-break: break-word;">comments</th>
                    <!-- <th style="width: 100px; word-break: break-word;">method</th> -->
                    <!-- <th style="width: 100px; word-break: break-word;">tracking</th> -->
                    <!-- <th style="width: 100px; word-break: break-word;">Edit/Save</th>
                    <th style="width: 100px; word-break: break-word;">Split</th>
                    <th style="width: 100px; word-break: break-word;">Delete</th> -->
                    <!-- <th style="width: 100px; word-break: break-word;">stmtDate</th> -->
                    <!-- <th style="width: 100px; word-break: break-word;">amtMike</th> -->
                    <!-- <th style="width: 100px; word-break: break-word;">amtMaura</th> -->
                    <!-- <th style="width: 100px; word-break: break-word;">total_amt</th>
                    <th style="width: 100px; word-break: break-word;">total_key</th>
                    <th style="width: 100px; word-break: break-word;">split_total</th> -->
                </tr>
            </thead>

            <tbody>
                <!-- transactions just uploaded -->
                @foreach($monthlies as $monthly)
                    <tr data-id={{ $monthly->name }}>
                        <!-- <td class="newtransaction">{{ $newTransaction["id"] ?? NULL }}</td> -->
                        <td class="date" style="text-align: center;">{{ $monthly->dateOfMonth ?? NULL  }}</td>
                        <td class="transDate">{{ $monthly->trans_date ?? NULL }}</td>
                        <td class="status">{{ $monthly->status ?? NULL }}</td>
                        <td class="account">{{ $monthly->account ?? NULL  }}</td>
                        <!-- <td style="display: none;" class="accountId">{{ $newTransaction["accountId"] ?? "id"  }}</td> -->
                        <td class="toFrom">{{ $monthly->toFrom ?? NULL  }}</td>
                        <td class="amount" style="text-align: right;">{{ $monthly->amount ?? NULL  }}</td>
                        <td class="category">{{ $monthly->category ?? NULL  }}</td>
                        <td class="bucket">{{ $monthly->bucket ?? NULL  }}</td>
                        <td class="notes">{{ $monthly->notes ?? NULL  }}</td>
                        <td class="comments">{{ $monthly->comments ?? NULL  }}</td>
                        <!-- <td class="method"></td> -->
                        <!-- <td class="tracking"></td> -->
                        <!-- once this ("edit") is clicked, change to save.  Once saved, change back to edit -->
                        <!-- <td>
                            <button class="btn btn-primary editTransaction" data-id=id>Edit</button>
                        </td>
                        <td>
                            <button class="btn btn-warning splitTransaction" data-id=id>Split</button>
                        </td>                       
                        <td>
                            <button class="btn btn-danger deleteTransaction" data-id=id>Delete</button>
                        </td> -->
                        <!-- <td class="stmtDate"></td> -->
                        <!-- <td class="amtMike" style="text-align: right;"></td> -->
                        <!-- <td class="amtMaura" style="text-align: right;"></td> -->
                        <!-- <td class="total_amt" style="text-align: right;"></td> -->
                        <!-- <td class="total_key"></td> -->
                        <!-- <td class="split_total" style="text-align: right;"></td> -->
                        <!-- <td class="lastBalanced"></td> -->
                        <!-- <td class="spent" style="text-align: right;">

                        </td> -->
                        <!-- <td class="ytmBudget" style="text-align: right;">

                        </td>
                        <td class="yearBudget" style="text-align: right;">
                                    
                        </td> -->
                    </tr>
                @endforeach

            </tbody>
        </table>
        
        
        <script>
            
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            


        </script>
    </body>

</html>