<?php
/*
Plugin Name: Asana Create Inventory Items
Plugin URI: https://github.com/CaelanBorowiec/YOURLS-Asana-Create-Inventory-Items/
Description: Create item tasks in bulk
Version: 0.0
Author: Caelan Borowiec
Author URI: https://github.com/CaelanBorowiec
*/

// No direct load
if ( !defined ('YOURLS_ABSPATH') ) { die(); }

// Register a page
yourls_add_action( 'plugins_loaded', 'asanalookup_add_page' );

function asanalookup_add_page() {
    yourls_register_plugin_page( 'asanalookup', 'Import Asana Details', 'asanalookup_display_page' );
}

function asanalookup_display_page() {
  //Print plugin details page.
  ?>
  <div class="about">
    <h2>Asana Details Plugin Overview</h2>
    <p>This plugin integrates <a href="http://yourls.org" target="_blank"><b>YOURLS</b></a> with the task/project management software <a href="https://asana.com/product" target="_blank"><b>Asana</b></a>.
        The plugin provides a method for YOURLS to retrieve task titles and other data from Asana when it would ordinarily only see a login screen.</p>

    <h3>Setup:</h3>
    <p>An Asana API "Personal Access Token" must be created for YOURLS.
        You may wish to do this with an Asana account dedicated to YOURLS.
        Note that the account used <b>must</b> be able to access the Asana projects/tasks that are being queried.<br />
      Please review this page on <a href="https://asana.com/guide/help/api/api#gl-access-tokens" target="_blank"><b>how to create a Persona Access Token</b></a>.</p>

      <p>After you have created a personal access token, please add it to your YOURLS config in the format: <em>$asanaPAT="yourtoken";</em></p>

    <h3>Existing Features</h3>
    <ul>
      <li>Task name to link title.</li>
    </ul>

    <h3>Planned Features</h3>
    <ul>
      <li>Project name to link title.</li>
    </ul>

    <h3>Credits</h3>
    <ul>
      <li>Plugin created by <a href="https://github.com/CaelanBorowiec" target="_blank">Caelan Borowiec</a></li>
      <li>GitHub user <a href="https://github.com/ajimix" target="_blank">ajimix</a>, for their <a href="https://github.com/ajimix/asana-api-php-class" target="_blank">asana-api-php-class</a></li>
    </ul>

  </div>
  <?php
}


// Hook our custom function into the 'shunt_add_new_link' filter
yourls_add_filter( 'shunt_get_remote_title', 'asanalookup_get_title');

function asanalookup_get_title($false, $url)
{
	if (preg_match("/http.:\/\/app\.asana\.com\/.\/([0-9]{1,})\/([0-9]{1,})/", $url, $matches))
  {
    // $matches[0] = input, $matches[1] = project, $matches[2] = task,
		return getAsanaTaskTitle($matches[2]);
	}
	return false;
}

function getAsanaTaskTitle($taskID) {
  require_once('asana-api-php-class/asana.php');

  // Set up Asana connection
  global $asanaPAT;
  $asana = new Asana([
      'personalAccessToken' => $asanaPAT
  ]);

  $asana->getTask($taskID);

  $result = $asana->getData();

  if ($asana->hasError()) {
      echo 'Error while trying to connect to Asana, response code: ' . $asana->responseCode;
  }
  if (isset($result->id))
    return $result->name;

  return false;
}
