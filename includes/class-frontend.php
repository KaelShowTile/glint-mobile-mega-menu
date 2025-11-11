<?php
class Glint_Mobile_Menu_Frontend {
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('wp_footer', [$this, 'render_menu']); 
        add_shortcode('glint_mobile_menu_button', [$this, 'menu_button_shortcode']);
    }
    
    public function enqueue_frontend_assets() {
        wp_enqueue_style('glint-frontend-css', GLINT_MOBILE_MENU_URL . 'assets/css/frontend.css');
        wp_enqueue_script('glint-frontend-js', GLINT_MOBILE_MENU_URL . 'assets/js/frontend.js', ['jquery'], '1.0', true);
        
        wp_localize_script('glint-frontend-js', 'glintMenu', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('glint_menu_nonce')
        ]);
    }

    //shortcode render menu icon
    public function menu_button_shortcode($atts) {
        $atts = shortcode_atts([
            'class' => '',
            'style' => ''
        ], $atts);
        
        ob_start();
        ?>
        <div class="glint-menu-toggle-container <?php echo esc_attr($atts['class']); ?>" 
             style="<?php echo esc_attr($atts['style']); ?>">
            <div id="glint-menu-toggle">
                <div class="menu-icon">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function render_menu() {
        if (is_admin()) return;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'glint_mobile_mega_menu';
        
        $all_items = $wpdb->get_results("SELECT * FROM $table_name ORDER BY menu_order ASC");
        
        // structure the menu
        $menu_tree = [];
        foreach ($all_items as $item) {
            $menu_tree[$item->parent_id][] = $item;
        }
        
        ?>
        <div id="glint-mobile-menu">
            <div id="glint-menu-toggle">
                <div class="menu-icon">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
            
            <div class="glint-menu-overlay"></div>
            
            <div class="glint-menu-panels">
                <!-- top level menu -->
                <div class="menu-panel active" data-level="1" data-parent="0" id="submenu-panel-999999">
                    <div class="menu-back-button" style="display:none">
                        <span>← Back</span>
                    </div>
                    <ul class="menu-items">
                        <?php if (!empty($menu_tree[0])): ?>
                            <?php foreach ($menu_tree[0] as $item): ?>
                                <?php $this->render_menu_item($item); ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="no-menu-items"></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- sub menu -->
                <div class="submenu-panels">
                    <?php 
                    foreach ($all_items as $item) {
                        if (!empty($menu_tree[$item->id])) {
                            $this->render_submenu_panel('999999', $menu_tree, $item->id);
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_menu_item($item) {
        $has_children = $this->has_children($item->id);
        $child_class = $has_children ? 'has-children' : '';
        $content = do_shortcode(wp_kses_post($item->content));
        
        ?>
        <li class="menu-item <?= $child_class ?>" 
            data-id="<?= $item->id ?>" 
            data-level="<?= $item->level ?>">
            <?= $content ?>
        </li>
        <?php
    }

    private function render_submenu_panel($parent_id, $menu_tree, $current_id) {
        $children = $menu_tree[$current_id];
        $current_item = $this->get_menu_item($current_id);
        ?>
        <div class="menu-panel" 
             data-level="<?= $current_item->level + 1 ?>" 
             data-parent="<?= $parent_id ?>"
             id="submenu-panel-<?= $current_id ?>">
            <div class="menu-back-button">
                <span>← Back</span>
            </div>
            <div class="submenu-header">
                <h3><?= $current_item->content ?></h3>
            </div>
            <ul class="menu-items">
                <?php foreach ($children as $child): ?>
                    <?php 
                    $has_children = $this->has_children($child->id);
                    $child_class = $has_children ? 'has-children' : '';
                    $content = do_shortcode(wp_kses_post($child->content));

                    ?>
                    <li class="menu-item <?= $child_class ?>" 
                        data-id="<?= $child->id ?>" 
                        data-level="<?= $child->level ?>">
                        <?= $content ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }

    private function has_children($item_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'glint_mobile_mega_menu';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE parent_id = %d", $item_id
        )) > 0;
    }

    private function get_menu_item($item_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'glint_mobile_mega_menu';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d", $item_id
        ));
    }

    private function output_menu_button() {
        ?>
        <div id="glint-menu-toggle">
            <div class="menu-icon">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
        <?php
    }
    
}