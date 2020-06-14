import http from '@/utils/request'

// 获取成为分销商信息
export function GET_APPLYCOMMISSION(data) {
	return http({
		url: '/addons/distribution/distribution/distributorApply_show',
		method: 'post',
		data,
		isWriteIn: true
	})
}

// 申请成为分销商
export function APPLY_COMMISSION(data) {
	return http({
		url: '/addons/distribution/distribution/distributorapply',
		method: 'post',
		data,
		isWriteIn: true,
		isShowLoading: true
	})
}

// 完善资料，提交表单
export function APPLY_REPLENISHINFO(data) {
	return http({
		url: '/addons/distribution/distribution/dataComplete',
		method: 'post',
		data,
		isWriteIn: true,
		isShowLoading: true
	})
}

// 获取分销中心信息
export function GET_CENTREINFO(data) {
	return http({
		url: '/addons/distribution/distribution/distributionCenter',
		method: 'post',
		data,
		isWriteIn: true,
	})
}

// 获取分销佣金详情
export function GET_COMMISSIONDETAIL(data) {
	return http({
		url: '/addons/distribution/distribution/myCommissiona',
		method: 'post',
		data
	})
}

// 获取分销佣金明细
export function GET_COMMISSIONLOG(data) {
	return http({
		url: '/addons/distribution/distribution/commissionDetail',
		method: 'post',
		data
	})
}

// 获取分销订单
export function GET_ORDERLIST(data) {
	return http({
		url: '/addons/distribution/distribution/distributionOrder',
		method: 'post',
		data
	})
}

// 获取我的团队
export function GET_TEAMLIST(data) {
	return http({
		url: '/addons/distribution/distribution/teamList',
		method: 'post',
		data
	})
}

// 获取我的客户
export function GET_CUSTOMERLIST(data) {
	return http({
		url: '/addons/distribution/distribution/customerList',
		method: 'post',
		data
	})
}

// 获取佣金提现信息
export function GET_WITHDRAWINFO(data) {
	return http({
		url: '/addons/distribution/distribution/commissionWithdraw_show',
		method: 'post',
		data
	})
}

// 佣金提现
export function APPLY_WITHDRAW(data) {
	return http({
		url: '/addons/distribution/distribution/commissionWithdraw',
		method: 'post',
		data,
		isWriteIn: true,
		isShowLoading: true
	})
}

// 获取推广码
export function GET_EXTENDCODE(data) {
	return http({
		url: '/member/qrcode',
		method: 'post',
		data
	})
}

// 分享链接或者扫码成为下线
export function EXTEND_SUB(data) {
	return http({
		url: '/member/checkReferee',
		method: 'post',
		data
	})
}

// 获取分销设置相关文案字眼
export function GET_COMMISSIONSETTEXT(data) {
	return http({
		url: '/addons/distribution/distribution/distributionSet',
		method: 'post',
		data
	})
}

// 获取分销推荐排行榜、佣金排行榜、积分排行榜
export function GET_COMMISSIONRANKING(data) {
	return http({
		url: '/addons/distribution/distribution/ranking',
		method: 'post',
		data
	})
}

//分红分销等级
export function GET_UPBONUESLEVEL(data){
	return http({
		url: '/addons/distribution/distribution/upbonusLevel',
		method: 'post',
		data,
		errorCallback:true
	})
}   