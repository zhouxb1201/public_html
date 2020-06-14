import http from '@/utils/request'

// 获取消费卡列表
export function GET_CONSUMERCARDLIST(data) {
  return http({
    url: '/member/consumerCard',
    method: 'post',
    data
  })
}

// 获取消费卡详情
export function GET_CONSUMERCARDDETAIL(data) {
  return http({
    url: '/member/consumerCardDetail',
    method: 'post',
    data
  })
}

// 获取消费卡核销记录
export function GET_CONSUMERCARDLOG(data) {
  return http({
    url: '/member/consumerCardRecord',
    method: 'post',
    data
  })
}

// 获取添加微信卡券的卡券参数
export function GET_WXCARDPARAMS(cards_id) {
  return http({
    url: '/member/getwxCard',
    method: 'post',
    data: { cards_id },
		isShowLoading: true
  })
}

// 微信卡券领取成功后执行
export function GET_WXCARDSTATE(cards_id) {
  return http({
    url: '/member/getwxCardUse',
    method: 'post',
    data: { cards_id }
  })
}