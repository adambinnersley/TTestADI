function countdown(seconds){
    clearInterval(countdown);
    if(seconds){var count = seconds;}else{var count = 5399;}
    var countdown = setInterval(function(){
        var time = secondstotime(count);
        $("#time").html(time);
        if(count == 0){
            clearInterval(countdown);
            endTest();
        }
        count--;
    }, 1000);	
}

function secondstotime(secs){
    var t = new Date(1970,0,1);
    t.setSeconds(secs);
    var s = t.toTimeString().substr(0,8);
    return s;
}

$(document).ready(function(){
    if(countstart !== false){countdown();}
});