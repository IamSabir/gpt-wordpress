<?php
/*
Plugin Name: Sabir's GPT Rewrite Plugin
Description: A WordPress plugin to rewrite content using OpenAI's GPT-3 API.
Version: 1.0
Author: Sabir Ali
Author URI: https://github.com/iamsabir
*/

// Enqueue the JavaScript file for the plugin.
function create_new_post($title, $prompt, $status = 'draft')
{
    // Prepare the API request.
    // $API_KEY = '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';
    $api_key = get_option('openai_api_key');
    $url = 'https://api.openai.com/v1/engines/text-davinci-002/completions';
    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type'  => 'application/json'
    );
    $body = array(
        'prompt'   => $prompt,
        'max_tokens' => 500
    );
    // Send the API request.
    $response = wp_remote_post($url, array(
        'headers' => $headers,
        'body'    => json_encode($body)
    ));

    print_r($response);

    // Check for errors.
    if (is_wp_error($response)) {
        return;
    }

    // Extract the content from the API response.
    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    echo 'RESPONSE BODY: ' . $response_body[''] . '';

    $content = $response_body['choices'][0]['text'];

    echo 'CONTENT : ' . $content . '';

    // Prepare post data.
    $post_data = array(
        'post_title'    => wp_strip_all_tags($title),
        'post_content'  => $content,
        'post_status'   => $status,
        'post_author'   => get_current_user_id(),
        'post_type'     => 'post'
    );

    // Insert the post into the database.
    wp_insert_post($post_data);
}
function create_new_post_admin_page()
{
    add_menu_page(
        'GPT Create New Post',
        'Create New Post',
        'manage_options',
        'create-new-post',
        'create_new_post_admin_page_content'
    );

    // Add a settings sub-menu page for the API key.
    add_submenu_page(
        'create-new-post',
        'Settings',
        'Settings',
        'manage_options',
        'create-new-post-settings',
        'create_new_post_settings_page'
    );
}
add_action('admin_menu', 'create_new_post_admin_page');

function create_new_post_admin_page_content()
{
?>
    <div class="wrap">
        <h2>Create New Post</h2>
        <form method="post" action="">
            <label for="url">URL:</label><br>
            <input type="text" name="url" id="url" size="50" placeholder="Enter your url here"><br><br>
            <!-- <label for="title">Title:</label><br>
            <input name="title" id="gpt_title" type="text" placeholder="Insert the Title here"><br>
            <label for="prompt">Prompt:</label><br>
            <textarea name="prompt" id="prompt" rows="4" cols="50" placeholder="Insert the prompt here..."></textarea><br><br> -->
            <!-- <input type="submit" name="create_post" class="button button-primary" value="Create Post"> -->
            <input type="submit" name="fetch_metadata" class="button button-primary" value="Fetch Metadata and Create Post">
        </form>
        <?php

        if (isset($_POST['fetch_metadata']) && !empty($_POST['url'])) {
            $url = sanitize_text_field($_POST['url']);
            $metadata = fetch_metadata_from_url($url);
            if ($metadata) {
                echo '<p>Meta Title: ' . esc_html($metadata['title']) . '</p>';
                echo '<p>Meta Description: ' . esc_html($metadata['description']) . '</p>';
                // Add JavaScript to populate the post title input field.
                echo '<script>';
                echo 'document.getElementById("gpt_title").value = "' . esc_js($metadata['title']) . '";';
                echo '</script>';
                $title = sanitize_text_field($metadata['title']);
                $prompt = sanitize_text_field($metadata['title']);
                create_new_post($title, $prompt, 'publish');
                echo '<div class="updated"><p>New post created successfully.</p></div>';
            } else {
                echo '<p>Error fetching metadata.</p>';
            }
        }
        // if (isset($_POST['create_post']) && !empty($_POST['prompt'])) {
        //     $title = '';
        //     $prompt = sanitize_text_field($_POST['prompt']);
        //     create_new_post($title, $prompt, 'publish');
        //     echo '<div class="updated"><p>New post created successfully.</p></div>';
        // }
        ?>
    </div>
<?php
}

function fetch_metadata_from_url($url)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $html = curl_exec($ch);
    curl_close($ch);

    if ($html === false) {
        return false;
    }

    $metadata = array();
    if (preg_match('/<title>(.*?)<\/title>/i', $html, $matches)) {
        $metadata['title'] = $matches[1];
    }

    if (preg_match('/<meta name="description" content="(.*?)"/i', $html, $matches)) {
        $metadata['description'] = $matches[1];
    }

    return $metadata;
}

function create_new_post_settings_page()
{
?>
    <div class="wrap">
        <h2>OpenAI Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('openai_settings_group'); ?>
            <?php do_settings_sections('create-new-post-settings'); ?>

            <input type="submit" class="button button-primary" value="Save API Key">
        </form>
    </div>
<?php
}
function openai_register_settings()
{
    register_setting('openai_settings_group', 'openai_api_key');
    add_settings_section('openai_settings_section', 'OpenAI API Settings', null, 'create-new-post-settings');
    add_settings_field('openai_api_key', 'API Key', 'openai_api_key_callback', 'create-new-post-settings', 'openai_settings_section');
}
add_action('admin_init', 'openai_register_settings');

function openai_api_key_callback()
{
    $api_key = get_option('openai_api_key');
    echo '<input type="text" id="openai_api_key" name="openai_api_key" value="' . esc_attr($api_key) . '" />';
}

function save_openai_api_key()
{
    if (isset($_POST['openai_api_key'])) {
        update_option('openai_api_key', sanitize_text_field($_POST['openai_api_key']));
    }
}
add_action('admin_post_save_openai_api_key', 'save_openai_api_key');
