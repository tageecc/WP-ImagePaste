setTimeout(function() {
    var iframe = document.getElementById('content_ifr');
    if (iframe) {
        iframe.contentWindow.addEventListener('paste', function(e) {

            var pasteDate = e.clipboardData ? e.clipboardData.items[0] : null;

            if (!pasteDate) return false;
            //判断是否是粘贴图片
            if (pasteDate.kind === 'file' && pasteDate.type.match(/^image\//i)) {
                var that = this,
                    reader = new FileReader();
                file = pasteDate.getAsFile();

                //ajax上传图片
                reader.onload = function(e) {
                    var xhr = new XMLHttpRequest();
                    var formData = new FormData();

                    xhr.open('POST', ajax_wp_imagepaste.uploadimage, true);
                    xhr.onload = function() {
                        var res = JSON.parse(xhr.responseText);

                        var img = document.createElement('img');
                        img.setAttribute('src', res.url);
                        if (res.code === 100) {

                            iframe.contentWindow.document.body.append(img);

                        }
                    }

                    // this.result得到图片的base64 
                    formData.append('file', this.result);
                    formData.append('action', 'upload_image');;

                    xhr.send(formData);
                }
                reader.readAsDataURL(file);
            }
        }, false);
    }


    document.getElementById('compressed').addEventListener('click', function() {
        document.getElementById("compress_num").disabled = !this.checked;
    });
    document.getElementById('watermark').addEventListener('click', function() {
        document.getElementById("watermark_type").disabled = !this.checked;
        document.getElementById("watermark_txt").disabled = !this.checked;
    });

}, 1000);
