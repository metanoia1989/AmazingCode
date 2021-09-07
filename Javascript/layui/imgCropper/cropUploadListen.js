cropUpload: function () {
    var uploadList = document.querySelectorAll("[data-crop-upload]");

    if (uploadList.length > 0) {
        $.each(uploadList, function (i, v) {
            var exts = "png|jpg|ico|jpeg"
                uploadName = $(this).attr('data-crop-upload'),
                uploadNumber = $(this).attr('data-upload-number'),
                uploadDir = $(this).attr('data-upload-dir')||'1-met/Uploads',
                uploadSign = $(this).attr('data-upload-sign'),
                uploadType = $(this).attr('data-upload-type')||'',
                uploadBucket = $(this).attr('data-upload-bucket')||'',
                uploadDirId = $(this).attr('data-upload-dir-id')||'',
                uploadDirDate = $(this).attr('data-upload-dir-date')||0,//取消目录内日期 0取消 1不取消
                uploadSize = $(this).attr('data-upload-size')||0;
                imgWidth = $(this).attr('data-img-width')||400;
                imgHeight = $(this).attr('data-img-height')||400;
            
            uploadSign = uploadSign || '|';
            var elem = "input[name='" + uploadName + "']",
                uploadElem = this;
            
            // 文件扩展名以及文件大小校验
            customCropper.render({
                elem: uploadElem,
                saveW:imgWidth,
                saveH:imgHeight,
                size: uploadSize,
                mark:1/1,    //选取比例
                area:'900px',  //弹窗宽度
                url: admin.url(init.upload_url), //图片上传接口返回和（layui 的upload 模块）返回的JOSN一样
                data: {upload_dir: uploadDir+uploadDirId,upload_date:uploadDirDate,bucket:uploadBucket,upload_type:uploadType},
                exts: exts,
                size: uploadSize,
                done: function(url){ //上传完毕回调
                    console.log("回调的数据", url)
                    $(elem).val(url);
                    $(elem).trigger("input");
                }
            }); 

            // 监听上传input值变化
            $(elem).bind("input propertychange", function (event) {
                var urlString = $(this).val(),
                    urlArray = urlString.split(uploadSign),
                    uploadIcon = $(uploadElem).attr('data-upload-icon');
                uploadIcon = uploadIcon || "file";
                $('#bing-' + uploadName).remove();
                if (urlString.length > 0) {
                    var parant = $(this).parent('div');
                    var liHtml = '';
                    $.each(urlArray, function (i, v) {
                        if(uploadIcon==='video'){
                            liHtml += '<li><video src="' + v + '" controls="controls" width="320" height="240"></video><small class="uploads-delete-tip bg-red badge" data-upload-delete="' + uploadName + '" data-upload-url="' + v + '" data-upload-sign="' + uploadSign + '">×</small></li>\n';
                        }else if(uploadIcon==='audio'){
                            liHtml += '<li><audio src="' + v + '" controls="controls" width="300" height="54"></audio><small class="uploads-delete-tip bg-red badge" data-upload-delete="' + uploadName + '" data-upload-url="' + v + '" data-upload-sign="' + uploadSign + '">×</small></li>\n';
                        }else if(uploadIcon==='flash'){
                            liHtml += '';
                        }else{
                            liHtml += '<li><a><img src="' + v + '" data-image  onerror="this.src=\'' + BASE_URL + 'admin/images/upload-icons/' + uploadIcon + '.png\';this.onerror=null"></a><small class="uploads-delete-tip bg-red badge" data-upload-delete="' + uploadName + '" data-upload-url="' + v + '" data-upload-sign="' + uploadSign + '">×</small></li>\n';
                        }

                    });
                    parant.after('<ul id="bing-' + uploadName + '" class="layui-input-block layuimini-upload-show">\n' + liHtml + '</ul>');
                }

            });

            // 非空初始化图片显示
            if ($(elem).val() !== '') {
                $(elem).trigger("input");
            }
        });
    }
},