import http from '@/utils/request'

// 登录
export function LOGIN(data) {
	return http({
		url: '/addons/execute/addons/store/controller/login/action/index',
		method: 'post',
		data,
		isShowLoading: true
	})
}

// 退出登录
export function LOGOUT(data) {
	return http({
		url: '/addons/execute/addons/store/controller/login/action/logout',
		method: 'post'
	})
}

// 获取短信验证码
export function GET_MSGCODE(data) {
	return http({
		url: '/addons/execute/addons/store/controller/login/action/getVerificationCode',
		method: 'post',
		data,
		isShowLoading: true
	})
}

// 重置密码
export function RESET_PASSWORD(data) {
	return http({
		url: '/addons/execute/addons/store/controller/login/action/resetPassword',
		method: 'post',
		data,
		isShowLoading: true
	})
}

// 微信登录
export function OTHER_LOGIN(data) {
	return http({
		url: '/addons/execute/addons/store/controller/login/action/oauthLogin',
		method: 'post',
		data
	})
}

// 验证手机号
export function VALID_MOBILE(mobile) {
	return http({
		url: '/addons/execute/addons/store/controller/login/action/checkMobileCanBund',
		method: 'post',
		data: { mobile }
	})
}

// 关联手机号
export function BIND_ACCOUNT(data) {
	return http({
		url: '/addons/execute/addons/store/controller/login/action/AssociateAccount',
		method: 'post',
		data
	})
}