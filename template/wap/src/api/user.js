import http from '@/utils/request'

// 登录
export function LOGIN(data) {
	return http({
		url: '/login',
		method: 'post',
		data,
		isShowLoading: true
	})
}

// 注册
export function REGISTER(data) {
	return http({
		url: '/login/register',
		method: 'post',
		data,
		isShowLoading: true
	})
}

// 退出登陆
export function LOGOUT() {
	return http({
		url: '/login/logout',
		method: 'post'
	})
}

// 发送验证码
export function GET_MSGCODE(data) {
	return http({
		url: '/login/getVerificationCode',
		method: 'post',
		data,
		isShowLoading: true
	})
}

// 获取图片验证码
export function GET_IMGCODE() {
	return http({
		url: '/login/captchaSrc',
		method: 'post',
		isShowLoading: true
	})
}

// 判断手机是否存在
export function IS_HASMOBILE(mobile, port) {
	return http({
		url: '/login/mobile',
		method: 'post',
		data: { mobile, mall_port: port }
	})
}

// 获取邮箱验证码
export function GET_EMAILCODE(data) {
	return http({
		url: '/login/getEmailVerificationCode',
		method: 'post',
		data,
		isShowLoading: true
	})
}

// 重设密码
export function RESET_PASSWORD(data) {
	return http({
		url: '/login/resetPassword',
		method: 'post',
		data,
		isShowLoading: true
	})
}

// 第三方登录
export function OTHER_LOGIN(data, method) {
	return http({
		url: '/login/oauthLogin',
		method,
		data,
		params: method == 'get' ? data : ''
	})
}

// 关联账户
export function BIND_ACCOUNT(data) {
	return http({
		url: '/login/AssociateAccount',
		method: 'post',
		data,
		isShowLoading: true
	})
}




