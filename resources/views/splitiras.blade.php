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

        <div>
            <h2>IRA Account Split Results</h2>
            <h1>REMEMBER TO SUM accounts so it's easy to check for typos, missing accounts, etc</h1>
            <form action="{{ route('addwfparts') }}"  method="POST">
                @csrf

                @php 
                    $mikes = explode(",", $mikes);
                    $mauras = explode(",", $mauras);
                @endphp

                <h3>Roth IRA Accounts</h3>
                @foreach($rothAccounts as $index => $account)
                    <div>
                        Roth IRA Balance:
                        {{ $account }}
                        <input class="roth" type="number" name="Roth{{ $index }}" step="0.01">
                        @if(in_array( substr($account, 0, 4), $mikes)) 
                            <span class="mikes" style="color: lightseagreen; font-weight:bold;"> Mike</span>
                            @elseif(in_array( substr($account, 0, 4), $mauras)) 
                            <span class="mauras" style="color: blue; font-weight:bold;"> Maura</span>
                        @endif

                        <!-- <at>if(in_array($account[1], json_decode($mikes)))
                        <at>endif -->
                    </div>
                @endforeach
                <input hidden class="numberRoth" type="number" name="numberRoth" value={{ count($rothAccounts) }}>

                <h3>Traditional IRA Accounts</h3>
                @foreach($tradAccounts as $index => $account)
                    <div>
                        Traditional IRA Balance:
                        {{ $account }}
                        <input class="trad" type="number" name="Trad{{ $index }}" step="0.01">
                        @if(in_array( substr($account, 0, 4), $mikes)) 
                            <span class="mikes" style="color: lightseagreen; font-weight:bold;"> Mike</span>
                            @elseif(in_array( substr($account, 0, 4), $mauras)) 
                            <span class="mauras" style="color: blue; font-weight:bold;"> Maura</span>
                        @endif
                    </div>
                @endforeach
                <input hidden class="numberTrad" type="number" name="numberTrad" value={{ count($tradAccounts) }}>

                <h3>Inheritance Account</h3>
                <div>
                    Inheritence Account Balance:
                    {{ $inhAccount }}
                    <input class="inh" type="number" name="Inh" step="0.01">
                    <span class="mauras" style="color: blue; font-weight:bold;"> Maura</span>
                </div>

                <h3>Non-IRA Account</h3>
                <div>
                    Non-IRA Account:
                    {{ $investmentAcct }}
                    <input class="wfinv" type="number" name="wfInv" step="0.01">
                </div>

                <!-- totals by Mike/Maura -->
                <h3 style="color: lightseagreen;">Mike's IRAs</h3>
                <div>
                    Total of Mike's IRAs (for error checking):
                    <input class="totmikes" id="mikesSubTot" type="number" name="totmikes" step="0.01">
                     Calc Subtot: <span type="number" id="mikeiracalctotal" style="color: green;"></span>
                    <span class="equal" id="equalMike"> ()</span>
                </div>
                
                <h3 style="color: blue;">Maura's IRAs</h3>
                <div>
                    Total of Maura's IRAs (for error checking):
                    <input class="totmauras" id="maurasSubTot" type="number" name="totmauras" step="0.01">
                     Calc Subtot: <span type="number" id="maurairacalctotal" style="color: blue;"></span>
                    <span class="equal" id="equalMaura"> ()</span>
                </div>

                <h3>Total WF</h3>
                <div>
                    Total Investments (for error checking):
                    <input class="wfTot" type="number" name="wfTot" step="0.01">
                </div>

                <button type="submit" class="btn btn-primary">Save WF splits</button>
            </form>
        </div>


        <script>

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $(document).ready(function() {

                function calcIraMMTotals() {

                    // calc mike and maura ira totals
                    var mikeiratotal = 0;
                    var maurairatotal = 0;
                    var total = 0;

                    $('.roth').each(function(index, element) {
                        // Work with individual element
                        // const name = $(element).attr('name');
                        // console.log("Index: " + index + ";  name: " + name + "; next class: " + $(element).next().attr('class'));
                        const nextclass = $(element).next().attr('class');
                        if(nextclass == 'mikes') mikeiratotal += Number($(element).val());
                        else if(nextclass == 'mauras') maurairatotal += Number($(element).val());
                        total += Number($(element).val());
                    });
                    $('.trad').each(function(index, element) {
                        const nextclass = $(element).next().attr('class');
                        if(nextclass == 'mikes') mikeiratotal += Number($(element).val());
                        else if(nextclass == 'mauras') maurairatotal += Number($(element).val());
                        total += Number($(element).val());
                    });
                    $('.inh').each(function(index, element) {
                        const nextclass = $(element).next().attr('class');
                        if(nextclass == 'mikes') mikeiratotal += Number($(element).val());
                        else if(nextclass == 'mauras') maurairatotal += Number($(element).val());
                        total += Number($(element).val());
                    });
                    $('.wfinv').each(function(index, element) {
                        total += Number($(element).val());
                    });

                    // round to 2 decimal places
                    mikeiratotal = mikeiratotal.toFixed(2);
                    maurairatotal = maurairatotal.toFixed(2);
                    total = total.toFixed(2);

                    $('#mikesSubTot').val(
                        Number( $('#mikesSubTot').val()).toFixed(2)
                    );
                    $('#maurasSubTot').val(
                        Number( $('#maurasSubTot').val()).toFixed(2)
                    );

                    // check to see if totals match; result on page
                    if(mikeiratotal != $('#mikesSubTot').val()) $('#equalMike').text(' (NOT EQUAL)').css('color', 'red');
                    else $('#equalMike').text(' (equal)').css('color', 'green');
                    
                    if(maurairatotal != $('#maurasSubTot').val()) $('#equalMaura').text(' (NOT EQUAL)').css('color', 'red');
                    else $('#equalMaura').text(' (equal)').css('color', 'green');

                    // put totals on page:
                    $('#mikeiracalctotal').text(mikeiratotal);
                    $('#maurairacalctotal').text(maurairatotal);
                    $('.wfTot').val(total);
                };

                calcIraMMTotals();

                $('input').on('change', function(e) {
                    e.preventDefault();

                    // if Mike or Maura account, update sub total & check if subtotals match,
                    //  or if Mike/Maura subtotal, check if subtotals match
                    //  or if wf inv changed, so total gets updated
                    const nextclass = $(this).next().attr('class');     // mike/maura in next element
                    if(['mikes', 'mauras'].includes(nextclass)          // if mike or maura account
                        || ['totmikes', 'totmauras'].includes($(this).attr('class'))  // sub total changed
                        || $(this).attr('class') == 'wfinv') {          // inv changed (so total gets updated)
                            calcIraMMTotals();
                    }

                });
            });


        </script>        
    </body>
</html>
