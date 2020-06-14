export default [
  {
    path: '/task/centre',
    name: 'task-centre',
    meta: {
      title: '任务中心',
      noKeepAlive: true,
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'task' */ '@/pages/task/Centre')
  },
  {
    path: '/task/list',
    name: 'task-list',
    meta: {
      title: '我的任务',
      noKeepAlive: true,
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'task' */ '@/pages/task/List')
  },
  {
    path: '/task/detail/:id',
    name: 'task-detail',
    meta: {
      title: '任务详情',
      noKeepAlive: true,
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'task' */ '@/pages/task/Detail')
  }
]
