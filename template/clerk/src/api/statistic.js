import http from '@/utils/request'

// 获取销售统计
export function GET_STATISTICDATA(date) {
  return http({
    url: '/addons/execute/addons/store/controller/wapstore/action/saleStatistics',
    method: 'post',
    data: { date }
  })
}