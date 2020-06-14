import cookie from 'vue-js-cookie'

const setSession = (key, value) => {
  sessionStorage.setItem(key, JSON.stringify(value));
}

const getSession = (key) => {
  return JSON.parse(sessionStorage.getItem(key));
}

const removeSession = (key) => {
  sessionStorage.removeItem(key);
}

const setLocal = (key, value) => {
  localStorage.setItem(key, JSON.stringify(value));
}

const getLocal = (key) => {
  return JSON.parse(localStorage.getItem(key));
}

const removeLocal = (key) => {
  localStorage.removeItem(key);
}

const setCookie = (key, value, day) => {
  return cookie.set(key, value, day)
}

const getCookie = (key) => {
  return cookie.get(key)
}

const removeCookie = (key) => {
  return cookie.remove(key)
}

export {
  setSession,
  getSession,
  removeSession,
  setLocal,
  getLocal,
  removeLocal,
  setCookie,
  getCookie,
  removeCookie
}