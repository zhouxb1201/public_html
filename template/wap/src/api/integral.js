import http from '@/utils/request'


//商品分类
export function GET_CATEGORYLIST(data) {
	return http({
		url: '/addons/integral/integral/integralcategorylist',
		method: 'post',
		data
	})
}

//商品列表
export function GET_GOODSLIST(data) {
	return http({
		url: '/addons/integral/integral/goodsList',
		method: 'post',
		data
	})
}

//商品详情
export function GET_GOODSDETAIL(data) {
	return http({
		url: '/addons/integral/integral/goodsdetail',
		method: 'post',
		data,
		errorCallback: true
	})
}

//确认订单
export function GET_ORDERINFO(data) {
	return http({
		url: '/addons/integral/integral/orderInfo',
		method: 'post',
		data,
		errorCallback: true,
		isWriteIn: true
	})
}

//立即支付
export function PAY_INTEGRALPAY(data) {
	return http({
		url: '/addons/integral/integral/integralPay',
		method: 'post',
		data,
		errorCallback: true,
		isWriteIn: true
	})
}


// 判断余额支付是否设置过密码
export function GET_INTEGRALPAYINFO(data) {
	return http({
		url: '/addons/integral/integral/getMemberBalancePoint',
		method: 'post',
		data,
		isWriteIn: true
	})
}