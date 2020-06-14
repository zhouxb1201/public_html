import http from '@/utils/request'

// 获取资产信息
export function GET_ASSET(data) {
	return http({
		url: '/member/asset',
		method: 'post',
		data
	})
}

// 获取提现账户列表
export function GET_ASSETACCOUNTLIST(data) {
	return http({
		url: '/member/bank_account',
		method: 'post',
		data
	})
}

// 删除账户
export function DEL_ASSETACCOUNT(account_id) {
	return http({
		url: '/member/del_account',
		method: 'post',
		data: { account_id },
		isWriteIn: true,
		isShowLoading: true
	})
}

// 添加/编辑账户
export function SET_ASSETACCOUNT(type, data) {
	return http({
		url: type == 'add' ? '/member/add_bank_account' : '/member/update_account',
		method: 'post',
		data,
		isWriteIn: true,
		isShowLoading: true
	})
}

// 获取余额信息
export function GET_ASSETBALANCE(data) {
	return http({
		url: '/member/balance',
		method: 'post',
		data
	})
}

// 获取余额明细列表
export function GET_ASSETBALANCELOG(data, isProceeds) {
	let url = isProceeds ? '/member/proceedsWater' : '/member/balancewater'
	return http({
		url,
		method: 'post',
		data
	})
}

// 获取余额明细详情
export function GET_ASSETBALANCEDETAIL(id) {
	return http({
		url: '/member/balanceDetail',
		method: 'post',
		data: { id }
	})
}

// 充值余额
export function RECHARGE_ASSETBALANCELOG(data) {
	return http({
		url: '/member/recharge',
		method: 'post',
		data,
		isWriteIn: true
	})
}

// 创建充值余额订单
export function CREATE_ASSETRECHARORDER(data) {
	return http({
		url: '/member/createRechargeOrder',
		method: 'post',
		data,
		isWriteIn: true,
		isShowLoading: true
	})
}

// 获取提现信息
export function GET_ASSETWITHDRAWINFO(data) {
	return http({
		url: '/member/withdraw_form',
		method: 'post',
		data
	})
}

// 提现余额
export function APPLY_ASSETWITHDRAW(data) {
	return http({
		url: '/member/withdraw',
		method: 'post',
		data,
		isWriteIn: true,
		isShowLoading: true
	})
}

// 积分
export function GET_ASSETPOINTS(data) {
	return http({
		url: '/member/integralWater',
		method: 'post',
		data
	})
}

// 银行列表
export function GET_BANKLIST(data) {
	return http({
		url: '/member/bank_list',
		method: 'post',
		data
	})
}

// 银行卡签约申请短信
export function GET_BANKSIGNINGSMS(data) {
	return http({
		url: '/Member/tlSigning',
		method: 'post',
		data,
		timeout: 0,
		isShowLoading: true,
		loadingText: '获取短信验证'
	})
}

// 签约银行卡
export function SIGNING_BANKCARD(data) {
	return http({
		url: '/member/tlAgreeSigning',
		method: 'post',
		data,
		timeout: 0,
		isShowLoading: true,
		loadingText: '签约中'
	})
}

// 解绑银行卡
export function UNTYING_BANKCARD(id) {
	return http({
		url: '/member/tlUntying',
		method: 'post',
		data: { id },
		timeout: 0,
		isShowLoading: true,
		loadingText: '解绑中'
	})
}

// 签约银行卡列表
export function GET_BANKCARDLIST(data) {
	return http({
		url: '/member/tl_bank_account',
		method: 'post',
		data
	})
}

// 余额积分兑换
export function EXCHANGE_BALANCEPOINT(data) {
	return http({
		url: '/member/transBalancePoint',
		method: 'post',
		data,
		isShowLoading: true,
		loadingText: '兑换中'
	})
}

// 余额转账
export function TRANSFER_BALANCE(data) {
	return http({
		url: '/member/transBalance',
		method: 'post',
		data,
		isShowLoading: true,
		loadingText: '转账中'
	})
}

// 获取各种资产手续费
export function GET_PROPERTYCHARGESERVICE(data) {
	return http({
		url: '/member/chargeService',
		method: 'post',
		data
	})
}