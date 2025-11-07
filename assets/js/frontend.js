jQuery(document).ready(function($) {
    var $menu = $('#glint-mobile-menu');
    var $toggle = $('#glint-menu-toggle');
    var $overlay = $('.glint-menu-overlay');
    var $panelsContainer = $('.glint-menu-panels');
    var $topLevelPanel = $('.menu-panel[data-parent="0"]');
    
    if ($menu.length === 0) return;
    
    $toggle.on('click', function(e) {
        e.stopPropagation();
        toggleMenu();
    });
    
    $overlay.on('click', function(e) {
        closeMenu();
    });
    
    function toggleMenu() {
        if ($panelsContainer.hasClass('active')) {
            closeMenu();
        } else {
            openMenu();
        }
    }
    
    function openMenu() {
        $toggle.addClass('active');
        $overlay.addClass('visible');
        $panelsContainer.addClass('active');
        
        $panelsContainer.find('.menu-panel').removeClass('active');
        $topLevelPanel.addClass('active');
    }
    
    function closeMenu() {
        $toggle.removeClass('active');
        $overlay.removeClass('visible');
        $panelsContainer.removeClass('active');
        
        setTimeout(function() {
            $panelsContainer.find('.menu-panel').removeClass('active');
            $topLevelPanel.addClass('active');
        }, 400);
    }
    
    $(document).on('click', '.menu-item.has-children', function(e) {
        e.stopPropagation();

        var $this = $(this);
        var itemId = $this.data('id');
        var $targetPanel = $('#submenu-panel-' + itemId);

        //console.log('Open menu panel: ','#submenu-panel-' + itemId);
        $updateParentID = $this.closest('.menu-panel').attr('id').match(/\d+$/)[0];
        //console.log('Parent ID: ', $updateParentID);
        
        if ($targetPanel.length) {
            $this.closest('.menu-panel').removeClass('active');
            $targetPanel.addClass('active');
            $targetPanel.attr('data-parent', $updateParentID);
        }
        
    });
    
    //back
    $(document).on('click', '.menu-back-button', function(e) {
        e.stopPropagation();
        e.preventDefault();
        
        var $currentPanel = $(this).closest('.menu-panel');
        var parentId = $currentPanel.data('parent');
        var $parentPanel = null;
        
        $parentPanel = $('#submenu-panel-' + parentId);
        
        if ($parentPanel) {
            //console.log('Found Parent Panel ID:', '#submenu-panel-' + parentId);
            //console.log('Current Panel ID:', $currentPanel.attr('id'));
        } else {
            console.warn('Parent panel not found');
        }
        
        if ($parentPanel && $parentPanel.length) {
            $currentPanel.removeClass('active');
            $parentPanel.addClass('active');
        } else {
            $panelsContainer.find('.menu-panel').removeClass('active');
            $topLevelPanel.addClass('active');
        }
    });
    
    function initBootstrapCollapse() {
        $('[data-bs-toggle="collapse"]').each(function() {
            var $button = $(this);
            var target = $button.data('bs-target');
            var $target = $(target);
            
            if ($target.length) {
                if (!$target.data('bs.collapse')) {
                    new bootstrap.Collapse($target[0], {
                        toggle: false
                    });
                }
                
                $button.off('click.collapse').on('click.collapse', function(e) {
                    e.stopPropagation();
                    $target.collapse('toggle');
                });
            }
        });
    }
    
    initBootstrapCollapse();

    $toggle.on('click', function() {
        setTimeout(initBootstrapCollapse, 100);
    });
    
    $(document).on('click', '.menu-item.has-children, .menu-back-button', function() {
        setTimeout(initBootstrapCollapse, 100);
    });
});