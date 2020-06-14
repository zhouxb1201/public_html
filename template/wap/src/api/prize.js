import http from '@/utils/request'

// 奖品列表
export function GET_PRIZELIST(data) {
	return http({
		url: '/member/myPrize',
		method: 'post',
		data
	})
}

// 奖品确认
export function GET_PRIZEDETAIL(data) {
	return http({
		url: '/member/prizeDetail',
		method: 'post',
		data,
		isWriteIn: true
	})
}

// 领取奖品
export function GET_ACCEPTPRIZE(data) {
	return http({
		url: '/member/acceptPrize',
		method: 'post',
		data,
		isWriteIn: true
	})
}