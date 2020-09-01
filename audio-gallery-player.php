<?php
/*
Plugin Name: Audio Gallery Player
Description: Audio Gallery Player Plugin is that display the LFM Audio categores and LFM Audios as several types for LFM Audio Website.
Version: 1.0
Author: LFMPlugins
Text Domain: audio-gallery-player
*/

if ( ! defined('ABSPATH')) exit;  // if direct access 

/**
* Custom Field For LFMAudio Item - order
**/

// Add new term meta to Add term page

add_action( 'add_meta_boxes', 'lfm_add_meta_boxes' );

function lfm_add_meta_boxes() {
    $post_types = get_post_types();
    foreach( $post_types as $ptype ) {
        if ( $ptype == 'lfmaudio') {
            add_meta_box( 'lfm-selector', 'Order of Audios', 'lfm_meta_box', $ptype, 'side', 'core' );
        }
    }
}

function lfm_meta_box( $post ) {
    $post_meta = get_post_meta( $post->ID );

    if (isset($post_meta['order'])) {
        $order = $post_meta['order'][0];
    } else {
        $order = 9999;
    }
    // Template Selector
    echo "<input id='order' name='order' value='" . $order . "' />";
}

add_action( 'save_post' , 'lfm_save_post_meta_order' );

function lfm_save_post_meta_order( $post_id ) {

    if ( isset( $_REQUEST['order'] ) ) 
    {
        update_post_meta( $post_id, 'order', $_REQUEST['order'] );
    }
    else
    {
        update_post_meta( $post_id, 'order', 9999 );
    }
}

add_action( 'wp_enqueue_scripts', 'lfm_audio_player_enqueue_scripts' );

/**
* Audio Gallery Player enqueue scripts
*/
if (!function_exists('lfm_audio_player_enqueue_scripts')) {
    function lfm_audio_player_enqueue_scripts( $hook ) {

        wp_register_script(
            'lfmaudio-audio-gallery', 
            plugins_url( '/js/audio-gallery.js', __FILE__ ), 
            array('wave-suffer', 'jquery'), 
            '', 
            true
        );

        wp_localize_script( 
            'lfmaudio-audio-gallery', 
            'ajax_posts', 
            array('ajaxurl' => admin_url( 'admin-ajax.php'))
        );

        wp_enqueue_script( 'lfmaudio-audio-gallery' );

        wp_enqueue_style( 
            'lfm-audio-gallery-css', 
            plugins_url( '/css/audio-gallery.css', __FILE__ ), 
            array(), 
            '', 
            'all' 
        );

        wp_enqueue_script(
            'wave-suffer', 
            'https://unpkg.com/wavesurfer.js'
        );

        wp_enqueue_script(
            'audio-player', 
            plugins_url( '/js/audio-player.js', __FILE__ ), 
            array('wave-suffer'), 
            false, 
            true
        );

        wp_enqueue_style( 
            'lfm-audio-player-css', 
            plugins_url( '/css/audio-player.css', __FILE__ ), 
            array(), 
            '', 
            'all' 
        );
    }
}


/**
 * Shortcode to Create LFM Audio Root Category nav and LFM Audio Sub Categories and Audios Container
 * @param shortcode attribute - parent_cats , extra_layouts
 *                                          parent_cats = slugs of the root categories to be show in the nav bar
 *                                          extra_layouts = slugs of the root categories to be show in extra layouts
 * @return LFM Audio Gallery HTML content
 */
