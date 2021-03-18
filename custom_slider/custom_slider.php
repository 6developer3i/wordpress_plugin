<?php
/**
 * Plugin Name: 	Custom Slider
 * Plugin URI:		http://test.com/
 * Description:		Custom slider plugin.
 * Version: 		0.0.1
 * Author:              Praks
 * Author URI: 		http://www.test.com/
 */
add_action('admin_menu', 'register_media_selector_settings_page');

function register_media_selector_settings_page() {
    add_menu_page('Custom Slider', 'Custom Slider', 'manage_options', 'custom-slider', 'custom_slider_settings_page_callback');
}

function custom_slider_settings_page_callback() {

// Save attachment ID
    if (isset($_POST['submit_image_selector'])) :
        $image_attachment_id = isset($_POST['image_attachment_id']) ? json_encode($_POST['image_attachment_id']) : '';
        update_option('slider_attachment_id', $image_attachment_id);
    endif;

    wp_enqueue_media();
    $images = get_option('slider_attachment_id');
    if (!empty($images)) {
        $images = json_decode($images, true);
    }
    ?>
    <div class="container custom_slider">
        <h1>Custom Slider</h1>
        <p>Please upload image to display in slider.</p>
        <p class="red shortcode">Shortcode: [custom_slider]</p>
        <form method='post'>
            <ul border="1" cellspacing="15" bordercolor="#ccc" id="image_list" class="connectedSortable">
                <?php
                if (!empty($images)) {
                    foreach ($images as $img_id) {
                        ?>
                        <li>
                            <div class='image-preview-wrapper'>
                                <img id='image-preview' src='<?php echo wp_get_attachment_url($img_id); ?>' height='100'>
                                <input type='hidden' name='image_attachment_id[]' id='image_attachment_id' value='<?php echo $img_id; ?>'>
                            </div>
                            <div class="action">
                                <button type="button" class="remove">Remove</button>
                            </div>
                        </li>
                        <?php
                    }
                } else {
                    echo '<tr><td><p>Please upload image</p></td></tr>';
                }
                ?>
            </ul>
            <input id="upload_image_button" type="button" class="button" value="<?php _e('Upload image'); ?>" />
            <input type="submit" name="submit_image_selector" value="Save" class="button-primary">
        </form>

    </div>

    <style>
        .container{padding: 50px;}
        #image_list{margin-bottom: 20px; width: 100%;}
        #image_list li{padding: 10px; border: 1px solid #ccc;}
        #image_list li img{vertical-align: middle;}
        #image_list .image-preview-wrapper{display: inline-block; margin-right: 15px;width: 200px;}
        #image_list .action{display: inline-block;}
        .shortcode{font-size: 16px; font-weight: 600; font-style: italic;}
        .red{color: red}
    </style>
    <?php
}

add_action('admin_footer', 'custom_slider_scripts');

function custom_slider_scripts() {

    $saved_attachment_post_id = get_option('slider_attachment_id', 0);
    ?>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script type='text/javascript'>

        jQuery(document).ready(function ($) {
            /* short image */
            $("#image_list").sortable({
                connectWith: ".connectedSortable"
            }).disableSelection();

            /* remove image */
            $(document).on('click', '.remove', function () {
                event.preventDefault();
                $(this).parents('li').remove();
            });
            
            // Uploading files
            var file_frame;
            var wp_media_post_id = wp.media.model.settings.post.id; // Store the old id
            var set_to_post_id = <?php echo (!empty($saved_attachment_post_id)) ? $saved_attachment_post_id : 0; ?>; // Set this

            jQuery('#upload_image_button').on('click', function (event) {
                event.preventDefault();

                // If the media frame already exists, reopen it.
                if (file_frame) {
                    // Set the post ID to what we want
                    file_frame.uploader.uploader.param('post_id', set_to_post_id);
                    // Open frame
                    file_frame.open();
                    return;
                } else {
                    wp.media.model.settings.post.id = set_to_post_id;
                }

                // Create the media frame.
                file_frame = wp.media.frames.file_frame = wp.media({
                    title: 'Select a image to upload',
                    button: {
                        text: 'Use this image',
                    },
                    multiple: true	// Set to true to allow multiple files to be selected
                });

                // When an image is selected, run a callback.
                file_frame.on('select', function () {
                    // We set multiple to false so only get one image from the uploader
                    selected = file_frame.state().get('selection').toJSON();
                    $.each(selected, function(key, attachment) {
                        var image_html = "<li><div class='image-preview-wrapper'><img id='image-preview' src='" + attachment.url + "' height='100'>" +
                            "<input type='hidden' name='image_attachment_id[]' id='image_attachment_id' value='" + attachment.id + "'></div>"+
                            "<div class=\"action\">"+
                                    "<button type=\"button\" class=\"remove\">Remove</button>"+
                                "</div>";
                        $('.custom_slider ul').append(image_html);
                        // Restore the main post ID
                        wp.media.model.settings.post.id = wp_media_post_id;
                    });
                });

                // Finally, open the modal
                file_frame.open();
            });

            // Restore the main ID when the add media button is pressed
            jQuery('a.add_media').on('click', function () {
                wp.media.model.settings.post.id = wp_media_post_id;
            });
        });

    </script><?php
}

// function that runs when shortcode is called
function custom_slider_shortcode() {

    $slider_images = get_option('slider_attachment_id');
    if (!empty($slider_images)) {
        $slider_images = json_decode($slider_images, true);

        // Things that you want to do. 
        $return = '<div class="flexslider custom_slider_flexslider">
                    <ul class="slides">';
        if (!empty($slider_images)) {
            foreach ($slider_images as $img_id) {
                $return .= '<li><img src="' . wp_get_attachment_url($img_id) . '" /></li>';
            }
        }
        $return .= '</ul></div>';
    }
// Output needs to be return
    return $return;
}

// register shortcode
add_shortcode('custom_slider', 'custom_slider_shortcode');
//
add_action('wp_head', 'register_scripts');

function register_scripts() {
    wp_register_style('flexslider', plugin_dir_url(__FILE__) . "flexslider/flexslider.css");
    wp_enqueue_style('flexslider');

    wp_register_style('custom_slider', plugin_dir_url(__FILE__) . "flexslider/custom.css");
    wp_enqueue_style('custom_slider');

    wp_register_script('flexslider', plugin_dir_url(__FILE__) . 'flexslider/jquery.flexslider.js', array('jquery'), '1.0.0', true);
    wp_print_scripts('flexslider');

    wp_register_script('custom_slider', plugin_dir_url(__FILE__) . 'flexslider/custom.js', array('jquery'), '1.0.0', true);
    wp_print_scripts('custom_slider');
}

function plugin_settings_link($links) { 
  $settings_link = '<a href="admin.php?page=custom-slider">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'plugin_settings_link' );