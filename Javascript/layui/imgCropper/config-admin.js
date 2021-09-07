var BASE_URL = document.scripts[document.scripts.length - 1].src.substring(0, document.scripts[document.scripts.length - 1].src.lastIndexOf("/") + 1);
window.BASE_URL = BASE_URL;
require.config({
    urlArgs: "v=" + CONFIG.VERSION,
    baseUrl: BASE_URL,
    paths: {
        "jquery": ["plugs/jquery-3.4.1/jquery-3.4.1.min"],
        "qrcode": ["plugs/qrcodejs/qrcode.min"],
        "jquery-particleground": ["plugs/jq-module/jquery.particleground.min"],
        "echarts": ["plugs/echarts/echarts.min"],
        "echarts-theme": ["plugs/echarts/echarts-theme"],
        "easy-admin": ["plugs/easy-admin/easy-admin"],
        "layuiall": ["plugs/layui-v2.5.6/layui.all"],
        "layui": ["plugs/layui-v2.5.6/layui"],
        "miniAdmin": ["plugs/lay-module/layuimini/miniAdmin"],
        "miniMenu": ["plugs/lay-module/layuimini/miniMenu"],
        "miniTab": ["plugs/lay-module/layuimini/miniTab"],
        "miniTheme": ["plugs/lay-module/layuimini/miniTheme"],
        "miniTongji": ["plugs/lay-module/layuimini/miniTongji"],
        "treetable": ["plugs/lay-module/treetable-lay/treetable"],
        "treeTable": ["plugs/lay-module/treeTable/treeTable"],
        "tableSelect": ["plugs/lay-module/tableSelect/tableSelect"],
        "iconPickerFa": ["plugs/lay-module/iconPicker/iconPickerFa"],
        "autocomplete": ["plugs/lay-module/autocomplete/autocomplete"],
        "locationX": ["plugs/lay-module/location/locationX"],
        "location": ["plugs/lay-module/location/location"],
        "vue": ["plugs/vue-2.6.10/vue.min"],
        "ckeditor": ["plugs/ckeditor4/ckeditor"],
        "layedit": ["plugs/layui-v2.5.6/lay/modules/layedit"],
        "dropdown": ["plugs/layui-v2.5.6/lay/modules/dropdown"],
        "lay": ["plugs/layui-v2.5.6/lay/modules/lay"],
        "table": ["plugs/layui-v2.5.6/lay/modules/table"],
        "excel": ["plugs/layui-v2.5.6/lay/modules/excel"],
        "dropMenu": ["plugs/lay-module/dropMenu/dropMenu"],
        "ckplayer": ["plugs/lay-module/ckplayer/ckplayer"],
        "layfilter": ["plugs/lay-module/layfilter/layfilter"],
        "laymock": ["plugs/lay-module/laymock/laymock"],
        "numinput": ["plugs/lay-module/numinput/numinput"],
        "textool": ["plugs/lay-module/textool/textool"],
        "selectMore": ["plugs/lay-module/selectMore/selectMore"],
        "webuploader": ["plugs/lay-module/webupload/uploader/webuploader"],
        "layWebupload": ["plugs/lay-module/webupload/layWebupload"],
        "svgPicker": ["plugs/lay-module/svgPicker/svgPicker"],
        "cropper": ["plugs/cropper/cropper"],
        "customCropper": ["plugs/cropper/customCropper"],
    },
    shim: {
        'customCropper': {
            deps: ['jquery', 'cropper'],
            exports: 'customCropper',
        }
    }
});

// 路径配置信息
var PATH_CONFIG = {
    iconLess: BASE_URL + "plugs/font-awesome-4.7.0/less/variables.less",
    svgJson: BASE_URL + "admin/js/svg-icons/iconfont.json",
};
window.PATH_CONFIG = PATH_CONFIG;

// 初始化控制器对应的JS自动加载
if ("undefined" != typeof CONFIG.AUTOLOAD_JS && CONFIG.AUTOLOAD_JS) {
    require([BASE_URL + CONFIG.CONTROLLER_JS_PATH], function (Controller) {
        if (eval('Controller.' + CONFIG.ACTION)) {
            eval('Controller.' + CONFIG.ACTION + '()');
        }
    });
}