// 请求方法
/**
 * url 请求地址
 * data 请求数据
 * header 带有签名信息的请求头
 */
export function request(url,data,header){
  console.log(url,header)
  return new Promise((resolve,reject) => {
    wx.request({
      url: url,
      data: data,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        resolve(res);               
      },
      fail: (res) => { },
    })
  })
  
}