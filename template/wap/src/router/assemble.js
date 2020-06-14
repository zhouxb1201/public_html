export default [
  {
    path: '/assemble/list',
    name: 'assemble-list',
    meta: {
      title: '拼团列表',
      noKeepAlive: true,
      shareType: 'current'
    },
    component: () => import(/* webpackChunkName: 'assemble' */ '@/pages/assemble/List')
  },
  {
    path: '/assemble/detail/:recordid',
    name: 'assemble-detail',
    meta: {
      title: '拼团详情',
      noKeepAlive: true,
      shareType: 'diy'
    },
    component: () => import(/* webpackChunkName: 'anticounterfeiting' */ '@/pages/assemble/Detail')
  }
]
