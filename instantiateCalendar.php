<?php
require_once('mainCalendar.php');
$test=new Calendar;
    $test->debug=FALSE;
    # $test->values=$test->_HTML_Calendar_Values_Default(); # Uncomment to use sample values.

    $test->values['task_ID:task_Name']=array(
        'taskCheckout'=>'Checkout operator',
        'taskCustomerService'=>'Customer Service',
        'taskStockroom'=>'Stockroom');
    $test->values['workers_IDs']=array('JohnDoe','JaneSmith');
    $test->values['user_ID:user_Name']=array('JohnDoe'=>'John Doe','JaneSmith'=>'Jane Smith');
    $test->values['status_ID:status_Text']=array(
        'A'=>"NONE",
        'Working_1*'=>"I AM WORKING THIS SHIFT",
        'Cannot_1'=>"I can't work this shift",
        'Available_1'=>"I am available to cover this shift",
        'PreferNot_1'=>"I would prefer not to work this shift",
        'PreferTo_1'=>"I would prefer to work this shift",
        'HolidayRequest_1'=>"HOLIDAY REQUEST");
    
    $test->values['file_Navigate_To_On_Save']="loginCalendar.php";
    $test->var['valMonthSelector_FilenameNavigateTo']='loginCalendar.php'; 


?>