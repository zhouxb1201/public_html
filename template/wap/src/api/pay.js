import http from '@/utils/request'

// 获取支付信息
export function GET_PAYINFO(out_trade_no) {
	return http({
		url: '/member/getPayValue',
		method: 'post',
		data: { out_trade_no },
		isWriteIn: true
	})
}

// 微信支付
export function PAY_WECHAT(out_trade_no, type) {
	return http({
		url: '/member/wchatPay',
		method: 'post',
		data: { out_trade_no, type },
		isWriteIn: true
	})
}

// 支付宝支付
export function PAY_ALIPAY(out_trade_no) {
	return http({
		url: '/Member/aliPay',
		method: 'post',
		data: { out_trade_no, type: 2 },
		isWriteIn: true
	})
}

// 余额支付
export function PAY_BALANCE(data) {
	return http({
		url: '/member/balance_pay',
		method: 'post',
		data,
		isWriteIn: true,
		isShowLoading: true
	})
}

// 获取支付结果
export function GET_PAYRESULT(out_trade_no) {
	return http({
		url: '/order/get_pay_result_info',
		method: 'post',
		data: { out_trade_no },
		isWriteIn: true
	})
}

// eth/eos支付
export function PAY_BLOCKCHAIN(type, data) {
	return http({
		url: '/Member/' + type + 'Pay',
		method: 'post',
		data,
		isWriteIn: true,
		timeout: 0,
		loadingText: '支付请求中',
		isShowLoading: true
	})
}

// 申请银行卡支付短信
export function APPLY_BANKCARDSMS(data) {
	return http({
		url: '/Member/tlPayApplyAgree',
		method: 'post',
		data,
		isWriteIn: true,
		timeout: 0,
		loadingText: '申请验证短信',
		isShowLoading: true
	})
}

// 获取银行卡支付短信
export function GET_BANKCARDSMS(data) {
	return http({
		url: '/Member/paySmsAgree',
		method: 'post',
		data,
		isWriteIn: true,
		timeout: 0,
		loadingText: '获取短信验证',
		isShowLoading: true
	})
}

// 银行卡支付
export function PAY_BANKCARD(data) {
	return http({
		url: '/Member/tlPay',
		method: 'post',
		data,
		isWriteIn: true,
		timeout: 0,
		loadingText: '支付中',
		isShowLoading: true
	})
}

// 货款支付
export function PAY_PROCEEDS(data) {
	return http({
		url: '/member/proceeds_pay',
		method: 'post',
		data,
		isWriteIn: true,
		isShowLoading: true
	})
}

// glopay 跨境支付
export function PAY_GLOPAY(data) {
	return http({
		url: '/Member/GlobePay',
		method: 'post',
		data,
		isWriteIn: true,
		isShowLoading: true
	})
}