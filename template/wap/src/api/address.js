import http from '@/utils/request'

// 获取省市区
export function GET_AREA(data) {
	return http({
		url: '/goods/area',
		method: 'post',
		data: { refresh: true }
	})
}

// 获取收货地址列表
export function GET_ADDRESSLIST(data) {
	return http({
		url: '/member/receiverAddressList',
		method: 'post',
		data
	})
}

// 收货地址详情
export function GET_ADDRESSDETAIL(id) {
	return http({
		url: '/member/receiverAddressDetail',
		method: 'post',
		data: { id }
	})
}

// 保存收货地址
export function SAVE_ADDRESS(data) {
	return http({
		url: '/member/saveReceiverAddress',
		method: 'post',
		data,
		isWriteIn: true
	})
}

// 删除收货地址
export function DEL_ADDRESS(id) {
	return http({
		url: '/member/deleteAddress',
		method: 'post',
		data: { id },
		isWriteIn: true,
		isShowLoading: true
	})
}

// 设置默认收货地址
export function SET_DEFAULTADDRESS(id) {
	return http({
		url: '/member/setDefaultAddress',
		method: 'post',
		data: { id },
		isWriteIn: true
	})
}