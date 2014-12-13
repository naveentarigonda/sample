<?php 
	include "DB/db_config.php";
	if(isset($_POST['feeder_id'])){
		$errors = array();
		if(isset($_POST['feeder_id']) || !empty($_POST['feeder_id'])){
			$feederId = trim($_POST['feeder_id']);
			$feederId = mysql_real_escape_string($feederId);	
		}else{
			$errors[] = "Feeder Id should not be empty";
		}
		if(isset($_POST['update'])){
			if(is_numeric($_POST['update'])){
				$request_type = trim($_POST['update']);
				$request_type = mysql_real_escape_string($request_type);
			}else{
				$errors[] = "Request type should be 0 or 1";
			}
		}
		if($_POST['bat_volt']){
			if(is_numeric($_POST['bat_volt'])){
				$battery = trim($_POST['bat_volt']);
				$battery = mysql_real_escape_string($battery);
			}else{
				$errors[] = "Battery voltage should be numeric value";
			}
		}
		if(isset($_POST['schedule_date'])){
			if(!empty($_POST['schedule_date'])){
				$a = explode('-',$_POST["schedule_date"]);
				$new_date = $a[2].'-'.$a[1].'-'.$a[0];
				$new_date = strtotime($new_date);
				$schedule_date = date("Y-m-d",$new_date);
			}else{
				$errors[] = "Schedule date should not be empty";
			}
		}
		if(isset($_POST['start_time'])){
			if(!empty($_POST['start_time'])){
				$sch_start_arry = rtrim($_POST['start_time'],",");
				$sch_start_arry = explode(",",$sch_start_arry);
			}else{
				$errors[] = "Schedule start time should not be empty";
			}
		}
		if(isset($_POST['stop_time'])){
			$sch_end_arry = rtrim($_POST['stop_time'],",");
			$sch_end_arry = explode(",",$sch_end_arry);
		}
		if(isset($_POST['status'])){
			$run_status = trim($_POST['status']);
			$run_status = mysql_real_escape_string($run_status);
		}
		if(isset($_POST['despense_interval']) && is_numeric($_POST['despense_interval'])){
			$kg_feed_disp_time = trim($_POST['despense_interval']);
			$kg_feed_disp_time = mysql_real_escape_string($kg_feed_disp_time);
		}else{
			$errors[] = "Kg feed dispense time should not be empty";
		}
		if(isset($_POST['ON_Timer'])){
			$on_timer_arry = rtrim($_POST['ON_Timer'],",");
			$on_timer_arry = explode(",",$on_timer_arry);
		}
		if(isset($_POST['OFF_Timer'])){
			$off_timer_arry = rtrim($_POST['OFF_Timer'],",");
			$off_timer_arry = explode(",",$off_timer_arry);
		}
		if(isset($_POST['dispense_count'])){
			$no_cycles_arry = rtrim($_POST['dispense_count'],",");
			$no_cycles_arry = explode(",",$no_cycles_arry);
		}
		$power = 0;
		$now_date = date("Y-m-d");
		$now_time = date("H:i:s");
		$addedOn = date("Y-m-d H:i:s");
		$changed_data = array();
		$qry = mysql_query("SELECT fs.deviceId,fs.start_time,fs.BT_input,fs.update_status,fs.schedule_start FROM autofeeder_schedules fs INNER JOIN autofeeders f ON fs.deviceId=f.idautofeeders WHERE f.feederId='".$feederId."' AND fs.schedule_start<='".$now_date."' ORDER BY fs.schedule_start DESC LIMIT 1");
		$count = mysql_num_rows($qry);
		if($count==1){
			$res = mysql_fetch_array($qry);
			if(strtotime($schedule_date)!=strtotime($res['schedule_start'])){
				echo $res['BT_input']."\n";
				$update = mysql_query("UPDATE autofeeder_schedules SET update_status='0' WHERE deviceId='".$res['deviceId']."' AND schedule_start='".$res['schedule_start']."' LIMIT 1");
			}else{
				if($request_type ==0){
					if($now_time >= $res['start_time']){
						echo $res['BT_input']."\n";
					}
				}else if($request_type ==1){
					if($res['update_status']==1){
						echo $res['BT_input']."\n";
						$update = mysql_query("UPDATE autofeeder_schedules SET update_status='0' WHERE deviceId='".$res['deviceId']."' AND schedule_start='".$res['schedule_start']."' LIMIT 1");
					}
				}
			}
			$sett = mysql_fetch_assoc(mysql_query("SELECT feed_gap,kg_feed_disp_time FROM feeder_settings WHERE fs_feederId='".$res['deviceId']."' LIMIT 1"));
			$feedGap = $sett['feed_gap'];
			$feedDispTime = $sett['kg_feed_disp_time'];
			$query = mysql_query("INSERT INTO feeder_data VALUES ('','".$res['deviceId']."','$battery','$power','$addedOn')");
			$startTime = date("H:i");
			$nowTime = date("H:i");
			$modifiedOn = date("Y-m-d H:i:s");
			foreach($sch_start_arry AS $key=>$val){
				if(!empty($no_cycles_arry[$key]) || $no_cycles_arry[$key]!==0){
					if($schedule_date!=date("Y-m-d"))
						$scheduleDate = date("Y-m-d");
					else
						$scheduleDate = $schedule_date;
					//Schedule start time
					$sch_start = mysql_real_escape_string($sch_start_arry[$key]);
					$sch_start = strtotime($sch_start);
					$sch_start = date('H:i',$sch_start);
					//Schedule end time
					$sch_end = mysql_real_escape_string($sch_end_arry[$key]);
					$sch_end = strtotime($sch_end);
					$sch_end = date('H:i',$sch_end);
					//ON_Timer
					$on_timer = mysql_real_escape_string($on_timer_arry[$key]);
					//No of cycles completed
					$no_cycles = mysql_real_escape_string($no_cycles_arry[$key]);
					$sql = mysql_query("SELECT fid,schedule_date,start_time,feed_gap,feed_disp_time,no_cycles,ON_Timer FROM feeder_running_log WHERE fid='".$res['deviceId']."' AND schedule_date='".$scheduleDate."' AND sch_start='".$sch_start."' AND sch_end='".$sch_end."' AND feed_gap='".$feedGap."' AND feed_disp_time='".$feedDispTime."' AND CAST(ON_Timer AS DECIMAL)=CAST($on_timer AS DECIMAL) LIMIT 1");
					if(mysql_num_rows($sql)>=1){
						$sql_res = mysql_fetch_assoc($sql);
						if($sql_res['feed_gap']!=$feedGap){
							$changed_data['feed_gap'] = $feedGap;
						}
						if($sql_res['feed_disp_time']!=$feedDispTime){
							$changed_data['feed_disp_time'] = $feedDispTime;
						}
						if($sql_res['ON_Timer']!=$on_timer){
							$changed_data['ON_Timer'] = $on_timer;
						}
						if($sql_res['no_cycles']!=$no_cycles){
							if($no_cycles>=$sql_res['no_cycles']){
								$changed_data['no_cycles'] = $no_cycles;
							}
						}
						if(sizeof($changed_data)>=1){
							if(strtotime($nowTime)>strtotime($sch_end)){
								$run_end = strtotime($sch_start)+($feedGap*$no_cycles);
								$changed_data['end_time'] = date("H:i",$run_end);
							}else{
								$changed_data['end_time'] = $nowTime;
							}
							$changed_data['running_status'] = $run_status;
							$changed_data['modifiedOn'] = $modifiedOn;
							$stmt = "UPDATE feeder_running_log SET ";
							$sep = "";
							foreach($changed_data as $key=>$value) {
								$stmt .= $sep.$key." = '".$value."'";
								$sep = ",";
							}
							$stmt .= " WHERE fid='".$res['deviceId']."' AND schedule_date='".$scheduleDate."' AND sch_start='".$sch_start."' AND sch_end='".$sch_end."' AND feed_gap='".$feedGap."' AND feed_disp_time='".$feedDispTime."' AND CAST(ON_Timer AS DECIMAL)=CAST($on_timer AS DECIMAL)  LIMIT 1 ";
							$log = mysql_query($stmt);
						}
					}else{
						if(strtotime($nowTime)>=strtotime($sch_start)){
							//$run_start = $sch_start;
							if(strtotime($nowTime)>strtotime($sch_end)){
								$run_end = strtotime($sch_start)+($feedGap*$no_cycles);
								$run_end = date("H:i",$run_end);
								$run_start = $sch_start;
							}else{
								if(!empty($no_cycles)){
									$run_start = strtotime($nowTime)-($feedGap*$no_cycles);
									$run_start = date("H:i",$run_start);
									if(strtotime($run_start)<strtotime($sch_start))
										$run_start = $sch_start;
									else
										$run_start = $run_start;
									$run_end = $nowTime;
								}else{
									$run_end = $nowTime;
									$run_start = $sch_start;
								}
							}
							$log = mysql_query("INSERT INTO feeder_running_log VALUES('','".$res['deviceId']."','$scheduleDate','$sch_start','$sch_end','$run_start','$run_end','".$feedGap."','".$feedDispTime."','$on_timer','$no_cycles','$run_status','$addedOn','0000-00-00 00:00:00')");
						}
					}
					//$status = mysql_query("INSERT INTO feeder_running_info VALUES('','".$res['deviceId']."','$addedOn','$run_status')");
				}
			}
		}
		
	}
	if(is_resource($con))
	{
		mysql_close($con);
	}
	
?>