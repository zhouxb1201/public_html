import http from '@/utils/request'

// 获取订单列表
export function GET_ORDERLIST(data) {
  return http({
    url: '/addons/execute/addons/store/controller/wapstore/action/getStoreOrderList',
    method: 'post',
    data
  })
}

// 获取订单详情
export function GET_ORDERDETAIL(order_id) {
  return http({
    url: '/addons/execute/addons/store/controller/wapstore/action/orderDetail',
    method: 'post',
    data: { order_id }
  })
}

// 获取售后订单列表
export function GET_AFTERORDERLIST(data) {
  return http({
    url: '/addons/execute/addons/store/controller/wapstore/action/afterOrderList',
    method: 'post',
    data
  })
}

// 同意打款
export function AGREE_AFTERORDER(data) {
  return http({
    url: '/addons/execute/addons/store/controller/wapstore/action/orderGoodsConfirmRefund',
    method: 'post',
    data,
    isShowLoading: true
  })
}

// 拒绝打款
export function REFUSE_AFTERORDER(data) {
  return http({
    url: '/addons/execute/addons/store/controller/wapstore/action/orderGoodsRefuseOnce',
    method: 'post',
    data,
    isShowLoading: true
  })
}