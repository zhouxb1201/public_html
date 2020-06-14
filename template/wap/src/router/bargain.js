export default [
  {
    path: '/bargain/list',
    name: 'bargain-list',
    meta: {
      title: '砍价列表',
      noKeepAlive: true,
      shareType: 'current'
    },
    component: () => import(/* webpackChunkName: 'bargain' */ '@/pages/bargain/List')
  },
  {
    path: '/bargain/detail/:goodsid/:bargainid/:bargainuid',
    name: 'bargain-detail',
    meta: {
      title: '砍价详情',
      noKeepAlive: true,
      loginRequire: true,
      shareType: 'diy'
    },
    component: () => import(/* webpackChunkName: 'bargain' */ '@/pages/bargain/Detail')
  }
]
