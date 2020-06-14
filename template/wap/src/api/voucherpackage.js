import http from '@/utils/request'

// 券包详情
export function GET_VOUCHERPACKAGEDETAIL(voucher_package_id) {
  return http({
    url: '/addons/voucherpackage/voucherpackage/voucherPackage',
    method: 'post',
    data: { voucher_package_id }
  })
}

// 领取券包
export function RECEIVE_VOUCHERPACKAGE(voucher_package_id) {
  return http({
    url: '/addons/voucherpackage/voucherpackage/userArchiveVoucherPackage',
    method: 'post',
    data: { voucher_package_id },
    isWriteIn: true,
    isShowLoading: true
  })
}