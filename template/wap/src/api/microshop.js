import http from '@/utils/request'

// 微店中心
export function GET_CENTREINFO(data) {
	return http({
		url: '/addons/microshop/microshop/microShopCenter',
		method: 'post',
		data
	})
}
//确认订单信息
export function GET_SHOPINFO(data) {
	return http({
		url: '/goods/orderMicroShopInfo',
		method: 'post',
		data,
		isWriteIn: true
	})
}
//等级中心
export function GET_GRADEINFO(data) {
	return http({
		url: '/addons/microshop/microshop/microShopLevelCenter',
		method: 'post',
		data
	})
}
//立即续费
export function GET_RENEW(data) {
	return http({
		url: '/addons/microshop/microshop/immediateRenewal',
		method: 'post',
		data
	})
}

//立即升级
export function GET_UPGRADE(data) {
	return http({
		url: '/addons/microshop/microshop/upgradeLevel',
		method: 'post',
		data
	})
}

// 获取微店收益明细 
export function GET_MICROSHOPLOG(data) {
	return http({
		url: '/addons/microshop/microshop/profitDetail',
		method: 'post',
		data
	})
}

//微店收益详情
export function GET_MICROSHOPDETAIL(data) {
	return http({
		url: '/addons/microshop/microshop/myProfit',
		method: 'post',
		data
	})
}

// 获取收益提现信息
export function GET_WITHDRAWSINFO(data) {
	return http({
		url: '/addons/microshop/microshop/profitShow',
		method: 'post',
		data
	})
}

// 收益提现
export function APPLY_WITHDRAW(data) {
	return http({
		url: '/addons/microshop/microshop/profitWithdraw',
		method: 'post',
		data,
		isWriteIn: true,
		isShowLoading: true
	})
}
// 微店设置
export function GET_SHOPSET(data) {
	return http({
		url: '/addons/microshop/microshop/microShopSet',
		method: 'post',
		data

	})
}
// 挑选微店商品列表
export function GET_GOODSLIST(data) {
	return http({
		url: '/goods/goodsList',
		method: 'post',
		data
	})
}

// 挑选微店商品分类
export function GET_GOODSCATEGORY(data) {
	return http({
		url: '/goods/categoryInfo',
		method: 'post',
		data
	})
}

//挑选商品
export function GET_SELECTGOODS(goods_id) {
	return http({
		url: '/addons/microshop/microshop/selectGoods',
		method: 'post',
		data: {
			goods_id
		}
	})
}

//取消选中商品
export function GET_DELGOOdS(goods_id) {
	return http({
		url: '/addons/microshop/microshop/delGoods',
		method: 'post',
		data: {
			goods_id
		}
	})
}

//预览微店商品
export function GET_PREVIEWMICROSHOP(data) {
	return http({
		url: '/addons/microshop/microshop/previewMicroShop',
		method: 'post',
		data
	})
}

//预览微店中商品分类(返回值为三级分类信息)
export function GET_PREVIEWMICROSHOGOODS(data) {
	return http({
		url: '/addons/microshop/microshop/previewMicroShopGoods',
		method: 'post',
		data
	})
}

//预览微店中商品分类
export function GET_PREVIEWMICROSHOPCATEGROY(data) {
	return http({
		url: '/addons/microshop/microshop/previewMicroShopCategory',
		method: 'post',
		data
	})
}
