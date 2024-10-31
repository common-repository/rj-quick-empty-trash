<?php

include_once('RJQET_LifeCycle.php');

class RJQET_Plugin extends RJQET_LifeCycle {

    /**
     * @return array of option meta data.
     */
    public function getOptionMetaData() {
        /*return array(
            //'_version' => array('Installed Version'), // Leave this one commented-out. Uncomment to test upgrades.
            'ATextInput' => array(__('Enter in some text', 'my-awesome-plugin')),
            'Donated' => array(__('I have donated to this plugin', 'my-awesome-plugin'), 'false', 'true'),
            'CanSeeSubmitData' => array(__('Can See Submission data', 'my-awesome-plugin'),
                                        'Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber', 'Anyone')
        );*/
    }

//    protected function getOptionValueI18nString($optionValue) {
//        $i18nValue = parent::getOptionValueI18nString($optionValue);
//        return $i18nValue;
//    }

    protected function initOptions() {
        $options = $this->getOptionMetaData();
        if (!empty($options)) {
            foreach ($options as $key => $arr) {
                if (is_array($arr) && count($arr > 1)) {
                    $this->addOption($key, $arr[1]);
                }
            }
        }
    }

    public function getPluginDisplayName() {
        return 'RJ Quick Empty Trash';
    }

    protected function getMainPluginFileName() {
        return 'rj-quick-empty-trash.php';
    }

    /**
     * Called by install() to create any database tables if needed.
     * Best Practice:
     * (1) Prefix all table names with $wpdb->prefix
     * (2) make table names lower case only
     * @return void
     */
    protected function installDatabaseTables() {
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("CREATE TABLE IF NOT EXISTS `$tableName` (
        //            `id` INTEGER NOT NULL");
    }

    /**
     * Drop plugin-created tables on uninstall.
     * @return void
     */
    protected function unInstallDatabaseTables() {
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("DROP TABLE IF EXISTS `$tableName`");
    }


    /**
     * Perform actions when upgrading from version X to version Y
     * @return void
     */
    public function upgrade() {
    }

    public function addActionsAndFilters() {

        // Add options administration page
        //add_action('admin_menu', array(&$this, 'addSettingsSubMenuPage'));

        // Add scripts
        add_action( 'admin_enqueue_scripts', array( &$this, 'rjquet_admin_enqueue' ) );

        // Register the AJAX
        add_action( 'wp_ajax_rjquet_custom_empty_trash_ajax', array( &$this, 'rjquet_custom_empty_trash_ajax' ) );
        add_action( 'wp_ajax_nopriv_rjquet_custom_empty_trash_ajax', array( &$this, 'rjquet_custom_empty_trash_die_ajax' ) );


    }

    // Add the styles and scripts to the page
    public function rjquet_custom_empty_trash() {
        echo "
        <style>#rj_custom_empty_trash {border: none;background: none;padding: 0;margin: 0;-webkit-box-shadow: none;box-shadow: none;font-size: 13px;color: #ccc;} #rj_custom_empty_trash:hover,#rj_custom_empty_trash.rj_emptying {color: red;}</style>";
        echo "
        <script>
            jQuery(document).ready(function($) {
                $('#rj_custom_empty_trash').click(function() {
                    var t = $(this);
                    t.addClass('rj_emptying').text('Emptying Trash...');

                    var data = {
                        action: 'rjquet_custom_empty_trash_ajax',
                        type: t.data('pt')
                    };

                    $.ajax('".admin_url()."admin-ajax.php', {
                        type: 'POST',
                        data: data,
                        cache: false,
                        success: function (res) {
                            location.reload();
                        }
                    });
                });
            });
        </script>";
    }
    // Add the empty trash button to the comments page subsubsub
    public function rjquet_custom_empty_trash_filter_menu($views)
    {
        if ( array_key_exists( 'trash', $views ) )
        {
            if (get_current_screen()->id == 'edit-comments') {
                $type = 'comment';
            } else {
                $type = get_current_screen()->post_type;
            }
            $views['trash'] .= ' | &nbsp;<div id="rj_custom_empty_trash" class="button apply" data-pt="'.$type.'">Empty Trash</div> ';
        }
        return $views;
        die;
    }
    // Are they on the Comments All page?
    public function rjquet_admin_enqueue($hook_suffix)
    {
        if ($hook_suffix == 'edit.php' ||
            $hook_suffix == 'edit-comments.php') {
            add_action( 'admin_head', array( &$this, 'rjquet_custom_empty_trash' ) );
        }
        if ($hook_suffix == 'edit-comments.php') {
            add_filter( 'views_edit-comments', array( &$this, 'rjquet_custom_empty_trash_filter_menu' ) );
        }
        if ($hook_suffix == 'edit.php') {
            add_filter( 'views_edit-'.get_current_screen()->post_type, array( &$this, 'rjquet_custom_empty_trash_filter_menu' ) );
        }
    }

    // Set up the AJAX request to delete the items
    public function rjquet_custom_empty_trash_ajax_die()
    {
        die( 'Houston, we have a problem.' );
    }
    public function rjquet_custom_empty_trash_ajax()
    {
        if ($_POST['type'] == 'comment') {
            // Empty Trash for Comments
            $comments = get_comments('status=trash');
            foreach($comments as $comment) :
                wp_delete_comment( $comment->comment_ID, true );
            endforeach;

            die( 'Emptied comment trash' );
        }

        $args = array(
            'post_status' => 'trash',
            'numberposts' => -1,
            'post_type' => $_POST['type']
        );

        $trash = get_posts($args);
        foreach($trash as $post) {
            wp_delete_post($post->ID, $bypass_trash = true);
        }

        die('Emptied trash for '.$_POST['type']);
    }

}
