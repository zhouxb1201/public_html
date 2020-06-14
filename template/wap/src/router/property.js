export default [
  {
    path: '/property',
    name: 'property',
    meta: {
      title: '我的资产',
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'property' */ '@/pages/property/Index')
  },
  {
    path: '/property/account',
    name: 'property-account',
    meta: {
      title: '提现账户',
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'property' */ '@/pages/property/account/Index')
  },
  {
    path: '/property/account/post',
    name: 'property-account-post',
    meta: {
      title: '新增账户',
      loginRequire: true,
      noKeepAlive: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'property' */ '@/pages/property/account/Post')
  },

  {
    path: '/property/account/detail',
    name: 'property-account-detail',
    meta: {
      title: '账户详情',
      loginRequire: true,
      noKeepAlive: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'property' */ '@/pages/property/account/Detail')
  },
  {
    path: '/property/balance',
    name: 'property-balance',
    meta: {
      title: '',
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'property' */ '@/pages/property/Balance')
  },
  {
    path: '/property/log',
    name: 'property-log',
    meta: {
      title: '',
      noKeepAlive: true,
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'property' */ '@/pages/property/Log')
  },
  {
    path: '/property/log/detail/:id',
    name: 'property-log-detail',
    meta: {
      title: '',
      noKeepAlive: true,
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'property' */ '@/pages/property/LogDetail')
  },
  {
    path: '/property/recharge',
    name: 'property-recharge',
    meta: {
      title: '充值',
      loginRequire: true,
      noKeepAlive: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'property' */ '@/pages/property/Recharge')
  },
  {
    path: '/property/withdraw',
    name: 'property-withdraw',
    meta: {
      title: '',
      loginRequire: true,
      noKeepAlive: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'property' */ '@/pages/property/Withdraw')
  },
  {
    path: '/property/points',
    name: 'property-points',
    meta: {
      title: '',
      noKeepAlive: true,
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'property' */ '@/pages/property/Points')
  },
  {
    path: '/property/exchange',
    name: 'property-exchange',
    meta: {
      title: '兑换',
      noKeepAlive: true,
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'property' */ '@/pages/property/Exchange')
  },
  {
    path: '/property/transfer',
    name: 'property-transfer',
    meta: {
      title: '转账',
      noKeepAlive: true,
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'property' */ '@/pages/property/Transfer')
  }
]
