<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SFPA_SearchClass {
    private $options;

    public function __construct(){
        $content_url = wp_upload_dir();
        $this->options = get_option( 'sf_pa_autosuggest_plugin_options' );
        if(!isset($this->options['show_cat'])){
            $this->options['show_cat'] = 0;
        }
        if(!isset($this->options['show_thumbnail'])){
            $this->options['show_thumbnail'] = 0;
        }
        if(!isset($this->options['show_price'])){
            $this->options['show_price'] = 0;
        }
        $this->count = 6;
        $this->content_url = $content_url['baseurl'];
        $this->results = array();
    }

    public function search($sf_pa_search){
        global $wpdb;

        $results = get_transient('sf_pa_'.$sf_pa_search);
        /* IF NOT CACHED */
        if(!$results){

            //Remove Stop words
            if(isset($this->options['stop_words']) && !empty($this->options['stop_words'])){
                $sf_pa_search = $this->remove_stop_words($sf_pa_search);
            }

            $prepare =  array($this->options['show_cat'], $this->options['show_thumbnail'], $this->options['show_price'], '%' . $wpdb->esc_like($sf_pa_search) .'%');
            $join = '';
            $where = '';

            //Check if site use wpml
            if (class_exists('SitePress')) {
                $lang = esc_sql(apply_filters( 'wpml_current_language', NULL ));
                $join = " LEFT JOIN ".$wpdb->prefix."icl_translations ON ".$wpdb->prefix."icl_translations.element_id = ".$wpdb->prefix."posts.ID AND element_type = 'post_product'";
                $where = " AND ".$wpdb->prefix."icl_translations.language_code = %s";
                $prepare[] = $lang;
            }

            // TODO: Join only if entity checkbox is true
            $sql = "SELECT ".$wpdb->prefix."posts.ID, ".$wpdb->prefix."posts.post_title as title, img_postmeta.meta_value as img_url, price_postmeta.meta_value as price,  ".$wpdb->prefix."terms.name as cat_title, %s as show_cat, %s as show_thumbnail, %s as show_price, 'product' AS type
                    FROM ".$wpdb->prefix."posts
                    LEFT JOIN ".$wpdb->prefix."postmeta ON ".$wpdb->prefix."postmeta.post_id =  ".$wpdb->prefix."posts.ID AND ".$wpdb->prefix."postmeta.meta_key = '_thumbnail_id'
                    LEFT JOIN ".$wpdb->prefix."postmeta as img_postmeta ON img_postmeta.post_id =  ".$wpdb->prefix."postmeta.meta_value AND img_postmeta.meta_key = '_wp_attachment_metadata'
                    LEFT JOIN ".$wpdb->prefix."postmeta as price_postmeta ON price_postmeta.post_id =  ".$wpdb->prefix."posts.ID AND price_postmeta.meta_key = '_price'
                    LEFT JOIN ".$wpdb->prefix."term_relationships ON ".$wpdb->prefix."term_relationships.object_id = ".$wpdb->prefix."posts.ID
                    LEFT JOIN ".$wpdb->prefix."terms ON ".$wpdb->prefix."terms.term_id = ".$wpdb->prefix."term_relationships.term_taxonomy_id
                    LEFT JOIN ".$wpdb->prefix."term_taxonomy ON ".$wpdb->prefix."term_taxonomy.term_id = ".$wpdb->prefix."terms.term_id
                    ".$join."
                    WHERE ".$wpdb->prefix."posts.post_title LIKE %s AND ".$wpdb->prefix."posts.post_type = 'product' AND ".$wpdb->prefix."posts.post_status = 'publish'
                    AND ".$wpdb->prefix."term_taxonomy.taxonomy = 'product_cat'". $where;
                    

            if(isset($this->options['exclude_ids']) && !empty($this->options['exclude_ids'])){
                $sql .=  "AND ".$wpdb->prefix."posts.id NOT IN (%s)";
                $prepare[] = $this->options['exclude_ids'];
            }

            if(isset($this->options['exclude_cat']) && !empty($this->options['exclude_cat'])){
                $sql .=  "AND ".$wpdb->prefix."terms.term_id NOT IN (%s)";
                $prepare[] = $this->options['exclude_cat'];
            }

            if(isset($this->options['results_count']) && !empty($this->options['results_count'])){
                $this->count = $this->options['results_count'];
            }
            $sql .= " GROUP BY ".$wpdb->prefix."posts.ID LIMIT %d";

            $prepare[] = $this->count;
            
            $results_prod = $wpdb->get_results($wpdb->prepare($sql, $prepare));

            $results_cat = array();
            if(isset($this->options['taxonomies_search']) && !empty($this->options['taxonomies_search']) && is_array($this->options['taxonomies_search'])){
                $results_cat = $this->get_taxonomies_search($sf_pa_search);
            }
            

            foreach($results_prod as $single_prod){
                $single_prod->img_url = $this->get_thumb_from_serialize($single_prod->img_url);
                $single_prod->title = esc_html($single_prod->title);
                $single_prod->cat_title = esc_html($single_prod->cat_title);
                $single_prod->link = esc_url($this->build_link($single_prod->type, $single_prod->ID));
                $single_prod->price = number_format((float)esc_html($single_prod->price), 2, '.', '').' '.get_woocommerce_currency_symbol();
                $this->results[] = $single_prod;
            }

            foreach($results_cat as $single_cat){
                $single_cat->name = esc_html($single_cat->title);
                $single_cat->link = esc_url($this->build_link($single_cat->type, false, $single_cat->taxonomy,$single_cat->slug ));
                $this->results[] = $single_cat;
            }

            //LIMIT ARRAY TO MAX COUNT
            if(is_int($this->count) && count($this->results) > $this->count){
                $this->results = array_slice($this->results, 0, $this->count);
            }

            $this->results = apply_filters('sf_pa_autosuggest_results', $this->results);
            //Save data in transient for 24h
            
            $transient_to_delete = get_transient('sf_pa_transient');
            if($transient_to_delete){
                $transient_to_delete[] = $sf_pa_search;
                set_transient('sf_pa_transient', $transient_to_delete);
            }
            else{
                $transient_to_delete = array($sf_pa_search);
                set_transient('sf_pa_transient', $transient_to_delete);
            }

            set_transient('sf_pa_'.$sf_pa_search, $this->results, 86400);
            
            
        }
        else {
            return $results;
        }
        
        
        return $this->results;
    }

    private function get_thumb_from_serialize($serialize_data){
        $data = maybe_unserialize($serialize_data);
        if($data){
            if (!empty( $data['sizes'] )) {
                $sizes = $data['sizes'];
                if(isset($sizes['thumbnail']['file']) && !empty($sizes['thumbnail']['file'])){
                    $file = $sizes['thumbnail']['file'];
                    if ( '.' !== $path = dirname( $data['file'] ) ){
                        $file = "$this->content_url/$path/$file";
                        return esc_url($file);
                    } 
                }
            }
            elseif(!empty( $data['file'] )){
                $file = $data['file'];
                $file = "$this->content_url/$file";
                return esc_url($file);
            }
        }
        return '';
    }

    private function get_taxonomies_search($sf_pa_search){
        global $wpdb;
        $prepare = $this->options['taxonomies_search'];
        $prepare[] = '%' . $wpdb->esc_like($sf_pa_search) .'%';
        $sql = "SELECT ".$wpdb->prefix."terms.name as title, ".$wpdb->prefix."terms.slug, ".$wpdb->prefix."term_taxonomy.taxonomy, 'taxonomy' AS type FROM ".$wpdb->prefix."terms
                INNER JOIN ".$wpdb->prefix."term_taxonomy ON ".$wpdb->prefix."terms.term_id =  ".$wpdb->prefix."term_taxonomy.term_id
                WHERE ".$wpdb->prefix."term_taxonomy.taxonomy IN (".implode(', ', array_fill(0, count($this->options['taxonomies_search']), '%s')).") AND ".$wpdb->prefix."terms.name LIKE %s AND ".$wpdb->prefix."term_taxonomy.count > 0";
        $results_cat = $wpdb->get_results($wpdb->prepare($sql, $prepare));
        return $results_cat;
    }

    private function build_link($type, $id, $taxonomy = false, $slug = false){
        if($type == 'product'){
            return get_site_url().'?p='.$id;
        }
        elseif('taxonomy'){
            return get_site_url().'?taxonomy='.$taxonomy.'&term='.$slug;
        }
    }

    private function remove_stop_words($search_words){
        $stop_words = explode('|', $this->options['stop_words']);
        

        foreach($stop_words as $stop_word){
            //If space is before and after
            $search_words = str_ireplace(' '.trim($stop_word).' ',' ',$search_words);
            //If space is after
            $search_words = str_ireplace(trim($stop_word).' ',' ',$search_words);
            //If space is before
            $search_words = str_ireplace(' '.trim($stop_word),' ',$search_words);
        }

        return $search_words;
        
    }
}