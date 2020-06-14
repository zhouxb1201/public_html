import http from '@/utils/request'

// 获取渠道商中心
export function GET_CENTREINFO(data) {
  return http({
    url: '/addons/channel/channel/channelIndex',
    method: 'post',
    data
  })
}

// 获取渠道商申请信息
export function GET_APPLYINFO(data) {
  return http({
    url: '/addons/channel/channel/applayChannelForm',
    method: 'post',
    data,
		isWriteIn: true,
  })
}

// 申请成为渠道商
export function APPLY_CHANNEL(data) {
  return http({
    url: '/addons/channel/channel/applayChannel',
    method: 'post',
    data,
		isWriteIn: true,
		isShowLoading: true
  })
}

// 获取商品分类
export function GET_GOODSCATEGORY(buy_type) {
  return http({
    url: '/addons/channel/channel/getChannelGoodsCategoryList',
    method: 'post',
    data: { buy_type }
  })
}

// 获取商品列表
export function GET_GOODSLIST(data) {
  return http({
    url: '/addons/channel/channel/getChannelGradeGoods',
    method: 'post',
    data
  })
}

// 添加到购物车
export function ADD_CARTGOODS(data) {
  return http({
    url: '/addons/channel/channel/addChannelCart',
    method: 'post',
    data,
		isWriteIn: true,
		isShowLoading: true
  })
}

// 获取购物车列表
export function GET_CARTLIST(data) {
  return http({
    url: '/addons/channel/channel/getChannelCartGoodsInfo',
    method: 'post',
    data
  })
}

// 删除购物车
export function REMOVE_CARTGOODS(data) {
  return http({
    url: '/addons/channel/channel/deleteChannelCart',
    method: 'post',
    data,
		isWriteIn: true,
		isShowLoading: true
  })
}

// 修改购物车数量
export function EDIT_CARTGOODSNUM(data) {
  return http({
    url: '/addons/channel/channel/channelCartAdjustNum',
    method: 'post',
    data,
		isWriteIn: true,
		isShowLoading: true
  })
}

// 计算运费
export function COUNT_FREIGHT(data) {
  return http({
    url: '/addons/channel/channel/countChannelFree',
    method: 'post',
    data
  })
}

// 获取确认订单信息
export function GET_ORDERINFO(buy_type) {
  return http({
    url: '/addons/channel/channel/channelSettlement',
    method: 'post',
    data: { buy_type }
  })
}

// 提交订单
export function CREATE_ORDER(data) {
  return http({
    url: '/addons/channel/channel/orderCreate',
    method: 'post',
    data,
		isWriteIn: true,
		isShowLoading: true
  })
}

// 获取我的团队
export function GET_TEAMLIST(data) {
  return http({
    url: '/addons/channel/channel/getMyTeam',
    method: 'post',
    data
  })
}

// 获取云仓库日志
export function GET_DEPOTLOG(data) {
  return http({
    url: '/addons/channel/channel/cloudStorageLog',
    method: 'post',
    data
  })
}

// 获取云仓库列表
export function GET_DEPOTLIST(data) {
  return http({
    url: '/addons/channel/channel/cloudStorage',
    method: 'post',
    data
  })
}

// 获取云仓库商品明细
export function GET_DEPOTDETAIL(data) {
  return http({
    url: '/addons/channel/channel/cloudStorageDetail',
    method: 'post',
    data
  })
}

// 获取我的业绩
export function GET_ACHIEVELIST(data) {
  return http({
    url: '/addons/channel/channel/MyChannelPerformance',
    method: 'post',
    data
  })
}

// 获取财务信息
export function GET_FINANCEINFO(data) {
  return http({
    url: '/addons/channel/channel/MyChannelBalance',
    method: 'post',
    data
  })
}

// 获取订单列表
export function GET_ORDERLIST(data) {
  return http({
    url: '/addons/channel/channel/getChannelOrderDetailList',
    method: 'post',
    data
  })
}

// 获取订单详情
export function GET_ORDERDETAIL(data) {
  return http({
    url: '/addons/channel/channel/getPurchaseOrderDetail',
    method: 'post',
    data
  })
}

// 关闭订单
export function CLOSE_ORDER(data) {
  return http({
    url: '/addons/channel/channel/channelOrderClose',
    method: 'post',
    data,
		isWriteIn: true,
		isShowLoading: true
  })
}

// 发货
export function CONFIRM_TAKEDELIVERY(data) {
  return http({
    url: '/addons/channel/channel/channelOrderClose',
    method: 'post',
    data,
		isWriteIn: true,
		isShowLoading: true
  })
}

// 采购订单支付
export function GET_CHANNELORDERPAYINFO(order_id) {
  return http({
    url: '/order/channelOrderPay',
    method: 'post',
    data: { order_id },
		isWriteIn: true
  })
}