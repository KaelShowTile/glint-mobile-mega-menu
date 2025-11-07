jQuery(document).ready(function($) {

    var editingItemId = 0;
    
    // edit menu item
    $('#glint-menu-form').on('submit', function(e) {
        e.preventDefault();
        
        var content = tinyMCE.get('menu_content').getContent();
        var parent_id = $('#parent_id').val();
        var isEditing = editingItemId > 0;
        
        var ajaxData = {
            nonce: glintAdmin.nonce,
            content: content,
            parent_id: parent_id
        };
        
        if (isEditing) {
            ajaxData.action = 'glint_update_menu_item';
            ajaxData.id = editingItemId;
        } else {
            ajaxData.action = 'glint_save_menu_item';
        }
        
        $.ajax({
            url: glintAdmin.ajaxurl,
            type: 'POST',
            data: ajaxData,
            beforeSend: function() {
                $('#submit-menu-item').prop('disabled', true).text('Processing...');
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Failed to update: ' + (response.data.message || 'Unknown issue'));
                }
            },
            error: function() {
                alert('Request Failed, please retry.');
            },
            complete: function() {
                $('#submit-menu-item').prop('disabled', false);
                if (isEditing) {
                    $('#submit-menu-item').text(glintAdmin.updateText);
                } else {
                    $('#submit-menu-item').text('Add');
                }
            }
        });
    });
    
    // Edit Item
    $(document).on('click', '.edit-item', function(e) {
        e.preventDefault();
        
        var itemId = $(this).data('id');
        editingItemId = itemId;
        
        // Get menu item content
        $.ajax({
            url: glintAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'glint_get_menu_item',
                nonce: glintAdmin.nonce,
                id: itemId
            },
            beforeSend: function() {
                $('#submit-menu-item').prop('disabled', true).text('加载中...');
            },
            success: function(response) {
                if (response.success) {
                    var item = response.data;
                    
                    $('#form-title').text('Edit');
                    $('#edit_item_id').val(item.id);
                    $('#parent_id').val(item.parent_id);
                    tinyMCE.get('menu_content').setContent(item.content);
                    
                    $('#submit-menu-item').text(glintAdmin.updateText);
                    $('#cancel-edit').show();
                    
                    $('html, body').animate({
                        scrollTop: $('#glint-menu-form').offset().top - 100
                    }, 500);
                } else {
                    alert('Failed to get menu item');
                }
            },
            complete: function() {
                $('#submit-menu-item').prop('disabled', false);
            }
        });
    });
    
    // Cancel editing
    $('#cancel-edit').on('click', function() {
        resetForm();
    });
    
    // Delete item
    $(document).on('click', '.delete-item', function(e) {
        e.preventDefault();
        if (!confirm('You sure?')) return;
        
        var item_id = $(this).closest('.menu-item').data('id');
        
        $.ajax({
            url: glintAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'glint_delete_menu_item',
                nonce: glintAdmin.nonce,
                id: item_id
            },
            success: function() {
                location.reload();
            }
        });
    });
    
    // Order
    $('#glint-menu-tree').sortable({
        handle: '.drag-handle',
        placeholder: 'ui-sortable-placeholder',
        update: function(event, ui) {
            var items = [];
            
            $('#glint-menu-tree > .menu-item').each(function(index) {
                items.push({
                    id: $(this).data('id'),
                    order: index,
                    parent: 0
                });
                
                // children
                $(this).find('.submenu > .menu-item').each(function(subIndex) {
                    items.push({
                        id: $(this).data('id'),
                        order: subIndex,
                        parent: $(this).closest('.submenu').prev().data('id')
                    });
                });
            });
            
            $.ajax({
                url: glintAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'glint_update_menu_order',
                    nonce: glintAdmin.nonce,
                    items: items
                }
            });
        }
    });
    
    // order childen
    $('.submenu').sortable({
        handle: '.drag-handle',
        placeholder: 'ui-sortable-placeholder',
        update: function(event, ui) {
            var parent_id = $(this).closest('.menu-item').data('id');
            var items = [];
            
            $(this).children('.menu-item').each(function(index) {
                items.push({
                    id: $(this).data('id'),
                    order: index,
                    parent: parent_id
                });
            });
            
            $.ajax({
                url: glintAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'glint_update_menu_order',
                    nonce: glintAdmin.nonce,
                    items: items
                }
            });
        }
    });
    
    // Reset
    function resetForm() {
        editingItemId = 0;
        $('#form-title').text('Add');
        $('#edit_item_id').val('0');
        $('#parent_id').val('0');
        tinyMCE.get('menu_content').setContent('');
        $('#submit-menu-item').text('Add');
        $('#cancel-edit').hide();
    }
});