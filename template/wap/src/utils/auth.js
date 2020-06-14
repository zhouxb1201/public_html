import cookie from 'vue-js-cookie'

const user_token = 'USER_TOKEN'
const bind_mobile = 'BIND_MOBILE'
const cookie_day = 7

const SET_TOKEN = (token) => {
  return cookie.set(user_token, token, cookie_day)
}
const GET_TOKEN = () => {
  return cookie.get(user_token)
}
const REMOVE_TOKEN = () => {
  return cookie.remove(user_token)
}

const SET_BINDMOBILE = (falg) => {
  return cookie.set(bind_mobile, falg, cookie_day)
}
const GET_BINDMOBILE = () => {
  return cookie.get(bind_mobile)
}
const REMOVE_BINDMOBILE = () => {
  return cookie.remove(bind_mobile)
}

export {
  SET_TOKEN,
  GET_TOKEN,
  REMOVE_TOKEN,
  SET_BINDMOBILE,
  GET_BINDMOBILE,
  REMOVE_BINDMOBILE
}
