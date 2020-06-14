import http from '@/utils/request'

// 刮刮乐详情
export function GET_SCRATCHCARDINFO(scratch_card_id) {
	return http({
		url: '/addons/scratchcard/scratchcard/scratchcardInfo',
		method: 'post',
		data: { scratch_card_id }
	})
}

// 刮刮乐次数
export function GET_FREQUENCY(scratch_card_id) {
	return http({
		url: '/addons/scratchcard/scratchcard/userFrequency',
		method: 'post',
		data: { scratch_card_id },
		isWriteIn: true
	})
}

//  中奖名单
export function GET_PRIZERECORDS(data) {
	return http({
		url: '/addons/scratchcard/scratchcard/prizeRecords',
		method: 'post',
		data
	})
}

// 刮奖
export function SET_USERSCRATCHCARD(scratch_card_id) {
	return http({
		url: '/addons/scratchcard/scratchcard/userScratchcard',
		method: 'post',
		data: { scratch_card_id },
		isWriteIn: true
	})
}
