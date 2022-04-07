/*
 * @Author: AdamSmith
 * @Date: 2022-01-22 10:39:20
 * @LastEditors: AdamSmith
 * @LastEditTime: 2022-04-07 10:02:36
 * @Description: 
 * email: sogaxili@gmail.com
 * Copyright (c) 2022 by AdamSmith, All Rights Reserved. 
 */
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

function isEmptyObj(obj) {
  return obj // 👈 null and undefined check
    && Object.keys(obj).length === 0
    && Object.getPrototypeOf(obj) === Object.prototype
}

// Pre-ECMA 5: 
function isEmpty(obj) {
  for(var prop in obj) {
    if(Object.prototype.hasOwnProperty.call(obj, prop)) {
      return false;
    }
  }

  return JSON.stringify(obj) === JSON.stringify({});
}