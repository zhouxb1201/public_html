export default [
  {
    path: '/commission/apply',
    name: 'commission-apply',
    meta: {
      title: '',
      noKeepAlive: true,
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'commission' */ '@/pages/commission/Apply')
  },
  {
    path: '/commission/centre',
    name: 'commission-centre',
    meta: {
      title: '',
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'commission' */ '@/pages/commission/Centre')
  },
  {
    path: '/commission/detail',
    name: 'commission-detail',
    meta: {
      title: '',
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'commission' */ '@/pages/commission/Detail')
  },
  {
    path: '/commission/log',
    name: 'commission-log',
    meta: {
      title: '',
      noKeepAlive: true,
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'commission' */ '@/pages/commission/Log')
  },
  {
    path: '/commission/order',
    name: 'commission-order',
    meta: {
      title: '',
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'commission' */ '@/pages/commission/Order')
  },
  {
    path: '/commission/team',
    name: 'commission-team',
    meta: {
      title: '',
      noKeepAlive: true,
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'commission' */ '@/pages/commission/Team')
  },
  {
    path: '/commission/customer',
    name: 'commission-customer',
    meta: {
      title: '',
      noKeepAlive: true,
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'commission' */ '@/pages/commission/Customer')
  },
  {
    path: '/commission/withdraw',
    name: 'commission-withdraw',
    meta: {
      title: '',
      noKeepAlive: true,
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'commission' */ '@/pages/commission/Withdraw')
  },
  {
    path: '/commission/qrcode',
    name: 'commission-qrcode',
    meta: {
      title: '',
      noKeepAlive: true,
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'commission' */ '@/pages/commission/Qrcode')
  },
  {
    path: '/commission/certificate',
    name: 'commission-certificate',
    meta: {
      title: '授权证书',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'commission' */ '@/pages/commission/Certificate')
  },
  {
    path: '/commission/ranking',
    name: 'commission-ranking',
    meta: {
      title: '排行榜',
      noKeepAlive: true,
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'commission' */ '@/pages/commission/Ranking')
  },
  {
    path: '/commission/level',
    name: 'commission-level',
    meta: {
      title: '等级详情',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'commission' */ '@/pages/commission/Level')
  }
]