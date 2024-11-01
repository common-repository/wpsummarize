(function() {
    tinymce.create('tinymce.plugins.WPSummarize', {
        init : function(ed, url) {
            ed.addButton('wpsummarize_button', {
                title : 'Insert WPSummarize Shortcode',
                text : 'WPSummarize', // This will show text instead of an icon
                cmd : 'wpsummarize_command',
                // icon : 'dashicons-list-view' // Comment out or remove this line
            });
            ed.addCommand('wpsummarize_command', function() {
                ed.insertContent('[wpsummarize]');
            });
        },
    });
    tinymce.PluginManager.add('wpsummarize_button', tinymce.plugins.WPSummarize);
})();