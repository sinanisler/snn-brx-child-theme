<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

use Bricks\Element;

/**
 * Custom Walker class to add image and description support to menu items.
 */
class SNN_Nav_Menu_Walker extends Walker_Nav_Menu {
    /**
     * Starts the element output.
     */
    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        // Get custom field for image URL
        $image_url = get_post_meta( $item->ID, '_menu_item_image_url', true );

        // Standard link attributes
        $atts = [];
        $atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
        $atts['target'] = ! empty( $item->target )     ? $item->target     : '';
        $atts['rel']    = ! empty( $item->xfn )        ? $item->xfn        : '';
        $atts['href']   = ! empty( $item->url )        ? $item->url        : '';
        $atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );
        $attributes = '';
        foreach ( $atts as $attr => $value ) {
            if ( ! empty( $value ) ) {
                $value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        // Build the menu item output
        $item_output = $args->before;
        $item_output .= '<a' . $attributes . '>';

        // Add image if URL exists
        if ( ! empty( $image_url ) ) {
            $item_output .= '<img src="' . esc_url( $image_url ) . '" class="menu-item-image" alt="' . esc_attr( $item->title ) . '">';
        }

        $item_output .= '<span class="menu-item-title">' . $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after . '</span>';
        
        // Add description if it exists
        if ( ! empty( $item->description ) ) {
            $item_output .= '<span class="menu-item-description">' . esc_html( $item->description ) . '</span>';
        }

        // Add chevron for parent items in mobile view
        if ( $args->walker->has_children ) {
             $item_output .= '<span class="snn-submenu-toggle"><i class="fas fa-chevron-down"></i></span>';
        }

        $item_output .= '</a>';
        $item_output .= $args->after;

        // Apply classes to the list item
        $classes = empty( $item->classes ) ? [] : (array) $item->classes;
        $class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
        $output .= '<li class="' . esc_attr( $class_names ) . '">';
        $output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
    }
}


class Snn_Nav_Menu extends Element {
    public $category = 'snn';
    public $name = 'snn-nav-menu';
    public $icon = 'ti-menu-alt';
    public $css_selector = '.snn-nav-menu-wrapper';
    public $scripts = [];
    public $nestable = false;

    public function get_label() {
        return esc_html__( 'SNN Nav Menu', 'bricks' );
    }

    public function set_control_groups() {
        $this->control_groups['desktop_menu'] = [
            'title' => esc_html__( 'Desktop Menu', 'bricks' ),
            'tab' => 'content',
        ];
        $this->control_groups['mobile_menu'] = [
            'title' => esc_html__( 'Mobile Menu', 'bricks' ),
            'tab' => 'content',
        ];
    }

    public function set_controls() {
        // --- DESKTOP CONTROLS ---

        $this->controls['menu'] = [
            'tab' => 'content',
            'group' => 'desktop_menu',
            'label' => esc_html__( 'Menu', 'bricks' ),
            'type' => 'select',
            'options' => $this->get_wp_menus(),
            'placeholder' => esc_html__( 'Select a menu', 'bricks' ),
        ];

        $this->controls['desktop_typography'] = [
            'tab' => 'content',
            'group' => 'desktop_menu',
            'label' => esc_html__( 'Typography', 'bricks' ),
            'type' => 'typography',
            'css' => [
                [
                    'property' => 'typography',
                    'selector' => '.snn-desktop-nav .menu-item a',
                ],
            ],
        ];

        $this->controls['desktop_dropdown_typography'] = [
            'tab' => 'content',
            'group' => 'desktop_menu',
            'label' => esc_html__( 'Dropdown Typography', 'bricks' ),
            'type' => 'typography',
            'css' => [
                [
                    'property' => 'typography',
                    'selector' => '.snn-desktop-nav .sub-menu .menu-item a',
                ],
            ],
        ];

        $this->controls['desktop_spacing'] = [
            'tab' => 'content',
            'group' => 'desktop_menu',
            'label' => esc_html__( 'Item Spacing', 'bricks' ),
            'type' => 'slider',
            'units' => [ 'px', 'em', 'rem' ],
            'default' => '15px',
            'css' => [
                [
                    'property' => 'padding-left',
                    'selector' => '.snn-desktop-nav > .menu-item',
                ],
                 [
                    'property' => 'padding-right',
                    'selector' => '.snn-desktop-nav > .menu-item',
                ],
            ],
        ];

        $this->controls['dropdown_bg_color'] = [
            'tab' => 'content',
            'group' => 'desktop_menu',
            'label' => esc_html__( 'Dropdown Background', 'bricks' ),
            'type' => 'color',
            'css' => [
                [
                    'property' => 'background-color',
                    'selector' => '.snn-desktop-nav .sub-menu',
                ],
            ],
            'default' => [ 'hex' => '#ffffff' ],
        ];


        // --- MOBILE CONTROLS ---

        $this->controls['mobile_menu_breakpoint'] = [
            'tab' => 'content',
            'group' => 'mobile_menu',
            'label' => esc_html__( 'Breakpoint', 'bricks' ),
            'type' => 'select',
            'options' => [
                '1024' => esc_html__( 'Desktop (1024px)', 'bricks' ),
                '991' => esc_html__( 'Tablet Landscape (991px)', 'bricks' ),
                '767' => esc_html__( 'Tablet Portrait (767px)', 'bricks' ),
                '478' => esc_html__( 'Mobile (478px)', 'bricks' ),
            ],
            'default' => '767',
        ];

        $this->controls['mobile_toggle_icon'] = [
            'tab' => 'content',
            'group' => 'mobile_menu',
            'label' => esc_html__( 'Toggle Icon', 'bricks' ),
            'type' => 'icon',
            'default' => [
                'library' => 'themify',
                'icon' => 'ti-menu',
            ],
        ];

        $this->controls['mobile_toggle_color'] = [
            'tab' => 'content',
            'group' => 'mobile_menu',
            'label' => esc_html__( 'Toggle Icon Color', 'bricks' ),
            'type' => 'color',
        ];

        $this->controls['mobile_bg_color'] = [
            'tab' => 'content',
            'group' => 'mobile_menu',
            'label' => esc_html__( 'Off-Canvas Background', 'bricks' ),
            'type' => 'color',
            'default' => [ 'hex' => '#333333' ],
        ];
        
        $this->controls['mobile_typography'] = [
            'tab' => 'content',
            'group' => 'mobile_menu',
            'label' => esc_html__( 'Typography', 'bricks' ),
            'type' => 'typography',
        ];
    }
    
