import http from '@/utils/request'

//大转盘详情
export function GET_WHEELSURFINFO(wheelsurf_id) {
	return http({
		url: '/addons/wheelsurf/wheelsurf/wheelsurfInfo',
		method: 'post',
		data: { wheelsurf_id }

	})
}

//开始抽奖
export function GET_USERWHEELSURF(wheelsurf_id) {
	return http({
		url: '/addons/wheelsurf/wheelsurf/userWheelsurf',
		method: 'post',
		data: { wheelsurf_id },
		isWriteIn: true
	})
}

//用户当日抽奖的次数
export function GET_USERFREQUENCY(wheelsurf_id) {
	return http({
		url: '/addons/wheelsurf/wheelsurf/userFrequency',
		method: 'post',
		data: { wheelsurf_id },
		isWriteIn: true
	})
}

//中奖名单
export function GET_PRIZERECORDS(data) {
	return http({
		url: '/addons/wheelsurf/wheelsurf/prizeRecords',
		method: 'post',
		data
	})
}