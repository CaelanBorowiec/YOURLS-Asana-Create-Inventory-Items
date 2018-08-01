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
    <p></p>

    <h3>Credits</h3>
    <ul>
      <li>Plugin created by <a href="https://github.com/CaelanBorowiec" target="_blank">Caelan Borowiec</a></li>
      <li>GitHub user <a href="https://github.com/ajimix" target="_blank">ajimix</a>, for their <a href="https://github.com/ajimix/asana-api-php-class" target="_blank">asana-api-php-class</a></li>
    </ul>

  </div>
  <?php
}
