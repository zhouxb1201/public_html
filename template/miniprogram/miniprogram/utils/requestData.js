var md5 = require('./md5.js');

//-----------封装签名请求--------
function requestSignFn(data) {
  
  var signString = ''; 

  if (data && typeof data == 'object') {
    for (const key in data) {
      if (data.hasOwnProperty(key)) {
        data[key] = data[key] === undefined ? '': data[key]
      }
    }
    signString = Object.keys(data).join('')
  }

  let key = 'P6l0Gx9p7Qsijklz';
  
  let sdata = md5.md5(key + signString)
  
  return sdata
}


module.exports = {
  requestSign : requestSignFn ,  
}