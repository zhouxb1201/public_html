import http from '@/utils/request'

// 获取任务中心信息
export function GET_TASKCENTRE(data) {
  return http({
    url: '/addons/taskcenter/taskcenter/getTaskList',
    method: 'post',
    data
  })
}

// 领取任务
export function RECEIVE_TASK(general_poster_id) {
  return http({
    url: '/addons/taskcenter/taskcenter/getMyTask',
    method: 'post',
    data: { general_poster_id }
  })
}

// 我的任务
export function GET_TASKLIST(data) {
  return http({
    url: '/addons/taskcenter/taskcenter/getMyTaskList',
    method: 'post',
    data
  })
}

// 任务详情
export function GET_TASKDETAIL(general_poster_id, user_task_id = '') {
  return http({
    url: '/addons/taskcenter/taskcenter/getTaskDetail',
    method: 'post',
    data: { general_poster_id, user_task_id }
  })
}