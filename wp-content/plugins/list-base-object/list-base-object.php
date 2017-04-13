<?php
/*
Plugin Name: List Base Object
Plugin URI: https://alfasado.net/
Description: Manage custom table.
Version: 0.1
Author: Alfasado Inc.
Author URI: https://alfasado.net/
License: GPL2
*/
// add_filter( 'pre_update_option_active_plugins', 'high_priority_active_plugins' );
/* TODO
    HTML Template
    Import from CSV or other format
*/
if(! class_exists( 'WP_List_Table' )){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
$plugin_id = 'ListBaseObject';
$init_plugin = $plugin_id . 'Init';
new $init_plugin;
class ListBaseObjectInit {
    protected $page_title;
    protected $magic_quotes = false;
    protected $objectTable = null;
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        load_plugin_textdomain( 'list-base-object', false, basename( dirname( __FILE__ ) ) . '/languages' );
    }
    public function admin_menu() {
        $classname = get_class( $this );
        $classname = preg_replace( '/Init$/', '', $classname );
        $objectTable = new $classname;
        $this->objectTable = $objectTable;
        if (! $objectTable->_display ) {
            return;
        }
        $user_id = $objectTable->_user()->ID;
        $_table = $objectTable->_table;
        $_page = "${_table}_list_objects";
        if( $_table . '-apply-display-options' === $objectTable->current_action() ) {
            $options = array();
            foreach ( $_REQUEST as $key => $value ) {
                if ( strpos ( $key, 'disp-opt-' ) === 0 ) {
                    $key = str_replace( 'disp-opt-', '', $key );
                    $options[] = $key;
                }
            }
            $paging = $_REQUEST[ $_table.'-object-per-page' ];
            if ( $paging ) {
                $paging = (int) $paging;
                update_option( "${_page}-paging-${user_id}", $paging );
            }
            update_option( "${_page}-disp_opt-${user_id}", implode( ',', $options ) );
        }
        $singular = $objectTable->_translate( $objectTable->singular );
        $plural = $objectTable->_translate( $objectTable->plural );
        $action = $objectTable->current_action();
        if ( $action && ( ( $action == 'edit' ) || ( $action == 'save' ) ) ) {
            if (! $_REQUEST[ $_table ] ) {
                $page_title = $objectTable->_translate( 'Add New %s', $singular );
            } else {
                $page_title = $objectTable->_translate( 'Edit %s', $singular );
            }
        } else {
            $page_title = $objectTable->_translate( 'List of %s', $plural );
        }
        $this->page_title = $page_title;
        $menu_function_name = $_table . '_add_menu_items';
        $permission = $objectTable->permission;
        $icon_url = null;
        if ( $objectTable->icon_url ) {
            $icon_url = plugins_url( $objectTable->icon_url, $objectTable->__path() );
        }
        if ( $objectTable->menu_type == 'object' ) {
            add_object_page( $page_title, $singular,
                $permission, $_table.'_list_objects', array( $this, 'class_render_list_page' ),
                $icon_url );
        } else {
            add_menu_page( $page_title, $singular,
                $permission, $_table . '_list_objects', array( $this, 'class_render_list_page' ),
                $icon_url, $objectTable->menu_order );
        }
        if ( $objectTable->_can_edit ) {
            add_submenu_page( $_table . '_list_objects',  $objectTable->_translate( 'Add New %s', $singular ),
            $objectTable->_translate( 'Add New %s', $singular ), $permission, 
                $_table . '_list_objects_submenu', array( $this, 'class_render_new_page' ) );
        }
    }
    function class_render_new_page() {
        $objectTable = $this->objectTable;
        $_REQUEST[ 'action' ] = 'edit';
        $this->class_render_list_page();
    }
    function class_render_list_page() {
        if (! $this->magic_quotes ) {
            function strip_magic_quotes_slashes ( $arr ) {
                return is_array( $arr ) ?
                array_map( 'strip_magic_quotes_slashes', $arr ) :
                stripslashes( $arr );
            }
            $_GET = strip_magic_quotes_slashes( $_GET );
            $_POST = strip_magic_quotes_slashes( $_POST );
            $_REQUEST = strip_magic_quotes_slashes( $_REQUEST );
            $_COOKIE = strip_magic_quotes_slashes( $_COOKIE );
        }
        $classname = get_class( $this );
        $classname = preg_replace( '/Init$/', '', $classname );
        //$objectTable = new $classname;
        $objectTable = $this->objectTable;
        if (! $objectTable->_display ) {
            return;
        }
        global $wpdb;
        $table = $wpdb->prefix . $objectTable->_table;
        $objectTable->prepare_items();
        $_table = $objectTable->_table;
        $_primary = $objectTable->_primary;
        $_page = "${_table}_list_objects";
        if( 'search' === $objectTable->current_action() ) {
            $q = esc_html( $_REQUEST[ 's' ] );
            $search_text = '';
            if ( $q ) {
                $search_text = $objectTable->_translate( 'Search Results for: %s', $q );
            }
        }
        $singular = $objectTable->_translate( $objectTable->singular );
        $search_label = $objectTable->_translate( 'Search %s', $singular );
        $create_button = '';
        $_edit_html = '';
        $message_block = '';
        $notice_class = 'success';
        $_can_edit = $objectTable->_can_edit;
        $obj;
        $id ;
        $cols = $objectTable->column_defs();
        if ( $message = $objectTable->_page_message ) {
            if ( $objectTable->_error ) {
                $notice_class = 'error';
            }
            $message_block = '<div id="message" class="notice notice-' . $notice_class . ' is-dismissible"><p>' . $message . '</p></div>';
        }
        if ( $objectTable->_can_edit ) {
            $save_label = $objectTable->_translate( 'Save' );
            $create_label = $objectTable->_translate( 'Add New' );
            $create_button = sprintf( '<a class="page-title-action" href="?page=%s&action=%s">%s</a>', $_page, 'edit', $create_label );
            $_edit_screen = $objectTable->_edit_screen;
            if ( $obj = $objectTable->current_object ) {
                $id = $obj->$_primary;
                $save_label = $objectTable->_translate( 'Save Changes' );
            }
            $_edit_html = $objectTable->_edit_html;
        }
        $bulk_actions = $objectTable->get_bulk_actions();
        $get_action_name = "";
        foreach ( $bulk_actions as $key => $value ) {
            $get_action_name .= " if ( name == '${key}' ) { return '${value}';} ";
        }
        $get_action_name = <<< EOT
            function get_action_name( name ) {
                ${get_action_name}
            }
EOT;
        $phrase_1 = $objectTable->_translate( 'Are you sure you want to ' );
        $phrase_2 = $objectTable->_translate( ' items? (' );
        $phrase_3 = $objectTable->_translate( ' items selected)' );
        $no_act = $objectTable->_translate( 'No action selected.' );
        $no_item = $objectTable->_translate( 'No item selected.' );
        $disp_options = '';
        if ( $objectTable->list_options ) {
            $disp_options = $this->display_options( $objectTable );
        }
        $has_option = false;
        $extra_tablenav = '';
        $search_box = '';
        if ( $objectTable->_can_search ) {
            $search_box = $this->display_search_box( $objectTable, $search_label );
            $has_option = true;
        }
        $months_dropdown = '';
        if ( ( $objectTable->month_filter ) && ( $objectTable->date_col ) ) {
            $months_dropdown = $this->display_months_dropdown( $objectTable, $objectTable->date_col );
            $objectTable->custom_filter = $objectTable->custom_filter . $months_dropdown;
            $has_option = true;
            //$extra_tablenav .= $months_dropdown;
        }
        // $objectTable->extra_tablenav = $extra_tablenav;
        $insert_footer = $objectTable->_insert_footer();
        ?>
        <?php echo $message_block ?>
        <?php if ($_edit_screen): ?>
        <div class="wrap">
            <h1><?php echo $this->page_title ?></h1>
        <?php else: ?>
        <?php echo $disp_options ?>
        <div class="wrap">
            <h1><?php echo $this->page_title ?> <?php echo $create_button ?> <?php if ($search_text): ?><span class="subtitle"><?php echo $search_text?></span><?php endif; ?></h1>
        <?php endif; ?>
        <?php if ($_edit_screen): ?>
            <form id="edit-form" method="post">
                <input type="hidden" name="page" value="<?php echo $_page ?>" />
                <input type="hidden" name="action" value="save" />
                <input type="hidden" name="<?php echo $_table ?>" value="<?php echo $id ?>" />
                <?php wp_nonce_field() ?>
                <?php echo $_edit_html ?>
                <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $save_label ?>"  />
                </p>
            </form>
        <?php else: ?>
            <?php if ($has_option): ?>
            <form id="posts-filter" method="get">
            <?php echo $search_box ?>
            <?php echo $objectTable->display_custom_filter() ?>
            </form>
            <?php endif; ?>
            <form id="objects-filter" method="post">
                <input type="hidden" name="page" value="<?php echo $_page ?>" />
                <?php $objectTable->display() ?>
                <!--Bug?-->
                <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce() ?>" />
            </form>
            <script>
            if(jQuery('#bulk-action-selector-top').length){
                var offset_selector = jQuery('#bulk-action-selector-top').offset();
                var doaction_selector = jQuery('#doaction').offset();
                jQuery('#custom-filters').offset({ top: offset_selector.top + 4, left: doaction_selector.left + jQuery('#doaction').width() + 37 });
            } else {
                var posts_filter = jQuery('#posts-filter').offset();
                jQuery('#custom-filters').offset({ top: posts_filter.top + 42 });
            }
            if(jQuery('#bulk-action-selector-top').length){
                jQuery('#doaction').on('click',function(){
                    if ( jQuery('#bulk-action-selector-top').val() == -1 ) {
                        alert( '<?php echo $no_act ?>' );
                        return false;
                    }
                    var checkboxes = jQuery( 'input[type="checkbox"]' );
                    var item_selected = 0;
                    for ( var i = checkboxes.length; i--; ) {
                        if ( checkboxes[i].name == '<?php echo $_table ?>[]' ) {
                            if ( checkboxes[i].checked ) {
                                 item_selected++;
                            }
                        }
                    }
                    if (! item_selected ) {
                        alert( '<?php echo $no_item ?>' );
                        return false;
                    }
                    var action_name = jQuery('#bulk-action-selector-top').val();
                    if(! confirm( '<?php echo $phrase_1 ?>' + get_action_name( action_name ) + '<?php echo $phrase_2 ?>' + item_selected + '<?php echo $phrase_3 ?>' )){
                        return false;
                    }
                })
            };
<?php echo $get_action_name ?>
            jQuery('#bulk-action-selector-top').on('change',function(){
                jQuery('#bulk-action-selector-bottom').val(jQuery(this).val());
            });
            jQuery('#bulk-action-selector-bottom').on('change',function(){
                jQuery('#bulk-action-selector-top').val(jQuery(this).val());
            });
            </script>
        <?php endif; ?>
        </div>
<?php echo $insert_footer ?>
        <?php
    }
    function display_months_dropdown( $objectTable, $date_col ) {
        global $wpdb;
        $date_col = $wpdb->escape( $date_col );
        $table = $wpdb->prefix . $objectTable->_table;
        $sql = "
            SELECT DISTINCT YEAR( ${date_col} ) AS year, MONTH( ${date_col} ) AS month
            FROM $table
            ORDER BY ${date_col} DESC";
        $months = $wpdb->get_results( $sql );
        $month_count = count( $months );
        if ( !$month_count || ( 1 == $month_count && 0 == $months[0]->month ) )
            return;
        if( 'search' === $objectTable->current_action() ) {
            $m = isset( $_REQUEST[ 'm' ] ) ? (int) $_REQUEST[ 'm' ] : 0;
        }
        $all = $objectTable->_translate( 'All dates' );
        global $wp_locale;
        $label = $objectTable->_translate( 'Filter by date' );
        $dd = <<< EOT
            <label for="filter-by-date" class="screen-reader-text">${label}</label>
            <select name="m" id="filter-by-date">
            <option>${all}</option>
EOT;
        foreach ( $months as $arc_row ) {
            if ( 0 == $arc_row->year )
                continue;
            $month = zeroise( $arc_row->month, 2 );
            $year = $arc_row->year;
            $dd .= sprintf( "            <option %s value='%s'>%s</option>\n",
                selected( $m, $year . $month, false ),
                esc_attr( $arc_row->year . $month ),
                sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year )
            );
        }
        $dd .= "            </select>\n";
        return $dd;
    }
    function display_search_box( $objectTable, $search_label, $name = 's' ) {
        $_table = $objectTable->_table;
        $_page = "${_table}_list_objects";
        if( 'search' === $objectTable->current_action() ) {
            $q = esc_html( $_REQUEST[ 's' ] );
        }
        $box = <<< EOT
            <p class="search-box">
                <label class="screen-reader-text" for="${_table}-search-input">${search_label}:</label>
                <input type="hidden" name="page" value="${_page}" />
                <input type="hidden" name="action" value="search" />
                <input type="search" id="${_table}-search-input" name="${name}" value="${q}" />
                <input type="submit" id="search-submit" class="button" value="${search_label}"  />
            </p>
EOT;
        return $box;
    }
    function display_options( $objectTable ) {
        $this->display_months_dropdown( $objectTable, 'date' );
        $user_id = $objectTable->_user()->ID;
        $action = $objectTable->_table;
        $_page = "${action}_list_objects";
        $disp_option = get_option( "${_page}-disp_opt-${user_id}" );
        $disp_paging = get_option( "${_page}-paging-${user_id}" );
        if (! $disp_paging ) {
            $disp_paging = $objectTable->per_page;
        }
        $disp_paging = (int) $disp_paging; // or esc_html
        $disp_options = explode( ',', $disp_option );
        $column_defs = $objectTable->column_defs();
        $cb = '';
        if ( $objectTable->_title ) {
        $cb = sprintf( '<label style="color:gray"><input type="checkbox" name="disp-opt-%s" value="1" checked disabled>%s</label>',
            $objectTable->_title, 
            $objectTable->_translate( $objectTable->column_defs()[ $objectTable->_title ][ 'label' ] ) );
        }
        foreach ( $column_defs as $key => $params ) {
            if ( $key != $objectTable->_title ) {
                if ( isset( $params[ 'list' ] ) && ( $params[ 'list' ] ) ) {
                    $checked = '';
                    if ( $disp_option ) {
                        if ( in_array( $key, $disp_options ) ) {
                            $checked = ' checked ';
                        }
                    } else {
                        $checked = ' checked ';
                    }
                    $label = $objectTable->_translate( $params[ 'label' ] );
                    $cb .= sprintf( '<label><input type="checkbox" name="disp-opt-%s" value="1"%s>%s</label>', $key, $checked, $label );
                }
            }
        }
        $tab_label = $objectTable->_translate( 'Screen Options Tab' );
        $button_label = $objectTable->_translate( 'Screen Options' );
        $apply_label = $objectTable->_translate( 'Apply' );
        $_prefix = $objectTable->_table;
        $_page = "${_prefix}_list_objects";
        $pagination = $objectTable->_translate( 'Pagination' );
        $pagination_label = $objectTable->_translate( 'Number of items per page:' );
        $column_label = $objectTable->_translate( 'Columns' );
        $options = <<< EOT
<div id="screen-meta" class="metabox-prefs">
<div id="screen-options-wrap" class="hidden" tabindex="-1" aria-label="${tab_label}">
    <form id='adv-settings' method='post'>
    <input type="hidden" name="page" value="${_page}" />
    <input type="hidden" name="action" value="${_prefix}-apply-display-options" />
    <fieldset class="metabox-prefs">
    <legend>${column_label}</legend>
    ${cb}
    </fieldset>
    <fieldset class="metabox-prefs">
    <legend>${pagination}</legend>
    <label for="object_per_page">${pagination_label}</label>
    <input type="number" step="1" min="1" max="999" class="screen-per-page" name="${_prefix}-object-per-page"
                id="object_per_page" maxlength="3"
                value="${disp_paging}" />
    </fieldset>
    <p class="submit"><input type="submit" name="screen-options-apply" id="screen-options-apply" class="button button-primary" value="${apply_label}"  /></p>
    </form>
</div>
</div>
<div id="screen-meta-links">
    <div id="screen-options-link-wrap" class="hide-if-no-js screen-meta-toggle">
        <button type="button" id="show-settings-link" class="button show-settings" aria-controls="screen-options-wrap" aria-expanded="false">${button_label}</button>
    </div>
</div>
EOT;
        return $options;
    }
}
class ListBaseObject extends WP_List_Table {
    public $plugin_id       = 'ListBaseObject';
    protected $plugin_key   = 'listbaseobject';
    protected $version      = 0.1;
    public $singular        = 'Option';
    public $plural          = 'Options';
    public $_table          = 'options';
    public $_primary        = 'option_id';
    public $_title          = 'option_name';
    public $_display        = false;
    public $_can_search     = false;
    public $_can_edit       = false;
    public $list_options    = false;
    protected $_can_upgrade = false;
    public $menu_type       = 'object';
    public $menu_order      = 1;
    public $icon_url        = null; //'images/icon.png';

