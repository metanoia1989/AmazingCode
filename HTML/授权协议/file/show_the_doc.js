
var read_the_doc = {};

//word 
read_the_doc.word = function (parm,content_id) {

    var options = {
        height: "1080px",
        pdfOpenParams: { view: 'FitV', page: '0' },
        name: "mans",
        fallbackLink: "<p>您的浏览器暂不支持此pdf，请下载最新的浏览器</p>"
    };

    PDFObject.embed(parm, content_id, options);

}




