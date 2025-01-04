
function oldVerifyDate(newValue) {

    var isGood = true;
    // is date of the format ####-##-##
    // should have 10 chars
    if(newValue.length != 10) return false;

    // #'s in ####-##-## should be numbers
    
    //      #### should be between 2020 and next year
    var nextYear = Number(new Date().getFullYear()) + 1;
    const year = parseInt(newValue.slice(0, 4))
    if(year == NaN || year > nextYear || year < 2020) return false;

    //      ## (middle) should be between 1 and 12
    const month = parseInt(newValue.slice(5, 7));
    if(month == NaN || month < 1 || month > 12) return false;

    //      ## (last) should be between 1 and 29, 30 or 31, depending on the month
    const day = parseInt(newValue.slice(8));
    var lastDayOfMonth;
    if([1, 3, 5, 7, 8, 10, 12].includes(month)) lastDayOfMonth = 31;
    else if (month == 2) lastDayOfMonth = 29;
    else lastDayOfMonth = 30;
    if(day == NaN || day < 1 || day > lastDayOfMonth) return false;
    // -'s should be "-"
    if(newValue.slice(4, 5) != '-') return false;
    if(newValue.slice(7, 8) != '-') return false;

    return isGood;
}

// newValue must be in the list of definedValues to be valid.
function verifyEnums(newValue, definedValues) {

    var isGood = true;
    // if newValue is not in the list of definedValues, it is not good
    if(!definedValues.includes(newValue)) return false;

    return isGood;
}


// Amount must be an integer or decimal with no $.
function verifyAmount(newValue) {

    var isGood = true;
    // make it a number
    newValue = Number(newValue);

    // if newValue is not is not a number
    if(isNaN(newValue)) {
        return false;
    }
    
    // question if more than 2 decimal points
    var countDecimals = function(value) {
        if(Math.floor(value) !== value) {
            return value.toString().split(".")[1].length || 0;
        }
        return 0;
    }

    var numberDecimals = countDecimals(newValue);
    if(numberDecimals > 3) {
        isOK = confirm("This value (" + newValue + ") has more than 3 decimal places.  Is that OK?");
        if(!isOK) return false;
    }

    // question if over 500 or less than -500
    if( newValue > 500 || newValue < -500) {
        isOK = confirm("This value (" + newValue + ") has is pretty big.  Is that OK?");
        if(!isOK) return false;
    }

    return isGood;
}

// Does data fit in database table column?
function verifyVarCharLength(newValue, allowedLength) {

    var isGood = true;

    // Is length is more than the column length?
    if(newValue.length > allowedLength) return false;

    return isGood;
}

// Is stmtDate in the format yy-Mon (i.e. 24-Oct)
function verifyStmtDate(newValue) {

    var isGood = true;

    // break into before "-" and after
    var parts = newValue.split("-");
    
    // first part needs to be an integer
    var inputYear = Number(parts[0]);
    if(isNaN(inputYear)) return false;

    // ending needs to be a 3 char abbreviation for a month
    var months = [
        'Jan',
        'Feb',
        'Mar',
        'Apr',
        'May',
        'Jun',
        'Jul',
        'Aug',
        'Sep',
        'Oct',
        'Nov',
        'Dec'
    ];
    if(!months.includes(parts[1])) return false;

    // if input year is not this year, and month is not this month or next,
    //  ask user to verify
    var thisYear = new Date().getFullYear();
    thisYear = thisYear % 100;
    if(inputYear != thisYear) {
        isGood = confirm("This statement date (" + newValue + ") is not this year.  Is it ok?");
        // isGood = true;  // temp
        if(!isGood) return false;
    }
            
    const formatter = new Intl.DateTimeFormat('en', { month: 'short' });
    var thisMonth = formatter.format(new Date()).slice(0, 3);
    if(thisMonth != parts[1]) {
        isGood = confirm("This statement date (" + newValue + ") is not this month.  Is it ok?");
        // isGood = true; // temp
        if(!isGood) return false;
    }

    return isGood;

}