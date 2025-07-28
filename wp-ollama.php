<?php
/*
Plugin Name: WP Ollama Chatbot
Description: A chatbot plugin that interacts with the Ollama API and renders via [wp-ollama] shortcode.
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) exit;

// Enqueue scripts and styles
function wp_ollama_enqueue_scripts() {
    wp_enqueue_script('wp-ollama-chatbot', plugins_url('chatbot.js', __FILE__), array('jquery'), '1.0', true);
    wp_enqueue_style('wp-ollama-chatbot-style', plugins_url('chatbot.css', __FILE__));
    wp_localize_script('wp-ollama-chatbot', 'wpOllama', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'wp_ollama_enqueue_scripts');

// Shortcode to render chatbot
function wp_ollama_shortcode() {
    return '<div id="wp-ollama-chatbot"></div>';
}
add_shortcode('wp-ollama', 'wp_ollama_shortcode');

// AJAX handler for chat
add_action('wp_ajax_wp_ollama_chat', 'wp_ollama_chat_handler');
add_action('wp_ajax_nopriv_wp_ollama_chat', 'wp_ollama_chat_handler');

function wp_ollama_chat_handler() {
    $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
    if (!$message) {
        wp_send_json_error('No message provided');
    }
    // Always use gemma3:4b model
    $api_url = 'http://ai.lipovcan.cz:11434/api/chat';
    $body = json_encode(array(
        'model' => 'gemma3:4b',
        'message' => $message
    ));
    $response = wp_remote_post($api_url, array(
        'body' => $body,
        'headers' => array('Content-Type' => 'application/json'),
        'timeout' => 30
    ));
    if (is_wp_error($response)) {
        wp_send_json_error('API request failed: ' . $response->get_error_message());
    }
    $raw_body = wp_remote_retrieve_body($response);
    $data = json_decode($raw_body, true);
    // Extract the actual response text for the frontend
    $bot_response = isset($data['message']) ? $data['message'] : (isset($data['response']) ? $data['response'] : '');
    if (!$bot_response || trim($bot_response) === '') {
        $debug_info = array(
            'error' => 'No response from API',
            'api_raw_body' => $raw_body,
            'api_decoded' => $data,
            'request_body' => $body
        );
        wp_send_json_error($debug_info);
    }
    // Always return response as an object with role and content
    wp_send_json_success(array('response' => array('role' => 'assistant', 'content' => $bot_response)));
}
