/**
 * 获取深圳图书馆 access token，用于免登录
 */ 
function getSzlibAccessToken() {
    var token = document.cookie.split('; ').find(item => item.startsWith('accessToken_szlib='))
    if (token === undefined) {
        return null
    }
    return token.split('=')[1]
}

function excute_query_reader(){
    // 深圳图书馆读者登陆获取token ---开始
    var szlib_token = getSzlibAccessToken()
    var szlib_code = null;
    if(szlib_token){
        if(true || !$.cookie('access_token')){
            $.post(host+'/home/auth_test/szlib',{"code":szlib_code,"client_secret":"xxxxx", "access_token": szlib_token},function(data){ 
                set_cookie()
                var returnCode = data.error;
                if (returnCode == 0) {
                    // history.replaceState(null, null, host);//去掉地址参数
                    readyLoginJungle()
                    $(".welcomeText").html(data.organizationName+'用户，欢迎您!');
                } else {
                    $('.help-block2').html(data.info);
                };
            })
        }

    }
    // 深圳图书馆读者登陆获取token ---结束 
}

function set_cookie(expire) {
    var str = 'PHPSESSID=mvb4ptv0m91iiqh5v9t2macua3; Hm_lvt_ec5492d102b5b4fc4e02ed51f134958e=1634277872; Hm_lpvt_ec5492d102b5b4fc4e02ed51f134958e=1634279477; ';
    let date = new Date();
    date.setTime(date.getTime() + expire);
    str.split('; ').map(item => {
        const expires = "expires=" + date.toUTCString();
        document.cookie = item + "; " + expires + "; path=/";
    })
}