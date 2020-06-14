export default [
  {
    path: '/giftvoucher/list',
    name: 'giftvoucher-list',
    meta: {
      title: '礼品券列表',
      noKeepAlive: true,
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'giftvoucher' */ '@/pages/giftvoucher/List')
  },
  {
    path: '/giftvoucher/detail/:recordid',
    name: 'giftvoucher-detail',
    meta: {
      title: '礼品券详情',
      noKeepAlive: true,
      loginRequire: true,
    },
    component: () => import(/* webpackChunkName: 'giftvoucher' */ '@/pages/giftvoucher/Detail')
  },
  {
    path: '/giftvoucher/receive/:giftvoucherid',
    name: 'giftvoucher-receive',
    meta: {
      title: '领取礼品券',
      noKeepAlive: true,
      shareType: 'current'
    },
    component: () => import(/* webpackChunkName: 'giftvoucher' */ '@/pages/giftvoucher/Receive')
  }
]
