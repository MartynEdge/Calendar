<?php
    require_once('instantiateCalendar.php');
    $test->values['file_Navigate_To_On_Save']="johndoe.php";
    $test->var['valMonthSelector_FilenameNavigateTo']='johndoe.php';
    $test->run('JohnDoe');
?>