    private function get_wp_menus() {
        $menus = wp_get_nav_menus();
        $options = [];
        foreach ( $menus as $menu ) {
            $options[ $menu->term_id ] = $menu->name;
        }
        return $options;
    }

    public function render() {
        $settings = $this->settings;
        $unique_id = 'snn-nav-menu-' . $this->id;

        $this->set_attribute( '_root', 'id', $unique_id );
        $this->set_attribute( '_root', 'class', 'snn-nav-menu-wrapper' );

        // --- Get settings with defaults ---
        $menu_id = $settings['menu'] ?? 0;
        $breakpoint = intval( $settings['mobile_menu_breakpoint'] ?? 767 );
        $mobile_bg_color = $settings['mobile_bg_color']['hex'] ?? '#333';
        $mobile_link_color = $settings['mobile_typography']['color']['hex'] ?? '#ffffff';
        $toggle_color = $settings['mobile_toggle_color']['hex'] ?? '#000000';
        
        echo "<div {$this->render_attributes('_root')}>";

        // --- START: In-line CSS ---
        // This method ensures styles are applied in both frontend and builder
        echo "<style>";
        
        // General Wrapper Styles
        echo "#$unique_id { position: relative; display: flex; justify-content: flex-end; align-items: center; }";
        
        // --- Desktop Menu Styles ---
        echo "#$unique_id .snn-desktop-nav { display: flex; list-style: none; margin: 0; padding: 0; }";
        echo "#$unique_id .snn-desktop-nav .menu-item { position: relative; }";
        echo "#$unique_id .snn-desktop-nav a { display: flex; align-items: center; text-decoration: none; transition: 0.3s; }";
        echo "#$unique_id .snn-desktop-nav .menu-item-image { margin-right: 8px; max-height: 24px; }";
        echo "#$unique_id .snn-desktop-nav .menu-item-description { font-size: 0.8em; opacity: 0.7; margin-left: 8px; }";
        
        // Desktop Dropdowns
        echo "#$unique_id .snn-desktop-nav .sub-menu { display: none; position: absolute; top: 100%; left: 0; list-style: none; padding: 10px 0; min-width: 220px; z-index: 10; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }";
        echo "#$unique_id .snn-desktop-nav .sub-menu .sub-menu { top: 0; left: 100%; }";
        echo "#$unique_id .snn-desktop-nav .menu-item:hover > .sub-menu { display: block; }";
        echo "#$unique_id .snn-desktop-nav .sub-menu a { padding: 8px 15px; width: 100%; }";
        echo "#$unique_id .snn-submenu-toggle { display: none; }"; // Hide chevron on desktop

        // --- Mobile Menu Styles ---
        echo "#$unique_id .snn-mobile-toggle { display: none; cursor: pointer; font-size: 24px; color: " . esc_attr($toggle_color) . "; z-index: 1001; }";
        echo "#$unique_id .snn-mobile-nav-wrapper { position: fixed; top: 0; left: -100%; width: 300px; max-width: 80%; height: 100vh; background-color: " . esc_attr($mobile_bg_color) . "; z-index: 1000; transition: left 0.4s ease; overflow-y: auto; padding: 60px 0; }";
        echo "#$unique_id .snn-mobile-nav-wrapper.is-open { left: 0; }";
        echo "#$unique_id .snn-mobile-nav { list-style: none; margin: 0; padding: 0 20px; }";
        echo "#$unique_id .snn-mobile-nav a { display: flex; flex-wrap: wrap; align-items: center; padding: 12px 0; text-decoration: none; color: " . esc_attr($mobile_link_color) . "; border-bottom: 1px solid rgba(255,255,255,0.1); }";
        if (isset($settings['mobile_typography'])) {
            // Apply font styles from typography control to mobile links
            $mobile_typo = $settings['mobile_typography'];
            if(isset($mobile_typo['font-family'])) echo "#$unique_id .snn-mobile-nav a { font-family: {$mobile_typo['font-family']}; }";
            if(isset($mobile_typo['font-size'])) echo "#$unique_id .snn-mobile-nav a { font-size: {$mobile_typo['font-size']}; }";
            if(isset($mobile_typo['font-weight'])) echo "#$unique_id .snn-mobile-nav a { font-weight: {$mobile_typo['font-weight']}; }";
            if(isset($mobile_typo['text-transform'])) echo "#$unique_id .snn-mobile-nav a { text-transform: {$mobile_typo['text-transform']}; }";
        }
        echo "#$unique_id .snn-mobile-nav .menu-item-description { display: block; width: 100%; font-size: 0.8em; opacity: 0.7; }";
        echo "#$unique_id .snn-mobile-nav .sub-menu { list-style: none; display: none; padding-left: 15px; }";
        echo "#$unique_id .snn-mobile-nav .snn-submenu-toggle { margin-left: auto; padding: 10px; cursor: pointer; }";
        
        // Breakpoint Media Query
        echo "@media (max-width: {$breakpoint}px) {";
        echo "  #$unique_id .snn-desktop-nav { display: none; }";
        echo "  #$unique_id .snn-mobile-toggle { display: block; }";
        echo "}";

        echo "</style>";
        // --- END: In-line CSS ---


        // --- START: HTML Output ---
        
        // Mobile Toggle Button
        if ( isset( $settings['mobile_toggle_icon'] ) ) {
            echo '<div class="snn-mobile-toggle">';
            \Bricks\Render::icon( $settings['mobile_toggle_icon'] );
            echo '</div>';
        }

        // Desktop Navigation
        if ( $menu_id ) {
            wp_nav_menu( [
                'menu'            => $menu_id,
                'container'       => 'nav',
                'container_class' => 'snn-desktop-nav-container',
                'menu_class'      => 'snn-desktop-nav',
                'walker'          => new SNN_Nav_Menu_Walker(),
                'depth'           => 3, // Support up to 3 levels
            ] );
        }

        // Mobile Off-Canvas Navigation
        echo '<div class="snn-mobile-nav-wrapper">';
        if ( $menu_id ) {
            wp_nav_menu( [
                'menu'            => $menu_id,
                'container'       => 'nav',
                'container_class' => 'snn-mobile-nav-container',
                'menu_class'      => 'snn-mobile-nav',
                'walker'          => new SNN_Nav_Menu_Walker(),
                'depth'           => 3,
            ] );
        }
        echo '</div>';

        // --- END: HTML Output ---


        // --- START: JavaScript ---
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuWrapper = document.getElementById('<?php echo esc_js( $unique_id ); ?>');
            if (!menuWrapper) return;

            const toggleBtn = menuWrapper.querySelector('.snn-mobile-toggle');
            const mobileNavWrapper = menuWrapper.querySelector('.snn-mobile-nav-wrapper');
            const submenuToggles = menuWrapper.querySelectorAll('.snn-mobile-nav .snn-submenu-toggle');

            if (toggleBtn && mobileNavWrapper) {
                toggleBtn.addEventListener('click', function() {
                    mobileNavWrapper.classList.toggle('is-open');
                });
            }

            submenuToggles.forEach(function(toggle) {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const subMenu = this.closest('.menu-item-has-children').querySelector('.sub-menu');
                    if (subMenu) {
                        if (subMenu.style.display === 'block') {
                            subMenu.style.display = 'none';
                            this.classList.remove('is-open');
                        } else {
                            subMenu.style.display = 'block';
                            this.classList.add('is-open');
                        }
                    }
                });
            });
        });
        </script>
        <?php
        // --- END: JavaScript ---

        echo "</div>"; // Close wrapper
    }
}