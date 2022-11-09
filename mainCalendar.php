<?php

    class Calendar{
        public $var=array();
        public $debug=FALSE;
        public $debugText='';
        
        public $settingsFilename='calendarSettings.json';
        public $settingsDefault=array();
        public $settings=array();
        
        private $results=array();
        private $user_ID='';
        private $months=['January','February','March','April','May','June','July','August','September','October','November','December'];


        function __construct(){
            $this->var['valDayElement']='';
            $this->var['valCalendarTitle']='My Calendar';
            $this->var['valCalendarSubtitle']='A place to organise our shifts!';
            $this->var['valCalendarHeading1']='Monthly calendar';
            $this->var['valFilenameCalendarHTML']='template_Calendar.html';
            $this->var['valMonthSelector_YearFrom']='2023';
            $this->var['valMonthSelector_YearTo']='2024';
            $this->var['valMonthSelector_FilenameNavigateTo']='mainCalendar.php';
            $this->var['pathUsers']='./';
            $this->values=array();
        }

        function _load_settings(){
            if (file_exists($this->settingsFilename)){
                $settingsText=file_get_contents($this->settingsFilename);
                $settingsJSON=json_decode($settingsText);
            }else{
                file_put_contents($this->settingsFilename,json_encode($this->settingsDefault));
                $settingsJSON=$this->settingsDefault;
            }
            
            $this->settings=$settingsJSON;
            $this->results['settingsExist']=TRUE;
        }

        function _save_settings(){
            file_put_contents($this->settingsFilename,json_encode($this->settings));
        }
        
        function _HTML_Month_Selector($yearFrom,$yearTo,$monthSelectorYYYYMM="202101"){
            # RETURNS HTML FOR THE MONTH DROP-DOWN MENU
            $htmlMonths="";
            $htmlMonths.="<form action='".$this->var['valMonthSelector_FilenameNavigateTo']."' method='post'>";
            $htmlMonths.="<select name='monthSelector' id='monthSelector' onchange='this.form.submit()'>";
            
            if ($monthSelectorYYYYMM==NULL) $monthSelectorYYYYMM=$yearFrom."01";
            if ($monthSelectorYYYYMM=="000000") $monthSelectorYYYYMM=$yearFrom."01";

            $monthSelectorYYYY=substr($monthSelectorYYYYMM,0,4);
            $monthSelectorMM=substr($monthSelectorYYYYMM,4,2);
            $monthSelectorM=intval($monthSelectorMM);
            $monthSelectorY=intval($monthSelectorYYYY);

            for ($year=$yearFrom;$year<=$yearTo;$year++){
                foreach (range(1,count($this->months)) as $i){
                    if ($i==$monthSelectorM && $year==$monthSelectorY){
                        $htmlMonths.="<option value='".$year.str_pad(strval($i),2,"0",STR_PAD_LEFT)."' selected>";
                    }else{
                        $htmlMonths.="<option value='".$year.str_pad(strval($i),2,"0",STR_PAD_LEFT)."'>";
                    }
                    
                    $htmlMonths.=$this->months[$i-1]." ".$year;
                    $htmlMonths.="</option>";
                }
            }
	    	$htmlMonths.="</select>";
            $htmlMonths.="</form>";
            
            return $htmlMonths;
        }

        function _HTML_Calendar_Values_Default(){
            $v=array();
            # Sample values
            $v['task_ID:task_Name']=array('taskTest'=>'Test - Task');
            $v['workers_IDs']=array('testUserA','testUserB');
            $v['user_ID:user_Name']=array('testUserA'=>'Test - UserA','testUserB'=>'Test - UserB');
            $v['working_IDs-taskTest-20210101']=array('testUserA','testUserB');
            $v['status_ID:status_Text']=array(
                'A'=>"NONE",
                'B'=>"I will do this shift",
                'C'=>"I can't do this shift",
                'D'=>"I am available to cover this shift");
            $v['status-taskTest-testUserA-20210101']='D';
            $v['status-taskTest-testUserB-20210101']='C';
            $v['file_Navigate_To_On_Save']="mainCalendar.php";
            return $v;
        
        }

        function _Get_Working_Names($year,$month,$day,$task,$v){
            # This will return the text of names of workers for a specific task.

            $v_key='working_IDs-'.$task."-".$this->_Convert_Date_YYYYMMDD($year,$month,$day);
            $v_val=$this->_get_value($v_key,$v);
            if (!$v_val) return "";
            
            $workersNamesList=array();
            
            foreach ($v_val as $workerID){
                $workersNamesList[]=$v['user_ID:user_Name'][$workerID];
            }
            $workersNamesText=implode(", ",$workersNamesList);
            return $workersNamesText;
        }

        function _Get_Status_Selection_And_Text($year,$month,$day,$task_ID,$user_ID,$v){
            # This returns the following list of lists:
            # [
            #   ['A','',"NO STATUS"],
            #   ['B','selected',"I will do this shift"],
            #   ['C','',"I can't do this shift"],
            #   ['D','',"I am available to cover this shift"],
            # ]
            $date=$this->_Convert_Date_YYYYMMDD($year,$month,$day);
            $status_Info=array();
            foreach ($v['status_ID:status_Text'] as $status_ID=>$status_Text){
                $status_Key='status-'.$task_ID.'-'.$user_ID.'-'.$date;
                $status_Selection_ID=$this->_get_value($status_Key,$v,'A'); # Option A is always default.
                if ($status_Selection_ID==$status_ID){
                    $status_Selection_HTML='selected';
                }else{
                    $status_Selection_HTML='';
                }
                
                $status_Info[]=[$status_ID,$status_Selection_HTML,$status_Text];
            }
            
            return $status_Info;
        }


        function _HTML_Calendar_Theme_Default($month,$year,$v=array()){
            # This recreates the default calendar layout, inserting the values 
            # from the dictionary $v.

            # Values are stored in $v
            # $month: INT
            # $year: INT as YYYY
        
            $HTML='';

            # SET UP FORM SUBMISSION
            $HTML.="\n<form action='".$v['file_Navigate_To_On_Save']."' method='post'>\n";
            $HTML.="    <input type='submit' name='Go' value='Save'>\n";

            # START DYNAMIC HTML
            $user_Name=$v['user_ID:user_Name'][$this->user_ID];
            $HTML.="<br><b>Hello $user_Name</b><br>";
            
            # Get number of days in month
            $days_in_month=cal_days_in_month(CAL_GREGORIAN,$month,$year);

            for ($day=1;$day<=$days_in_month;$day++){
                $task_Date=$this->_Convert_Date_YYYYMMDD($year,$month,$day);

                # GET WEEKDAY OF DATE
                $timestamp=strtotime($year."-".$month."-".$day);
                $day_of_week=date("l",$timestamp);

                # TASK DATE
                $HTML.="    <section><div class='dayDate'>";
                $HTML.=$day_of_week." ".strval($day)." ".$this->months[$month-1]." ".strval($year)."</div>\n";

                ## TASKS
                foreach ($v['task_ID:task_Name'] as $task_ID=>$task_Name){
                    $HTML.="        <section class='task'>\n";
                    $HTML.="            <div class='taskName'>";
                    $HTML.=$task_Name."</div>\n";

                    $HTML.="            <span class='taskWorkers'>Working this shift:</span> ".$this->_Get_Working_Names($year,$month,$day,$task_ID,$v)."<br>\n";
                    
                    # STATUS
                    $HTML.="            <span class='taskStatus'>".$user_Name.":</span>\n";
                    $HTML.="            <select name='status-".$task_ID."-".$this->user_ID."-".$task_Date."'>\n";
                    
                    ## My status, as a drop-down list box.
                    $status_Options=$this->_Get_Status_Selection_And_Text($year,$month,$day,$task_ID,$this->user_ID,$v);
                    $status_ID_Text=array();
                    foreach ($status_Options as [$status_ID,$status_Selected,$status_Text]){
                        $status_ID_Text[$status_ID]=$status_Text;
                        $status_Tag=$status_ID;# "status-".$task_ID."-".$status_ID."-".$task_Date;
                        $HTML.="                <option value='".$status_Tag."' ".$status_Selected.">".$status_Text."</option>\n";
                    }
                    $HTML.="            </select><br>\n";

                    ## Other workers statuses, as labels.
                    foreach ($v['workers_IDs'] as $worker_ID){
                        if ($worker_ID==$this->user_ID) continue;
                        $worker_Status_Key="status-".$task_ID."-".$worker_ID."-".$task_Date;
                        $worker_Status_Value=$this->_get_value($worker_Status_Key,$v,'A');
                        $worker_Status_Text=$status_ID_Text[$worker_Status_Value];
                        $HTML.="            <span class='taskStatus'>".$v['user_ID:user_Name'][$worker_ID].":</span> ".$worker_Status_Text."<br>\n";

                    }

                    $HTML.="        </section>\n";
                }

                # END TASK DATE
                $HTML.="    </section>\n";
                $HTML.="    <br>\n";
                
            }
            # END FORM SUBMISSION
            $HTML.="</form>\n";

            return $HTML;
        }
        
        function _get_value($key,$dictionary,$no_key_response=NULL){
            if (array_key_exists($key,$dictionary)){
                return $dictionary[$key];
            }
            return $no_key_response;
        }

        function _Convert_Date_YYYYMMDD($year,$month,$day){
            # $day, $month, $year (YYYY): INT
            # Returns string: YYYYMMDD
            return $year.str_pad($month,2,'0',STR_PAD_LEFT).str_pad($day,2,'0',STR_PAD_LEFT);
        }

        function _Convert_Date_YYYYMM($year,$month){
            # $month, $year (YYYY): INT
            # Returns string: YYYYMM
            return $year.str_pad($month,2,'0',STR_PAD_LEFT);
        }

        function _Convert_Date($dateYYYYMM,$defaultDateIfNULL="000000"){
            if ($dateYYYYMM==NULL) $dateYYYYMM=$defaultDateIfNULL;
            $date=array();
            $date['YYYYMM']=substr($dateYYYYMM,0,6); # STRING
            $date['YYYY']=substr($dateYYYYMM,0,4); # STRING
            $date['MM']=substr($dateYYYYMM,4,2); # STRING
            $date['m']=intval($date['MM']); # INT
            $date['yyyy']=intval($date['YYYY']); # INT
            return $date;
        }


        function HTML($filenameHTML){
            $HTML=file_get_contents($filenameHTML);

            # Add debug info.
            if ($this->debug==TRUE){
                $this->debugText.=print_r($_POST,TRUE)."<br>";
                $this->debugText=str_replace("\n","<br>",$this->debugText);
            }
            $this->var['valDebugText']=$this->debugText;
            
            # Get Month selected
            $this->monthSelectorYYYYMM=$this->_get_value('monthSelector',$_POST);
            $this->monthSelector=$this->_Convert_Date($this->monthSelectorYYYYMM,$this->var['valMonthSelector_YearFrom']."01");
            
            # Add date selector
            $this->var['valMonthSelector']=$this->_HTML_Month_Selector(intval($this->var['valMonthSelector_YearFrom']),intval($this->var['valMonthSelector_YearTo']),$this->monthSelector['YYYYMM']);
        
            # Insert dynamic HTML
            $this->var['valDayElement']=$this->_HTML_Calendar_Theme_Default($this->monthSelector['m'],$this->monthSelector['yyyy'],$this->values);

            # Replace static variables
            $HTML=$this->_insert_var_into_HTML($HTML);
            
            return $HTML;
        }

        function run($username=''){
            # Referencing a username so that this can be linked from a log in page if required.
            if ($username==''){
                die("You must log in to use ".$this->var['valCalendarTitle'].'.');
            }

            # Load settings
            $this->_load_settings();

            # Set this user
            $this->user_ID=$username;

            # Save postings, if necessary
            if ($this->_get_value('Go',$_POST,'')=='Save'){
                $this->_save_posted_tasks();
            }
            
            # Load user values
            foreach ($this->values['workers_IDs'] as $user_ID){
                $valuesLoaded=$this->_load_user_tasks($this->var['pathUsers'],$user_ID);
                $this->values=array_merge($this->values,$valuesLoaded);
            }
            
            # Set workers
            $this->_set_workers();
            
            $HTML=$this->HTML($this->var['valFilenameCalendarHTML']);
            echo $HTML;
        }

        function _set_workers(){

            # RESET ALL WORKERS VALUES
            foreach ($this->values as $key=>$value){
                if ($this->_begins('working_IDs',$key)){
                    unset($this->values[$key]);
                }
            }

            $workersNow=array();
            foreach ($this->values as $key=>$value){
                if ($this->_begins('status-',$key)){
                    if (substr($value,-1,1)=="*"){
                        $keyComponents=explode('-',$key);
                        $workersKey='working_IDs-'.$keyComponents[1].'-'.$keyComponents[3];
                        $workersCurrent=$this->_get_value($workersKey,$this->values,array());
                        $workersCurrent[]=$keyComponents[2];
                        $workersNow[$workersKey]=$workersCurrent;
                    }
                }
            }

            $this->values=array_merge($this->values,$workersNow);

        }

        function _save_posted_tasks($path="./"){
            $valuesNew=array();

            # Load all users in the workers group.
            foreach ($this->values['workers_IDs'] as $worker){
                $workerTasks=$this->_load_user_tasks($path,$worker);
                $valuesNew[$worker]=$workerTasks;
            }

            $user_IDs_found=array(); # Only save to users which have posted info. Reduces unneccesary writing to other users.
            foreach ($_POST as $key=>$value){
                if ($this->_begins('status',$key)){
                    $keyComponents=explode('-',$key);
                    $keyUser_ID=$keyComponents[2];
                    $valuesNew[$keyUser_ID][$key]=$value;
                    if (!in_array($keyUser_ID,$user_IDs_found)) $user_IDs_found[]=$keyUser_ID;
                }
            }
            
            # Overwrite user file with selections.
            # Only save users which were posted.
            foreach ($user_IDs_found as $user_ID){ 
                $this->_save_user_tasks($path,$user_ID,$valuesNew[$user_ID]);
            }
            
            
        }

        function _save_user_tasks($path="./",$user_ID,$jsonObject){
            $filename=$path.$user_ID.".json";
            $jsonString=json_encode($jsonObject);
            file_put_contents($filename,$jsonString);
        }
        function _load_user_tasks($path="./",$user_ID){
            # path must end in forward-slash: ./
            # return JSON object of user tasks:
            #   { testUserA:
            #       { status-...: 'A',
            #         status-...: 'D'}
            #   }
            $filename=$path.$user_ID.".json";
            if (!file_exists($filename)) return array();
            $jsonString=file_get_contents($filename);
            $jsonObject=json_decode($jsonString,TRUE);
            return $jsonObject;
        }

        function _begins($beginning_string,$full_string){
            # Returns TRUE if the first few characters of a string match.
            #   'abc','abcd' returns TRUE
            #   'abcd','abc' returns FALSE
            #   'abc','1234' returns FALSE
            
            $lenB=strlen($beginning_string);
            if ($lenB>strlen($full_string)) return NULL;

            if (substr($full_string,0,$lenB)==$beginning_string){
                return TRUE;
            }
            return FALSE;
        }

        function _insert_var_into_HTML($HTML){
            # Replace variables in webpage
            foreach ($this->var as $varKey=>$varValue){
                $search="*".$varKey."*";
                $HTML=str_replace($search,$varValue,$HTML);
            }
            return $HTML;
        }

    }

?>