<?php function create_cache_sitemap() {

    global $wpdb;
    global $sitepress;

    $urls = array();

    // Get homepage.
    $urls[] = get_site_url();

    // Get all pages.
    $page_ids = get_all_page_ids();

    foreach ($page_ids as $page_id) {
        $urls[] = get_permalink( $page_id );
    }

    $languages = array('en', 'nl', 'fr');
    $tax = array('product_cat','pa_designmadein', 'pa_material', 'pa_prod-colour');

    foreach( $languages as $language ) {
        $sitepress->switch_lang($language);
        
        $terms = get_terms( $tax , array(
            'hide_empty' => false,
        ) );

        // Add all terms to array
        foreach ($terms as $term) {
            $urls[] = get_term_link($term);
        }

    }

    foreach( $languages as $language ) {
        $sitepress->switch_lang($language);
        
        $terms = get_terms( 'product_tag' , array(
            'hide_empty' => false,
        ) );

        // Add all terms to array
        foreach ($terms as $term) {
            $key = get_term_meta( $term->term_id );
            if( $key['_featured_term'][0] == 1 ) {
                $urls[] = get_term_link($term);
            }
        }

    }

    $sitepress->switch_lang(ICL_LANGUAGE_CODE);

    // Get top 200 bestselling products.
    $args = array(
        'post_type' => 'product',
        'meta_key' => 'total_sales',
        'orderby' => 'meta_value_num',
        'posts_per_page' => 200
    );

    $query = new WP_Query( $args );
    $products = $query->posts;

    foreach($products as $product) {
        $urls[] = get_permalink( $product->ID );
    }

    // TODO Add all paginated pages.

    include  'tools/php-sitemap-generator/SitemapGenerator.php';
    // create object
    $sitemap = new SitemapGenerator(get_site_url(), get_home_path());
    // add urls

    add_action('admin_notices', function() use ( $urls ) {
        echo ' <div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"> 
            <p><strong>' . count( $urls ) . ' URLs added to cachemap</strong></p>
            <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
        </div>';   
    });


    foreach ($urls as $url) {
        $sitemap->addUrl($url);
    }

    // Change site map name
    $sitemap->sitemapFileName = 'cachemap.xml';
    // if (file_exists ( get_home_path() . 'cachemap.xml' ) ) {
    //     unlink (get_home_path() . 'cachemap.xml');
    // }
    
    // create sitemap
    $sitemap->createSitemap();
    // write sitemap as file
    $sitemap->writeSitemap();
    // update robots.txt file
    // $sitemap->updateRobots();
    // submit sitemaps to search engines
    //$sitemap->submitSitemap();

}

function display_cachemap_url_total($urls) {
    ?>

    <div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"> 
        <p><strong>' <?php echo count( $urls ) ?>  URLs added to cachemap</strong></p>
        <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
    </div>
 
    <?php

}

// create custom plugin settings menu
add_action('admin_menu', 'cachemap_create_menu');

function cachemap_create_menu() {

    add_options_page( 'CacheMap Settings', 'CacheMap', 'manage_options', 'cachemap', 'cachemap_settings_page' );

}

add_action( 'admin_init', 'register_cachemap_settings' );

function register_cachemap_settings() {
    //register our settings
    register_setting( 'cachemap-settings-group', 'cachemapgo' );
    if (isset($_GET['settings-updated']) && $_GET['page'] == 'cachemap' ) {
        create_cache_sitemap();
    }
}

function cachemap_settings_page() { ?>
    <div class="wrap">
    <h1>CacheMap</h1>
    
    <p>
        <?php 
        if (file_exists ( get_home_path() . 'cachemap.xml' ) ) {
            echo 'CacheMap exists and was created on ' . date('Y-m-d h:i:sa', filemtime ( get_home_path() . 'cachemap.xml' ));
        } else {
            echo 'CacheMap does not exist';
        }
        ?>
    </p>

    <form method="post" action="options.php">
        <?php settings_fields( 'cachemap-settings-group' ); ?>
        <?php do_settings_sections( 'cachemap-settings-group' ); ?>
        <input type="submit" name="submit" id="submit" class="button button-primary" value="Create CacheMap">
        <input type="hidden" name="create_cachemap" value="1234">
    </form>
    </div>
<?php }

