import http from '@/utils/request'

//获取礼品
export function GET_ACCEPTFESTIVALCARE(prize_id) {
	return http({
		url: '/addons/festivalcare/festivalcare/acceptFestivalcare',
		method: 'post',
		data: { prize_id }
	})
}   