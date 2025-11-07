<?php
class Glint_Mobile_Menu_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_glint_save_menu_item', [$this, 'ajax_save_menu_item']);
        add_action('wp_ajax_glint_update_menu_item', [$this, 'ajax_update_menu_item']); 
        add_action('wp_ajax_glint_delete_menu_item', [$this, 'ajax_delete_menu_item']);
        add_action('wp_ajax_glint_update_menu_order', [$this, 'ajax_update_menu_order']);
        add_action('wp_ajax_glint_get_menu_item', [$this, 'ajax_get_menu_item']); 
    }

    public function add_admin_menu() {
        add_menu_page(
            'Mobile Mega Menu Setting',
            'Mobile Mega Menu',
            'manage_options',
            'glint-mobile-menu',
            [$this, 'render_admin_page'],
            'dashicons-smartphone',
            99
        );
    }

    public function enqueue_admin_assets($hook) {
        if ($hook === 'toplevel_page_glint-mobile-menu') {
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('glint-admin-js', GLINT_MOBILE_MENU_URL . 'assets/js/admin.js', ['jquery', 'jquery-ui-sortable'], '1.0', true);
            wp_enqueue_style('glint-admin-css', GLINT_MOBILE_MENU_URL . 'assets/css/admin.css');
            
            wp_localize_script('glint-admin-js', 'glintAdmin', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('glint_menu_nonce'),
                'editText' => 'Edit',
                'cancelEditText' => 'Cancel',
                'updateText' => 'Update'
            ]);
        }
    }

    public function render_admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'glint_mobile_mega_menu';
        
        // Delete
        if (isset($_GET['delete']) && !empty($_GET['delete'])) {
            $id = intval($_GET['delete']);
            $wpdb->delete($table_name, ['id' => $id]);
            $wpdb->delete($table_name, ['parent_id' => $id]); // Delete Child
        }
        
        // Get all items
        $menu_items = $wpdb->get_results("SELECT * FROM $table_name ORDER BY menu_order ASC");
        
        // Gerente structure
        $menu_tree = [];
        foreach ($menu_items as $item) {
            $menu_tree[$item->parent_id][] = $item;
        }
        ?>
        <div class="wrap">
            <h1>Mobile Mega Menu Setting</h1>
            
            <form id="glint-menu-form">
                <h2 id="form-title">Add New</h2>
            
                <input type="hidden" id="edit_item_id" name="edit_item_id" value="0">
                
                <div class="form-group">
                    <label for="parent_id">Parent</label>
                    <select name="parent_id" id="parent_id">
                        <option value="0">Top</option>
                        <?php $this->render_menu_options($menu_tree, 0, 0); ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="menu_content">Content(HTML)</label>
                    <?php 
                    wp_editor('', 'menu_content', [
                        'textarea_name' => 'content',
                        'media_buttons' => true,
                        'textarea_rows' => 6,
                        'teeny' => false,
                        'editor_class' => 'glint-menu-editor'
                    ]); 
                    ?>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" id="submit-menu-item" class="button button-primary">Add</button>
                    <button type="button" id="cancel-edit" class="button" style="display:none;">Cancel</button>
                </div>
            </form>
            
            <h2>Structure Preview</h2>
            <div id="glint-menu-container">
                <ul id="glint-menu-tree" class="menu-tree">
                    <?php $this->render_menu_tree($menu_tree, 0); ?>
                </ul>
            </div>
        </div>
        <?php
    }
    
    private function render_menu_options($menu_tree, $parent_id, $depth) {
        if (!isset($menu_tree[$parent_id])) return;
        
        foreach ($menu_tree[$parent_id] as $item) {
            echo sprintf(
                '<option value="%d">%s%s</option>',
                $item->id,
                str_repeat('&mdash; ', $depth),
                wp_strip_all_tags($item->content)
            );
            
            if (isset($menu_tree[$item->id])) {
                $this->render_menu_options($menu_tree, $item->id, $depth + 1);
            }
        }
    }
    
    private function render_menu_tree($menu_tree, $parent_id) {
        if (!isset($menu_tree[$parent_id])) return;
        
        foreach ($menu_tree[$parent_id] as $item) {
            $has_children = isset($menu_tree[$item->id]);
            ?>
            <li class="menu-item" data-id="<?= $item->id ?>">
                <div class="menu-item-header">
                    <span class="drag-handle">☰</span>
                    <div class="menu-item-content"><?= $item->content ?></div>
                    <div class="menu-actions">
                        <a href="#" class="edit-item" data-id="<?= $item->id ?>">Edit</a> | 
                        <a href="?page=glint-mobile-menu&delete=<?= $item->id ?>" class="delete-item">Delete</a>
                    </div>
                </div>
                <?php if ($has_children): ?>
                    <ul class="submenu">
                        <?php $this->render_menu_tree($menu_tree, $item->id); ?>
                    </ul>
                <?php endif; ?>
            </li>
            <?php
        }
    }

    public function ajax_save_menu_item() {
        check_ajax_referer('glint_menu_nonce', 'nonce');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'glint_mobile_mega_menu';
        
        $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
        $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
        
        // level
        $level = 1;
        if ($parent_id > 0) {
            $parent = $wpdb->get_row($wpdb->prepare(
                "SELECT level FROM $table_name WHERE id = %d", $parent_id
            ));
            $level = $parent ? $parent->level + 1 : 1;
        }
        
        // get max order
        $max_order = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(menu_order) FROM $table_name WHERE parent_id = %d", $parent_id
        ));
        $menu_order = $max_order ? $max_order + 1 : 0;
        
        $wpdb->insert($table_name, [
            'parent_id' => $parent_id,
            'menu_order' => $menu_order,
            'content' => $content,
            'level' => $level
        ], ['%d', '%d', '%s', '%d']);
        
        wp_send_json_success([
            'id' => $wpdb->insert_id,
            'content' => $content,
            'parent_id' => $parent_id,
            'level' => $level
        ]);
    }
    
    // ajax get single menu item
    public function ajax_get_menu_item() {
        check_ajax_referer('glint_menu_nonce', 'nonce');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'glint_mobile_mega_menu';
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id) {
            $item = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d", $id
            ));
            
            if ($item) {
                wp_send_json_success($item);
            }
        }
        
        wp_send_json_error();
    }
    
    // ajax update menu
    public function ajax_update_menu_item() {
        check_ajax_referer('glint_menu_nonce', 'nonce');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'glint_mobile_mega_menu';
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
        $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
        
        if (!$id) {
            wp_send_json_error(['message' => 'Invalid ID']);
        }
        
        $original_item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d", $id
        ));
        
        if (!$original_item) {
            wp_send_json_error(['message' => 'item not exsited']);
        }
        
        $level = 1;
        if ($parent_id > 0) {
            $parent = $wpdb->get_row($wpdb->prepare(
                "SELECT level FROM $table_name WHERE id = %d", $parent_id
            ));
            $level = $parent ? $parent->level + 1 : 1;
        }
        
        // update
        $result = $wpdb->update($table_name, [
            'parent_id' => $parent_id,
            'content' => $content,
            'level' => $level
        ], ['id' => $id], ['%d', '%s', '%d'], ['%d']);
        
        if ($result === false) {
            wp_send_json_error(['message' => 'Update Fail']);
        }
        
        // if parent change, all children changing 
        if ($original_item->parent_id != $parent_id) {
            $this->update_children_levels($id, $level + 1);
        }
        
        wp_send_json_success([
            'id' => $id,
            'content' => $content,
            'parent_id' => $parent_id,
            'level' => $level
        ]);
    }
    
    // update all children levels
    private function update_children_levels($parent_id, $new_level) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'glint_mobile_mega_menu';
        
        $wpdb->update($table_name, 
            ['level' => $new_level],
            ['parent_id' => $parent_id],
            ['%d'],
            ['%d']
        );
        
        $children = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM $table_name WHERE parent_id = %d", $parent_id
        ));
        
        foreach ($children as $child_id) {
            $this->update_children_levels($child_id, $new_level + 1);
        }
    }
    
    public function ajax_delete_menu_item() {
        check_ajax_referer('glint_menu_nonce', 'nonce');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'glint_mobile_mega_menu';
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id) {

            $children = $wpdb->get_col($wpdb->prepare(
                "SELECT id FROM $table_name WHERE parent_id = %d", $id
            ));
            foreach ($children as $child_id) {
                $wpdb->delete($table_name, ['id' => $child_id]);
            }

            $wpdb->delete($table_name, ['id' => $id]);
        }
        
        wp_send_json_success();
    }
    
    public function ajax_update_menu_order() {
        check_ajax_referer('glint_menu_nonce', 'nonce');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'glint_mobile_mega_menu';
        $items = isset($_POST['items']) ? $_POST['items'] : [];
        
        foreach ($items as $item) {
            $wpdb->update($table_name, [
                'menu_order' => $item['order'],
                'parent_id' => $item['parent']
            ], ['id' => $item['id']], ['%d', '%d'], ['%d']);
        }
        
        wp_send_json_success();
    }
}