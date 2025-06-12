(function ($) {
    $.fn.initRedactor = function (id, context,plugins,fontcolors,language) {
        plugins = (typeof plugins !== 'undefined') ? plugins : [];
        plugins.unshift('alignment');
        fontcolors = (typeof fontcolors !== 'undefined') ? (fontcolors.length ? (['#f5f5f5','#505050'].concat(fontcolors)) : '' ) : [];
        language = (typeof language !== 'undefined') ? language : 'en';

        config = {
            imageUpload: function(formData, files, e, upload)
            {
                let promise = new Promise(function (resolve, reject) {
                    $.ajax({
                        url: !context ? false : 'ajax_native.php?confirmImageCopyright=1',
                        type: "get",
                        success: function (msg) {
                            Swal.fire({
                                title: '',
                                text: msg,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#3085d6',
                                cancelButtonColor: '#d33',
                                confirmButtonText: 'Yes, continue'
                            }).then((result) => {
                                if (result.value) {
                                    var url = 'ajax_native.php?uploadRedactorMedia='+context;
                                    var xhr = new XMLHttpRequest();
                                    xhr.onreadystatechange = function (e) {
                                        if (xhr.readyState === 4) {
                                            if (xhr.status === 200) {
                                                let jr = JSON.parse(xhr.response);
                                                if (jr.error) {
                                                    reject(jr.message);
                                                } else {
                                                    resolve(jr);
                                                }
                                            } else {
                                                reject(xhr.status);
                                            }
                                        }
                                    }
                                    xhr.open('post', url);
                                    xhr.send(formData);
                                } else {
                                    upload.complete({
                                        "error": true,
                                        "message": "Upload canceled"
                                    });
                                    $(".redactor-modal-box").addClass("redactor-animate-hide");
                                    $('.redactor-overlay').css({
                                        'z-index' : '-1',
                                        'background-color' : 'transparent'
                                    });
                                }
                            });
                        }
                    })

                }).then(function (response) {
                    // success
                    upload.complete(response);
                }).catch(function (response) {
                    // fail
                    upload.complete({
                        "error": true,
                        "message": "Upload Error: "+response,
                    });
                });
                return promise;
            },
            callbacks: {
                image: {
                    uploadError: function(response) {swal.fire({title: 'Error!',text:response.message});},
                    uploaded: function(image, response)
                    {
                        let img_width = response.file.img_width;
                        if (img_width > 0 && img_width < 600) {
                            const imgObjects = image.getElementsByTagName("img");
                            if (imgObjects) {
                                imgObjects[0].setAttribute("width", img_width);
                                imgObjects[0].setAttribute("data-img-600px-safe", 1);
                            }
                        }
                    }
                }
            },
            imageResizable: false,
            imageLink: true,
            imagePosition : false,
            linkNewTab: false,
            linkTarget: '_blank',
            linkTitle: true,
            linkNofollow: true,
            linkSize: 256,
            linkValidation: true,
            styles: false,
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
            lang: language,
            // handle:'ajax_native.php?searchHashTag=1',
            // handleStart: 2,
            // handleTrigger: '#'
        }

    if (fontcolors.length){
        config.fontcolors = fontcolors;
    }
    
	$R('#'+id, 'destroy');
        $R('#' + id,
            config
        );
    };

})(jQuery);
