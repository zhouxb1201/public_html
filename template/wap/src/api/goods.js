import http from '@/utils/request'

// 获取商品列表
export function GET_GOODSLIST(data) {
	return http({
		url: '/goods/goodsList',
		method: 'post',
		data
	})
}

// 根据商品id组获取商品列表
export function GET_GOODSCUSTOMLIST(goods_ids) {
	return http({
		url: '/goods/goodsListIndex',
		method: 'post',
		data: { goods_ids }
	})
}

// 获取商品分类
export function GET_GOODSCATEGORY(data) {
	return http({
		url: '/goods/categoryInfo',
		method: 'post',
		data
	})
}

// 获取商品详情
export function GET_GOODSDETAIL(data) {
	return http({
		url: '/goods/goodsDetail',
		method: 'post',
		data,
		errorCallback: true
	})
}

// 获取商品分享信息
export function GET_GOODSSHAREINFO(goods_id) {
	return http({
		url: '/goods/goodsShareDetail',
		method: 'post',
		data: { goods_id }
	})
}

// 获取商品评价列表
export function GET_GOODSEVALUATE(data) {
	return http({
		url: '/goods/goodsReviewsList',
		method: 'post',
		data,
		isShowLoading: true
	})
}

// 商品收藏
export function SET_GOODSCOLLECT(goods_id, seckill_id) {
	return http({
		url: '/goods/collectGoods',
		method: 'post',
		data: {
			goods_id,
			seckill_id
		},
		isWriteIn: true,
		isShowLoading: true
	})
}

// 商品收藏列表
export function GET_GOODSCOLLECTLIST(data) {
	return http({
		url: '/member/myGoodsCollection',
		method: 'post',
		data
	})
}

// 取消商品收藏
export function CANCEL_GOODSCOLLECT(goods_id) {
	return http({
		url: '/goods/cancelCollectGoods',
		method: 'post',
		data: {
			goods_id
		},
		isWriteIn: true,
		isShowLoading: true
	})
}

// 加入购物车
export function ADD_CART(data) {
	return http({
		url: '/goods/addCart',
		method: 'post',
		data,
		isWriteIn: true,
		isShowLoading: true
	})
}

// 立即购买
export function BUY_NOW(data) {
	return http({
		url: '/goods/buyNow',
		method: 'post',
		data,
		isWriteIn: true,
		isShowLoading: true
	})
}

// 客服
export function GET_CUSTOMERSERVICE(shop_id, goods_id) {
	return http({
		url: '/goods/qlkefuInfo',
		method: 'post',
		data: {
			shop_id,
			goods_id
		}
	})
}

// 获取商品主图
export function GET_GOODSIMGBASE64(goods_id) {
	return http({
		url: '/goods/getGoodsImgOfBase64',
		method: 'post',
		data: { goods_id }
	})
}

// 获取商品基本信息
export function GET_GOODSINFO(data, config = {}) {
	return http({
		url: '/goods/getGoodsBasicInfo',
		method: 'post',
		data,
		isShowLoading: config.loading || false
	})
}