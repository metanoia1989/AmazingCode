/**
 * 给a标签字符串添加 _blank 
 * 
 * @param {string} s 
 * @returns string
 */
function addBlankTargets(s) {
  return (""+s).replace(/<a\s+href=/gi, '<a target="_blank" href=');
}

function addBlankTargets(s) {
  var p = $('<p>' + s + '</p>');
  p.find('a').attr('target', '_blank');
  return p.html();
}