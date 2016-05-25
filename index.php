<?php
/*
Plugin Name: BuddyPress Profile Link
Description: Insert a buddypress profile link into a navigation menu (use settings to change the default menu)
Author:      ole1986
Author URI:  https://profiles.wordpress.org/ole1986
*/

class BuddyPressProfileLink {
    public static $MENUES = ['primary_navigation', 'secondary_navigation', 'mobile_navigation', 'topbar_navigation', 'footer_navigation'];
    private $defaultMenu = 'topbar_navigation';
    private $selectedMenu = '';
    
    private $showLogoutMenuItem = false;
    
    public function __construct(){
        $this->loadOptions();
        // filter on the selected wordpress (frontend) menu to add the additional items
        add_filter( 'wp_nav_menu_items', array(&$this, 'buddypress_profile_menu_item'), 10, 2 );
        // create custom plugin settings menu
        add_action('admin_menu', array(&$this, 'buddypress_create_settings_menu'));
    }
    
    protected function loadOptions(){
        $this->selectedMenu = get_option('bpplink_opt_menu_name', $this->defaultMenu);
        $this->showLogoutMenuItem = get_option('bpplink_opt_show_logout', false);
    }
    
    /**
     * FILTER: display buddypress profile and logout link in the selected navigation menu defined in settings
     */
    public function buddypress_profile_menu_item ( $items, $args ) {       
        if ($args->theme_location == $this->selectedMenu) {
            $items .= '<li><a href="' . bp_loggedin_user_domain( '/' ) . '">' . wp_get_current_user()->user_login .'</a></li>';
            if($this->showLogoutMenuItem)
                $items .= '<li><a href="' . wp_logout_url( ) . '">Logout</a></li>';
        }
        return $items;
    }
    
    /**
     * ACTION: Settings menu to configure the frontend menu name
     */
    public function buddypress_create_settings_menu() {
        //create new top-level menu
        add_options_page('Buddypress Profile Link', 'Budypress Profile Link', 'manage_options', __FILE__, array(&$this,'buddypress_profile_link_settings_page')  );

        //call register settings function
        if ( is_admin() ) {
            add_action( 'admin_init', array(&$this, 'register_buddypress_profile_link_settings') );
        }
    }
    
    private function onSaveSettings(){
        if (strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') return;
        
        // option on where to display the profile link
        if(isset($_POST['bpplink_opt_menu_name']) && $_POST['bpplink_opt_menu_name'] != $this->defaultMenu) {
            $this->selectedMenu = ($_POST['bpplink_opt_menu_name']);
            update_option('bpplink_opt_menu_name', $this->selectedMenu);
        } else {
            delete_option('bpplink_opt_menu_name');
            $this->selectedMenu = $this->defaultMenu;
        }
        
        // option to either includ eor exclude the logout menu entry
        if(isset($_POST['bpplink_opt_show_logout']) && $_POST['bpplink_opt_show_logout'] == "1") {
            $this->showLogoutMenuItem = true;
            update_option('bpplink_opt_show_logout', $this->showLogoutMenuItem);
        } else {
            delete_option('bpplink_opt_show_logout');
            $this->showLogoutMenuItem = false;
        }
    }
    
    /**
     * ACTION: register the setting options
     */
    public function register_buddypress_profile_link_settings(){        
        $this->onSaveSettings();
        
        // register menu name option
        register_setting( 'buddypress-profile-link-settings', 'bpplink_opt_menu_name' );
        // register display logout option
        register_setting( 'buddypress-profile-link-settings', 'bpplink_opt_show_logout' );
        
    }

    public function buddypress_profile_link_settings_page() {
    ?>
    <div class="wrap">
    <script>
        jQuery(function(){
            jQuery('.bpplink_radio').click(function(){
                var v = jQuery(this).val();
                jQuery('#bpplink_opt_menu_name').val(v);
            });
        });
    </script>
    <h2>Buddypress Profile Link Settings</h2>
    <form method="post" action="">
        <?php settings_fields( 'buddypress-profile-link-settings' ); ?>
        <?php do_settings_sections( 'buddypress-profile-link-settings' ); ?>
        <p>Enter the below navigation menu where to display the profile link. Example can be topbar_navigation (default) or primary_navigation, ...</p>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Menu Name</th>
                <td>
                    <?php foreach(BuddyPressProfileLink::$MENUES as $v) { ?>
                    <div style="margin-bottom: 0.5em;">
                        <input type="radio" class="bpplink_radio" name="bpplink_radio" id="bpplink_radio_<?php echo $v ?>" value="<?php echo $v; ?>" <?php echo ($this->selectedMenu == $v)?'checked':'' ?> />
                        <label for="bpplink_radio_<?php echo $v; ?>"><?php echo $v; ?></label>
                    </div>
                    <?php } ?>
                    <input type="hidden" id="bpplink_opt_menu_name" name="bpplink_opt_menu_name" value="<?php echo $this->selectedMenu ?>" />
                </td>              
            </tr>
            <tr valign="top">
                <th scope="row">Show Logout</th>
                <td>
                    <input type="checkbox" class="bpplink_chk" name="bpplink_opt_show_logout" value="1" <?php echo ($this->showLogoutMenuItem)?'checked':'' ?> />
                </td>              
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
    </div>
    <?php }

}

new BuddyPressProfileLink();