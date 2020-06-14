import http from '@/utils/request'

// 客服咨询列表
export function GET_CHATLIST(data) {
	return http({
		url: '/addons/qlkefu/qlkefu/chatList',
		method: 'post',
		data
	})
}