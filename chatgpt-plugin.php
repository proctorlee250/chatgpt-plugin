<?php
/*
Plugin Name: ChatGPT Plugin
Plugin URI: https://example.com/
Description: Plugin that generates a new post from a supplied phrase using the ChatGPT API.
Version: 1.0
Author: Lee Proctor
Author URI: https://lee-proctor.co.uk
*/

// Activate the plugin
function chatgpt_activate_plugin() {
    // Code to run on plugin activation
}
register_activation_hook( __FILE__, 'chatgpt_activate_plugin' );


// Add an admin menu item for the plugin
function chatgpt_add_admin_menu() {
    add_menu_page(
        'ChatGPT Plugin',
        'ChatGPT Plugin',
        'manage_options',
        'chatgpt-plugin',
        'chatgpt_admin_page'
    );
}
add_action( 'admin_menu', 'chatgpt_add_admin_menu' );

// Generate a new post from the supplied phrase
function chatgpt_generate_post($title,$url,$image,$cat) {
	$article = 'AI not connected yet!';
    // Call the ChatGPT API with the supplied phrase and retrieve the resulting article
    
    $api_url = 'https://api.openai.com/v1/completions';
    $api_key = '';
	
	//The prompt using var's $title and $url
		
	$prompt = 'Create me a detailed review of '.$title.' try and make it over 2000 words, use a natural tone and ';
	
	//$prompt .= '';
	
	$prompt .= 'use html for formatting with paragraphs and sub headings. At the very end of the article ';
	
	$prompt .= 'use this url '.$url.' as an external hyperlink, opening in a new tab, with a no follow tag, with the link text of "View it here!",';
	
	$prompt .= 'one final thing try and include a pros and cons section at the top and a specification list at the bottom, followed by a price guide in dollars and uk pounds, followed by a couple of refrence urls';
    	
	
	$data = array(
        'prompt' => $prompt,
        //'length' => 1000, // Set the desired length of the generated article
        //'api_key' => $api_key
        'model'=> 'text-davinci-003',
		'max_tokens'=> 3000,
		

    );
    $response = wp_remote_post($api_url, array(
		'timeout' => 300,
		'headers'     => array(
		'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $api_key,
    	),
        'body' => json_encode($data)
    ));
	
	//print_r($response);
	//exit;
	
    if (is_wp_error($response)) {
        // Handle error
        //return;
    }
	
	
	
    $article = json_decode(wp_remote_retrieve_body($response), true);



    // Use WordPress functions to create a new post with the retrieved article as the content
    // using the above 
    $post_data = array(
        'post_title' => $title,
        'post_content' => $article['choices'][0]['text'],
        'post_status' => 'draft',
		'post_category' => array( $cat )

    );
    $post_id = wp_insert_post($post_data);
	
	// SEO title
	update_post_meta( $post_id, '_yoast_wpseo_title', $title.' review' );
	
	// SEO desc
	$desc =  wp_trim_words( $article['choices'][0]['text'], 20, '...' );
    update_post_meta( $post_id, '_yoast_wpseo_metadesc', $desc );

	//SEO key phrase 
	update_post_meta( $post_id, '_yoast_wpseo_focuskw', $title.' review' );

	
	// Add the image to the post via side load ?	
	$altText = $title;
	$image_url = $image;
	Generate_Featured_Image( $image_url, $post_id, $title, $altText ); //added 4th param for alt text
	

}

// Handle form submission from admin page
function chatgpt_handle_form_submission() {
	// to do the vars !?!?!?!?!?!!?!?!?
    if (isset($_POST['title'])) {
        $title = $_POST['title'];
        $url = $_POST['url'];
        $image = $_POST['image'];
		$cat = $_POST['cat'];
		
        chatgpt_generate_post($title,$url,$image,$cat);
    }
}

// Create the admin page
function chatgpt_admin_page() {
	
	
    chatgpt_handle_form_submission(); // Handle form submission if applicable
    ?>
    <div class="wrap">
	
		<?php //enter cat ids and values ?>

        <h1>ChatGPT Plugin</h1>
        <form method="post">
            <label for="title">Enter product title:</label>
			<br>
            <input type="text" id="title" name="title">
			<br><br>
			<label for="cat">Enter product category:</label>
			<br>
			<select name="cat" id="cat">
			  <option value=""></option>
			  <option value=""></option>
			  <option value=""></option>
			  <option value=""></option>
			</select>
			<br><br>
			<label for="url">Enter product url:</label>
			<br>
            <input type="text" id="url" name="url">
			<br><br>
			<label for="image">Enter product image:</label>
			<br>
            <input type="text" id="image" name="image">
			<br><br>
            <button type="submit">Generate Post</button>
        </form>
    </div>
    <?php
}


/**
* Downloads an image from the specified URL and attaches it to a post as a post thumbnail.
*
* @param string $file    The URL of the image to download.
* @param int    $post_id The post ID the post thumbnail is to be associated with.
* @param string $desc    Optional. Description of the image.
* @return string|WP_Error Attachment ID, WP_Error object otherwise.
*/
function Generate_Featured_Image( $file, $post_id, $desc, $altText ){
    // Set variables for storage, fix file filename for query strings.
    preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
    if ( ! $matches ) {
         return new WP_Error( 'image_sideload_failed', __( 'Invalid image URL' ) );
    }

    $file_array = array();
    $file_array['name'] = basename( $matches[0] );

    // Download file to temp location.
    $file_array['tmp_name'] = download_url( $file );

    // If error storing temporarily, return the error.
    if ( is_wp_error( $file_array['tmp_name'] ) ) {
        return $file_array['tmp_name'];
    }

    // Do the validation and storage stuff.
    $id = media_handle_sideload( $file_array, $post_id, $desc );

	// add alt text here ?
	if ( ! is_wp_error( $id ) ) {
		update_post_meta( $id, '_wp_attachment_image_alt', $altText );
	}
	
    // If error storing permanently, unlink.
    if ( is_wp_error( $id ) ) {
        @unlink( $file_array['tmp_name'] );
        return $id;
    }
    return set_post_thumbnail( $post_id, $id );

}

?>
