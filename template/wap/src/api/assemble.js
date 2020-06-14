import http from '@/utils/request'

// 拼团列表
export function GET_ASSEMBLELIST(data) {
  return http({
    url: '/addons/groupshopping/groupshopping/groupShoppingListForWap',
    method: 'post',
    data
  })
}

// 获取拼团详情
export function GET_ASSEMBLEDETAIL(record_id) {
  return http({
    url: '/addons/groupshopping/groupshopping/getGroupMemberListForWap',
    method: 'post',
    data: { record_id }
  })
}

