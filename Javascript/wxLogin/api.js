/**
* 获取code
* @method getJscode
*/
function getJscode() {
  let auth = `https://www.kongquechao.com/api/api/getCode?host=${encodeURIComponent(
    window.location.href
  )}`;
  window.open(auth, "_self");
}

/**
 * 获取微信js-sdk参数
 * @param {String} url 当前页面的url
 * @return {Object} 获取到的参数
 * 
*/
function getShareParam(url) {
  return axios({
    url: urls.getShareParam,
    data: { url },
    method:"post"
  }).then(res=>[null,res]).catch(err=>[err])
}