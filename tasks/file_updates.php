<?php
	if(!defined('DS')) { define('DS', DIRECTORY_SEPARATOR); }
	if(!defined('APP')) { define('APP', dirname(dirname(__FILE__)).DS); }
	if(!defined('LIBRARY')) { define('LIBRARY', APP.'libraries'.DS); }
	require LIBRARY.'php-activerecord/ActiveRecord.php';
	
	ActiveRecord\Config::initialize(function($cfg) {
		$cfg->set_model_directory(APP.'models');
		$cfg->set_connections(array('development' => 'mysql://root:@localhost/lra'));
	});
	
	$file_arr = scandir(APP.'data/');
	array_shift($file_arr);
	array_shift($file_arr);
	foreach($file_arr as $file) {
		$file_time = filemtime(APP.'data/'.$file);
		$dt_file = date('Y-m-d H:i:s', $file_time);
		
		$file_info = File::find_by_filename($file);
		$check_file_time = mktime($file_info->updated->format('H'), $file_info->updated->format('i'), $file_info->updated->format('s'), $file_info->updated->format('n'), $file_info->updated->format('j'), $file_info->updated->format('Y'));
		
		if(stripos($file, 'vacant')!==false) {
			$plot_type = 3;
		}
		
		if($file_time>$check_file_time) {
			// 4879 SACRAMENTO AV,Penrose,1,,63115,Vacant Lot,240,43880605900
			$fh = fopen(APP.'data/'.$file, 'r');
			while (($plot = fgetcsv($fh, 1000, ",")) !== false) {
				if($plot[0]!=='Address' && count($plot)==8) {
					$plot_check = Plot::find(array('conditions'=>'parcel_id="'.$plot[7].'" AND city="'.$file_info->city.'" AND state="'.$file_info->state.'"'));
					
					if($plot_check!==NULL) {
						$plot_check->type = $file_info->plot_type;
						$plot_check->street = ucwords(strtolower($plot[0]));
						$plot_check->ward = $plot[2];
						$plot_check->neighborhood = $plot[1];
						$plot_check->zip = $plot[4];
						$plot_check->description = $plot[3];
						$plot_check->usage = $plot[5];
						$plot_check->lot_square_feet = $plot[6];
						$plot_check->parcel_id = $plot[7];
						$plot_check->city = $file_info->city;
						$plot_check->state = $file_info->state;
						$plot_check->save();
					} else {
						require_once(LIBRARY.'uuid.php');
						$save_plot = array(
							'type'=>$file_info->plot_type,
							'street'=>ucwords(strtolower($plot[0])),
							'ward'=>$plot[2],
							'neighborhood'=>$plot[1],
							'zip'=>$plot[4],
							'description'=>$plot[3],
							'usage'=>$plot[5],
							'lot_square_feet'=>$plot[6],
							'parcel_id'=>$plot[7],
							'city'=>$file_info->city,
							'state'=>$file_info->state,
							'id'=>UUID::generate(UUID::UUID_RANDOM, UUID::FMT_STRING)
						);
						$plot = Plot::create($save_plot);
						$plot->save();
					}
				} else {
					error_log(var_export($plot, true));
				}
			}
			$file_info->save();
		}
	}
