import http from '@/utils/request'

// 砍价列表
export function GET_BARGAINLIST(data) {
	return http({
		url: '/addons/bargain/bargain/getBargainList',
		method: 'post',
		data
	})
}

// 砍价详情
export function GET_BARGAINDETAIL(data) {
	return http({
		url: '/addons/bargain/bargain/myActionBargain',
		method: 'post',
		data,
		errorCallback: true
	})
}

// 帮砍
export function SUB_BARGAIN(bargain_record_id) {
	return http({
		url: '/addons/bargain/bargain/helpBargain',
		method: 'post',
		data: { bargain_record_id },
		isWriteIn: true,
		isShowLoading: true
	})
}
