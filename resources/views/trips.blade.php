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
            <br> --- <u>Maintenance</u> (tracking is the car, notes begins with "maint"),
            <br> --- <u>Tolls</u> (can upload - "Upload Tolls" below - from EZPass download - beware of duplicate tolls records),
            <br> --- <u>Insurance</u> (if there's been a recent car insurance payment),
            <br> --- <u>Mileage</u> has recently been updated in carcostdetails (add input for this to this page), and
            <br> --- <u>gas</u> or <u>charging</u> purchased en route are up to date in the transactions table,
            <br> --- and (for gas car) other <u>recent gas purchases</u> are recorded.
            <br>
            <br> When done do this in MySQL Workbench and copy results to Trips file in Google Drive:
            <br> --- select who, begin, end, trip, car, totalCost, tolls, mileage, sharePurchase, shareIns, shareMaint, gallonsKwHused, gasChargingDollars, other from trips where tripName = ...;
        </p>
        <p style="color:red; margin-left:20px; white-space: pre-wrap;">{{ $errMsg ?? '' }}</p>
        <p style="margin-left:20px;">* Indicates a required field.</p>

        <!-- data needed to calc use of a car -->
        <form action="{{ route('recordTrip') }}" method="POST">
        @csrf

            <div class="form-row">
                <label class="tripLabel" for="tripName">Trip name (16 char max - date will be appended): *
                    <br>
                    <span style="font-size: 10px; font-weight: normal; margin: 0;">Note: if multiple dates appear, delete all dates & tab through to "Trip Ended on:"</span>
                </label>
                <br>
                <input class="form-control tripInput" type="text" required id="tripName" name="tripName" maxlength="16">
                <div style="margin-top: 5px;">
                    <button type="button" class="btn btn-success uploadTollsButton">Upload Tolls</button>
                        <button type="button" class="btn btn-success tallyTollsButton">Tally Tolls</button>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group row">

                    <div class="col-md-4">
                        <label class="tripLabel" for="tripBegin">Trip Began on: *</label>
                        <input class="tripInput form-control" type="date" id="tripBegin" name="tripBegin" required>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="tripLabel" for="tripEnd">Trip Ended on: *</label>
                        <input class="tripInput form-control" type="date" id="tripEnd" name="tripEnd" required>
                    </div>

                    <div class="col-md-4">
                        <label class="tripLabel" for="tripWho">Who used the car: *</label>
                        <select name="tripWho" id="tripWho" class="form-control tripInput" required>
                            <option value="">Who used the car</option>
                            <option value="Mike" {{ old('tripWho') == 'Mike' ? 'selected' : '' }}>Mike</option>
                            <option value="Maura" {{ old('tripWho') == 'Maura' ? 'selected' : '' }}>Maura</option>
                            <option value="both" {{ old('tripWho') == 'both' ? 'selected' : '' }}>both</option>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <scan style="color:red; font-size: 12px; font-weight: bold;" id="dateErr"></scan>
                    </div>

                </div>
            </div>    

            <div class="form-row">
                <div class="form-group row">
                    <div class="col-md-4">
                        <label class="tripLabel" for="tripCar">Which car was used: *</label>
                        <select name="tripCar" id="tripCar" class="form-control tripInput" required>
                            <option value="" disabled selected hidden>Which car was used</option>
                            @foreach($carInfo as $car)
                                <option value="{{ $car['car'] }}"  {{ old('tripCar') == $car["car"] ? 'selected' : '' }}>{{ $car['car'] }}</option>
                            @endforeach                            
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="tripLabel" for="tripOdom">Odometer reading for that car:</label>
                        <input type="number" 
                            class="tripInput form-control" 
                            id="tripOdom" 
                            name="tripOdom">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="tripLabel" for="tripOdomDate">Date of Odometer Reading:</label>
                        <input type="date" 
                            class="tripInput form-control" 
                            id="tripOdomDate" 
                            name="tripOdomDate">
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group row">

                <div class="col-md-6">
                    <label class="tripLabel" for="tripmiles">Est number of miles driven: *</label>
                    <br>
                    <input class="tripInput form-control" type="number" id="tripmiles" name="tripmiles" required>
                </div>
                
                <div class="col-md-6">
                    <label class="tripLabel" for="tripTolls">Tolls tallied: * (via Tally Tolls button)</label>
                    <br>
                    <input class="tripInput form-control" type="number" id="tripTolls" name="tripTolls" disabled>
                </div>
            </div>


            <button type="submit" class="btn btn-success processtrip" disabled>Process Trip</button>
        </form>

        <script>

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $(document).ready(function() {

                // data passed in
                var carInfo = {{ Js::from($carInfo) }};
                var tripNames = {{ Js::from($tripNames) }};

                // info for car entered (or default car for driver entered)
                var car = null;
                var thisCarInfo = [];

                // enable "Process Trip" when all fields completed.
                var processTripArray = {
                    "name": false,
                    "begin": false,
                    "end": false,
                    "who": false,
                    "car": false,
                    "miles": false,
                    "tolls": false
                }
                var areAllTrue = Object.values(processTripArray).every(value => value === true);

                
                // note that name was entered (to determine if Process Trip button should be enabled)
                $('#tripName').on('blur', function(e) {
                    processTripArray.name = true;
                    // console.log("1 processTripArray:", processTripArray);
                    areAllTrue = Object.values(processTripArray).every(value => value === true);
                    if(areAllTrue) $(".processTrip").prop("disabled", false);
                });


                // When trip began on is entered, 
                // fill in Trip Ended on with the same date (if not entered, yet)
                $('#tripBegin').on('blur', function(e) {
                    e.preventDefault();

                    // if tripEnd has not been entered, make it the same as tripBegin
                    if($('#tripEnd').val() == '') {
                        $('#tripEnd').val($('#tripBegin').val());
                        processTripArray.end = true;
                    }

                    // if date of odometer reading not set, make this the default
                    if($('#tripOdomDate').val() == '') {
                        $('#tripOdomDate').val($('#tripBegin').val());
                    }

                    // append tripBegin to tripName (use last 2 of year - drop '20'), if not already there
                    var tripName = $('#tripName').val();
                    var tripBegin = $('#tripBegin').val();  // yyyy-mm-dd format
                    // format tripBegin to mm-dd-yy
                    tripBegin = tripBegin.substr(5, 5) + '-' + tripBegin.substr(2, 2);

                    // only add tripBegin date if it's not already there.
                    if(tripName.slice(-8) != tripBegin) {
                        tripName += ' ' + tripBegin;
                        $('#tripName').val(tripName);
                    }

                    // Is tripName unique
                    if(tripNames.includes($('#tripName').val())) {
                        alert("This trip name is already being used.  Change it.");
                        processTripArray.name = false;
                        // if "Process Trip" was enabled, it needs to be disabled
                        if(areAllTrue) $(".processTrip").prop("disabled", true);
                    } else {

                        // if tripEnd already entered, make sure begin is before end
                        if($('#tripEnd').val()) {
                            if($('#tripEnd').val() < $('#tripBegin').val()) {
                                $("#dateErr").html("Trip must start before it ends.");
                            } else {
                                $("#dateErr").html(" ");
                            }
                        }
                        
                        processTripArray.begin = true;
                        // console.log("2 processTripArray:", processTripArray);
                        areAllTrue = Object.values(processTripArray).every(value => value === true);
                        if(areAllTrue) $(".processTrip").prop("disabled", false);
                    }
                }); // end tripBegin blurred


                // When trip end entered, make sure it's = or after begin
                $('#tripEnd').on('change', function(e) {
                    e.preventDefault();

                    // check to see if begin date is before or equal end date
                    if($('#tripBegin').val()) {
                        // console.log("\n(end) checking dates...");
    
                        // console.log("begin: " + $('#tripBegin').val());
                        // console.log("end: " + $('#tripEnd').val());
                        // console.log("compare: " + ($('#tripEnd').val() < $('#tripBegin').val()) );
                        if($('#tripEnd').val() < $('#tripBegin').val()) {
                            $("#dateErr").html("Trip must start before it ends.");
                        } else {
                            $("#dateErr").html(" ");
                        }
                    }

                    // mark this as entered, even if there's an error.
                    //      if fixed in #tripBegin, this won't be marked as filled.
                    //      user's responsibility...
                    processTripArray.end = true;
                    // console.log("3 processTripArray:", processTripArray);
                    areAllTrue = Object.values(processTripArray).every(value => value === true);
                    if(areAllTrue) $(".processTrip").prop("disabled", false);

                }); // end tripEnd blurred


                // default "car" when "who" is entered
                $('#tripWho').on('change', function(e) {
                    // get the driver entered
                    const driver = $('#tripWho').val();

                    if(driver != 'both') {
                        // get the info for the car this driver usually drives
                        thisCarInfo = carInfo.find(thisCar => thisCar.Driver === driver);

                        // set the default car for this driver
                        $('#tripCar').val(thisCarInfo.car);

                        // car is filled
                        processTripArray.car = true;
                    }

                    // driver is filled
                    processTripArray.who = true;

                    // are all the fields filled?
                    // console.log("4 processTripArray:", processTripArray);
                    areAllTrue = Object.values(processTripArray).every(value => value === true);
                    if(areAllTrue) $(".processTrip").prop("disabled", false);
                });


                // if the car was changed
                $('#tripCar').on('change', function(e) {
                    car = $('#tripCar').val();
                    thisCarInfo = carInfo.find(thisCar => thisCar.car === car);

                    // car is filled
                    processTripArray.car = true;

                    // are all the fields filled?
                    // console.log("7 processTripArray:", processTripArray);
                    areAllTrue = Object.values(processTripArray).every(value => value === true);
                    if(areAllTrue) $(".processTrip").prop("disabled", false);
                });


                $('#tripmiles').on('blur', function(e) {
                    // get tripMiles entered
                    var tripMiles = parseInt($('#tripmiles').val());
                    
                    var keepTripMiles;
                    // if less than 30, may not need to do this
                    if( tripMiles <= 30) {
                        var keepTripMiles = confirm("Don't need to do this for short trips (< 1/2 hour or so).  Do you want to continue?");
                        if(!keepTripMiles) {
                            $("#tripmiles").val('');
                            processTripArray.miles = false;
                            // if "Process Trip" was enabled, it needs to be disabled
                            if(areAllTrue) $(".processTrip").prop("disabled", true);
                            return;
                        }
                    }

                    // make sure > 150 is not a mistake
                    if( tripMiles >= 150) {
                        keepTripMiles = confirm(tripMiles + " is a long trip. Is this correct?");
                        if(!keepTripMiles) {
                            $("#tripmiles").val('');
                            processTripArray.miles = false;
                            // if "Process Trip" was enabled, it needs to be disabled
                            if(areAllTrue) $(".processTrip").prop("disabled", true);
                            return;
                        }
                    }

                    // if ok, continue
                    processTripArray.miles = true;
                    // console.log("5 processTripArray:", processTripArray);
                    areAllTrue = Object.values(processTripArray).every(value => value === true);
                    if(areAllTrue) $(".processTrip").prop("disabled", false);
                });


                // Is Odometer reading more than last reading?
                $('#tripOdom').on('change', function(e) {
                    var odomMilesEntered = parseInt($('#tripOdom').val());
                    if( odomMilesEntered < thisCarInfo.Mileage) alert("Last recorded odometer reading was " + thisCarInfo.Mileage + ".  Enter a reading more than that.");
                });


                // Is Odometer date after last reading?
                $('#tripOdomDate').on('blur', function(e) {
                    var odomDateEntered = $('#tripOdomDate').val(); // yyyy-mm-dd
                    // reformat to yymmdd
                    odomDateEntered = odomDateEntered.substr(2, 2) + odomDateEntered.substr(5, 2) + odomDateEntered.substr(8,2);
                    if( odomDateEntered < thisCarInfo.OdomDate) alert("Last recorded odometer reading was on " + thisCarInfo.OdomDate + ".  Enter a reading after that.");
                });


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
                        alert("Please enter a trip name before Tallying Tolls.");
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
                                var tollMsg = '';
                                var tollRcds = JSON.parse(response['tollRcds']);
                                tollRcds.forEach(rcd => {
                                    tollMsg += "When: " + rcd[0] + ' ' + rcd[1] + ';  Where: ' + rcd[2] + '  ' + rcd[3] + ';  $' + rcd[4] + "\n"; 
                                });
                                var tollsOk = confirm("Do these tolls look correct?\n\n" + tollMsg);
                                if(tollsOk) $("#tripTolls").val(response['tolls']);

                                processTripArray.tolls = true;    
                                // console.log("6 processTripArray:", processTripArray);                
                                areAllTrue = Object.values(processTripArray).every(value => value === true);
                                if(areAllTrue) $(".processTrip").prop("disabled", false);
                            },
                            error: function(xhr, status, error) {
                                var errorMsg = "Error tallying tolls.";
                                console.log(errorMsg, error);
                                alert(errorMsg, error);
                            }
                        });
                    }

                }); // end of listener for tallyTollsButton

                // remove disabled before submitting so triptolls gets sent with input
                $('form').on('submit', function(e) {
                    $('#tripTolls').prop('disabled', false);
                });

            });

        </script>

    </body>
</html>