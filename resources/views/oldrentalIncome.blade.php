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
            <h2>Monthly Rental Income</h2>
            <form action="{{ route('saverentalincome') }}"  method="POST">
                @csrf

                <p>type: {{ gettype($monthlyRentalIncome) }}</p>
                <p>{{ json_encode($monthlyRentalIncome) }}</p>

                <h3>Monthly Rental Income</h3>
                @foreach($monthlyRentalIncome as $tenant => $rentByYearMonth)
                    <div>
                        Tenant: {{ $tenant }}
                        @foreach($rentByYearMonth as $year => $rentByMonth)
                            Year: {{ $year }}
                            @foreach($rentByMonth as $month => $rent)
                                Month: {{ $month }} <input class="rent" type="number" name="Rent{{ $tenant . '-' . $year . '-' . $month }}">
                            @endforeach 
                        @endforeach

                    </div>
                @endforeach

                <button type="submit" class="btn btn-primary">Save Rental Income</button>
            </form>
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