if (! function_exists('lfmaudio_audio_gallery')):
    function lfmaudio_audio_gallery($attr, $content) {
        $attr = shortcode_atts(array(
                'parent_cats'   => array(), // slugs of the root categories to be show in the nav bar
                'extra_layouts' => array(), // slugs of the root categories to be show in extra layouts

            ), $attr);

        $parents = array();

        if ($attr['parent_cats']) {
            $parents = explode(',', $attr['parent_cats']);
        }

        $extra_layouts = array();

        if ($attr['extra_layouts']) {
            $extra_layouts = explode(',', $attr['extra_layouts']);
        }

        $args = array(
            'taxonomy'      => 'lfmaudio_category',
            'hide_empty'    => false,
            'orderby'       => 'id',
            'order'         => 'asc'
        );

        /**
         * If parent_cats is empty
         *          show all root categories
         * Else
         *          show root categories in parent_cats
         */

        if (empty( $parents ) ) {
            $args['parent']     = 0;
        } else {
            $args['slug']       = $parents;
        }

        $categories = get_categories( $args );

        $parent_id      = 0;
        $parent_label   = '';


        /**
         * If there are root categories to show 
         *             first root category is set as default category to show 
         */

        if ( !empty( $categories ) ) {
            $parent_id      = $categories[0]->term_id;
            $parent_label   = $categories[0]->name;
        }

        $nav_items = '';

        foreach( $categories as $key => $category) {
            $active_class = '';
            if ($key == 0) {
                $active_class = 'active';
            }
            $extra_mode = '';
            if (in_array($category->slug, $extra_layouts)) {
                $extra_mode = '1';
            }
            $nav_items .= "<li class='$active_class'><a href='javascript:void(0)' data-cat='$category->name' data-cat-id='$category->term_id' data-extra-mode='$extra_mode'>$category->name</a></li>";
        }

        $out = '<div class="category-navbar"><ul>' . $nav_items . '</ul></div>';
        $out .= '<div class="audio-categories">';
        $out .= '<div class="current-cat"><a href="javascript:void(0)" data-init-cat="' . $parent_label . '" data-init-cat-id="' . $parent_id . '" data-parents="" data-parent-ids=""><span>&#x23;</span>Back</a><h3></h3></div>';
        $out .= '<div class="lds-dual-ring"></div>';
        $out .= '<div class="wrapper">';
        $out .= lfmaudio_expand_category($parent_id); // Show sub categories as grid
        $out .= '</div></div>';
        return $content . $out;
    }
endif;

add_shortcode( 'audio_items_grid', 'lfmaudio_audio_gallery' );

/**
 * Expand sub category according to parent category id
 * @param number    $cat_id             parent category id.
 * @param number    $page_number        the number of page to show.
 * @param number    $page_size          the number of item to show once.
 * @param number    $show_mode          the show mode - "0" or "1"
 * @return HTML content
 */

