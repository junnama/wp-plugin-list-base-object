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
    public $version      = 1.4;                   // Version for upgrade table
    public $singular     = 'Contact Info';        // Object label
    public $plural       = 'Contact Info';        // Object label plural
    public $_table       = 'contact_info';        // Database table name
    public $_primary     = 'ID';                  // Primary key for table
    public $_title       = 'name';                // Title column for list & edit screen
    public $_display     = true;                  // Can view list screen
    public $_can_upgrade = true;                  // Can create or upgrade table
    public $_can_edit    = true;                  // Can edit, save or delete object(s)
    public $_can_create  = true;                  // Can create new object
    public $_can_search  = true;                  // Display search box
    public $list_options = true;                  // Show display options
    public $textdomain   = 'object-list-example'; // Labguage text domain
    public $permission   = 'activate_plugins';    // Permission for this action(s)
    protected $_filter   = '';                    // Add query for get list objects, ex "post_type='post'"
    public $icon_url     = 'images/icon.png';     // Add icon to menu item
    public $menu_type    = 'object';              // Placement of menu item
    public $menu_order   = 1;                     // Position of menu item( when $menu_type='menu' )
    public $month_filter = true;                  // Show month filter
    public $date_col     = 'date';                // Columns for month filter

    public function __path() {
        return __FILE__;
    }
    function column_defs(){
        $columns = array(
            'ID'      => array( 'label' => 'ID',
                                'list' => false,
                                'search' => false,
                                'type' => 'integer',
                                'property' => 'int(11) NOT NULL',
                              ),
            'name'    => array( 'label' => 'Name',
                                'list' => true,
                                'edit' => true,
                                'search' => true,
                                'type' => 'string',
                                'indexed' => true,
                                'property' => "varchar(50) NOT NULL DEFAULT ''",
                                'required' => true,
                              ),
            'email'   => array( 'label' => 'Email',
                                'list' => true,
                                'edit' => true,
                                'search' => true,
                                'type' => 'string',
                                'indexed' => true,
                                'property' => "varchar(75) NOT NULL DEFAULT ''",
                                'required' => true,
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
                                'created' => true, // or 'modifired' => true,
                                'readonly' => true,
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
            'flag'    => array( 'label' => 'Flag',
                                'list' => true,
                                'edit' => true,
                                'type' => 'boolean',
                                'indexed' => true,
                                'property' => "tinyint(4) default '0'",
                                'items' => array(
                                    0 => 'None',
                                    1 => 'Flagged',
                                ),
                              ),
            'status'  => array( 'label' => 'Status',
                                'list' => true,
                                'edit' => true,
                                'type' => 'select',
                                'items' => array(
                                    1 => 'Disable',
                                    2 => 'Active',
                                ),
                                'indexed' => true,
                                'property' => "int(11) default '2'",
                              ),
        );
        return $columns;
    }
}
$init_plugin = $plugin_id . 'Init';
new $init_plugin;
class ObjectListExampleInit extends ListBaseObjectInit {
}
