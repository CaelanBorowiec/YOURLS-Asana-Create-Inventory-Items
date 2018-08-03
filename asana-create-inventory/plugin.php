<?php
/*
Plugin Name: Asana Create Inventory Items
Plugin URI: https://github.com/CaelanBorowiec/YOURLS-Asana-Create-Inventory-Items/
Description: Create item tasks in bulk
Version: 0.1
Author: Caelan Borowiec
Author URI: https://github.com/CaelanBorowiec
*/

// No direct load
if ( !defined ('YOURLS_ABSPATH') ) { die(); }

require_once('settings.php');
require_once('asana-api-php-class/asana.php');

// Register a page
yourls_add_action( 'plugins_loaded', 'aot_create_item_page' );

function aot_create_item_page() {
    yourls_register_plugin_page( 'inventory', 'Create Inventory Barcodes', 'aot_create_item_display_page' );
}

function aot_create_item_display_page() {
  //Print plugin details page.
  ?>
  <div class="about">
    <link href="http://fonts.googleapis.com/css?family=Raleway:400,700" rel="stylesheet" />
    <link href="<?php echo yourls_plugin_url("asana-create-inventory"); ?>/css/buttons.css" rel="stylesheet" />
    <script src="<?php echo yourls_plugin_url("asana-create-inventory"); ?>/js/buttons.js"></script>

    <h2>Plugin Overview</h2>
    <p>This plugin is a tool for creating "Asana of Things" items in <a href="https://asana.com/product" target="_blank"><b>Asana</b></a>.
        The actions below will allow you to generate a number of tasks, and simultaneously shorten them for printing.</p>

    <h2>Actions:</h2>
    <p><a id="generateButton" href="#" class="progress-button red" data-loading="Generating..." data-finished="Download CSV" data-type="background-horizontal">Generate Items</a></p>

    <h3>Credits</h3>
    <ul>
      <li>Plugin created by <a href="https://github.com/CaelanBorowiec" target="_blank">Caelan Borowiec</a></li>
      <li>GitHub user <a href="https://github.com/ajimix" target="_blank">ajimix</a>, for their <a href="https://github.com/ajimix/asana-api-php-class" target="_blank">asana-api-php-class</a></li>
    </ul>

  </div>
  <?php
}


yourls_add_filter( 'api_action_createaot', 'create_aot_record' );

function create_aot_record() {
  //  API can't be accessed without a valid login already (YOURLS Default)

	// Need 'count' parameter
  $count = $_REQUEST['count'];
	if( !isset($count) || !is_numeric($count))
  {
		return array(
			'statusCode' => 400,
			'simple'     => "Need a 'count' parameter",
			'message'    => 'Error: Invalid parameter: count',
		);
	}
  // Need 'start' parameter
  if(!isset($_REQUEST['start']) || !is_numeric($_REQUEST['start']))
  {
    return array(
      'statusCode' => 400,
      'simple'     => "Need a 'start' parameter",
      'message'    => 'Error: Invalid parameter: start',
    );
  }

	// Check if valid shorturl
	if(yourls_is_shorturl($_REQUEST['start']))
  {
		return array(
			'statusCode' => 400,
			'simple'     => 'Error: Start value already exists',
			'message'    => 'Error: Start value already exists',
		);
	}

  $asana = new Asana([
      'personalAccessToken' => asanaPAT
  ]);

  $start = $_REQUEST['start'];
  $current = $start;
  $last = $current + $count - 1;
  $details = "";

  $description = "1. Describe the item and attach a photo
2. Add location details
3. Remove from new items project";

  $error = false;
  $importResults = array();
  for (; $current <= $last; $current++)
  {
    if(yourls_is_shorturl($current))
    {
      $error = true;
      $importResults[$current] = 'exists';
      continue;
    }

    $title="New item #".$current;
    $asana->createTask([
  		 'workspace' => WORKSPACE, // Workspace ID
  		 'name' => $title, // Name of task
       'projects' => array(PQINEWITEMS, "537393307143896"),
       "notes" => $description,
  		 'custom_fields' => [(string)BARCODE_FIELD => (string)$current]
    ]);

    $result = $asana->getData();

  	if ($asana->hasError())
    {
      $error = true;
      $importResults[$current] = 'Asana failed';
  	}
  	else if (isset($result->id))
  	{
      $long = "http://app.asana.com/0/0/".$result->id;
      $shortResult = yourls_add_new_link($long, $current, $title);
      if ($shortResult['status'] == 'success')
        $importResults[$current] = $result->id;
      else
      {
        $error = true;
        $importResults[$current] = 'YOURLS failed';
      }
  	}
  }

  if ($error)
  {
    return array(
      'statusCode' => 422,
      'simple'     => 'Error while creating items in Asana',
      'message'    => 'One or more errors occured creating items in Asana.  Please review the results list of the outcome of each code attempt.',
      'results'      => json_encode($importResults),
    );
  }

  return array(
    'statusCode'  => 200,
    'simple'      => 'ok',
    'message'     => "Created $count barcodes from $start to $last",
    'results'     => json_encode($importResults),
  );



}
