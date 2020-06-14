import http from '@/utils/request'

// 获取课程商品列表
export function GET_GOODSLIST(data) {
	return http({
		url: '/goods/myCourse',
		method: 'post',
		data
	})
}

// 获取课程商品详情目录列表
export function GET_GOODSDETAIL_LIST(data) {
	return http({
		url: '/goods/wapGetKnowledgePaymentList',
		method: 'post',
		data,
	})
}

// 获取课程商品详情
export function GET_GOODSDETAIL(data) {
	return http({
		url: '/goods/seeKnowledgePayment',
		method: 'post',
		data,
	})
}