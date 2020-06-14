import http from '@/utils/request'

// 获取店铺首页
export function GET_SHOPINFO(shop_id) {
	return http({
		url: '/addons/shop/shop/shopInfo',
		method: 'post',
		data: { shop_id }
	})
}

// 获取店铺组
export function GET_SHOPGROUP(data) {
	return http({
		url: '/addons/shop/shop/shopgroup',
		method: 'post',
		data
	})
}

// 获取店铺列表
export function GET_SHOPLIST(data) {
	return http({
		url: '/addons/shop/shop/shopSearch',
		method: 'post',
		data
	})
}

// 店铺收藏
export function SET_COLLECTSHOP(shop_id) {
	return http({
		url: '/addons/shop/shop/collectShop',
		method: 'post',
		data: { shop_id },
		isWriteIn: true,
		isShowLoading: true
	})
}

// 店铺取消收藏
export function CANCEL_COLLECTSHOP(shop_id) {
	return http({
		url: '/addons/shop/shop/cancelCollectShop',
		method: 'post',
		data: { shop_id },
		isWriteIn: true,
		isShowLoading: true
	})
}

// 店铺收藏列表
export function GET_SHOPCOLLECTLIST(data) {
	return http({
		url: '/addons/shop/shop/myShopCollection',
		method: 'post',
		data
	})
}

// 申请入驻店铺
export function APPLY_SHOP(data) {
	return http({
		url: '/addons/shop/shop/applyForWap',
		method: 'post',
		data,
		isWriteIn: true,
		isShowLoading: true
	})
}

// 获取店铺相关协议
export function GET_SHOPAPPLYPROTOCOL(data) {
	return http({
		url: '/addons/shop/shop/getShopProtocolByWap',
		method: 'post',
		data
	})
}

// 获取店铺申请入驻状态
export function GET_SHOPAPPLYSTATE(data) {
	return http({
		url: '/addons/shop/shop/getApplyStateByWap',
		method: 'post',
		data
	})
}

// 获取店铺申请入驻状态
export function GET_SHOPAPPLYCUSTOMFORM(data) {
	return http({
		url: '/addons/shop/shop/getApplyCustomForm',
		method: 'post',
		data
	})
}


