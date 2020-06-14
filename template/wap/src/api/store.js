import http from '@/utils/request'

// 获取平台下所有门店列表
export function GET_STORELIST(data, isShop) {
  const url = isShop ? '/addons/store/store/getStoreListForWap' : '/addons/store/store/getAllStoreListForWap'
  return http({
    url: url,
    method: 'post',
    data
  })
}

// 获取门店信息
export function GET_STOREINFO(data) {
  return http({
    url: '/addons/store/store/storeIndex',
    method: 'post',
    data
  })
}

// 获取门店商品分类
export function GET_STOREGOODSCATEGORY(data) {
  return http({
    url: '/addons/store/store/getStoreGoodsCategoryList',
    method: 'post',
    data
  })
}

// 获取门店商品列表
export function GET_STOREGOODSLIST(data) {
  return http({
    url: '/addons/store/store/getStoreGoods',
    method: 'post',
    data
  })
}

// 获取门店购物车列表
export function GET_STORECARTLIST(data) {
  return http({
    url: '/addons/store/store/cart',
    method: 'post',
    data
  })
}

// 添加门店商品到门店购物车
export function ADD_STOREGOODSTOCART(data) {
  return http({
    url: '/addons/store/store/addCart',
    method: 'post',
    data,
    isShowLoading: true
  })
}

// 删除购物车商品
export function REMOVE_STORECART(cart_id) {
  return http({
    url: '/addons/store/store/deleteCartGoods',
    method: 'post',
    data: { cart_id },
    isShowLoading: true
  })
}

// 修改购物车数量
export function EDIT_STORECARTNUM(data) {
  return http({
    url: '/addons/store/store/editCartNum',
    method: 'post',
    data,
    isShowLoading: true
  })
}

// 根据店铺id或商品id获取门店列表
export function GET_SHOPSTORELIST(data) {
  return http({
    url: '/goods/getStoreList',
    method: 'post',
    data
  })
}