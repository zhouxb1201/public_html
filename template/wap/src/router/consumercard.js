export default [
  {
    path: '/consumercard/list',
    name: 'consumercard-list',
    meta: {
      title: '消费卡',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'consumercard' */ '@/pages/consumercard/List')
  },
  {
    path: '/consumercard/detail/:cardid',
    name: 'consumercard-detail',
    meta: {
      title: '消费卡详情',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'consumercard' */ '@/pages/consumercard/Detail')
  }
]
