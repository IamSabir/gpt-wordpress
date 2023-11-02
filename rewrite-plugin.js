jQuery(document).ready(function ($) {
    $('#openai-rewrite-button').on('click', function () {
        var post_id = $(this).data('post-id');
        console.log(post_id);

        // Send an AJAX request to your server to initiate the rewriting process.
        $.ajax({
            type: 'POST',
            url: rewrite_plugin_data.ajax_url,
            data: {
                action: 'rewrite_content',
                post_id: post_id,
            },
            success: function (response) {
                alert(response.data);
            },
        });
    });
});
