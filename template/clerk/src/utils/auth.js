import cookie from 'vue-js-cookie'

const user_token = 'CLERK_TOKEN'
const bind_mobile = 'CLERK_BIND_MOBILE'
const store_id = 'STORE_ID'
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

const SET_STOREID = (id) => {
  return cookie.set(store_id, id, cookie_day)
}
const GET_STOREID = () => {
  return cookie.get(store_id)
}
const REMOVE_STOREID = () => {
  return cookie.remove(store_id)
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

  SET_STOREID,
  GET_STOREID,
  REMOVE_STOREID,

  SET_BINDMOBILE,
  GET_BINDMOBILE,
  REMOVE_BINDMOBILE
}
