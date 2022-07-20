<?php
/*
Plugin Name: Simple Alert
Description: A test plugin to demonstrate wordpress functionality
Author: Jared Christians
Version: 0.1
*/

function simple_alert_register_settings() {
	add_option( 'simple_alert_option_message', 'This is our message.');
	add_option( 'simple_alert_option_posts', '');
	add_option( 'simple_alert_option_post_types', '');

	register_setting( 'simple_alert_options_group', 'simple_alert_option_message', 'simple_alert_callback' );
	register_setting( 'simple_alert_options_group', 'simple_alert_option_posts', 'simple_alert_callback' );
	register_setting( 'simple_alert_options_group', 'simple_alert_option_post_types', 'simple_alert_callback' );
}
add_action( 'admin_init', 'simple_alert_register_settings' );

function simple_alert_register_options_page() {
	add_options_page('Simple Alert', 'Simple Alert', 'manage_options', 'simple_alert', 'simple_alert_options_page');
}
add_action('admin_menu', 'simple_alert_register_options_page');

function getMyPostTypes(){
	$simple_alert_post_types = get_option('simple_alert_option_post_types');
	echo "<h3>Post Types</h3>";
	$args = array(
	   'public'   => true,
	);
	$posts = get_post_types($args);
	echo "<table>";
	foreach($posts as $post_type){
		echo "<tr><td><label for='$post_type'>$post_type</label> </td>";
		echo "<td><input type='checkbox' name='simple_alert_option_post_types[]' id='$post_type' value='$post_type' onChange='isChecked(this)'";
		
		if($simple_alert_post_types != ""){
			if(in_array($post_type,$simple_alert_post_types)){
				echo " checked ";
			}
		}
		echo "></td></tr>";
	}
	echo "</table>";
}

function getMyPosts(){
	?>
	<h2>Posts</h2>
	<select multiple id='simple_alert_option_posts' name='simple_alert_option_posts[]'>
		<?php
		$simple_alert_post_types = get_option('simple_alert_option_post_types');
		$selected_posts = get_option("simple_alert_option_posts");
		
			$args = array(
				'post_type'  	=> $simple_alert_post_types,
				'public'   		=> true,
				'post_status' 	=> array( 'publish' ),
			);
			$posts = new WP_Query ( $args );
			foreach($posts->posts as $post){
				echo "<option value='".$post->post_name."'";
				if($selected_posts != ""){
					if(in_array($post->post_name,$selected_posts)){
						echo " selected ";
					}
				}
				echo ">";
				echo $post->post_title."</option>";
			}
		
	?>
	</select>

	<?php
}

function simple_alert_options_page()
{
?>
  <div>
  <h2>Simple Alert Plugin Page</h2>
	  <p>
		  When you check any of the post type’s checkbox, all posts of that post type will be listed below in the multi-selectbox. You can select multiple posts from the selection for which they want to show alert on frontend.
	  </p>
  <form method="post" action="options.php">
  <?php settings_fields( 'simple_alert_options_group' ); ?>
	  <h3>
		    <label for="simple_alert_option_message">Alert Message</label>
	  </h3>
	  <input type="text" id="simple_alert_option_message" name="simple_alert_option_message" value="<?php echo get_option('simple_alert_option_message'); ?>" />
	  <?php getMyPostTypes(); ?>
	  <?php getMyPosts(); ?>
	  <?php submit_button(); ?>
  </form>
  </div>
<?php
}
?>

<?php
function wp_hook_javascript() {
	global $post;
	$page_name = $post->post_name;
	$simple_alert_posts = get_option('simple_alert_option_posts');
	$simple_alert_message = get_option('simple_alert_option_message');
	if($simple_alert_posts != ""){
		if(in_array($page_name,$simple_alert_posts)){
		?>
			<script>
			  alert("<?php echo $simple_alert_message; ?>");
			</script>
		<?php
		}
	}
	
}

add_action('wp_head', 'wp_hook_javascript');

function wpadmin_hook_javascript() {
	?>
	<script>
	function isChecked(e){
		var url = "https://dev.robotweb.co.za/uni4online/wp-json/simple_alert/v1/posts?post_type="+e.value;
		fetch(url).then(response => response.json())
  				.then(data=>{
				if(e.checked){
					data.forEach(addOptions);

				}else{
					data.forEach(removeOptions);
				}
		});
		
	}
		
	function addOptions(post){
		var selected = <?php echo json_encode(get_option('simple_alert_option_posts')); ?>;
		console.log(selected);
		var select = document.getElementById("simple_alert_option_posts");
		var option = document.createElement("option");
		option.text = post.post_title;
		option.value = post.post_name;
		if(selected.includes(post.post_name)){
			option.setAttribute('selected', true);
		}
		select.add(option);

	}
		
	function removeOptions(data){
		var select = document.getElementById("simple_alert_option_posts");
		for (var i=0; i<select.length; i++) {
			if (select.options[i].value == data.post_name)
				select.remove(i);
		}
	}
	</script>
	<?php
}

add_action('admin_head', 'wpadmin_hook_javascript');

function simple_alert_post_function($request){
	$args = array(
		'post_type'  => array( $request['post_type'] ),
		'public'   => true,
	);
	$posts = new WP_Query ( $args );
	echo json_encode($posts->posts);
	
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'simple_alert/v1', '/posts', array(
    'methods' => 'GET',
    'callback' => 'simple_alert_post_function',
  ) );
} );

function shortcodes_in_cf7( $form ) {
	$form = do_shortcode( $form );
	return $form;
}
add_filter( 'wpcf7_form_elements', 'shortcodes_in_cf7' );
