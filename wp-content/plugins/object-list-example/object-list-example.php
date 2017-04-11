<?php
/*
Plugin Name: Object List Example
Plugin URI: https://alfasado.net/
Description: Example for List Base Object.
Version: 0.1
Author: Alfasado Inc.
Author URI: https://alfasado.net/
License: GPL2
*/
$plugin_id = 'ObjectListExample';
load_plugin_textdomain( 'object-list-example', false, basename( dirname( __FILE__ ) ) . '/languages' );
require_once( ABSPATH . 'wp-content/plugins/list-base-object/list-base-object.php' );
class ObjectListExample extends ListBaseObject {
    public $plugin_id    = 'Object List Example';
    public $plugin_key   = 'objectlistexample';   // Uniqkey for upgrade table
    public $version      = 1.1;                   // Version for upgrade table
    public $singular     = 'Contact Info';        // Object label
    public $plural       = 'Contact Info';        // Object label plural
    public $_table       = 'contact_info';        // Database table name
    public $_primary     = 'ID';                  // Primary key for table
    public $_title       = 'name';                // Title column for list & edit screen
    public $_display     = true;                  // Can view list screen
    public $_can_upgrade = true;                  // Can create or upgrade table
    public $_can_edit    = true;                  // Can edit, save or delete object(s)
    public $_can_search  = true;                  // Display search box
    public $list_options = true;                  // Show display options
    public $textdomain   = 'object-list-example'; // Labguage text domain
    public $permission   = 'activate_plugins';    // Permission for this action(s)
    protected $_filter   = '';                    // Add query for get list objects, ex "post_type='post'"
    function column_defs(){
        $columns = array(
            'ID'      => array( 'label' => 'ID',
                                'list' => false,
                                'search' => false,
                                'type' => 'integer',
                                'property' => 'int(11) NOT NULL',
                              ),
            'name'    => array( 'label' => 'Name',  // Label for list and edit screen.
                                'list' => true,     // Show column for list screen
                                'edit' => true,     // Show column for edit screen
                                'search' => true,   // Search this column
                                'type' => 'string', // Control type for edit screen
                                'indexed' => true,  // Add index to this column
                                'property' => "varchar(50) NOT NULL DEFAULT ''", // SQL for CREATE or ALTER TABLE
                              ),
            'email'   => array( 'label' => 'Email',
                                'list' => true,
                                'edit' => true,
                                'search' => true,
                                'type' => 'string',
                                'indexed' => true,
                                'property' => "varchar(75) NOT NULL DEFAULT ''",
                              ),
            'company' => array( 'label' => 'Company',
                                'list' => true,
                                'edit' => true,
                                'search' => true,
                                'type' => 'string',
                                'property' => 'varchar(75) DEFAULT NULL',
                              ),
            'url'     => array( 'label' => 'URL',
                                'list' => false,
                                'edit' => true,
                                'search' => true,
                                'type' => 'string',
                                'property' => 'varchar(75) DEFAULT NULL',
                              ),
            'date'    => array( 'label' => 'Date',
                                'list' => true,
                                'edit' => true,
                                'type' => 'datetime',
                                'indexed' => true,
                                'property' => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
                                'modifired' => true, // or 'created' => true,
                              ),
            'author'  => array( 'label' => 'Author',
                                'list' => true,
                                'edit' => true,
                                'type' => 'object',
                                'indexed' => true,
                                'table' => 'users',
                                'obj_col' => 'user_nicename',
                                'property' => "bigint(20) unsigned NOT NULL default '0'",
                                'user_id' => true,
                              ),
        );
        return $columns;
    }
}
$init_plugin = $plugin_id . 'Init';
new $init_plugin;
class ObjectListExampleInit extends ListBaseObjectInit {
}
