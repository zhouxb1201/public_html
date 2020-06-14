import http from '@/utils/request'

// 根据核销码获取订单列表
export function GET_CODEORDER(code) {
  return http({
    url: '/addons/execute/addons/store/controller/wapstore/action/getOrderListByCode',
    method: 'post',
    data: { code },
    errorCallback: true
  })
}

// 核销订单
export function VERIFY_ORDER(order_id) {
  return http({
    url: '/addons/execute/addons/store/controller/wapstore/action/pickupOrder',
    method: 'post',
    data: { order_id },
    isShowLoading: true
  })
}

// 根据核销码获取消费卡
export function GET_CODECARD(card_code) {
  return http({
    url: '/addons/execute/addons/store/controller/wapstore/action/consumerCardDetail',
    method: 'post',
    data: { card_code },
    errorCallback: true
  })
}

// 核销消费卡
export function VERIFY_CARD(code) {
  return http({
    url: '/addons/execute/addons/store/controller/wapstore/action/consumerCardUse',
    method: 'post',
    data: { code },
    isShowLoading: true
  })
}

// 根据核销码获取礼品券
export function GET_CODEGIFT(gift_voucher_code) {
  return http({
    url: '/addons/execute/addons/store/controller/wapstore/action/userGiftvoucherInfo',
    method: 'post',
    data: { gift_voucher_code },
    errorCallback: true
  })
}

// 核销礼品券
export function VERIFY_GIFT(code) {
  return http({
    url: '/addons/execute/addons/store/controller/wapstore/action/giftvoucherUse',
    method: 'post',
    data: { code },
    isShowLoading: true
  })
}

// 获取核销记录
export function GET_VERIFYLOG(data) {
  return http({
    url: '/addons/execute/addons/store/controller/wapstore/action/verificationLog',
    method: 'post',
    data
  })
}