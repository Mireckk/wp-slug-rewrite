<?php

/**
 * Plugin Name:       Slug rewrite based on custom field
 * Plugin URI:        
 * Description:       Add custom field value to every posts slug
 * Version:           1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Mirek Michalski
 * Author URI:        https://montsoft.pl/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       slug-rewrite
 */




if ( !class_exists( 'MM_slug_rewrite' ) ) {
    class MM_slug_rewrite {
        
        static $instance = false;

        private function __construct() {
            // add_action('save_post', [ $this, 'my_custom_slug' ]);
            add_action('admin_menu', [ $this, 'submenuPage' ]);

            $this->customFieldName = 'id_oryginalne';
            $this->dividingString = '_more_';

            $this->counter = [
                'good' => 0,
                'fixed' => 0,
                'all' => 0,
                'problemUpdating' => 0,
                'noCustomField' => 0,
            ];
        }

        public function submenuPage() {
            $hookname = add_submenu_page(
                'tools.php',
                'Slug Rewrite',
                'Slug Rewrite',
                'manage_options',
                'mmslugrewrite',
                [ $this, 'mmslugrewriteOptionsPage' ]
            );

            // add_action( 'load-' . $hookname, [ $this, 'mmslugrewriteOptionsPage' ]);
        }

        public function mmslugrewriteOptionsPage() {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }
            ?>
            <div class="wrap">
                <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
                <?php

                if('POST' === $_SERVER['REQUEST_METHOD']) {
                    $this->loopThroughAllPosts();

                    echo 'Znalezionych postów: ' . $this->counter['all'];
                    ?><br><?php
                    echo 'Posty z poprawnym slugiem: ' . $this->counter['good'];
                    ?><br><?php
                    echo 'Zaktualizowanych slugów: ' . $this->counter['fixed'];
                    ?><br><?php
                    
                    if($this->counter['problemUpdating'] > 0)
                    echo 'Problem z aktualizacją sluga: ' . $this->counter['problemUpdating'];
                    ?><br><?php
                    
                    if($this->counter['noCustomField'] > 0)
                    echo 'Brak customowego pola: ' . $this->counter['noCustomField'];
                    ?><br><?php

                } 
                ?><br><?php
                ?><br><?php
                ?><br><?php
                ?><br><?php
                ini_set('max_execution_time', 0);
                echo 'max_execution_time: ' . ini_get('max_execution_time');
                ?><br><?php
                echo 'Upewnij się że powyższa wartość to 0, albo że jest bardzo duża.';

                ?>
                <form action="<?php menu_page_url( 'mmslugrewrite' ) ?>" method="post">
                    <input type="hidden" name="action" value="mmslugrewrite">
                    <?php
                    // output security fields for the registered setting "wporg_options"
                    // settings_fields( 'mmslugrewrite_options' );
                    // output setting sections and their fields
                    // (sections are registered for "wporg", each field is registered to a specific section)
                    // do_settings_sections( 'mmslugrewrite' );
                    // output save settings button
                    submit_button( __( 'Rewrite slugs', 'mmslugrewrite' ) );
                    
                    ?>
                </form>

            </div>
            <?php
        }

        public static function getInstance() {
            if ( !self::$instance )
                self::$instance = new self;
            return self::$instance;
        }


        public function loopThroughAllPosts() {
            $max = 500;
            $total = 50000; 

            for($i=0; $i<=$total; $i+=$max) {
                $posts = get_posts([ 'numberposts' => $max, 'offset' => $i, 'post_type' => ['infobus', 'inforail', 'infotram', 'infotrans', 'infoair', 'infobike', 'infoship', 'przetargi', 'interwencje'] ]);

                foreach ( $posts as $post ) {
                    $this->counter['all']++;

                    $customFieldValue = get_post_meta( $post->ID, $this->customFieldName, true );
                    $slug = $post->post_name;

                    if (isset($customFieldValue) && $customFieldValue != "") {
                        if(strpos($slug, $customFieldValue) !== false || strpos($slug, $this->dividingString) !== false) {
                            $this->counter['good']++;
                            continue;
                        } else {
                            $newSlug = $slug . $this->dividingString . $customFieldValue;
                            $updatedId = $this->updateSlug($post->ID, $newSlug);
                            if(!$updatedId) {
                                $this->counter['problemUpdating']++;
                            } else {
                                $this->counter['fixed']++;
                            }
                        }
                    } else {
                        $this->counter['noCustomField']++;
                    }
                }
            }
        }

        public function updateSlug($postId, $newSlug) {
            $newPost = [
                'ID' => $postId,
                'post_name' => $newSlug,
            ];

            return wp_update_post( $newPost );
        }
 
    }
    // end of class

    $MM_slug_rewrite = MM_slug_rewrite::getInstance();
    // MM_slug_rewrite::my_custom_slug();
}