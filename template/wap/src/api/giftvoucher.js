import http from '@/utils/request'

// 礼品券列表
export function GET_GIFTVOUCHERLIST(data) {
  return http({
    url: '/addons/giftvoucher/giftvoucher/userGiftvoucher',
    method: 'post',
    data
  })
}

// 礼品券详情
export function GET_GIFTVOUCHERDETAIL(record_id) {
  return http({
    url: '/addons/giftvoucher/giftvoucher/userGiftvoucherInfo',
    method: 'post',
    data: { record_id }
  })
}

// 领取礼品券
export function RECEIVE_GIFVOUCHER(data) {
  return http({
    url: '/addons/giftvoucher/giftvoucher/giftvoucherReceive',
    method: 'post',
    data,
    isWriteIn: true
  })
}

// 礼品券详情领取页
export function GET_GIFTVOUCHERDETAILRECEIVE(gift_voucher_id) {
  return http({
    url: '/addons/giftvoucher/giftvoucher/giftvoucherDetail',
    method: 'post',
    data: { gift_voucher_id }
  })
}

// 礼品券适用门店
export function GET_GIFTVOUCHERSTORE(data) {
  return http({
    url: '/addons/giftvoucher/giftvoucher/giftvoucherStore',
    method: 'post',
    data
  })
}