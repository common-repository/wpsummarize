
( function( blocks, element ) {
    var el = element.createElement;

    blocks.registerBlockType( 'wpsummarize/wpsummarize', {
        title: 'WPSummarize',
        icon: 'list-view',
        category: 'common',
        edit: function() {
            return el(
                'div',
                { className: 'wpsummarize-block' },
                'WP Summarize Shortcode'
            );
        },
        save: function() {
            return '[wpsummarize]';
        },
    } );
} )( window.wp.blocks, window.wp.element );


