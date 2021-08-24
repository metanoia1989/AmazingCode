  /**
   * 异常日志记录
   * @param {*} fun 
   * @param {*} msg 
   */
  function errorLog(fun,msg){
    var that = this;
    var mobile,wxcode,minicode,os,pagepath,curfunction,errormsg;
    wx.getSystemInfo({
      success(res){
        //console.log('res',res)
        mobile =res.brand +' ' + res.model;
        wxcode = res.version;
        os = res.platform; 
      }
    })
    const accountInfo = wx.getAccountInfoSync();
    minicode = (accountInfo.miniProgram.version ?accountInfo.miniProgram.version:'0.0.0');//小程序版本号
    pagepath = this.route;
    curfunction = fun;
    errormsg = msg;
    //console.log(mobile,wxcode,minicode,os,pagepath,curfunction,errormsg)
    var data = {
      'user_id': that.data.userId,
      'mobile':mobile,
      'wx_code':wxcode,
      'mini_code':minicode,
      'os':os,
      'page_path':pagepath,
      'function':curfunction,
      'content':errormsg
    }
    wx.request({
      url:configs.hostUrl + 'home/Index/error_log',
      method:'post',
      header: {'content-type': 'application/x-www-form-urlencoded'},
      data:data,
      async: false,
      success:(res)=>{
        //console.log(res) 
      },
      fail:(res)=>{
       // console.log(res)
      },
      complete:(res)=>{
      }
    })
  }