<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SFPA_ResultPageClass {
    private $options;

    public function __construct(){
        $this->options = get_option( 'sf_pa_autosuggest_plugin_options' );
        if(isset($this->options['filter_in_results_page']) && $this->options['filter_in_results_page'] == 'on'){
            add_filter('pre_get_posts', array($this, 'filter_results_data'));
        }
    }

    public function filter_results_data($query){
        if ( ( $query->is_search() || $query->is_main_query() ) && isset($_GET['s']) && isset( $_GET['sf_search'] ) && $_GET['sf_search'] == "true") {
            $search = new SFPA_SearchClass;
            $products = $search->search(sanitize_text_field($_GET['s']));
            foreach($products as $product){
                if(isset($product->ID))
                    $posts_id[] =  $product->ID;
            }
            $query->set( 'post__in', $posts_id );
        }
    }
}

new SFPA_ResultPageClass();