import { BASESRC, formatDate, handleErrorSrc } from './util'

// 处理商品价格符号
const yuan = (value) => {
  return "¥ " + parseFloat(value ? value : 0).toFixed(2);
}

// 处理价格转为保留小数点后两位字符串
const priceToFixed = (value, yuan) => {
  return (yuan ? yuan + parseFloat(value).toFixed(2) : parseFloat(value).toFixed(2))
}

export {
  BASESRC,
  yuan,
  priceToFixed,
  formatDate,
  handleErrorSrc
}