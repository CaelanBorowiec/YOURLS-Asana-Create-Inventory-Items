<?php
/*
Plugin Name: Asana Create Inventory Items
Plugin URI: https://github.com/CaelanBorowiec/YOURLS-Asana-Create-Inventory-Items/
Description: Create item tasks in bulk
Version: 0.1
Author: Caelan Borowiec
Author URI: https://github.com/CaelanBorowiec
*/

require_once('settings.php');
require_once('asana-api-php-class/asana.php');

// No direct load
if ( !defined ('YOURLS_ABSPATH') ) { die(); }

// Register a page
yourls_add_action( 'plugins_loaded', 'aot_create_item_page' );

function aot_create_item_page() {
    yourls_register_plugin_page( 'inventory', 'Create Inventory Barcodes', 'aot_create_item_display_page' );
}

function aot_create_item_display_page() {
  //Print plugin details page.
  ?>
  <div class="about">
    <h2>Plugin Overview</h2>
    <p>This plugin is a tool for creating "Asana of Things" items in <a href="https://asana.com/product" target="_blank"><b>Asana</b></a>.
        The actions below will allow you to generate a number of tasks, and simultaneously shorten them for printing.</p>

    <h2>Actions:</h2>
    <p><a href="" target="_blank"><b>Create 1 task</b></a>.</p>

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
      'personalAccessToken' => $asanaPAT
  ]);

  $start = $_REQUEST['start'];
  $current = $start;
  $last = $current + $count;
  $details = "";

  $description = "1. Describe the item and attach a photo
2. Add location details
3. Remove from new items project";

  for (; $current <= $last; $current++)
  {
    $asana->createTask([
  		 'workspace' => $workspace, // Workspace ID
  		 'name' => "New item #".$current, // Name of task
       'projects' => PQINEWITEMS,
       "html_notes" => $description,
  		 'custom_fields' => [$fieldIDs["Barcode"] => $current]
    ], array('opt_fields' => "html_notes"));

    $details .= "$current, \r\n";
  }

  return array(
    'statusCode'  => 200,
    'simple'      => 'ok',
    'message'     => "Created $count barcodes from $start to $last",
    'details'     => $details,
  );



}