    protected $_filter      = null; // "post_type='post' AND post_status !='auto-draft'";
    public $_edit_screen    = false;
    public $last_query      = false;
    protected $_message     = '';
    public $_edit_html      = '';
    public $_page_message   = '';
    public $_error          = '';
    public $per_page        = 100;
    protected $str_len      = 45;
    public $textdomain      = 'list-base-object';
    public $permission      = 'activate_plugins';
    public $current_object  = '';
    public $extra_tablenav  = '';
    public $custom_filter   = '';
    public function __path() {
        return __FILE__;
    }
    public function __construct() {
        if ( $this->_can_upgrade ) {
            global $wpdb;
            $db_version = get_option( $this->plugin_key . '_version' );
            $table_name = $wpdb->prefix . $this->_table;
            $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" );
            if ( $table_exists && (! $db_version ) ) {
                wp_die( $this->_translate( 'The table %s is already in use.', $table_name ) );
            }
            if ( ( $db_version != $this->version ) 
            || ( $table_exists != $table_name ) ) {
                $scheme = $this->build_scheme();
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                dbDelta( $scheme );
                $this->last_query = $scheme;
                update_option( $this->plugin_key . '_version', $this->version );
                $this->_message = $this->_translate( 'The %s plugin (version %s) has been installed or upgraded.', array( $this->plugin_id, $this->version ) );
                add_action( 'admin_notices', array( $this, 'show_message' ) );
            }
        }
        global $status, $page;
        parent::__construct( array(
            'singular'  => $this->singular,
            'plural'    => $this->plural,
            'ajax'      => false
        ) );
    }
    function column_defs() {
        $columns = array(
            'option_id'     => array( 'label' => 'ID',
                                      'list' => false,
                                      'type' => 'integer',
                                      'property' => "bigint(20) unsigned NOT NULL auto_increment" ),
            'option_name'   => array( 'label' => 'Name',
                                      'list' => true,
                                      'search' => true,
                                      'edit'   => true,
                                      'type' => 'string',
                                      'property' => "varchar(191) NOT NULL default ''",
                                       ),
            'option_value'  => array( 'label' => 'Value',
                                      'list' => true,
                                      'search' => true,
                                      'edit'   => true,
                                      'type' => 'text',
                                      'property' => "longtext NOT NULL",
                                       ),
            'autoload'      => array( 'label' => 'Autoload',
                                      'list' => true,
                                      'edit'   => true,
                                      'type' => 'string',
                                      'property' => "varchar(20) NOT NULL default 'yes'",
                                       ),
            /*'post_author'  => array( 'label' => 'Author',
                                       'list' => true,
                                       'type' => 'object',
                                       'table' => 'users',
                                       'obj_col' => 'user_nicename' ),*/
        );
        return $columns;
    }
    public function display_custom_filter() {
        if ( $this->custom_filter ) {
            $label = $this->_translate( 'Filter' );
            echo "        <span id=\"custom-filters\">\n";
            echo '          ' . $this->custom_filter;
            echo "          <input type=\"submit\" name=\"_filter_action\" class=\"button\" value=\"${label}\" />";
            echo "        </span>\n";
        }
    }
    public function extra_tablenav( $which ) {
        echo $this->extra_tablenav;
    }
    function _user() {
        return wp_get_current_user();
    }
    function build_scheme() {
        global $wpdb;
        $column_defs = $this->column_defs();
        $table_name = $wpdb->prefix . $this->_table;
        $indexed = array();
        $columns = array();
        $primary_key = $this->_primary;
        $charset_collate = $wpdb->get_charset_collate();
        foreach ( $column_defs as $key => $params ) {
            if ( isset ( $params[ 'property' ] ) ) {
                $prop = $params[ 'property' ];
                if ( $prop ) {
                    if ( $key == $this->_primary ) {
                        $columns[] = "${key} ${prop} AUTO_INCREMENT";
                    } else {
                        $columns[] = "${key} ${prop}";
                    }
                    if ( isset ( $params[ 'indexed' ] ) ) {
                        if ( $params[ 'indexed' ] ) {
                            $indexed[] = "KEY ${key} (${key})";
                        }
                    }
                }
            }
        }
        $cols = implode( ",\n", $columns );
        if ( count( $indexed ) ) {
            $indexed = implode( ",\n", $indexed );
            $indexed = "\n," . $indexed;
        } else {
            $indexed = '';
        }
        $sql = "CREATE TABLE " . $table_name . " ( 
$cols,
PRIMARY KEY (${primary_key})${indexed}
) ${charset_collate};";
        return $sql;
    }
    function column_default( $item, $column_name, $no_trim = false ) {
        if ( $column_name == 'title' ) {
            $column_name = $this->_title;
        }
        $value = $item[ $column_name ];
        $cols = $this->column_defs();
        $args = $cols[ $column_name ];
        if ( $args[ 'type' ] == 'object' ) {
            global $wpdb;
            $table = $wpdb->prefix . $args[ 'table' ];
            $value = $wpdb->escape( $value );
            $key = 'ID';
            if ( isset( $args[ 'key' ] ) ) {
                $key = $args[ 'key' ];
            }
            $col = $args[ 'obj_col' ];
            $sql = "SELECT $col FROM $table WHERE ${key}=${value} LIMIT 1";
            $row = $wpdb->get_results( $sql );
            // $this->last_query = $sql;
            if ( is_array( $row ) ) {
                $row = $row[ 0 ];
                $value = $row->$col;
            }
        }
        if (! $no_trim ) {
            $value = $this->trim_to( $value );
        }
        return esc_html( $value );
    }
    function show_message() {
        $message = $this->_message;
        $html = <<< EOT
        <div id="message" class="updated fade">
          <p><strong>${message}</strong></p>
        </div>
EOT;
        echo $html;
    }
    function _get_textdomain( $phrase, $params = null ) {
        $textdomain = $this->textdomain;
        if ( is_string( $params ) ) {
            $check = sprintf( $phrase, 'dummy' );
            $comp  = sprintf( __( $phrase, $textdomain ), 'dummy' );
            if ( $check == $comp ) {
                $textdomain = null;
                if ( $this->textdomain != 'list-base-object' ) {
                    $textdomain = 'list-base-object';
                    if ( is_string( $params ) ) {
                        $check = sprintf( $phrase, 'dummy' );
                        $comp  = sprintf( __( $phrase, $textdomain ), 'dummy' );
                        if ( $check == $comp ) {
                            $textdomain = null;
                        }
                    }
                }
            }
        } else if ( is_array( $params ) ) {
            $_params = array();
            foreach( $params as $param ) {
                $_params[] = 'dummy';
            }
            $check = vsprintf( $phrase, $_params );
            $comp  = vsprintf( __( $phrase, $textdomain ), $_params );
            if ( $check == $comp ) {
                $textdomain = null;
                if ( $this->textdomain != 'list-base-object' ) {
                    $textdomain = 'list-base-object';
                    $_params = array();
                    foreach( $params as $param ) {
                        $_params[] = 'dummy';
                    }
                    $check = vsprintf( $phrase, $_params );
                    $comp  = vsprintf( __( $phrase, $textdomain ), $_params );
                    if ( $check == $comp ) {
                        $textdomain = null;
                    }
                }
            }
        } else {
            $comp = __( $phrase, $textdomain );
            if ( $comp == $phrase ) {
                $textdomain = null;
                if ( $this->textdomain != 'list-base-object' ) {
                    $textdomain = 'list-base-object';
                    $comp = __( $phrase, $textdomain );
                    if ( $comp == $phrase ) {
                        $textdomain = null;
                    }
                }
            }
        }
        return $textdomain;
    }
    function _translate( $phrase, $params = null ) {
        $textdomain = $this->_get_textdomain( $phrase, $params );
        if ( $textdomain ) {
            if ( is_string( $params ) ) {
                $new_phrase = sprintf( __( $phrase, $textdomain ), $params );
            } else if ( is_array( $params ) ) {
                $new_phrase = vsprintf( __( $phrase, $textdomain ), $params );
            } else {
                $new_phrase = __( $phrase, $textdomain );
            }
        } else {
            if ( is_string( $params ) ) {
                $new_phrase = sprintf( __( $phrase ), $params );
            } else if ( is_array( $params ) ) {
                $new_phrase = vsprintf( __( $phrase ), $params );
            } else {
                $new_phrase = __( $phrase );
            }
        }
        return $new_phrase;
    }
    function _query( $sql ) {
        global $wpdb;
        ob_start();
        $wpdb->show_errors();
        $res = $wpdb->query( $sql );
        $this->last_query = $sql;
        $wpdb->print_error();
        $msg = ob_get_contents();
        ob_end_clean();
        $msg = preg_replace( '/^.*?\[(.*?)\].*$/', '$1', $msg );
        return $msg;
    }
    function build_control( $name, $value, $label, $type ) {
        $html;
        if ( $type == 'string' ) {
            if ( $name == $this->_title ) {
                $html = '<input placeholder="' . $label.  '" type="text" id="title" name="' . $name .'" value="' . $value . '">';
                $html = "<div id=\"titlediv\" style=\"margin-top:10px;margin-bottom:10px\"><div id=\"titlediv\">${html}</div></div>";
            } else {
                $html = '<input id="' . $name . '" class="regular-text" type="text" name="' . $name .'" value="' . $value . '">';
            }
        } else if ( $type == 'text' ) {
            $html = '<textarea rows="5" class="large-text code" name="' . $name .'">' . $value . '</textarea>';
        } else if ( $type == 'datetime' ) {
            $html = '<input id="' . $name . '" class="regular-text" type="text" name="' . $name .'" value="' . $value . '">';
        } else if ( $type == 'object' ) {
            $html = $value;
        }
        if ( $name != $this->_title ) {
            $html = <<< EOT
    <table class="form-table">
      <tr class="${name}-wrap">
        <th><label for="${name}">${label}</label></th>
        <td>${html}</td>
      </tr>
    </table>
EOT;
        }
        return $html;
    }
    function column_title( $item ) {
        $action = $this->_table;
        $title = $this->trim_to( $item[ $this->_title ] );
        if (! $this->_can_edit ) {
            return $title;
        }
        $edit = $this->_translate( 'Edit' );
        $delete = $this->_translate( 'Delete' );
        $_nonce = wp_create_nonce();
        $_page = "${action}_list_objects";
        $phrase = $this->_translate( 'Are you sure you want to delete this object?' );
        $actions = array(
            'edit'      => sprintf( '<a href="?page=%s&action=%s&%s=%s&_wpnonce=%s">%s</a>', $_page, 'edit', $action, $item[ $this->_primary ], $_nonce, $edit),
            'delete'    => sprintf( '<a onclick="
                if (! confirm( \'%s\' ) ) {
                    return false;
                }
            " href="?page=%s&action=%s&%s=%s&_wpnonce=%s">%s</a>', $phrase, $_page, 'delete', $action, $item[ $this->_primary ], $_nonce, $delete),
        );
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            esc_html( $title ),
            $item[ $this->_primary ],
            $this->row_actions( $actions )
        );
    }
    function ts2db ( $ts ) {
        // YYYY-MM-DD HH:MM:SS
        $ts = preg_replace( '/[^0-9]/', '', $ts );
        if ( strlen( $ts ) <= 14 ) {
            $pad = 14 - strlen( $ts );
            for ( $count = 0; $count < $pad; $count++ ){
                $ts .= '0';
            }
        } else if ( strlen( $ts ) > 14 ) {
            $ts = substr( $ts, 0, 14 );
        }
        preg_match( '/^(\d\d\d\d)?(\d\d)?(\d\d)?(\d\d)?(\d\d)?(\d\d)?$/', $ts, $matches );
        list( $ALL, $Y, $M, $D, $h, $m, $s ) = $matches;
        return sprintf( "%04d-%02d-%02d %02d:%02d:%02d", $Y, $M, $D, $h, $m, $s );
    }
    function trim_to( $str, $max = 0 ) {
        if (! $max ) {
            $max = $this->str_len;
        }
        $len = mb_strlen( $str );
        if ( $max <= $len ) {
            $str = mb_strimwidth( $str, 0, $max ) . '...';
        }
        return $str;
    }
    function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
             $this->_table,
             $item[ $this->_primary ]
        );
    }
    function get_columns() {
        $columns = array(
            'cb' => '<input type="checkbox" />',
        );
        $cols = $this->column_defs();
        $user_id = $this->_user()->ID;
        $action = $this->_table;
        $_page = "${action}_list_objects";
        $disp_option = get_option( "${_page}-disp_opt-${user_id}" );
        $disp_options = explode( ',', $disp_option );
        foreach ( $cols as $key => $values ) {
            if ( isset( $values[ 'list' ] ) && ( $values[ 'list' ] ) ) {
                if ( $key == $this->_title ) {
                    $key = 'title';
                }
                if ( $key != 'title' ) {
                    if ( $disp_option ) {
                        if (! in_array( $key, $disp_options ) ) {
                            continue;
                        }
                    }
                }
                $columns[ $key ] = $this->_translate( $values[ 'label' ] );
            }
        }
        return $columns;
    }
    function get_sortable_columns() {
        $sortable_columns = array();
        $cols = $this->column_defs();
        foreach ( $cols as $key => $values ) {
            if ( $key == $this->_title ) {
                $key = 'title';
            }
            $sortable_columns[ $key ] = array( $key, false );
        }
        return $sortable_columns;
    }
    function get_bulk_actions() {
        if (! $this->_can_edit ) {
            return;
        }
        $actions = array(
            'delete' => $this->_translate( 'Delete' ),
        );
        return $actions;
    }
    function process_bulk_action() {
        global $wpdb;
        $table = $wpdb->prefix . $this->_table;
        $_primary = $this->_primary;
        $_edit_html = '';
        $sql = '';
        $cols = $this->column_defs();
        if( 'delete' === $this->current_action() ) {
            if (! $this->_can_edit ) {
                wp_die( $this->_translate( 'Invalid Request.' ) );
            }
            if ( (! check_admin_referer() )||(! wp_verify_nonce( $_REQUEST[ '_wpnonce' ] ) ) ) {
                wp_die( $this->_translate( 'Invalid Token.' ) );
            }
            $ids = $_REQUEST[ $this->_table ];
            if ( is_string( $ids ) ) {
                $ids = array( $ids );
            }
            $_ids;
            if ( is_array( $ids ) ) {
                foreach ( $ids as $id ) {
                    $_ids[] = (int) $id;
                }
            }
            if ( count( $_ids ) ) {
                $_ids = implode( ',', $_ids );
                $_sql = "SELECT * FROM ${table} WHERE ${_primary} IN ( ${_ids} )";
                $rows = $wpdb->get_results( $_sql );
                $sql = "DELETE FROM ${table} WHERE ${_primary} IN ( ${_ids} )";
                $message = $this->_query( $sql );
                $this->last_query = $sql;
                $params = array( 'query'    => $sql,
                                 'rows'     => $rows,
                                 'callback' => 'post_delete' );
                $this->_callback( $params );
                if ( $message ) {
                    $this->_page_message = $message;
                    $this->_error = true;
                } else {
                    $plural = $this->_translate( $this->plural );
                    $this->_page_message = $this->_translate( 'The selected %s has been deleted from the database.', $plural );
                }
            }
        } else if( 'search' === $this->current_action() ) {
            if (! $this->_can_search ) {
                wp_die( __( 'Invalid Request.', $this->textdomain ) );
            }
            $q = $_REQUEST[ 's' ];
            $this->search_objects( $q );
        } else if ( ( 'edit' === $this->current_action() ) 
                || ( 'save' === $this->current_action() ) ) {
            if (! $this->_can_edit ) {
                wp_die( __( 'Invalid Request.', $this->textdomain ) );
            }
            $this->_edit_screen = true;
            $id = $_REQUEST[ $this->_table ];
            if (! ctype_digit( $id ) ) {
                $id = null;
            }
            if ( 'save' === $this->current_action() ) {
                if ( (! check_admin_referer() )||(! wp_verify_nonce( $_REQUEST[ '_wpnonce' ] ) ) ) {
                    $message = $this->_translate( 'Invalid Token.' );
                    $this->_page_message = $message;
                    $this->_error = 1;
                    $notice_class = 'error';
                    $error = 1;
                } else {
                    // Save or Update
                    $formats = array();
                    $values  = array();
                    $fields  = array();
                    foreach( $cols as $key => $params ) {
                        $type = $params[ 'type' ];
                        if ( $key == $this->_primary ) {
                            continue;
                        }
                        if ( $key == 'title' ) {
                            $key = $this->_title;
                        }
                        $fields[] = $key;
                        $format = '%s';
                        $value = $_REQUEST[ $key ];
                        if ( $type == 'object' ) {
                            if ( isset( $params[ 'user_id' ] ) && ( $params[ 'user_id' ] ) ) {
                                $value = $this->_user()->ID;
                                $type = 'integer';
                            } else {
                                continue;
                            }
                        }
                        if ( ( $type == 'integer' ) || ( $type == 'boolean' ) ) {
                            $value = (int) $value;
                            $format = '%d';
                        } else if ( $type != 'object' ) {
                            $format = '%s';
                        }
                        if (! $format ) {
                            continue;
                        }
                        if ( ( $type == 'datetime' ) || ( $type == 'timestamp' ) ) {
                            if ( isset( $params[ 'created' ] ) && ( $params[ 'created' ] ) ) {
                                if (! $id ) {
                                    $value = date_i18n( "Y-m-d H:i:s" );
                                }
                            } else if ( isset( $params[ 'modifired' ] ) && ( $params[ 'modifired' ] ) ) {
                                $value = date_i18n( "Y-m-d H:i:s" );
                            }
                            $value = $this->ts2db( $value );
                        }
                        if ( is_null( $value ) ) {
                            $value = 'NULL';
                        }
                        $values[] = $value;
                        if (! $id ) {
                            $formats[] = $format;
                        } else {
                            $formats[] = "$key=$format";
                        }
                    }
                    $formats = implode( ', ', $formats );
                    $fields = implode( ', ', $fields );
                    if ( $id ) {
                        $sql = "UPDATE ${table} SET ${formats} WHERE ${_primary}=%d";
                        $values[] = $id;
                    } else {
                        $sql = "INSERT INTO ${table} (${fields}) VALUES (${formats})";
                    }
                    $sql = $wpdb->prepare( $sql, $values );
                    $message = $this->_query( $sql );
                    $this->last_query = $sql;
                    if ( $message ) {
                        $message = $this->_translate( 'Error occurred during saving %s. : %s', array( $singular, $message ) );
                        $this->_page_message = $message;
                        $this->_error = 1;
                        $error = 1;
                        $notice_class = 'error';
                    } else {
                        if (! $id ) {
                            $id = $wpdb->insert_id;
                        }
                        $message = $this->_translate( 'Your changes have been saved.' );
                        $this->_page_message = $message;
                    }
                }
            }
            if ( $id ) {
                $id = (int) $id;
                $col = $this->_primary;
                $sql = "SELECT * FROM ${table} WHERE $col=%d";
                $sql = $wpdb->prepare( $sql, $id );
                $row = $wpdb->get_results( $sql );
                if ( is_array( $row ) ) {
                    $this->current_object = $row;
                    if ( 'save' === $this->current_action() ) {
                        $params = array( 'query'    => $this->last_query,
                                         'rows'     => $row,
                                         'callback' => 'post_save' );
                        $this->_callback( $params );
                    }
                    $row = $row[ 0 ];
                    $this->current_object = $row;
                    $obj = (array) $row;
                    
                }
            }
            foreach( $cols as $key => $values ) {
                if (! $values[ 'edit' ] ) {
                    continue;
                }
                $value = '';
                if ( $obj ) {
                    $value = $this->column_default( $obj, $key, true );
                }
                if ( $key == 'title' ) {
                    $key = $this->_title;
                }
                if ( $error ) {
                    if ( isset( $_REQUEST[ $key ] ) ) {
                        $value = $_REQUEST[ $key ];
                    }
                }
                $label = $this->_translate( $values[ 'label' ] );
                $_edit_html .= $this->build_control( $key, $value, $label, $values[ 'type' ] );
            }
            $this->_edit_html = $_edit_html;
        }
    }
    function search_objects( $q ) {
        global $wpdb;
        $q = $wpdb->escape( $q );
        $phrases = preg_split('/[\s,]+/', $q);
        $cols = $this->column_defs();
        $queries = array();
        foreach( $cols as $key => $values ) {
            if ( $key == 'title' ) {
                $key = $this->_title;
            }
            if ( isset( $values[ 'search' ] ) && ( $values[ 'search' ] ) ) {
                $exp = array();
                foreach ( $phrases as $phrase ) {
                    $wpdb->escape( $phrase );
                    $exp[] = $key . " LIKE '%${phrase}%'";
                }
                // $queries[] = $key . " LIKE '%$q%'";
                $querie = implode( ' AND ', $exp );
                $queries[] = "( ${querie} )";
            }
        }
        $where = implode( ' OR ', $queries );
        if ( $where ) $where = "( ${where} )";
        if ( $filter = $this->_filter ) {
            if ( $where ) $where .= ' AND ';
            $where .= $filter;
        }
        if ( ( $this->month_filter ) && ( $this->date_col ) ) {
            $d = $this->date_col;
            $m = $_REQUEST[ 'm' ];
            $m = (int) $m;
            if ( $m ) {
                $ts = strtotime( $this->ts2db( $m . '01' ) );
                $start = date( 'Y-m-01', $ts );
                $start = $this->ts2db( $start . '00:00:00' );
                $end = date( 'Y-m-t', $ts );
                $end = $this->ts2db( $end . '23:59:59' );
                $where .= " AND ( $d >= '${start}' AND $d <= '${end}' ) ";
            }
        }
        $this->_filter = $where;
    }
    function build_query( $where = '' ) {
        if ( $this->_filter ) {
            $filter = $this->_filter;
            $where = " WHERE ${filter} " . $where;
        }
        return $where;
    }
    function order_by() {
        global $wpdb;
        $_OrderBy = '';
        if ( isset ( $_REQUEST[ 'orderby' ] ) ) {
            $orderby = $_REQUEST[ 'orderby' ];
            $orderby = $wpdb->escape( $orderby );
            if ( $orderby == 'title' ) {
                $orderby = $this->_title;
            }
        }
        if ( isset ( $_REQUEST[ 'order' ] ) ) {
            $order = $_REQUEST[ 'order' ];
            $order = $wpdb->escape( $order );
        }
        if ( $orderby ) {
            if (! $order ) $order = 'ASC';
            $order = strtoupper( $order );
            if ( ( $order != 'ASC' ) && ( $order != 'DESC' ) ) {
                $order = 'ASC';
            }
            $_OrderBy .= " ORDER BY ${orderby} ${order} ";
        }
        return $_OrderBy;
    }
    function prepare_items() {
        global $wpdb;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );
        $this->process_bulk_action();
        $offsetLimit = '';
        $whereOrderBy = $this->build_query();
        if ( isset ( $_REQUEST[ 'paged' ] ) ) {
            $paged = $_REQUEST[ 'paged' ];
        }
        $paged = (int) $paged;
        if (! $paged ) $paged = 1;
        $user_id = $this->_user()->ID;
        $_table = $this->_table;
        $_page = "${_table}_list_objects";
        $disp_paging = get_option( "${_page}-paging-${user_id}" );
        if ( $disp_paging ) {
            $this->per_page = $disp_paging;
        }
        $per_page = $this->per_page;
        $offsetLimit .= " LIMIT ${per_page} ";
        if ( $paged > 1 ) {
            $paged--;
            $paged *= $per_page;
            $offsetLimit .= " OFFSET $paged ";
        }
        $teble = $wpdb->prefix . $this->_table;
        $sql = "SELECT COUNT(*) FROM $teble ${whereOrderBy}";
        $count = $wpdb->get_results( $sql );
        $counts = ( array ) $count[ 0 ];
        foreach ( $counts as $key => $value ) {
            $count = $value;
        }
        $whereOrderBy .= $this->order_by();
        $sql = "SELECT * FROM $teble ${whereOrderBy} ${offsetLimit}";
        $rows = $wpdb->get_results( $sql );
        $this->last_query = $sql;
        $params = array( 'query'    => $sql,
                         'rows'     => $rows,
                         'callback' => 'post_load' );
        $this->_callback( $params );
        $data = array();
        if( $rows ){
            foreach ( $rows as $obj ) {
                $arr = (array) $obj;
                $data[] = $arr;
            }
        }
        $current_page = $this->get_pagenum();
        $total_items = $count;
        $this->items = $data;
        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items/$per_page )
        ) );
    }
    function _callback( &$params ) {
      /*
        $callback = $params[ 'callback' ];
        $query    = $params[ 'query' ];
        $rows     = $params[ 'rows' ];
        ob_start();
        var_dump( $params );
        $msg = ob_get_contents();
        ob_end_clean();
        $this->_debug( $msg );
      */
    }
    function _insert_footer() {
        // Do Some Actions or Set Style.
        $html = <<< EOT
EOT;
        return $html;
    }
    function _debug( $str ) {
        $this->_page_message = $this->_page_message . "<pre>${str}</pre>";
    }
}