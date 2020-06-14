import http from '@/utils/request'

// 获取成为代理商信息
export function GET_APPLYAGENTINFO(type) {
  return http({
    url: '/Member/applyagent',
    method: 'post',
    data: { type },
    isWriteIn: true
  })
}

// 申请成为全球代理
export function APPLY_GLOBALAGENT(data) {
  return http({
    url: '/addons/globalbonus/Globalbonus/globalAgentApply',
    method: 'post',
    data,
    isWriteIn: true,
    isShowLoading: true
  })
}

// 申请成为团队代理
export function APPLY_TEAMAGENT(data) {
  return http({
    url: '/addons/teambonus/Teambonus/teamAgentApply',
    method: 'post',
    data,
    isWriteIn: true,
    isShowLoading: true
  })
}

// 申请成为区域代理
export function APPLY_AREAAGENT(data) {
  return http({
    url: '/addons/areabonus/areabonus/areaAgentApply',
    method: 'post',
    data,
    isWriteIn: true,
    isShowLoading: true
  })
}

// 获取分红中心
export function GET_CENTREINFO(data) {
  return http({
    url: '/Member/bonusIndex',
    method: 'post',
    data
  })
}

// 获取分红金额
export function GET_BONUSDETAIL(data) {
  return http({
    url: '/Member/myBonus',
    method: 'post',
    data
  })
}

// 获取分红明细
export function GET_BONUSLOG(data) {
  return http({
    url: '/Member/bonus_detail',
    method: 'post',
    data
  })
}

// 获取分红订单
export function GET_ORDERLIST(data) {
  return http({
    url: '/Member/bonus_order',
    method: 'post',
    data
  })
}

// 获取分红设置相关文案字眼
export function GET_BONUSSETTEXT(data) {
	return http({
		url: '/member/bonusSet',
		method: 'post',
		data
	})
}