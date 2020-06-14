import http from '@/utils/request'

// 计算运费
export function COUNT_FREIGHT(data) {
	return http({
		url: '/goods/count_free',
		method: 'post',
		data
	})
}

/**
 * 确认订单
*/
export function GET_ORDERINFO(data, isShowLoading = false) {
	// let url = orderType == 'store' ? '/addons/store/store/orderInfo' : '/goods/orderInfo'
	return http({
		url: '/goods/orderInfo',
		method: 'post',
		data,
		errorCallback: true,
		isWriteIn: true,
		isShowLoading
	})
}

// 提交订单
export function CREATE_ORDER(order_data, isStoreOrder) {
	let url = isStoreOrder ? '/order/StoreOrderCreate' : '/order/orderCreate'
	return http({
		url,
		method: 'post',
		data: { order_data },
		isWriteIn: true,
		isShowLoading: true
	})
}

// 订单列表
export function GET_ORDERLIST(data) {
	return http({
		url: '/order/orderlist',
		method: 'post',
		data
	})
}

// 订单详情
export function GET_ORDERDETAIL(order_id) {
	return http({
		url: '/order/orderDetail',
		method: 'post',
		data: { order_id }
	})
}

// 获取订单申请售后信息
export function GET_REFUNDINFO(data) {
	return http({
		url: '/order/refundDetail',
		method: 'post',
		data
	})
}

// 提交申请售后
export function APPLY_REFUNDASK(data) {
	return http({
		url: '/order/refundAsk',
		method: 'post',
		data,
		isWriteIn: true,
		isShowLoading: true
	})
}

// 取消申请售后
export function CANCEL_REFUNDASK(data) {
	return http({
		url: '/order/cancelOrderRefund',
		method: 'post',
		data,
		isWriteIn: true,
		isShowLoading: true
	})
}

// 提交退货信息
export function SUB_REFUNDEXPRESS(data) {
	return http({
		url: '/order/orderGoodsRefundExpress',
		method: 'post',
		data,
		isWriteIn: true,
		isShowLoading: true
	})
}

// 关闭订单
export function CLOSE_ORDER(order_id) {
	return http({
		url: '/order/orderClose',
		method: 'post',
		data: { order_id },
		isWriteIn: true,
		isShowLoading: true
	})
}

// 删除订单
export function EDLETE_ORDER(order_id) {
	return http({
		url: '/order/deleteOrder',
		method: 'post',
		data: { order_id },
		isWriteIn: true,
		isShowLoading: true
	})
}

// 确认收货
export function CONFIRM_TAKEDELIVERY(order_id) {
	return http({
		url: '/order/orderTakeDelivery',
		method: 'post',
		data: { order_id },
		isWriteIn: true,
		isShowLoading: true
	})
}

// 订单商品评价
export function ADD_ORDEREVALUATE(data) {
	return http({
		url: '/order/addOrderEvaluate',
		method: 'post',
		data,
		isShowLoading: true
	})
}

// 订单商品再次评价
export function ADD_ORDERAGAINEVALUATE(data) {
	return http({
		url: '/order/addOrderEvaluateAgain',
		method: 'post',
		data,
		isShowLoading: true
	})
}

// 获取物流信息
export function GET_LOGISTICSDETAIL(order_id) {
	return http({
		url: '/order/orderShippingInfo',
		method: 'post',
		data: { order_id }
	})
}

// 再次购买
export function ADD_BUYAGAIN(data) {
	return http({
		url: '/goods/buyAgain',
		method: 'post',
		data,
		isWriteIn: true,
		isShowLoading: true
	})
}

// 获取订单支付信息
export function GET_ORDERPAYINFO(order_id) {
	return http({
		url: '/order/orderPay',
		method: 'post',
		data: { order_id }
	})
}

// 获取尾款支付单号
export function GET_TAILMONEYNO(data) {
	return http({
		url: '/pay/pay_last_money',
		method: 'post',
		data,
		isWriteIn: true,
		isShowLoading: true
	})
}

// 货到付款
export function PAY_DPAY(data) {
	return http({
		url: '/Member/dPay',
		method: 'post',
		data,
		isWriteIn: true,
		timeout: 0,
		isShowLoading: true
	})
}

// 获取物流公司
export function GET_EXPRESSCOMPANY(data) {
	return http({
		url: '/order/getvExpressCompany',
		method: 'post',
		data
	})
}