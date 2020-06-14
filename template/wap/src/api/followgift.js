import http from '@/utils/request'

//获取礼品
export function GET_ACCEPTFOLLOWGIFT(prize_id) {
	return http({
		url: '/addons/followgift/followgift/acceptFollowgift',
		method: 'post',
		data: { prize_id }
	})
}   