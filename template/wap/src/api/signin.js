import http from '@/utils/request'

// 获取会员签到信息
export function GET_SIGNININFO(data) {
  return http({
    url: '/addons/signin/signin/userSignInInfo',
    method: 'post',
    data
  })
}

// 获取会员当月签到列表
export function GET_SIGNINLIST(data) {
  return http({
    url: '/addons/signin/signin/userSignInList',
    method: 'post',
    data
  })
}

// 获取会员签到记录
export function GET_SIGNINLOG(data) {
  return http({
    url: '/addons/signin/signin/userSignInRecord',
    method: 'post',
    data
  })
}

// 会员签到
export function SET_SIGNIN(data) {
  return http({
    url: '/addons/signin/signin/userSignIn',
    method: 'post',
    data,
		isWriteIn: true
  })
}