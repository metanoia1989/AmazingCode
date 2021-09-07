/*!
 * Cropper v3.0.0
 */

layui.config({
    base: '/static/plugs/cropper/' //layui自定义layui组件目录
}).define(['jquery','layer','cropper'],function (exports) {
    var $ = layui.jquery
        ,layer = layui.layer;
    var obj = {
        error: function (errMsg) {
            return layer.msg(errMsg, {icon: 2, shade: [0.02, '#000'], scrollbar: false, time: 3000, shadeClose: true,zIndex:29891014});
        },
        render: function(e){
            var self = this,
                elem = e.elem,
                saveW = e.saveW,
                saveH = e.saveH,
                mark = e.mark,
                area = e.area,
                url = e.url,
                size = e.size,
                data = e.data,
                exts= e.exts,
                done = e.done;
            var limitTips = size == 0 ? '' : `，大小在${size}kb以内`
            var html =  `
                <link rel="stylesheet" href="/static/plugs/cropper/cropper.css" />
                <div class="layui-fluid showImgEdit" style="margin-top: 15px;padding-bottom: 10px;">
                    <div class="layui-form-item">
                        <div class="layui-input-inline layui-btn-container" style="width: auto;">
                            <label for="cropper_avatarImgUpload" class="layui-btn layui-btn-primary">
                                <i class="layui-icon">&#xe67c;</i>选择图片
                            </label>
                            <input class="layui-upload-file" id="cropper_avatarImgUpload" type="file" value="选择图片" name="file">
                        </div>
                        <div class="layui-form-mid layui-word-aux">图片的尺寸限定${saveW}x${saveH}px${limitTips}</div>
                    </div>
                    <div class="layui-row layui-col-space15">
                        <div class="layui-col-xs9">
                            <div class="readyimg" style="height:450px;background-color: rgb(247, 247, 247);">
                                <img src="" >
                            </div>
                        </div>
                        <div class="layui-col-xs3">
                            <div class="img-preview" style="width:200px;height:200px;overflow:hidden">
                            </div>
                        </div>
                    </div>
                    <div class="layui-row layui-col-space15">
                        <div class="layui-col-xs9">
                            <div class="layui-row">
                                <div class="layui-col-xs5">
                                    <button type="button" class="layui-btn layui-icon layui-icon-left" cropper-event="rotate" data-option="-15" title="Rotate -90 degrees"> 向左旋转</button>
                                    <button type="button" class="layui-btn layui-icon layui-icon-right" cropper-event="rotate" data-option="15" title="Rotate 90 degrees"> 向右旋转</button>
                                </div>
                                <div class="layui-col-xs7" style="text-align: right;">
                                    <button type="button" class="layui-btn" title="">移动</button>
                                    <button type="button" class="layui-btn" title="">放大图片</button>
                                    <button type="button" class="layui-btn" title="">缩小图片</button>
                                    <button type="button" class="layui-btn layui-icon layui-icon-refresh" cropper-event="reset" title="重置图片"></button>
                                </div>
                            </div>
                        </div>
                        <div class="layui-col-xs3">
                            <button class="layui-btn layui-btn-fluid" cropper-event="confirmSave" type="button"> 保存修改</button>
                        </div>
                    </div>
                </div>
            `
            var image, preview, file, options 
            var index;
            $(elem).on('click',function () {
                index = layer.open({
                    type: 1
                    , content: html
                    , area: area
                    , success: function () {
                        image = $(".showImgEdit .readyimg img")
                        preview = '.showImgEdit .img-preview'
                        file = $(".showImgEdit input[name='file']")
                        options = {aspectRatio: mark,preview: preview,viewMode:1}
                        image.cropper(options);
                    }
                    , cancel: function (index) {
                        layer.close(index);
                        image.cropper('destroy');
                    }
                });
            });
            $("body").on('click', '.layui-btn',function () {
                var event = $(this).attr("cropper-event");
                //监听确认保存图像
                if(event === 'confirmSave'){
                    var cropObj = image.cropper("getCroppedCanvas",{
                        width: saveW,
                        height: saveH
                    })
                    if (!cropObj) {
                        return obj.error('图片不能空！')
                    }
                    cropObj.toBlob(function(blob){
                        var formData=new FormData();
                        formData.append('file',blob,'head.jpg');
                        for (let key in data) {
                            formData.append(key, data[key]);
                        }
                        var loadIndex = layer.load(2, {time: 10*1000});
                        $.ajax({
                            method:"post",
                            url: url, //用于文件上传的服务器端请求地址
                            data: formData,
                            processData: false,
                            contentType: false,
                            success:function(result){
                                layer.close(loadIndex)
                                if(result.code == 1){
                                    layer.msg(result.msg,{icon: 1});
                                    layer.close(index)
                                    return done(result.data.url);
                                }else if(result.code == 0){
                                    layer.alert(result.msg,{icon: 2});
                                }

                            },finally: () => {
                                layer.close(loadIndex)
                            }
                        });
                    });
                    //监听旋转
                }else if(event === 'rotate'){
                    var option = $(this).attr('data-option');
                    image.cropper('rotate', option);
                    //重设图片
                }else if(event === 'reset'){
                    image.cropper('reset');
                }
                //文件选择
                file.change(function () {
                    var r= new FileReader();
                    var f=this.files[0];
                    var errMsg = ""
                    if (!f.name.split('.').pop().match(exts)) {
                        errMsg = "无效的文件类型，图片文件后缀必须为" + exts.split('|').join("、")
                    }
                    if (f.size / 1024 > size) {
                        errMsg = `文件大小不能超过${size}kb`
                    }

                    if (errMsg) {
                        return obj.error(errMsg)
                    }
                    r.readAsDataURL(f);
                    r.onload=function (e) {
                        image.cropper('destroy').attr('src', this.result).cropper(options);
                    };
                });
            });
        }

    };
    exports('customCropper', obj);
});