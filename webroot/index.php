<?php
/**
 * Step 1: Require the Slim PHP 5 Framework
 */
if(!defined('DS')) { define('DS', DIRECTORY_SEPARATOR); }
if(!defined('APP')) { define('APP', dirname(dirname(__FILE__)).DS); }
if(!defined('LIBRARY')) { define('LIBRARY', APP.'libraries'.DS); }
require LIBRARY.'slim/Slim/Slim.php';
require LIBRARY.'php-activerecord/ActiveRecord.php';

ActiveRecord\Config::initialize(function($cfg) {
	$cfg->set_model_directory(APP.'models');
	$cfg->set_connections(array('development' => 'mysql://root:@localhost/lra', 'production'=>'mysql://signalfade:rk6xh:9NBzTq@internal-db.s507.gridserver.com
/db507_lra'));
});

/**
 * Step 2: Instantiate the Slim application
 */
$app = new Slim();

$app->get('/data', function () use ($app) {
	header('Content-type: application/json');
	
	$conditions = 'hidden<>1';
	
	if(isset($_GET['north']) && !empty($_GET['north']) && isset($_GET['south']) && !empty($_GET['south']) && isset($_GET['east']) && !empty($_GET['east']) && isset($_GET['west']) && !empty($_GET['west'])) {
		$conditions .= ' AND latitude<"'.$_GET['north'].'" AND latitude>"'.$_GET['south'].'" AND longitude>"'.$_GET['east'].'" AND longitude<"'.$_GET['west'].'"';
	}
	if(isset($_GET['the_types'])) {
		$types = implode(', ', $_GET['the_types']);
		$conditions .= ' AND type IN ('.$types.')';
	}
	
	$plots = Plot::find('all', array('limit'=>100, 'order'=>'RAND()', 'conditions'=>$conditions));
	// $plots = Plot::find('all', array('limit'=>200, 'order'=>'RAND()', 'conditions'=>'latitude IS NULL AND hidden<>1'));
	$all_plots = array();
	foreach($plots as $plot) {
		$this_plot = $plot->attributes();
		unset($this_plot['created']);
		unset($this_plot['modified']);
		$all_plots[] = $this_plot;
	}
	echo json_encode($all_plots);
	exit();
});

$app->post('/update_address/:id', function ($id) use ($app) {
	$plot = Plot::find($id);
	if(isset($_POST['latitude']) && isset($_POST['longitude']) && !empty($_POST['latitude']) && !empty($_POST['longitude'])) {
		$plot->latitude = $_POST['latitude'];
		$plot->longitude = $_POST['longitude'];
		$plot->save();
		echo json_encode(array('error'=>false));
	} else {
		echo json_encode(array('error'=>true));
	}
	exit();
});

$app->post('/hide_plot/:id', function ($id) use ($app) {
	$plot = Plot::find($id);
	$plot->hidden = 1;
	$plot->save();
	echo json_encode(array('error'=>false));
	exit();
});

/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This is responsible for executing
 * the Slim application using the settings and routes defined above.
 */
$app->run();