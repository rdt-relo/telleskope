(function ($) {
    $.fn.initRedactor = function (id, context,plugins,fontcolors,repositionable_images) {
        plugins = (typeof plugins !== 'undefined') ? plugins : [];
        plugins.unshift('alignment');
        // fontcolors = ['#f5f5f5','#505050'].concat((typeof fontcolors !== 'undefined') ? fontcolors : []);
        repositionable_images = (typeof repositionable_images !== 'undefined') ? repositionable_images : false;
        $R('#'+id, 'destroy');
        $R('#' + id,
            {
                imageUpload: !context ? false : 'ajax_upload.php?uploadRedactorMedia='+context,
                callbacks: {
                    image: { uploadError: function(response) {swal.fire({title: 'Error!',text:response.message});}}
                },
                imageResizable: true,
				imageLink: true,
                imagePosition : repositionable_images,
                linkNewTab: false,
                linkTarget: '_blank',
                linkTitle: true,
                linkNofollow: true,
                linkSize: 256,
                linkValidation: true,
                styles: true,
                minHeight: '300px',
                buttons: ['redo', 'undo', 'format', 'bold', 'italic', 'underline', 'ul', 'ol', 'line', 'link', 'image',
					// 'indent',
					// 'outdent
				],
                formatting: ['p', 'blockquote'],
                pasteLinkTarget: '_blank',
                pasteImages: true, //Since we have clipboardUpload set to true.
                pastePlainText: false,
                pasteBlockTags: ['ul', 'ol', 'li', 'p','blockquote'],
                pasteInlineTags: ['a', 'br', 'b', 'u', 'i'],
                // Uploads
                dragUpload: true,
                multipleUpload: false,
                clipboardUpload: true,
                plugins: plugins,
                // fontcolors: fontcolors,
                toolbarFixedTopOffset: 68
            }
        );
    };

})(jQuery);
