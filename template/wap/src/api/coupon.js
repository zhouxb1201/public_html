import http from '@/utils/request'

// 领券中心
export function GET_COUPONCENTRE(data) {
	return http({
		url: '/addons/coupontype/coupontype/couponCentre',
		method: 'post',
		data
	})
}

// 获取商品详情优惠券列表
export function GET_SHOPCOUPONLIST(data) {
	return http({
		url: '/addons/coupontype/coupontype/goodsCouponList',
		method: 'post',
		data
	})
}

// 我的优惠券列表
export function GET_COUPONLIST(data) {
	return http({
		url: '/member/getcouplist',
		method: 'post',
		data
	})
}

// 获取优惠券详情
export function GET_COUPONDETAIL(coupon_type_id) {
	return http({
		url: '/addons/coupontype/coupontype/couponDetail',
		method: 'post',
		data: { coupon_type_id }
	})
}

// 获取优惠券详情适用商品列表
export function GET_COUPONDETAILGOODS(data) {
	return http({
		url: '/addons/coupontype/coupontype/couponGoodsList',
		method: 'post',
		data
	})
}

// 领取优惠券
export function RECEIVE_COUPON(data) {
	return http({
		url: '/addons/coupontype/coupontype/userArchiveCoupon',
		method: 'post',
		data,
		isShowLoading: true,
		isWriteIn: true
	})
}
