import http from '@/utils/request'

// 获取门店商品分类
export function GET_STOREGOODSCATEGORY(data, type) {
	let action = type == 'add' ? 'getAddGoodsCategoryList' : 'getStoreGoodsCategoryList';
	return http({
		url: '/addons/execute/addons/store/controller/wapstore/action/' + action,
		method: 'post',
		data,
	})
}

// 获取门店分类商品
export function GET_STOREGOODSLIST(data, type) {
	let action = type == 'add' ? 'getAddGoodsList' : 'getGoodsList';
	return http({
		url: '/addons/execute/addons/store/controller/wapstore/action/' + action,
		method: 'post',
		data,
	})
}

// 设置商品上架/下架/删除
export function SET_STOREGOODS(action, goods_id) {
	return http({
		url: '/addons/execute/addons/store/controller/wapstore/action/goods' + action,
		method: 'post',
		data: { goods_id },
	})
}

// 获取商品信息
export function GET_STOREGOODSINFO(goods_id, type) {
	let action = type == 'add' ? 'addGoods' : 'goodsEdit';
	return http({
		url: '/addons/execute/addons/store/controller/wapstore/action/' + action,
		method: 'post',
		data: { goods_id },
	})
}

// 保存商品信息
export function SAVE_STOREGOODSINFO(data) {
	return http({
		url: '/addons/execute/addons/store/controller/wapstore/action/saveGoods',
		method: 'post',
		data,
	})
}