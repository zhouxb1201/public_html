import {
  Toast
} from 'vant'


/**
 * 验证是否为空
 */
const validEmpty = (value, msg) => {
  if (!value || value == '') {
    Toast(msg ? msg : '不能为空')
    return false
  } else {
    return true
  }
}

/**
 * 验证手机号
 */
const validMobile = (value) => {
  let valid = /^1[0-9]{10}$/.test(value);
  if (!valid) {
    if (!value || value == '') {
      Toast('手机号不能为空');
    } else {
      Toast('请填入正确的手机号码');
    }
  }
  return valid;
}

/**
 * 验证用户名
 */
const validUsername = (value, msg) => {
  if (!value || value == '') {
    Toast(msg ? msg : '用户名或者手机不能为空')
    return false
  } else {
    return true
  }
}

/**
 * 验证短信验证
 */
const validMsgcode = (value) => {
  if (!value || value == '') {
    Toast('验证码不能为空')
    return false
  } else if (value.length !== 6) {
    Toast('验证码为6位数')
    return false
  } else {
    return true
  }
}

/**
 * 验证图片验证
 */
const validImgcode = (value) => {
  if (!value || value == '') {
    Toast('图片验证码不能为空')
    return false
  } else if (value.length !== 4) {
    Toast('图片验证码为4位数')
    return false
  } else {
    return true
  }
}

/**
 * 验证密码
 */
const validPassword = (value) => {
  var patrn = /^(\w){6,20}$/;
  if (!value || value == '') {
    Toast('密码不能为空')
    return false
  } else if (!patrn.exec(value)) {
    Toast('只能输入6-20个字母、数字、下划线')
    return false
  } else {
    return true
  }
}

/**
 * 验证两次密码是否符合
 */
const validCheckPassword = (value1, value2) => {
  if (!value2 || value2 == '') {
    Toast('确认密码不能为空')
    return false
  } else if (value1 !== value2) {
    Toast('两次密码不符合')
    return false
  } else {
    return true
  }
}

/**
 * 验证邮箱
 */
const validEmail = (value) => {
  var reg = /^(\w-*\.*)+@(\w-?)+(\.\w{2,})+$/;
  if (value === "") {
    Toast("邮箱不能为空！");
    return false
  } else if (!reg.test(value)) {
    Toast("请输入正确的邮箱");
    return false
  } else {
    return true
  }
}

// 验证身份证
const validCard = (value) => {
  var reg = /^(\d{6})(\d{4})(\d{2})(\d{2})(\d{3})([0-9]|X)$/;
  if (!reg.test(value)) {
    Toast("请输入正确的身份证号！");
    return false
  } else {
    return true
  }
}

// 验证支付密码
const validPaymentPassword = (value) => {
  var reg = /^[0-9a-zA-Z_%&.,=\-_]{9,20}$/;
  if (!reg.test(value)) {
    Toast("由9-20个字母、数字、普通字符组成");
    return false
  } else {
    return true
  }
}

// 验证纯数字
const validNumber = (value, msg) => {
  var reg = /^[1-9]+[0-9]*]*$/
  if (reg.test(value)) {
    Toast(msg ? msg : '不能为纯数字');
    return false
  } else {
    return true
  }
}

export {
  validEmpty,
  validMobile,
  validUsername,
  validMsgcode,
  validImgcode,
  validPassword,
  validCheckPassword,
  validEmail,
  validCard,
  validPaymentPassword,
  validNumber
}
