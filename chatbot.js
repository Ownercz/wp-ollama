// Basic chatbot UI
jQuery(document).ready(function($){
    var $chatbot = $('#wp-ollama-chatbot');
    $chatbot.html('<div class="ollama-chat-window"></div><input type="text" class="ollama-chat-input" placeholder="Type your message..."><button class="ollama-chat-send">Send</button>');
    var $window = $chatbot.find('.ollama-chat-window');
    var $input = $chatbot.find('.ollama-chat-input');
    var $send = $chatbot.find('.ollama-chat-send');

    function appendMessage(sender, text) {
        $window.append('<div class="ollama-msg ollama-msg-'+sender+'">'+text+'</div>');
        $window.scrollTop($window[0].scrollHeight);
    }

    $send.on('click', function(){
        var msg = $input.val();
        if (!msg) return;
        appendMessage('user', msg);
        $input.val('');
        $.post(wpOllama.ajax_url, { action: 'wp_ollama_chat', message: msg }, function(res){
            if (res.success && res.data && res.data.response) {
                appendMessage('bot', res.data.response);
            } else {
                appendMessage('bot', 'Error: Could not get response.');
            }
        });
    });

    $input.on('keypress', function(e){
        if (e.which === 13) $send.click();
    });
});
