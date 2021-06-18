//http.j小程序
import axios from 'axios'
import mpAdapter from 'axios-miniprogram-adapter'
axios.defaults.adapter = mpAdapter

import { loginRedirect } from './util'
import { store } from '../store/index'
let baseURL = ''
let headers = {}
export const http = axios.create({
  baseURL,
  headers,
})
const requestInterceptor = config => {
  config.headers.token = wx.getStorageSync('token')
  return config
}
const responseInterceptor = response => {
  console.log(response.config.url, response)
  if (response.data.code === 1) {
    return Promise.resolve(response.data)
  } else if (response.data.code === -1) {
    return wxp.login().then(({ code }) => {
      return http
        .post('/auth/login', {
          code,
          source: 2,
        })
        .then(({ data }) => {
          store.setUserInfo(data.userInfo)
          store.setMemberInfo(data.memberInfo)
          wx.setStorageSync('token', data.userInfo.token)
          return http(response.config)
        })
        .catch(error => {
          loginRedirect()
          return Promise.reject(error)
        })
    })
  } else {
    if (response.data.msg !== '用户未绑定小程序') {
      wx.showToast({ title: response.data.msg, icon: 'none' })
    }
    return Promise.reject(response.data)
  }
}

const responseError = error => {
  return Promise.reject(error.message)
  if (error.response.status === 401) {
    loginRedirect()
  } else {
    wx.showToast({ title: error.message || error.msg, icon: 'none' })
  }
}
//请求拦截器
http.interceptors.request.use(requestInterceptor)
//响应拦截器
http.interceptors.response.use(responseInterceptor, responseError)

/**
 * 上传图片
 * @param {String} tempFilePath 图片路径
 */
export const uploadImage = tempFilePath => {
  return wx.uploadFile({
    url: baseURL + '/weapp' + '/common/uploadali',
    filePath: tempFilePath,
    name: 'file',
    formData: {
      file: tempFilePath,
    },
    header: {
      'Content-Type': 'multipart/form-data',
      token: wx.getStorageSync('token'),
      ...headers,
    },
  })
}

/**
 * 统一支付方法
 * @param {'3' | '1'} payType - 支付方式：3：微信，1：余额
 * @param {Number} orderId - 订单id
 */
export const payMent = (payType, orderId) => {
  const tp = typeof payType
  if (tp !== 'string') {
    console.error('支付方式仅限字符串')
    payType = String(payType)
  }
  wx.showLoading({
    title: '支付中...',
  })
  if (payType === '1') {
    return http
      .post('/order/pay', {
        order_id: orderId,
        pay_type: payType,
      })
      .then(res => {
        wx.showToast({
          title: '支付成功',
          icon: 'none',
        })
        return Promise.resolve(res)
      })
      .catch(err => {
        wx.showToast({
          title: err.msg || '支付失败',
          icon: 'none',
        })
        return Promise.reject(err)
      })
  }
  if (payType === '3') {
    return http
      .post('/order/pay', {
        order_id: orderId,
        pay_type: payType,
      })
      .then(({ data }) => {
        const pay = JSON.parse(data.pay)
        return wxp
          .requestPayment({
            timeStamp: pay.timeStamp,
            nonceStr: pay.nonceStr,
            package: pay.package,
            signType: pay.signType,
            paySign: pay.paySign,
          })
          .then(() => {
            wx.showToast({
              title: '支付成功',
              icon: 'none',
            })
          })
          .catch(err => {
            wx.showToast({
              title: '支付失败',
              icon: 'none',
            })
            if (err.errMsg === 'requestPayment:fail cancel') {
              err.errMsg = '用户取消支付'
            }
            return Promise.reject(err)
          })
      })
  }
}

const commonHttp = axios.create({
  baseURL,
  headers,
})

commonHttp.interceptors.request.use(requestInterceptor)
commonHttp.interceptors.response.use(responseInterceptor, responseError)

/**
 * 发送短信
 * @param {(String|Number)} mobile - 手机号
 * @param {'mobilelogin'} event - 事件
 */
export const sendSms = async (mobile, event) => {
  return commonHttp.get('/api/sms/send', {
    params: {
      mobile,
      event,
      from: 3,
    },
  })
}

