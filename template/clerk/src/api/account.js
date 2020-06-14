import http from '@/utils/request'

// 获取店员信息
export function GET_ACCOUNTINFO(data) {
	return http({
		url: '/addons/execute/addons/store/controller/wapstore/action/getAssistantInfo',
		method: 'post',
		data
	})
}

// 验证旧密码
export function CHECK_PASSWORD(password) {
	return http({
		url: '/addons/execute/addons/store/controller/wapstore/action/checkPassword',
		method: 'post',
		data: { password }
	})
}

// 修改密码
export function	UPDATE_PASSWORD(password) {
	return http({
		url: '/addons/execute/addons/store/controller/wapstore/action/updatePassword',
		method: 'post',
		data: { password }
	})
}