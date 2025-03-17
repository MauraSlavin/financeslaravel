<html>
    <head>
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script> -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </head>

    <body>
        <h1>Trips - Cost to use car</h1>
        <p style="color:red; margin-left:20px;"> NOTE: Make sure 
            <br> --- <u>Maintenance</u> (tracking is the car, notes begins with "maint") 
            <br> --- <u>Tolls</u> (can upload - "Upload Tolls" below - from EZPass download - beware of duplicate tolls records)
            <br> --- and <u>Insurance</u> (if there's been a recent car insurance payment)
            <br> are up to date.</p>
        <!-- data needed to calc use of a car -->
        <form action="{{ route('recordTrip') }}" method="POST">
        @csrf

            <div class="form-row">
                <label class="tripLabel" for="tripName">Trip name (25 char max):</label>
                <br>
                <input class="form-control tripInput" type="text" required id="tripName" name="tripName" maxlength="25">
            </div>
            
            <div class="form-row">
                <label class="tripLabel" for="tripBegin">Trip Began on:</label>
                <input class="tripInput" type="date" id="tripBegin" name="tripBegin" class="form-control" required>
            </div>
            
            <div class="form-row">
                <label class="tripLabel" for="tripEnd">Trip Ended on:</label>
                <input class="tripInput" type="date" id="tripEnd" name="tripEnd" class="form-control" required>
            </div>

            <div class="form-row">
                <label class="tripLabel" for="tripWho">Who used the car:</label>
                <select name="tripWho" class="form-control tripInput" required>
                    <option value="">Who used the car</option>
                    <option value="Mike" {{ old('tripWho') == 'Mike' ? 'selected' : '' }}>Mike</option>
                    <option value="Maura" {{ old('tripWho') == 'Maura' ? 'selected' : '' }}>Maura</option>
                    <option value="both" {{ old('tripWho') == 'both' ? 'selected' : '' }}>both</option>
                </select>
            </div>

            <div class="form-row">
                <label class="tripLabel" for="tripCar">Which car was used:</label>
                <select name="tripCar" class="form-control tripInput" required>
                    <option value="">Which car was used</option>
                    <option value="Bolt" {{ old('tripCar') == 'Bolt' ? 'selected' : '' }}>Bolt</option>
                    <option value="CRZ" {{ old('tripCar') == 'CRZ' ? 'selected' : '' }}>CRZ</option>
                </select>
            </div>

            <div class="form-row">
                <label class="tripLabel" for="tripmiles">Est number of miles driven:</label>
                <br>
                <input class="tripInput" type="number" id="tripmiles" name="tripmiles" class="form-control" required>
            </div>

            <div class="form-row d-flex align-items-center" style="margin-bottom:0;">
                <label class="tripLabel mr-auto" for="tripTolls" style="width: 275px;">Click button to upload & tally tolls</label>
                <button type="button" class="btn btn-success tallyTollsButton">Tally Tolls</button>
                <button type="button" class="btn btn-success uploadTollsButton">Upload Tolls</button>
            </div>
            <div class="form-row" style="margin-top:0;">
                <input class="form-control tripInput" type="text" id="tripTolls" name="tripTolls" disabled>
            </div>

            <button type="submit" class="btn btn-success processtrip">Process Trip</button>
        </form>

        <script>

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $(document).ready(function() {

                // Upload Tolls button clicked
                $('.uploadTollsButton').on('click', function(e) {
                    e.preventDefault();

                    // upload any new tolls in uploadFiles/tolls.csv file
                    $.ajax({
                        url: '/accounts/uploadtolls',
                        type: 'GET',
                        dataType: 'json',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            console.log("response: ", response);
                            alert("Tolls successfully written to tolls table.");
                        },
                        error: function(xhr, status, error) {
                            var errorMsg = "Error writing tolls to tolls table";
                            console.log(errorMsg, error);
                            alert(errorMsg, error);
                        }
                    });

                }); // end of listener for uploadTollsButton


                // Tally Tolls button clicked
                $('.tallyTollsButton').on('click', function(e) {
                    e.preventDefault();

                    // get name of trip from the page
                    var trip = $('#tripName').val();

                    // if it's blank, ask user to enter a trip name
                    if(trip == undefined || trip == '') {
                        alert("Please enter a trip name.");
                    } else {

                        // tally tolls for this trip & put on page
                        $.ajax({
                            url: '/accounts/tallytolls',
                            type: 'POST',
                            contentType: 'application/json',
                            dataType: 'json',
                            data: JSON.stringify({
                                _token: "{{ csrf_token() }}",
                                trip: trip
                            }),
                            success: function(response) {
                                // write tally to page
                                $("#tripTolls").val(response['tolls']);
                            },
                            error: function(xhr, status, error) {
                                var errorMsg = "Error tallying tolls.";
                                console.log(errorMsg, error);
                                alert(errorMsg, error);
                            }
                        });
                    }

                }); // end of listener for tallyTollsButton

            });

        </script>

    </body>
</html>