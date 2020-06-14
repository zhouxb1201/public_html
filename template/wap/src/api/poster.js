import http from '@/utils/request'

// 获取超级海报图片
export function GET_POSTERIMG(data) {
  return http({
    url: '/addons/poster/poster/getKindPoster',
    method: 'post',
    data
  })
}
