<html>
    <head>
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <meta name="csrf-token" content="{{ csrf_token() }}">
    </head>

    <body id="moveBuckets">

        <!-- include common functions, if needed -->
        <!-- <script src="{{ asset('js/commonFunctions.js') }}"></script> -->


        <!-- headers -->
        <h1>Move Funds Between Buckets</h1>

        <!-- fields for a new transaction -->
        <form action="{{ route('moveFundsBetweenBuckets') }}" method="POST">
            @csrf
            <div class="form-row">
                <label class="fromBucketaLbel" for="fromBucket">From Bucket:</label>
                <br>
                <input class="fromBucketInput" type="text" id="fromBucket" name="fromBucket" class="form-control" required>
            </div>
            
            <div class="form-row">
                <label class="toBucketLabel" for="toBucket">To Bucket:</label>
                <br>
                <input class="toBucketInput" type="text" id="toBucket" name="toBucket" class="form-control" required>
            </div>
            
            <div class="form-row">
                <label class="moveBucketLabel" for="moveBucketAmount">Amount:</label>
                <br>
                <input class="moveBucketInput" type="amount" id="moveBucketAmount" name="moveBucketAmount" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary">Move</button>
        </form>

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