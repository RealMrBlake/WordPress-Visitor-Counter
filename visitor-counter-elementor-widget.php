<?php
class Visitor_Counter_Elementor_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'visitor_counter';
    }

    public function get_title() {
        return __('Visitor Counter', 'plugin-name');
    }

    public function get_icon() {
        return 'eicon-number-field';
    }

    public function get_categories() {
        return ['basic'];
    }

    protected function render() {
        $count = get_option('vc_visitor_count', 0);
        echo '<div class="visitor-counter">';
        echo '<p>' . __('Besucheranzahl:', 'plugin-name') . ' <strong>' . $count . '</strong></p>';
        echo '</div>';
    }

    protected function _content_template() {
        ?>
        <#
        var count = <?php echo get_option('vc_visitor_count', 0); ?>;
        #>
        <div class="visitor-counter">
            <p><?php _e('Besucheranzahl:', 'plugin-name'); ?> <strong>{{{ count }}}</strong></p>
        </div>
        <?php
    }
}

function register_visitor_counter_elementor_widget($widgets_manager) {
    require_once(__DIR__ . '/visitor-counter-elementor-widget.php');
    $widgets_manager->register_widget_type(new \Visitor_Counter_Elementor_Widget());
}
add_action('elementor/widgets/widgets_registered', 'register_visitor_counter_elementor_widget');
