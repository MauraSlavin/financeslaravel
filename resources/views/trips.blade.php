<html>
    <head>
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Favicon -->
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/favicon/apple-touch-icon.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/favicon/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/img/favicon/favicon-16x16.png') }}">
        <link rel="shortcut icon" href="{{ asset('assets/img/favicon/favicon.ico') }}">
        <link rel="manifest" href="{{ asset('assets/img/favicon/site.webmanifest') }}">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script> -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </head>

    <body>
        <h1>Trips - Cost to use car</h1>
        <p style="color:red; margin-left:20px;"> NOTE: Make sure 
            <br> --- <u>Maintenance</u> (tracking is the car, notes begins with "maint"),
            <br> --- <u>Tolls</u> (can upload - "Upload Tolls" below - from EZPass download - beware of duplicate tolls records),
            <br> --- --- NOTE: Can take at least 4 days to appear in EZPass for MA tolls.
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

            <div style="color: red; font-weight: bold; background-color: yellow; margin: 5px 0px 0px 20px;">
                <p id="msg"></p>
            </div>

            <div style="margin: 5px 0px 0px 20px;">
                <button type="button" class="btn btn-success uploadTollsButton">Upload Tolls</button>
            </div>

            <!-- Choose the function: 
                Run (add records to the database for a new trip); 
                Test (just show the results); 
                Modify (change the database for an existing trip)
            -->
            <div class="form-row">
                <label class="tripLabel" for="tripFunction">Function: *</label>
                <select class="tripInput form-control" id="tripFunction" name="tripFunction" required>
                    <option value="Run">Run</option>
                    <option value="Test">Test</option>
                    <option value="Modify">Modify (not functional yet)</option>
                </select>
            </div>

            <div class="form-row" id="tripNameDiv">
                <label class="tripLabel" for="tripName">Trip name (16 char max - date will be appended): *
                    <br>
                    <span style="font-size: 10px; font-weight: normal; margin: 0 0 5px 0;">Note: if multiple dates appear, delete all dates & tab through to "Trip Ended on:"</span>
                </label>
                <br>
                <input class="form-control tripInput" type="text" required id="tripName" name="tripName" maxlength="16">
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
                        <span style="font-size: 10px; font-weight: normal; margin: 0 0 5px 0;">Note: Records this in database for RUN and TEST functions.  Ignores for MODIFY function.</span>
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
                    <label class="tripLabel" for="tripTolls" style="width: 300px;">Tolls tallied: * (use this button &rarr;)</label>
                    <button type="button" class="btn btn-success tallyTollsButton" style="margin-bottom: 5px;">Tally Tolls</button>
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

                // used to determine whether to enable "Process Trip" button
                var areAllTrue;

                // used to see if trip info changed for function Modify
                var origTripData;

                // variable for function being done (Run, Modify, or Test)
                var tripFunction = "Run";

                // info for car entered (or default car for driver entered)
                var car = null;
                var thisCarInfo = [];

                // enable "Process Trip" when all fields completed.
                var processTripArrayRun = {
                    "name": false,
                    "begin": false,
                    "end": false,
                    "who": false,
                    "car": false,
                    "miles": false,
                    "tolls": false
                }

                var processTripArrayTest = {
                    "begin": false,
                    "end": false,
                    "car": false,
                    "miles": false,
                    "tolls": false
                }

                var processTripModify = false;

                // prompt for a brief trip description where missing in tolls table
                function promptForTrip(missingTrips) {
                    var tripDates, tripTimes, tripCars, tripDescs, tripDesc, tripMsg, confirmed;
                    tripDates = [];
                    tripTimes = [];
                    tripCars = [];
                    tripDescs = [];
                    var missingTrips = JSON.parse(missingTrips);
                    missingTrips.forEach( missingTrip => {
                        confirmed = false;
                        while(!confirmed) {
                            tripMsg = "Trip for this record should be: \n"
                                + "Date: " + missingTrip["Transaction Date"] + "\n"
                                + "Time: " + missingTrip["Transaction Time"] + "\n"
                                + "Agency: " + missingTrip["Agency"] + "\n"
                                + "Entry - Exit - Lane: " + missingTrip["Entry Plaza"] + " - " + missingTrip["Exit Plaza"] + " - " + missingTrip["Exit Lane"] + "\n"
                                + "Toll: " + missingTrip["Outgoing"] + "\n"
                                + "CAR: " + missingTrip["Car"];
                            tripDesc = prompt(tripMsg + "\n\n"
                                + "(for spending, use this format: <desc> mm-dd-yy)"
                            );
                            confirmed = confirm("You said: " + tripDesc + ". Is that correct?");
                        }
                        if(tripDesc != '') {
                            tripDescs.push(tripDesc);
                            tripDates.push(missingTrip["Transaction Date"]);
                            tripTimes.push(missingTrip["Transaction Time"]);
                            tripCars.push(missingTrip["Car"]);
                        }
                    });

                    // only update tripDescs (Trip column) in tolls table if there are some
                    if(tripDescs.length != 0) {
                        tripDates = encodeURIComponent(tripDates);
                        tripTimes = encodeURIComponent(tripTimes);
                        tripCars  = encodeURIComponent(tripCars);
                        tripDescs = encodeURIComponent(tripDescs);

                        var url = '/accounts/trip/updateTripDescs/' + tripDates + '/' + tripTimes + '/' + tripCars + '/' + tripDescs;

                        // send ids and trips all at once - just one ajax call
                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: {
                                _token: "{{ csrf_token() }}"
                            },
                            dataType: 'json',

                            success: function(response) {
                                // no need to do anything
                                console.log("Trip column in tolls table updated successfully.");
                            },

                            error: function(xhr, status, error) {
                                var errorMsg = "Error updating 'Trip' in tolls table.";
                                console.error(errorMsg, xhr.responseJSON ? xhr.responseJSON.error : error);
                                alert(errorMsg + ": " + (xhr.responseJSON ? xhr.responseJSON.details : error));
                            }
                        });
                    } // end of if no tripDescs
                }

                // does trip name entered exist?
                function DoesTripNameExist(tripName, tripNames) {
                    if(tripNames.includes(tripName)) return true;
                    else return false;
                }

                // get the tripName date from the tripname
                //  return false if it's not a date
                function getTripNameDate(tripName) {

                    // get the date from the name & break into parts
                    tripNameDate = tripName.substr(-8);

                    var tripMonth = parseInt(tripNameDate.substr(0, 2));
                    var tripDay = parseInt(tripNameDate.substr(3, 2));
                    var tripYear = parseInt(tripNameDate.substr(6, 2));
                    var tripDashOne = tripNameDate.substr(2, 1);
                    var tripDashTwo = tripNameDate.substr(5, 1);

                    // if it does NOT look like a date, return false
                    if( !(tripDashOne == '-' &&
                        tripDashTwo == '-' &&
                        tripMonth >= 1 &&
                        tripMonth <= 12 &&
                        tripDay >= 1 &&
                        tripDay <= 31 &&
                        tripYear >= 24 &&
                        tripYear <= 62)
                    ) {
                        return false;
                    } else {
                        // tripNameDate is a string, parts are integers, year is 2 digits
                        return [tripNameDate, tripMonth, tripDay, tripYear];
                    }
                }

                // get date from trip name.
                //      if no date, just return
                //      if there is a date, 
                //          and no begin/end,
                //          set begin & end to that date
                // updates processTripArray to indicate what's been changed
                function DefaultTripDatesFromName(tripName, processTripArray) {

                    // get the date from the name & break into parts
                    var tripNameDate, tripMonth, tripDay, tripYear;
                    // tripNameDate is a string, parts are integers, year is 2 digits
                    var getTripNameDateResult = getTripNameDate(tripName);

                    // if tripname has a date...
                    if(getTripNameDateResult != false) {
                        [tripNameDate, tripMonth, tripDay, tripYear] = getTripNameDateResult;
                        // ...use as default begin and end dates (if they're not entered yet)
                        // get in the right format
                        tripNameDate = (tripYear + 2000).toString() + '-' 
                            + String(tripMonth).padStart(2, '0') + '-'
                            + String(tripDay).padStart(2, '0');

                        if($('#tripBegin').text() == '' || $('#tripBegin').text() == null) {
                            $('#tripBegin').val(tripNameDate);
                            if(typeof processTripArray.begin !== 'undefined') {
                                processTripArray.begin = true;
                            }
                        }

                        if($('#tripEnd').text() == '' || $('#tripEnd').text() == null) {
                            $('#tripEnd').val(tripNameDate);
                            if(typeof processTripArray.end !== 'undefined') {
                                processTripArray.end = true;
                            }                            
                        }
                    }

                    return processTripArray;

                }   // end of function DefaultTripDatesFromName

                // returns true if begin date is <= end date
                function IsBeginBeforeEndDate() {
                    var beginDate = $('#tripBegin').val();
                    var endDate = $('#tripEnd').val();

                    if(beginDate <= endDate) return true;
                    else return false;
                }
                

                // if tripname doesn't have date at end, 
                //  append begin date to the tripname
                function AppendDateToTripName(processArray) {
                    var tripName = $('#tripName').val();
                    var tripBegin = $('#tripBegin').val();  // yyyy-mm-dd format
                    // format tripBegin to mm-dd-yy
                    tripBegin = tripBegin.substr(5, 5) + '-' + tripBegin.substr(2, 2);

                    // only add tripBegin date to tripName if it's not already there.
                    if(tripName.slice(-8) != tripBegin) {
                        tripName += ' ' + tripBegin;
                        $('#tripName').val(tripName);

                        if(typeof processArray == 'boolean') {
                            processArray = true;
                        } else {
                            if(typeof processArray.name != 'undefined') {
                                processArray.name = true;
                            }
                        }
                    }       
                    
                    return processArray;

                }   // end function AppendDateToTripName


                // capture function
                // If "Modify", change trip name choices to existing trips
                // If "Run", make sure it's the freeform entry field (check for duplicates is later - after date appended)
                // If "Test", disable trip name (not needed)
                $('#tripFunction').on('change', function(e) {
                    tripFunction = $(this).val();

                    // clear out all the values
                    $('#tripName').val("");
                    $('#tripBegin').val("");
                    $('#tripEnd').val("");
                    $('#tripWho').val("");
                    $('#tripCar').val("");
                    $('#tripOdom').val("");
                    $('#tripOdomDate').val("");
                    $('#tripmiles').val("");
                    $('#tripTolls').val("");

                    // If Run, clear out old values
                    if(tripFunction == 'Run') {

                        // disable Process Trip button
                        $(".processTrip").prop("disabled", true);

                        // remember no data entered
                        processTripArrayRun.name = false;
                        processTripArrayRun.begin = false;
                        processTripArrayRun.end = false;
                        processTripArrayRun.who = false;
                        processTripArrayRun.car = false;
                        processTripArrayRun.miles = false;
                        processTripArrayRun.tolls = false;

                        // make sure it's an open entry field for function RUN
                        $("#tripName").remove();  // remove existing
                        const freeformTripNameInput = '<input class="form-control tripInput" type="text" required id="tripName" name="tripName" maxlength="16">';
                        // add to page
                        $('#tripNameDiv').append(freeformTripNameInput);

                        // make sure tolls disabled, odometer fields & who enabled
                        $('#tripTolls').prop("disabled", true);
                        $('#tripOdom').prop("disabled", false);
                        $('#tripOdomDate').prop("disabled", false);
                        $('#tripWho').prop("disabled", false);

                    // If Modify, make tripName input a selection of the existing trips (with most recent on top)
                    //  Tolls should be input disabled (only change via "Tally Tolls" button)
                    } else if(tripFunction == 'Modify') {

                        // sortedTripNames has date first, for easier ordering.  Can omit dashes.
                        var sortedTripNames = [];
                        tripNames.forEach( (tname, nameIdx) => {
                            var date = tname.substr(-8);
                            var newDate = date.substr(6)+date.substr(0,2)+date.substr(3,2);
                            var baseName = tname.replace(" " + date, "");
                            sortedTripNames.push(newDate + " " + baseName);
                        });

                        // sort trip names.  This will be in chronological order.  Remember to display with most recent first.
                        sortedTripNames.sort();

                        // remove freeform input field
                        $("#tripName").remove();

                        // create drop down selections
                        var date, basename, value, optionHtml;
                        var selectTripName = '';
                        sortedTripNames.forEach( tname => {
                            // reformat sorted trip names to original format: <basename> mm-dd-yy
                            basename = tname.substr(7);
                            date = tname.substr(2,2) + '-' + tname.substr(4,2) + '-' + tname.substr(0,2); 
                            value = basename + " " + date;
                            optionHtml = '<option value="' + value + '">' + value + '</option>';
                            // add to beginning of array, so most recent trip is on the top of the list
                            selectTripName = optionHtml + selectTripName;
                        });
                        // Add prompt as first selection to front
                        var selectTripName = '<option value="prompt">Choose an existing trip.</option>' + selectTripName;

                        // wrap selections on <select...>,</select> stmts
                        selectTripName = '<select class="tripInput form-control" id="tripName" name="tripName" required>"' + selectTripName + "</select>";

                        // add to page
                        $('#tripNameDiv').append(selectTripName);

                        // make sure tolls, odometer fields disabled; who is enabled
                        $('#tripTolls').prop("disabled", true);
                        $('#tripOdom').prop("disabled", true);
                        $('#tripOdomDate').prop("disabled", true);
                        $('#tripWho').prop("disabled", false);

                        processTripArrayModify.begin = false;
                        processTripArrayModify.end = false;
                        processTripArrayModify.car = false;
                        processTripArrayModify.miles = false;
                        processTripArrayModify.tolls = false;

                        // end of creating dropdown & disabling Tolls for function Modify

                    } else if(tripFunction == "Test") {
                        //  Tolls should be allow input for testing
                        $('#tripTolls').prop("disabled", false);
                        //  Odometer values & who aren't changed in TEST
                        $('#tripOdom').prop("disabled", true);
                        $('#tripOdomDate').prop("disabled", true);
                        $('#tripWho').prop("disabled", true);

                        // make sure it's an open entry field for function RUN
                        $("#tripName").remove();  // remove existing
                        const freeformTripNameInput = '<input class="form-control tripInput" type="text" required id="tripName" name="tripName" maxlength="16">';
                        // add to page
                        $('#tripNameDiv').append(freeformTripNameInput);                        
                    }


                });

                // NOTES on TRIP NAME:
                // - NO trip name needed (not allowed) for Function "Test"
                // - NEW trip name needed for Funtion "Run"
                //      check this when Trip Begin date is entered, since it's appended to the trip name automatically
                // - EXISTING trip name needed for Function "Modify"
                //      check this when Trip Name entered (give drop down choices?)

                // note that name was entered (to determine if Process Trip button should be enabled)
                $('#tripNameDiv').on('blur', '#tripName', function(e) {
                    e.preventDefault();

                    var tripName = $('#tripName').val();
                    if(tripFunction == "Modify") {
                        $('#msg').text("Wait for trip data to load (may take several seconds).");
                        // get data for trip from database
                        if(tripName == "prompt") {
                            $('#msg').text("Choose a trip name (not the prompt)");
                        } else {
                            processTripModify = false;
                            $('.processTrip').prop('disabled', true);

                            $.ajax({
                                url: '/transactions/getTripData/' + tripName,
                                type: 'GET',
                                dataType: 'json',
                                data: {
                                    _token: "{{ csrf_token() }}"
                                },
                                success: function(response) {
                                    // mms - then fill in existing data on page!!
                                    origTripData = JSON.parse(response['message']);
                                    $('#tripBegin').val(origTripData['begin']);
                                    $('#tripEnd').val(origTripData['end']);
                                    $('#tripWho').val(origTripData['who']);
                                    $('#tripCar').val(origTripData['car']);
                                    $('#tripmiles').val(origTripData['mileage']);
                                    $('#tripTolls').val(origTripData['tolls']);
                                    $('#msg').text("");
                                    // mms - handle when changes made (& enable Process Trip when changes made)
                                },
                                error: function(xhr, status, error) {
                                    console.log("Error getting trip data from trips table for: ", tripName);
                                    alert("Error getting trip data from trips table for: " + tripName);
                                }
                            });
                        }
                        

                    } else {

                        // NOTE: Don't verify that it's a new trip name until begin date entered,
                        //      because that may change the trip name.
                        areAllTrue = false;
                        if(tripFunction == "Run") {
                            processTripArrayRun = DefaultTripDatesFromName(tripName, processTripArrayRun);
                            processTripArrayRun.name = true;
                            areAllTrue = Object.values(processTripArrayRun).every(value => value === true);
                        } else if(tripFunction == "Test") {
                            processTripArrayTest = DefaultTripDatesFromName(tripName, processTripArrayTest);
                            // processTripArrayTest.name = true;  // don't need name to be entered for Test
                            areAllTrue = Object.values(processTripArrayTest).every(value => value === true);
                        }
                        if(areAllTrue) $(".processTrip").prop("disabled", false);
                        else $(".processTrip").prop("disabled", true);
                    }

                    // if tripName includes a date, use that for a default for begin, end, and odometer

                });


                // When trip began on is entered, 
                // fill in Trip Ended and Date Odometer Read with the same date (if not entered, yet)
                // If function "RUN", append trip begin to tripName & check to make sure it's a new trip name
                // enable "Process Trip" if all necessary fields entered
                $('#tripBegin').on('blur', function(e) {
                    e.preventDefault();

                    var inputsAreGood = true;   // assume inputs are good until found otherwise
                    if($('#tripBegin').val() == "") {
                        inputsAreGood = false;
                        $('#msg').text('Enter a "Trip Begin" date.');
                        $('.processTrip').prop('disabled', true);
                    } else {

                        // if tripEnd has not been entered, make it the same as tripBegin
                        if($('#tripEnd').val() == '') {
                            $('#tripEnd').val($('#tripBegin').val());
                            if(tripFunction == "Run") {
                                processTripArrayRun.end = true;
                                areAllTrue = Object.values(processTripArrayRun).every(value => value === true);
                                if(areAllTrue) $('.processTrip').prop('disabled', false);
                                else $(".processTrip").prop("disabled", true);
                            } else if(tripFunction == "Test") {
                                processTripArrayTest.end = true;
                                console.log("processTripArrayTest 1: ", processTripArrayTest);
                                areAllTrue = Object.values(processTripArrayTest).every(value => value === true);
                                if(areAllTrue) $('.processTrip').prop('disabled', false);
                                else $(".processTrip").prop("disabled", true);
                            } else if(tripFunction == "Modify") {
                                processTripModify = true;
                                if(processTripModify) $('.processTrip').prop('disabled', false);
                                else $(".processTrip").prop("disabled", true);
                            }
                        }

                        // if date of odometer reading not set, make this the default
                        if($('#tripOdomDate').val() == '') {
                            $('#tripOdomDate').val($('#tripBegin').val());
                        }

                        // if Function is RUN, append tripBegin to tripName (use last 2 of year - drop '20'), if not already there
                        //  and check to see if it's a unique trip name
                        if(tripFunction == "Run") {
                            processTripArrayRun = AppendDateToTripName(processTripArrayRun);
                            if(DoesTripNameExist($('#tripName').val(), tripNames)) {
                                alert("This trip name (" + $('#tripName').val() + ") is already being used.  Change it.");
                                $('#tripName').val("");
                                processTripArrayRun.name = false;
                                // Disable "Process Trip"
                                $(".processTrip").prop("disabled", true);
                                inputsAreGood = false;
                            } // end check for unique trip name                            
                        } else if(tripFunction == "Modify") {
                            processTripModify = AppendDateToTripName(processTripModify);
                            if(processTripModify == true) {
                                $(".processTrip").prop('disabled', true);
                            }
                        }
                        
                     }  // end if Trip Begin entered

                    if(inputsAreGood) {
                        // if tripEnd already entered, make sure begin is before end
                        if($('#tripEnd').val()) {
                            if(!IsBeginBeforeEndDate()) {
                                $("#dateErr").html("Trip must start before it ends.");
                                $(".processTrip").prop('disabled', true);
                                inputsAreGood = false;
                            } else {
                                $("#dateErr").html(" ");
                            }
                        }
                        
                        // only continue if no error found yet
                        if(inputsAreGood) {
                            if(tripFunction == "Run") {
                                processTripArrayRun.begin = true;
                                areAllTrue = Object.values(processTripArrayRun).every(value => value === true);
                            } else if(tripFunction == "Test") {
                                processTripArrayTest.begin = true;
                                console.log("processTripArrayTest 0: ", processTripArrayTest);
                                areAllTrue = Object.values(processTripArrayTest).every(value => value === true);
                            }
                            // enable "Process Trip" if good inputs
                            if(areAllTrue) $(".processTrip").prop("disabled", false);
                            else $(".processTrip").prop("disabled", true);

                        }  // end of 2nd check of inputsAreGood
                    }   // end of 1st check of inputsAreGood

                }); // end tripBegin blurred


                // When trip end entered, make sure it's = or after begin
                $('#tripEnd').on('blur', function(e) {
                    e.preventDefault();

                    var goodInput = true;

                    if($("#tripEnd").val() == "") {
                        $('#msg').text('Enter a "Trip End" date.');
                        goodInput = false;
                        $('.processTrip').prop('disabled', true);
                        if(tripFunction == 'Run') {
                            processTripArrayRun.end = false;
                            $('.processTrip').prop('disabled', true);
                        } else if(tripFunction == 'Test') {
                            processTripArrayTest.end = false;
                            console.log("processTripArrayTest 2: ", processTripArrayTest);
                            $('.processTrip').prop('disabled', true);
                        }
                    } else {
                        // check to see if begin date is before or equal end date
                        if($('#tripBegin').val()) {

                            if(!IsBeginBeforeEndDate()) {
                                goodInput = false;
                                $(".processTrip").prop("disabled", true);
                                $("#dateErr").html("Trip must start before it ends.");
                            } else {
                                $("#dateErr").html(" ");
                            }
                        }

                        // mark this as entered, even if there's an error.
                        //      if fixed in #tripBegin, this won't be marked as filled.
                        //      user's responsibility...
                        if(tripFunction == "Run" && goodInput) {
                            processTripArrayRun.end = true;
                            // console.log("3 processTripArray:", processTripArray);
                            areAllTrue = Object.values(processTripArrayRun).every(value => value === true);
                            if(areAllTrue) $(".processTrip").prop("disabled", false);
                            else $(".processTrip").prop("disabled", true);
                        } else if(tripFunction == "Test" && goodInput) {
                            processTripArrayTest.end = true;
                            console.log("processTripArrayTest 3: ", processTripArrayTest);
                            areAllTrue = Object.values(processTripArrayTest).every(value => value === true);
                            if(areAllTrue) $(".processTrip").prop("disabled", false);
                            else $(".processTrip").prop("disabled", true);
                        }
                    }  // end else tripEnd not blank

                }); // end tripEnd blurred


                // default "car" when "who" is entered (if not already entered)
                $('#tripWho').on('change', function(e) {

                    var goodInput = true; 

                    if($('#tripCar').val() == "" || $('#tripCar').val() == null) {
                        // get the driver entered
                        const driver = $('#tripWho').val();
                        // console.log("tripCar: " + $('#tripCar').val());
                        if(driver != 'both') {
                            // get the info for the car this driver usually drives
                            thisCarInfo = carInfo.find(thisCar => thisCar.Driver === driver);
                            if(typeof thisCarInfo == 'undefined') {
                                alert("Choose a valid car (not a prompt)");
                                goodInput = false;
                                $('.processTrip').prop('disabled', true);
                                if(tripFunction == "Run") {
                                    processTripArrayRun.car = false;
                                }
                            } else {
                                // set the default car for this driver
                                $('#tripCar').val(thisCarInfo.car);

                                // car is filled
                                if(tripFunction == "Run") {
                                    processTripArrayRun.car = true;
                                }
                            }  // end of else thisCarInfo is defined
                        }
                    }

                    // driver is filled
                    if(tripFunction == "Run" && goodInput) {
                        processTripArrayRun.who = true;
                        // are all the fields filled?
                        areAllTrue = Object.values(processTripArrayRun).every(value => value === true);
                        if(areAllTrue) $(".processTrip").prop("disabled", false);
                        else $(".processTrip").prop("disabled", true);
                    }
                    
                });


                // if the car was changed
                $('#tripCar').on('change', function(e) {
                    car = $('#tripCar').val();
                    thisCarInfo = carInfo.find(thisCar => thisCar.car === car);

                    // car is filled
                    if(tripFunction == "Run") {
                        processTripArrayRun.car = true;
                        // are all the fields filled?
                        // console.log("7 processTripArray:", processTripArray);
                        areAllTrue = Object.values(processTripArrayRun).every(value => value === true);
                        if(areAllTrue) $(".processTrip").prop("disabled", false);
                        else $(".processTrip").prop("disabled", true);
                    } else if(tripFunction == "Test") {
                        processTripArrayTest.car = true;
                        console.log("processTripArrayTest 4: ", processTripArrayTest.car);
                        console.log("processTripArrayTest 4: ", processTripArrayTest);
                        areAllTrue = Object.values(processTripArrayTest).every(value => value === true);
                        if(areAllTrue) $(".processTrip").prop("disabled", false);
                        else $(".processTrip").prop("disabled", true);
                    }

                });


                $('#tripmiles').on('blur', function(e) {
                    // get tripMiles entered
                    var tripMiles = parseInt($('#tripmiles').val());

                    if(tripMiles == 0 || tripMiles == "" || isNaN(tripMiles)) {
                        $('#msg').text("Enter miles!");
                        if(tripFunction == "Run") processTripArrayRun.miles = false;
                        else if(tripFunction == "Test") processTripArrayTest.miles = false;
                        $('.processTrip').prop('disabled', true);
                        return;
                    }
                    
                    var keepTripMiles;
                    // if less than 30, may not need to do this
                    if( tripMiles <= 30) {
                        var keepTripMiles = confirm("Don't need to do this for short trips (< 1/2 hour or so).  Do you want to continue?");
                        if(!keepTripMiles) {
                            $("#tripmiles").val('');
                            if(tripFunction == "Run") processTripArrayRun.miles = false;
                            else if(tripFunction == "Test") processTripArrayTest.miles = false;
                            console.log("processTripArrayTest 5: ", processTripArrayTest);
                            $(".processTrip").prop("disabled", true);
                            return;
                        }
                    }

                    // make sure > 150 is not a mistake
                    if( tripMiles >= 150) {
                        keepTripMiles = confirm(tripMiles + " is a long trip. Is this correct?");
                        if(!keepTripMiles) {
                            $("#tripmiles").val('');
                            if(tripFunction == "Run") processTripArrayRun.miles = false;
                            else if(tripFunctiono == "Test") processTripArrayTest.miles = false;
                            console.log("processTripArrayTest 6: ", processTripArrayTest);
                            $(".processTrip").prop("disabled", true);
                            return;
                        }
                    }

                    // if ok, continue
                    if(tripFunction == "Run") {
                        processTripArrayRun.miles = true;
                        areAllTrue = Object.values(processTripArrayRun).every(value => value === true);
                        if(areAllTrue) $(".processTrip").prop("disabled", false);
                        else $(".processTrip").prop("disabled", true);
                    } else if(tripFunction == "Test") {
                        processTripArrayTest.miles = true;
                        console.log("processTripArrayTest 7: ", processTripArrayTest);
                        areAllTrue = Object.values(processTripArrayTest).every(value => value === true);
                        if(areAllTrue) $(".processTrip").prop("disabled", false);
                        else $(".processTrip").prop("disabled", true);
                    }
                });


                // Is Odometer reading more than last reading?
                $('#tripOdom').on('change', function(e) {
                    var odomMilesEntered = parseInt($('#tripOdom').val());
                    if( odomMilesEntered < thisCarInfo.Mileage) $('#msg').text("Last recorded odometer reading was " + thisCarInfo.Mileage + ".  Enter a reading more than that.");
                });


                // Is Odometer date after last reading?
                $('#tripOdomDate').on('blur', function(e) {
                    var odomDateEntered = $('#tripOdomDate').val(); // yyyy-mm-dd
                    // reformat to yymmdd
                    odomDateEntered = odomDateEntered.substr(2, 2) + odomDateEntered.substr(5, 2) + odomDateEntered.substr(8,2);
                    if( odomDateEntered < thisCarInfo.OdomDate) $('#msg').text("Last recorded odometer reading was on " + thisCarInfo.OdomDate + ".  Enter a reading after that.");
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
                            var rcdsWritten = response['rcdsWritten'];
                            alert(rcdsWritten + " Tolls successfully written to tolls table.");
                            var missingTrips = response['missingTrips'];
                            promptForTrip(missingTrips);
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
                    console.log("tripname: ", trip);

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

                                areAllTrue = false;
                                // console.log("6 processTripArray:", processTripArray);                
                                if(tripFunction == "Run") {
                                    processTripArrayRun.tolls = true;    
                                    areAllTrue = Object.values(processTripArrayRun).every(value => value === true);
                                } else if(tripFunction == "Test") {
                                    processTripArrayTest.tolls = true;    
                                    console.log("processTripArrayTest 8: ", processTripArrayTest);
                                    areAllTrue = Object.values(processTripArrayTest).every(value => value === true);
                                }
                                if(areAllTrue) $(".processTrip").prop("disabled", false);
                                else $(".processTrip").prop("disabled", true);
                            },
                            error: function(xhr, status, error) {
                                var errorMsg = "Error tallying tolls.";
                                console.log(errorMsg, error);
                                alert(errorMsg, error);
                            }
                        });
                    }

                }); // end of listener for tallyTollsButton

                $('#tripTolls').on('blur', function(e) {
                    e.preventDefault();
                    var tripTolls = $('#tripTolls').val();
                    console.log("tripTolls: ", tripTolls);
                    if(isNaN(tripTolls) || tripTolls == '') {
                        $('#msg').text("Enter a tolls number (can be 0)");
                        if(tripFunction == "Run") {
                            processTripArrayRun.tolls = false;
                        } else if(tripFunction == "Test") {
                            processTripArrayTest.tolls = false;
                        }
                        $('.processTrip').prop('disabled', true);
                        return;
                    }

                    areAllTrue = false;

                    if(tripFunction == "Run") {
                        processTripArrayRun.tolls = true;
                        areAllTrue = Object.values(processTripArrayRun).every(value => value === true);
                    } else if(tripFunction == "Test") {
                        processTripArrayTest.tolls = true;
                        console.log("processTripArrayTest 9: ", processTripArrayTest.tolls);
                        console.log("processTripArrayTest 9: ", processTripArrayTest);
                        areAllTrue = Object.values(processTripArrayTest).every(value => value === true);
                    }

                    if(areAllTrue) $(".processTrip").prop("disabled", false);
                    else $(".processTrip").prop("disabled", true);
                });

                // remove disabled before submitting so triptolls gets sent with input
                $('form').on('submit', function(e) {
                    $('#tripTolls').prop('disabled', false);
                });

            });

        </script>

    </body>
</html>