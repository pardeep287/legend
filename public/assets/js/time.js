/** 
 * @file time.js
 * @description used for create timer with date
 */

/**    
 * Function is used to create time & date
 */
function startTime()
{
    var today = new Date();
    var h = today.getHours();
    var m = today.getMinutes();
    var s = today.getSeconds();

    d = today.getDate();
    day = today.getDay();
    month = today.getMonth();
    year = today.getFullYear();

    months = new Array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
    weekday = new Array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');

    // add a zero in front of numbers<10
    h = checkTime(h);
    m = checkTime(m);
    s = checkTime(s);
    
    //Check for PM and AM
    var day_or_night = (h > 11) ? "PM" : "AM";

    //Convert to 12 hours system
    if (h > 12)
        h -= 12;

    var enable = '';
    if( $('#time').length ) {
        enable = 'time';
        clock = h+":"+m+":"+s+" "+day_or_night;
    }
    if( $('#datetime').length ) {
        enable = 'datetime';
        clock = checkTime(d)+" "+months[month]+", "+year+" <br/>"+h+":"+m+":"+s+" "+day_or_night;
    }

    //Add time/datetime to the headline and update every 500 milliseconds
    if ($('#'+enable).length) {
        $('#'+enable).html(clock);
    }
    setTimeout(function() {
        startTime()
    }, 500);
}

/**    
 * Function is used to return value as per time required
 * 
 */
function checkTime(i)
{
    return ( i < 10 ) ? "0"+i : i;
}