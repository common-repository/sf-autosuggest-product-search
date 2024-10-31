<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SFPA_AdminClass {
    public function __construct() {
        add_action( 'admin_init', [$this, 'register_settings'] );
        add_action( 'admin_menu', [$this, 'add_settings_page'] );
        add_action( 'wp_ajax_delete_sf_pa_transient', [$this, 'delete_sf_pa_transient'] );
        add_action( 'init', [$this, 'create_block_sf_search_block'] );
   }

   public function add_settings_page() {
        add_menu_page( 'SF Autosuggest Products', 'SF Autosuggest Products', 'manage_options', 'sf_pa-autosuggest-plugin', [$this, 'render_plugin_settings'], 'dashicons-search' );
    }
    
    public function render_plugin_settings() {
        ?>
        <div class="sf_pa-be-container">
            <h2 class="sf_pa_be-title"><?php _e('SF Autosuggest Products', 'sf_products_autosuggest'); ?></h2>
            <div class="sf_pa_shortcode-banner">
                <?php _e('To show input use this shortcode: <strong>[sf_pa_search]</strong>', 'sf_products_autosuggest'); ?>
            </div>
            <div class="sf_pa-row">
                <div class="sf_pa-col-7">
                    <form action="options.php" method="post">
                        <?php 
                        settings_fields( 'sf_pa_autosuggest_plugin_options' );
                        do_settings_sections( 'sf_pa_autosuggest_plugin' ); 
                        ?>
                        <h3><?php _e('Delete Transient', 'sf_products_autosuggest'); ?></h3>
                        <p><?php echo $this->count_sf_pa_transient(); ?> <?php _e('Searches saved in transients.', 'sf_products_autosuggest'); ?></p>
                        <p><?php _e('Delete all transients and remove cache data:', 'sf_products_autosuggest'); ?> <button type="button" id="sf_remove_transient"><?php _e('Remove Transient', 'sf_products_autosuggest'); ?></button>
                        </p>
                        <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save', 'sf_products_autosuggest'); ?>" />
                    </form>
                </div>
                <div class="sf_pa-col-5 sf_pa-image-example">
                    <img src="<?php echo untrailingslashit( plugin_dir_url( __FILE__ ) ).'/../assets/img/search-example.png'; ?>">
                    <p><i><?php _e('Search example image', 'sf_products_autosuggest'); ?></i></p>
                </div>
            </div>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting( 'sf_pa_autosuggest_plugin_options', 'sf_pa_autosuggest_plugin_options', [$this, 'plugin_options_validate'] );
        add_settings_section( 'sf_pa_apparance', __('Customize search input and autosuggest results', 'sf_products_autosuggest'), [$this, 'apparance_intro'], 'sf_pa_autosuggest_plugin' );
    
        $fields_array = array(
                            array(
                                'title' => __('Search results count', 'sf_products_autosuggest'),
                                'section' => 'sf_pa_apparance',
                                'key' => 'results_count',
                                'input' => 'text_input',
                                'instruction' => __('Maximum number of products to display', 'sf_products_autosuggest')
    
                            ),
                            array(
                                'title' => __('Search placeholder', 'sf_products_autosuggest'),
                                'section' => 'sf_pa_apparance',
                                'key' => 'input_placeholder',
                                'input' => 'text_input',
                                'instruction' => __('Text of input placeholder', 'sf_products_autosuggest')
                            ),
                            array(
                                'title' => __('Text "Show all results"', 'sf_products_autosuggest'),
                                'section' => 'sf_pa_apparance',
                                'key' => 'all_text',
                                'input' => 'text_input',
                                'instruction' => __('Text of the element "Show all results"', 'sf_products_autosuggest')
                            ),
                            array(
                                'title' => __('Backgorund color "Show all results"', 'sf_products_autosuggest'),
                                'section' => 'sf_pa_apparance',
                                'key' => 'all_bg',
                                'input' => 'color_picker',
                                'instruction' => __('Background color of the element "Show all results"', 'sf_products_autosuggest')
                            ),
                            array(
                                'title' => __('Text color "Show all results"', 'sf_products_autosuggest'),
                                'section' => 'sf_pa_apparance',
                                'key' => 'all_color',
                                'input' => 'color_picker',
                                'instruction' => __('Text color of the element "Show all results"', 'sf_products_autosuggest')
                            ),
                            array(
                                'title' => __('Text "Nothing Found"', 'sf_products_autosuggest'),
                                'section' => 'sf_pa_apparance',
                                'key' => 'not_found',
                                'input' => 'text_input',
                                'instruction' => __('Text of the element "Nothing Found"', 'sf_products_autosuggest')
                            ),
                            array(
                                'title' => __('Show thumbnails', 'sf_products_autosuggest'),
                                'section' => 'sf_pa_apparance',
                                'key' => 'show_thumbnail',
                                'input' => 'checkbox',
                                'instruction' => __('Check if you want to show thumbnails in results', 'sf_products_autosuggest')
                            ),
                            array(
                                'title' => __('Show categories', 'sf_products_autosuggest'),
                                'section' => 'sf_pa_apparance',
                                'key' => 'show_cat',
                                'input' => 'checkbox',
                                'instruction' => __('Check if you want to show product category in results', 'sf_products_autosuggest')
                            ),
                            array(
                                'title' => __('Show Price', 'sf_products_autosuggest'),
                                'section' => 'sf_pa_apparance',
                                'key' => 'show_price',
                                'input' => 'checkbox',
                                'instruction' => __('Check if you want to show product price in results', 'sf_products_autosuggest')
                            ),
                            array(
                                'title' => __('Exclude categories from search', 'sf_products_autosuggest'),
                                'section' => 'sf_pa_apparance',
                                'key' => 'exclude_cat',
                                'input' => 'text_input',
                                'instruction' => __('Exclude categories from search. Insert comma separated id of categories. For example "12,20"', 'sf_products_autosuggest')
                            ),
                            array(
                                'title' => __('Exclude products from search', 'sf_products_autosuggest'),
                                'section' => 'sf_pa_apparance',
                                'key' => 'exclude_ids',
                                'input' => 'text_input',
                                'instruction' => __('Exclude products from search. Insert comma separated id of products. For example "12,20"', 'sf_products_autosuggest')
                            ),
                            array(
                                'title' => __('Search for taxonomies', 'sf_products_autosuggest'),
                                'section' => 'sf_pa_apparance',
                                'key' => 'taxonomies_search',
                                'input' => 'taxonomies',
                                'instruction' => __('Select wich taxonomy want search.', 'sf_products_autosuggest')
                            ),
                            array(
                                'title' => __('Stop Words', 'sf_products_autosuggest'),
                                'section' => 'sf_pa_apparance',
                                'key' => 'stop_words',
                                'input' => 'textarea',
                                'instruction' => __('Words that should be ignored by the search. Write them divided by "|".', 'sf_products_autosuggest')
                            ),
                            array(
                                'title' => __('Use same filter in Results page', 'sf_products_autosuggest'),
                                'section' => 'sf_pa_apparance',
                                'key' => 'filter_in_results_page',
                                'input' => 'checkbox',
                                'instruction' => __('Check if you want use the same filter in results page', 'sf_products_autosuggest')
                            )
                        );
    
        foreach($fields_array as $single_field){
            add_settings_field( $single_field['section'].'_'.$single_field['key'], $single_field['title'], [$this, $single_field['input']], 'sf_pa_autosuggest_plugin', $single_field['section'], array('section' => $single_field['section'],'key' => $single_field['key'], 'instruction' => $single_field['instruction']) );
        }
    }

    public function apparance_intro() {
        return '';
    }
    
    public function text_input($params){
        $options = get_option( 'sf_pa_autosuggest_plugin_options' );
        $value = '';
        if (isset($options[$params['key']]))
            $value = esc_attr( $options[$params['key']]);
        
        echo "<input id='".$params['section']."_".$params['key']."' name='sf_pa_autosuggest_plugin_options[".$params['key']."]' type='text' value='" .$value. "' />";
        
        if(isset($params['instruction']) && !empty($params['instruction'])){
            echo '<p class="sf_pa-instruction">'.esc_html($params['instruction']).'</p>';
        }
    }

    public function textarea($params){
        $options = get_option( 'sf_pa_autosuggest_plugin_options' );
        $value = '';
        if (isset($options[$params['key']]))
            $value = esc_attr( $options[$params['key']]);
        
        echo "<textarea id='".$params['section']."_".$params['key']."' name='sf_pa_autosuggest_plugin_options[".$params['key']."]' type='text' />".$value."</textarea>";
        
        if(isset($params['instruction']) && !empty($params['instruction'])){
            echo '<p class="sf_pa-instruction">'.esc_html($params['instruction']).'</p>';
        }
    }
    
    public function color_picker($params){
        $options = get_option( 'sf_pa_autosuggest_plugin_options' );
        $value = '';
        if (isset($options[$params['key']]))
            $value = esc_attr( $options[$params['key']]);
        
        echo "<input id='".$params['section']."_".$params['key']."' class='sf_pa_color_picker' name='sf_pa_autosuggest_plugin_options[".$params['key']."]' type='text' value='" .$value. "' />";
        
        if(isset($params['instruction']) && !empty($params['instruction'])){
            echo '<p class="sf_pa-instruction">'.esc_html($params['instruction']).'</p>';
        }
    }
    
    public function checkbox($params){
        $options = get_option( 'sf_pa_autosuggest_plugin_options' );
        $value = '';
        if (isset($options[$params['key']]) && $options[$params['key']] =='on')
            $value = 'checked';
        
        echo "<input id='".$params['section']."_".$params['key']."' name='sf_pa_autosuggest_plugin_options[".$params['key']."]' type='checkbox' $value />";
        
        if(isset($params['instruction']) && !empty($params['instruction'])){
            echo '<p class="sf_pa-instruction">'.esc_html($params['instruction']).'</p>';
        }
    
    }

    public function taxonomies($params){
        $options = get_option( 'sf_pa_autosuggest_plugin_options' );
        $value = '';
        $tax_to_not_use = array('product_type', 'product_visibility', 'product_shipping_class');

        $taxonomies = get_object_taxonomies('product','objects');
        $html = '<select name="sf_pa_autosuggest_plugin_options['.$params["key"].'][]" multiple="multiple">
                    <option value="">-- None --</option>';
        foreach($taxonomies as $taxonomy){
            if(!in_array($taxonomy->name, $tax_to_not_use)){
                $selected = '';
                if(isset($options[$params['key']]) && in_array($taxonomy->name, $options[$params['key']])){
                    $selected = 'selected';
                }
                $html .= '<option value="'.$taxonomy->name.'" '.$selected.'>'.$taxonomy->label.'</option>';
            }
        }

        $html .= '</select>';
        echo $html;
    }
    
    public function plugin_options_validate( $input ) {
        $validator = new SFPA_ValidatorClass;
        $newinput['results_count'] = $validator->int($input['results_count'], 6);
        $newinput['input_placeholder'] = $validator->plaintext($input['input_placeholder']);
        $newinput['all_text'] =  $validator->plaintext($input['all_text']);
        $newinput['all_bg'] =  $validator->hexadecimal($input['all_bg'], '#000');
        $newinput['all_color'] =  $validator->hexadecimal($input['all_color'], '#fff');
        $newinput['not_found'] =  $validator->plaintext($input['not_found']);
        $newinput['stop_words'] =  $validator->plaintext($input['stop_words']);
        if(isset($input['show_thumbnail']) && !empty($input['show_thumbnail'])){
            $newinput['show_thumbnail'] = $validator->checkbox($input['show_thumbnail']);
        }
        if(isset($input['show_cat']) && !empty($input['show_cat'])){
            $newinput['show_cat'] = $validator->checkbox($input['show_cat']);
        }
        if(isset($input['show_price']) && !empty($input['show_price'])){
            $newinput['show_price'] = $validator->checkbox($input['show_price']);
        }
        if(isset($input['show_price']) && !empty($input['filter_in_results_page'])){
            $newinput['filter_in_results_page'] = $validator->checkbox($input['filter_in_results_page']);
        }
        $newinput['exclude_cat'] = $validator->ids($input['exclude_cat']);
        $newinput['exclude_ids'] =  $validator->ids($input['exclude_ids']);
        $newinput['taxonomies_search'] = array();
        if(isset($input['taxonomies_search']) && !empty($input['taxonomies_search'])){
            foreach($input['taxonomies_search'] as $single_tax){
                if( $single_tax != '')
                    $newinput['taxonomies_search'][] =  $validator->plaintext($single_tax);
            }
        }
    
        return $newinput;
    }
    
    private function count_sf_pa_transient(){
        $transient_to_delete = get_transient('sf_pa_transient');
        if($transient_to_delete){
            return count($transient_to_delete);
        }
        return 0;
    }

    public function delete_sf_pa_transient(){
        $transient_to_delete = get_transient('sf_pa_transient');
        if($transient_to_delete){
            foreach($transient_to_delete as $single_transient){
                delete_transient('sf_pa_'.$single_transient);
            }
        }
        delete_transient('sf_pa_transient');
        return 1;
    }

    function create_block_sf_search_block() {
        register_block_type( dirname(__FILE__) . '/../build' );
    }

}
new SFPA_AdminClass();