function lfmaudio_expand_category($cat_id, $page_number = 1, $page_size = 12, $show_mode = '') {
    ob_start();

    /*
     * If show mode is normal
     */

    if ($show_mode == 0) {
        $categories = get_categories( array(
            'taxonomy'      => 'lfmaudio_category',
            'hide_empty'    => false,
            'orderby'       => 'name',
            'parent'        => $cat_id
        ) );

        $sub_cats_cnt = count($categories);

        foreach ($categories as $category) { 
            if (function_exists('z_taxonomy_image_url')) {
                // Call API functions defined in 'Categories Images' Plugin
                $category_img_url = z_taxonomy_image_url($category->term_id, 'full', true);
            }
            if (empty($category_img_url)) {
                $category_img_url = plugins_url( '/default-image.png', __FILE__ );
            }
            ?>
            <a href="javascript:void(0)" class="expand-category" data-term="<?php echo $category->name ?>" data-term-id="<?php echo $category->term_id?>">
                <div class="category-item">
                    <div class="category-image-wrap">
                        <img src="<?php echo $category_img_url ?>">
                    </div>
                    <h3 class="category-name"><span class="right-arrow">&#x24;</span><span><?php echo $category->name ?><span></h3>
                    </div>
                </a>
            <?php }
            if ($sub_cats_cnt == 0) {
                $args = array(
                    'post_type' => 'lfmaudio',
                    'tax_query' => array(
                        array(
                            'taxonomy'  => 'lfmaudio_category',
                            'field'     => 'term_id',
                            'terms'     => $cat_id
                        )
                    ),
                    'orderby'           => 'meta_value',
                    'meta_key'          => 'order',
                    'order'             => 'ASC'
                );
                $loop = new WP_Query( $args );
                $total_posts = $loop->post_count;

                $args = array(
                    'post_type'         => 'lfmaudio',
                    'paged'             => $page_number,
                    'posts_per_page'    => $page_size,
                    'tax_query'         => array(
                        array(
                            'taxonomy'      => 'lfmaudio_category',
                            'field'         => 'term_id',
                            'terms'         => $cat_id
                        )
                    ),
                    'orderby'           => 'meta_value',
                    'meta_key'          => 'order',
                    'order'             => 'ASC'
                );
                
                $loop = new WP_Query( $args );
                if ($loop->have_posts()) : while ($loop->have_posts()) : $loop->the_post();
                    $audio = get_posts( array('post_parent' => get_the_ID(), 'post_type' => 'attachment', 'post_mime_type' => 'audio' ) );

                    $audio_img_url = get_the_post_thumbnail_url();
                    if (empty($audio_img_url)) {
                        $audio_img_url = "https://dev.lfmaudio.com/wp-content/uploads/2020/07/riverside-white.png";
                    }
                    ?>
                    <div class="audio-item">
                        <div class="audio-featured-img">
                            <img src="<?php echo $audio_img_url; ?>">
                            <div class="overlay">
                                <?php
                                $audio_src      = wp_get_attachment_url( $audio[0]->ID, 'full' );
                                $audio_post     = get_home_url();
                                $title          = htmlspecialchars(get_the_title());
                                $description    = htmlspecialchars(get_the_excerpt());
                                ?>
                                <a href="javascript:void(0)" class="audio-item-overlay audio-play-btn" data-audio-src="<?php echo $audio_src ?>" data-audio-post="<?php echo $audio_post ?>" data-audio-title="<?php echo $title ?>" data-audio-description="<?php echo $description ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="21.5" height="21.5" viewBox="0 0 21.5 21.5"><defs><style>.an{fill:none;stroke:#fff;stroke-linecap:round;stroke-linejoin:round;stroke-width:1.5px;}</style></defs><g transform="translate(-1.25 -1.25)"><circle class="an" cx="10" cy="10" r="10" transform="translate(2 2)"/><path class="an" d="M10,8l6,4-6,4Z"/></g></svg>
                                </a>
                            </div>
                        </div>
                        <div class="info">
                            <h4 class="title"><?php echo $title; ?></h4>
                            <p class="description"><?php echo $description; ?></p>
                        </div>
                    </div>
                    <?php
                endwhile;

                if ($total_posts > $page_size * $page_number):
                    ?>
                    <div class="show-more">
                        <a href="javascript:void(0)" class="show-more-button">SHOW MORE</a>
                    </div>
                    <?php      
                endif;
                wp_reset_postdata();
            endif;
        }
    }
    else if ($show_mode == 1) {

        $categories = get_categories( array(
            'taxonomy'      => 'lfmaudio_category',
            'hide_empty'    => false,
            'orderby'       => 'name',
            'parent'        => $cat_id
        ) );

        $sub_cats_cnt = 0;

        foreach ($categories as $category) {
            
            $sub_categories = get_categories( array(
                'taxonomy'      => 'lfmaudio_category',
                'hide_empty'    => false,
                'orderby'       => 'name',
                'parent'        => $category->term_id
            ) );

            $sub_cats_cnt += count($sub_categories);

        }
        ?>

        <div class="extra-layout">
            <div class="theme-wrapper">
                
        <?php
        foreach ($categories as $category) {

            if (function_exists('z_taxonomy_image_url')) {
                // Call API functions defined in 'Categories Images' Plugin
                $category_img_url = z_taxonomy_image_url($category->term_id, 'full', true);
            }
            if (empty($category_img_url)) {
                $category_img_url = plugins_url( '/default-image.png', __FILE__ );
            }

            if ($sub_cats_cnt):
            ?>
                <div class="theme-item expand-category" data-extra-mode="<?php echo $show_mode; ?>" data-term="<?php echo $category->name ?>" data-term-id="<?php echo $category->term_id?>">
                    <div class="theme-featured-img">
                        <img src="<?php echo $category_img_url; ?>">
                        <div class="overlay">
                            <?php
                            $description = htmlspecialchars(get_the_excerpt());
                            ?>
                            <a href="javascript:void(0)" class="theme-item-overlay theme-play-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="21.5" height="21.5" viewBox="0 0 21.5 21.5"><defs><style>.an{fill:none;stroke:#fff;stroke-linecap:round;stroke-linejoin:round;stroke-width:1.5px;}</style></defs><g transform="translate(-1.25 -1.25)"><circle class="an" cx="10" cy="10" r="10" transform="translate(2 2)"/><path class="an" d="M10,8l6,4-6,4Z"/></g></svg>
                            </a>
                        </div>
                    </div>
                    <div class="info">
                        <h4 class="title"><?php echo $category->name; ?></h4>
                        <p class="description">play all cats ></p>
                    </div>
                </div>
            <?php 

            else:

                            $args = array(
                                'post_type' => 'lfmaudio',
                                'tax_query' => array(
                                    array(
                                        'taxonomy'  => 'lfmaudio_category',
                                        'field'     => 'term_id',
                                        'terms'     => $category->term_id
                                    )
                                ),
                                'orderby'           => 'meta_value',
                                'meta_key'          => 'order',
                                'order'             => 'ASC'
                            );
                            $loop = new WP_Query( $args );

                ?>

                <div class="audio-theme-item">
                    <div class="audio-theme-info expand-category" data-term="<?php echo $category->name ?>" data-term-id="<?php echo $category->term_id?>">
                        <div class="theme-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><defs><style>.music-symbol{fill:#fff;}</style></defs><path class="music-symbol" d="M12,13.535V3h8V5H14V17a4,4,0,1,1-2-3.465ZM10,19a2,2,0,1,0-2-2A2,2,0,0,0,10,19Z"/></svg>
                        </div>
                        <div class="theme-name"><?php echo $category->name; ?></div>
                    </div>
                    <div class="audio-grid">

                        <?php
                            if ($loop->have_posts()) : while ($loop->have_posts()) : $loop->the_post();
                                $audio = get_posts( array('post_parent' => get_the_ID(), 'post_type' => 'attachment', 'post_mime_type' => 'audio' ) );

                                $title          = htmlspecialchars(get_the_title());
                                $audio_src      = wp_get_attachment_url( $audio[0]->ID, 'full' );
                                $audio_post     = get_home_url();
                                $description    = htmlspecialchars(get_the_excerpt());
                        ?>
                        <div class="theme-audio-item">                              
                            <a href="<?php echo $audio_src; ?>" rel="nofollow" class="audio-download" download>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><defs><style>.a1{opacity:0.6;transition:all .5s;}.b1{fill:none;}.c1{fill:#FFFFFF;}</style></defs><g class="a1"><path class="b1" d="M0,0H24V24H0Z"/><path class="c1" d="M11.617,9.12h4.371l-5.245,5.245L5.5,9.12H9.868V3h1.748ZM3.748,16.988H17.736v-6.12h1.748v6.994a.874.874,0,0,1-.874.874H2.874A.874.874,0,0,1,2,17.862V10.868H3.748Z" transform="translate(1.258 1.132)"/></g></svg>
                            </a>
                            <a href="javascript:void(0);" class="audio-play-btn" data-audio-src="<?php echo $audio_src ?>" data-audio-post="<?php echo $audio_post ?>" data-audio-title="<?php echo $title ?>" data-audio-description="<?php echo $description ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><defs><style>.an{fill:none;stroke:#FFFFFF;stroke-linecap:round;stroke-linejoin:round;stroke-width:1.5px;transition:all .5s;}</style></defs><g class="a1"><circle class="an anc" cx="8" cy="8" r="7" transform=""></circle><path class="an" d="M6,4l6,4-6,4Z"></path></g></svg>
                            </a>
                            <a href="javascript:void(0);" class="audio-name"><?php echo $title; ?></a>
                        </div>
                        <?php
                            endwhile;
                        endif;
                        wp_reset_postdata();
                        ?>
                    </div>
                </div>

            <?php
            
            endif;
        }
        ?>

            </div>
        </div>

        <?php
    }

    return ob_get_clean();
}


/**
 * Expand category from ajax
 */
function expand_category_ajax() {
    header("Content-Type: text/html");
    $args = $_REQUEST;
    $cat_id = $args['cat_id'];
    $page_number = $args['paged'];
    $page_size = $args['page_size'];
    $show_mode = $args['show_mode'];

    $out = lfmaudio_expand_category($cat_id, $page_number, $page_size, $show_mode);
    die($out);
}

add_action( 'wp_ajax_nopriv_expand_category_ajax', 'expand_category_ajax' );
add_action( 'wp_ajax_expand_category_ajax', 'expand_category_ajax' );

/**
 * Create Audio Gallery Box to show in Sung Jingle Pages 
 * @param   array       $attr       shortcode attributes 
 * @param   string      $content    HTML content to return
 * 
 */

if (! function_exists('lfmaudio_audio_box')):
    function lfmaudio_audio_box($attr, $content) {
        $attr = shortcode_atts(array(
                'parent_cats' => ''
            ), $attr);

        $parent = $attr['parent_cats'];
        $args = array(
            'taxonomy'      => 'lfmaudio_category',
            'hide_empty'    => false,
            'slug'          => $parent,
            'orderby'       => 'id',
            'order'         => 'asc'
        );

        $categories = get_categories( $args );

        if ( !empty( $categories ) ) {
            $parent_id      = $categories[0]->term_id;
            $parent_label   = $categories[0]->name;
        }

        $categories = get_categories( array(
            'taxonomy'      => 'lfmaudio_category',
            'hide_empty'    => false,
            'orderby'       => 'name',
            'parent'        => $parent_id
        ) );

        $nav_items = '  <div class="jingle-theme-container">
                        <div class="current-cat">
                            <a href="javascript:void(0)" data-init-cat="'. $parent_label .'" data-init-cat-id="'. $parent_id .'" data-parents="" data-parent-ids="" class="back-to-jingle-theme"><span>#</span>Back</a>
                            <h3></h3>
                        </div>
                        <div class="theme-wrapper"></div>
                        <div class="lds-dual-ring"></div>
                        <ul class="jingle-themes">';
        foreach( $categories as $key => $category) {
            $active_class = '';
            if ($key == 0) {
                $active_class = 'active';
            }
            $nav_items .= "<li class='$active_class'><a href='javascript:void(0)' class='expand-category' data-term='$category->name' data-term-id='$category->term_id' data-extra-mode='1'><div>$category->name</div></a></li>";
        }
        $nav_items .= ' </ul>
                        </div>';

        return $content . $nav_items;
    }

    add_shortcode( 'audio_items_box', 'lfmaudio_audio_box' );
endif;

/**
 * Expand jingle category from ajax in jingles page
 */
function expand_jingle_theme_ajax() {
    header("Content-Type: text/html");
    $args = $_REQUEST;
    $cat_id = $args['cat_id'];
    $page_number = $args['paged'];
    $page_size = $args['page_size'];
    $show_mode = $args['show_mode'];

    $out = lfmaudio_expand_category($cat_id, $page_number, $page_size, $show_mode);
    die($out);
}

add_action( 'wp_ajax_nopriv_expand_jingle_theme_ajax', 'expand_jingle_theme_ajax' );
add_action( 'wp_ajax_expand_jingle_theme_ajax', 'expand_jingle_theme_ajax' );


/**
 * Shortcode function to Create Audios List 
 * @param array     $category       If count of $category is one then display only audio list
 *                                  IF count of $category is more than one then display category nav and audio list
 * @shortcode sample [audio_items_list parent_cats=jingles,imaging posts_per_page=8]
 */

if (! function_exists('lfmaudio_audio_list')) :
    
    function lfmaudio_audio_list($attr, $content) {
        $attr = shortcode_atts(array(
                        'parent_cats'       => '',
                        'posts_per_page'    => 8
                    ), $attr);

        $parents = array();
        $page_size = $attr['posts_per_page'];

        if ($attr['parent_cats']) {
            $parents = explode(',', $attr['parent_cats']);
        }

        $args = array(
            'taxonomy'      => 'lfmaudio_category',
            'hide_empty'    => false,
            'orderby'       => 'id',
            'order'         => 'asc',
            'slug'          => $parents
        );

        $categories = get_categories( $args );

        $out = '';
        if (count($categories) == 1):

            $args = array(
                                'post_type'         => 'lfmaudio',
                                'posts_per_page'    => $page_size,
                                'tax_query'         => array(
                                    array(
                                        'taxonomy'      => 'lfmaudio_category',
                                        'field'         => 'term_id',
                                        'terms'         => $categories[0]->term_id
                                    )
                                ),
                                'orderby'           => 'meta_value',
                                'meta_key'          => 'order',
                                'order'             => 'ASC'
                            );
            $loop = new WP_Query( $args );

            $out = '<div class="audio-list">
                                <ul>';
            if ($loop->have_posts()) : while ($loop->have_posts()) : $loop->the_post();


                $audio_img_url = get_the_post_thumbnail_url();
                if (empty($audio_img_url)) {
                    $audio_img_url = "https://dev.lfmaudio.com/wp-content/uploads/2020/07/riverside-white.png";
                }
                $audio = get_posts( 
                                array(
                                    'post_parent'       => get_the_ID(), 
                                    'post_type'         => 'attachment', 
                                    'post_mime_type'    => 'audio' 
                                ) 
                            );

                $title = htmlspecialchars(get_the_title());
                $audio_src = wp_get_attachment_url( $audio[0]->ID, 'full' );
                $audio_post = get_home_url();
                $description = htmlspecialchars(get_the_excerpt());
                $out .= '   
                            <li>
                                <div>
                                    <div class="image-wraper">
                                        <img src="'. $audio_img_url .'">
                                        <div class="overlay">
                                            <a href="javascript:void(0)" class="audio-item-overlay audio-play-btn" data-audio-src="'. $audio_src .'" data-audio-post="'. $audio_post .'" data-audio-title="'. $title .'" data-audio-description="'. $description .'">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="21.5" height="21.5" viewBox="0 0 21.5 21.5"><defs><style>.a{fill:none;stroke:#fff;stroke-linecap:round;stroke-linejoin:round;stroke-width:1.5px;}</style></defs><g transform="translate(-1.25 -1.25)"><circle class="a" cx="10" cy="10" r="10" transform="translate(2 2)"></circle><path class="a" d="M10,8l6,4-6,4Z"></path></g></svg>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="text">
                                        <h4>
                                            '. $title .'
                                        </h4>
                                        <p>
                                            '. $description .'
                                        </p> 
                                    </div>
                                </div>
                            </li>';
                endwhile;
            endif;

            $out .= '   </ul>  
                    </div>';
        elseif (count($categories) > 1):

            $out .= '   <div class="audio-list-category-navbar">
                            <ul>';
            $audio_list = '';
            foreach ($categories as $key => $category) {

                $category_class = '';
                $audio_list_style = '';
                if ($key == 0)
                    $category_class = 'active';
                else
                    $audio_list_style = 'display: none;';

                $out .= '       <li class="' . $category_class . '">
                                    <a href="javascript:void(0)" data-cat="' . $category->slug . '" data-cat-id="' . $category->term_id . '">' . $category->name . '</a>
                                </li>';
                $args = array(
                                'post_type' => 'lfmaudio',
                                'posts_per_page' => $page_size,
                                'tax_query' => array(
                                    array(
                                        'taxonomy' => 'lfmaudio_category',
                                        'field' => 'term_id',
                                        'terms' => $category->term_id
                                    )
                                ),
                                'orderby'           => 'meta_value',
                                'meta_key'          => 'order',
                                'order'             => 'ASC'
                            );

                $loop = new WP_Query( $args );



                $audio_list .= '<div class="audio-list audio-list-' . $category->term_id . '" style="' . $audio_list_style . '">
                                    <ul>';
                if ($loop->have_posts()) : while ($loop->have_posts()) : $loop->the_post();


                    $audio_img_url = get_the_post_thumbnail_url();
                    if (empty($audio_img_url)) {
                        $audio_img_url = "https://dev.lfmaudio.com/wp-content/uploads/2020/07/riverside-white.png";
                    }
                    $audio = get_posts( array('post_parent' => get_the_ID(), 'post_type' => 'attachment', 'post_mime_type' => 'audio' ) );

                    $title = htmlspecialchars(get_the_title());
                    $audio_src = wp_get_attachment_url( $audio[0]->ID, 'full' );
                    $audio_post = get_home_url();
                    $description = htmlspecialchars(get_the_excerpt());
                    $audio_list .= '   
                                <li>
                                    <div>
                                        <div class="image-wraper">
                                            <img src="'. $audio_img_url .'">
                                            <div class="overlay">
                                                <a href="javascript:void(0)" class="audio-item-overlay audio-play-btn" data-audio-src="'. $audio_src .'" data-audio-post="'. $audio_post .'" data-audio-title="'. $title .'" data-audio-description="'. $description .'">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="21.5" height="21.5" viewBox="0 0 21.5 21.5"><defs><style>.a{fill:none;stroke:#fff;stroke-linecap:round;stroke-linejoin:round;stroke-width:1.5px;}</style></defs><g transform="translate(-1.25 -1.25)"><circle class="a" cx="10" cy="10" r="10" transform="translate(2 2)"></circle><path class="a" d="M10,8l6,4-6,4Z"></path></g></svg>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="text">
                                            <h4>
                                                '. $title .'
                                            </h4>
                                            <p>
                                                '. $description .'
                                            </p> 
                                        </div>
                                    </div>
                                </li>';
                    endwhile;
                endif;

                $audio_list .= '   </ul>  
                        </div>';
            }
            $out .= '       </ul>
                        </div>';

            $out .= $audio_list;
        endif;


        return $content . $out;
    }

    add_shortcode('audio_items_list', 'lfmaudio_audio_list');
endif;

/**
 * Audio Player in Footer
 */
if (! function_exists('output_audio_player')):
    function output_audio_player() {
        ?>
        <div id="audio-player" class="close">
            <div class="container">
                <a href="javascript:void(0)" class="audio-btn btn-play" onclick="audioPlayPause()">
                    <svg class="play-icon hide" xmlns="http://www.w3.org/2000/svg" width="15.2" height="19.6" viewBox="0 0 15.2 19.6">
                        <path id="Path_5324" data-name="Path 5324" d="M10,8l13.2,8.8L10,25.6Z" transform="translate(-9 -7)" fill="none" stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                    </svg>
                    <svg class="pause-icon" xmlns="http://www.w3.org/2000/svg" width="15.2" height="19.6" viewBox="0 0 15.2 19.6">
                        <path id="Path_5324" data-name="Path 5324" d="M12,8 L12,25 M20,8, L20,25Z" transform="translate(-9 -7)" fill="none" stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                    </svg>
                </a>
                <div class="meta-info">
                    <h5>LFM AUDIO</h5>
                    <p>usstream usradio...</p>
                </div>
                <div id="waveform"></div>
                <div class="timestamp"><span></span></div>
                <div class="actions">
                    <a href="javascript:void(0)" class="audio-btn btn-share">
                        <svg id="share-forward-2-line" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                            <path id="Path_5409" data-name="Path 5409" d="M0,0H24V24H0Z" fill="none"/>
                            <path id="Path_5410" data-name="Path 5410" d="M4,19H20V14h2v6a1,1,0,0,1-1,1H3a1,1,0,0,1-1-1V14H4ZM16.172,7l-3.95-3.95,1.414-1.414L20,8l-6.364,6.364L12.222,12.95,16.172,9H5V7Z" fill="#fff"/>
                        </svg>
                    </a>
                </div>
                <div class="share-box">
                    <h6>SHARE:</h6>
                    <div id="social-networks">
                        <a href="javascript:void(0)" class="share-button" id="facebook"><img alt="Facebook" src="/wp-content/uploads/2020/05/facebook-fill-1.png" width="24" height="24" style="margin-right: 20px"></a>
                        <a href="javascript:void(0)" class="share-button" id="instagram"><img alt="Instagram" src="/wp-content/uploads/2020/05/instagram-line-1.png" width="24" height="24" style="margin-right: 20px"></a>
                        <a href="javascript:void(0)" class="share-button" id="twitter"><img alt="Twitter" src="/wp-content/uploads/2020/05/twitter-line-1.png" width="24" height="24" style="margin-right: 20px"></a>
                        <a href="javascript:void(0)" class="share-button" id="soundcloud"><img alt="Soundcloud" src="/wp-content/uploads/2020/05/soundcloud-line.png" width="24" height="24"></a>
                    </div>
                    <div class="embed-input-box">
                        <input type="text" id="embed-input" name="embed-input" class="embed-input" />
                        <a href="javascript:void(0)" class="share-button copy-button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><defs><style>.share1,.share2{fill:none;}.share1{stroke:#fff;stroke-linecap:round;stroke-linejoin:round;stroke-width:1.5px;}</style></defs><g transform="translate(-1384 -700)"><g transform="translate(1386 702)"><rect class="share1" width="10" height="10" rx="2" transform="translate(8 8)"/><path class="share1" d="M4.406,12.426H3.6a1.6,1.6,0,0,1-1.6-1.6V3.6A1.6,1.6,0,0,1,3.6,2h7.218a1.6,1.6,0,0,1,1.6,1.6v.8"/></g><rect class="share2" width="24" height="24" transform="translate(1384 700)"/></g></svg>
                        </a>
                    </div>
                </div>
            </div>
            <div class="audio-player-controller">
                <a href="javascript:void(0)" class="audio-player-controller-button"></a>
            </div>
        </div>
        <?php
    }
endif;

add_action( 'wp_footer','output_audio_player' );

/**
 * Shortcode function to Create Single Audio Player.
 * @param   string      $audio_location         audio file location in internal and external site.
 * @shortcode sample            [audio_player audio_location=https://lfmaudio.com/sample.mp3]
 */
if (! function_exists('single_audio_player')):
    
    function single_audio_player($attr, $content) {
        $attr = shortcode_atts( array(
            'audio_location' => '', // audio file location
        ), $attr );

        if ($attr['audio_location']) {
            $audio_location = $attr['audio_location'];
        }

        $out = '';
        $out .= '<div class="audio-frame">  
                    <div class="play-button">
                        <a href="javascript: void(0);" class="play-action-button" data-src="' . $audio_location . '">
                            <svg class="play-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="18" viewBox="0 0 16 18">
                                <path id="Path_5324" data-name="Path 5324" d="M10,8 L22,15 L10,22 Z" transform="translate(-9 -7)" fill="none" stroke="#8132E8" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            <svg class="pause-icon hide" xmlns="http://www.w3.org/2000/svg" width="16" height="18" viewBox="0 0 16 18">
                                <path id="Path_5324" data-name="Path 5324" d="M10,6 L10,24 M18,6, L18,24Z" transform="translate(-9 -7)" fill="none" stroke="#8132E8" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                            </svg>
                        </a>
                    </div>
                    <div class="time-frame">
                        <div class="audio-wave" data-src="' . $audio_location . '">
                            <div class="current-time">
                                <span>00:00</span>
                            </div>
                        </div>
                        <div class="time-stamp">
                            <span>2:16</span>
                        </div>
                    </div>
                </div>';

        return $content . $out;
    }

    add_shortcode('audio_player', 'single_audio_player');
endif;

?>