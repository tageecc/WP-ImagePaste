document.addEventListener('paste', function(e) {
    console.log(111);
    var pasteDate = e.clipboardData ? e.clipboardData.items[0] : null;

    if (!pasteDate) return false;
    console.log(pasteDate);
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
                console.log(xhr.responseText);
            }

            // this.result得到图片的base64 (可以用作即时显示)
            formData.append('file', this.result);
            formData.append('action', 'upload_image');;
            // that.innerHTML = '<img src="' + this.result + '" alt=""/>';

            xhr.send(formData);
        }
        reader.readAsDataURL(file);
    }
}, false);
