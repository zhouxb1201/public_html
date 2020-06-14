export default [
  {
    path: '/coupon/centre',
    name: 'coupon-centre',
    meta: {
      title: '领券中心',
      noKeepAlive: true,
      shareType: 'current'
    },
    component: () => import(/* webpackChunkName: 'coupon' */ '@/pages/coupon/Centre')
  },
  {
    path: '/coupon/list',
    name: 'coupon-list',
    meta: {
      title: '我的优惠券',
      noKeepAlive: true,
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'coupon' */ '@/pages/coupon/List')
  },
  {
    path: '/coupon/detail/:couponid',
    name: 'coupon-detail',
    meta: {
      title: '优惠券详情',
      noKeepAlive: true,
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'coupon' */ '@/pages/coupon/Detail')
  },
  {
    path: '/coupon/receive/:couponid',
    name: 'coupon-receive',
    meta: {
      title: '领取优惠券',
      noKeepAlive: true,
      shareType: 'current'
    },
    component: () => import(/* webpackChunkName: 'coupon' */ '@/pages/coupon/Receive')
  }
]