import http from '@/utils/request'

// 购物车列表
export function GET_CARTLIST(data) {
	return http({
		url: '/goods/cart',
		method: 'post',
		data
	})
}

// 删除购物车
export function REMOVE_CARTGOODS(cart_id) {
	return http({
		url: '/goods/delete_car_goods',
		method: 'post',
		data: { cart_id },
		isWriteIn: true,
		isShowLoading: true
	})
}

// 修改购物车数量
export function EDIT_CARTNUM(data) {
	return http({
		url: '/goods/cartAdjustNum',
		method: 'post',
		data,
		isWriteIn: true,
		isShowLoading: true
	})
}

// 根据店铺id和门店id获取购物车信息
export function GET_SHOPCARTINFO(data) {
	return http({
		url: '/goods/cartGetGoodsList',
		method: 'post',
		data,
		isShowLoading: true
	})
}

// 编辑购物车信息
export function EDIT_CARTINFO(data) {
	return http({
		url: '/goods/cartEditSkuOrNum',
		method: 'post',
		data,
		isShowLoading: true
	})
}