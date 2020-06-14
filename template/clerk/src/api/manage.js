import http from '@/utils/request'

// 获取店员列表
export function GET_CLERKLIST(data) {
	return http({
		url: '/addons/execute/addons/store/controller/wapstore/action/assistantList',
		method: 'post',
		data
	})
}

// 更新店员信息
export function UPADTE_CLERKINFO(data) {
	return http({
		url: '/addons/execute/addons/store/controller/wapstore/action/addOrUpdateAssistant',
		method: 'post',
		data
	})
}

// 获取岗位列表
export function GET_JONSLIST(data) {
	return http({
		url: '/addons/execute/addons/store/controller/wapstore/action/getJobsList',
		method: 'post',
		data
	})
}