import http from '@/utils/request'

// 会员中心
export function GET_MEMBERINFO(data) {
	return http({
		url: '/member/memberIndex',
		method: 'post',
		data,
		noCancel:true
	})
}

// 获取账号信息
export function GET_ACCOUNTINFO(data) {
	return http({
		url: '/member/getMemberBaseInfo',
		method: 'post',
		data
	})
}

// 设置账号信息
export function SET_ACCOUNTINFO(data) {
	return http({
		url: '/member/saveMemberBaseInfo',
		method: 'post',
		data,
		isWriteIn: true,
		isShowLoading: true
	})
}

// 验证手机短信验证码
export function VALID_MSGCODE(data) {
	return http({
		url: '/login/checkVerificationCode',
		method: 'post',
		data,
		isWriteIn: true,
		isShowLoading: true
	})
}

// 修改密码
export function UPDATE_PASSWORD(password) {
	return http({
		url: '/member/updatePassword',
		method: 'post',
		data: { password },
		isWriteIn: true,
		isShowLoading: true
	})
}

// 修改支付密码
export function UPDATE_PAYMENTPASSWORD(payment_password) {
	return http({
		url: '/member/updatePaymentPassword',
		method: 'post',
		data: { payment_password },
		isWriteIn: true,
		isShowLoading: true
	})
}

// 修改手机
export function UPDATE_MOBILE(data) {
	return http({
		url: '/member/updateMobile',
		method: 'post',
		data,
		isWriteIn: true,
		isShowLoading: true
	})
}

// 绑定邮箱
export function UPDATE_EMAIL(data) {
	return http({
		url: '/member/updateEmail',
		method: 'post',
		data,
		isWriteIn: true,
		isShowLoading: true
	})
}

// 获取关联账号
export function GET_ACCOUNTRELEVANT(data) {
	return http({
		url: '/member/associationList',
		method: 'post',
		data
	})
}

// 检查支付密码
export function CHECK_PAYPASSWORD(password) {
	return http({
		url: '/member/check_pay_password',
		method: 'post',
		data: { password }
	})
}

// 获取文案
export function GET_MEMBERSETTEXT(data) {
	return http({
		url: '/config/copyStyle',
		method: 'post',
		data
	})
}


//等级详情
export function GET_MEMBERLEVEL(data){
	return http({
		url: '/member/memberLevel',
		method: 'post',
		data
	})
}