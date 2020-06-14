export default [
  {
    path: '/channel/centre',
    name: 'channel-centre',
    meta: {
      title: '微商中心',
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'channel' */ '@/pages/channel/Centre')
  },
  {
    path: '/channel/apply',
    name: 'channel-apply',
    meta: {
      title: '成为渠道商',
      loginRequire: true,
      noKeepAlive: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'channel' */ '@/pages/channel/Apply')
  },
  {
    path: '/channel/goods/:type',
    name: 'channel-goods',
    meta: {
      title: '商品',
      loginRequire: true,
      noKeepAlive: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'channel' */ '@/pages/channel/Goods')
  },
  {
    path: '/channel/team',
    name: 'channel-team',
    meta: {
      title: '我的团队',
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'channel' */ '@/pages/channel/Team')
  },
  {
    path: '/channel/depot',
    name: 'channel-depot',
    meta: {
      title: '我的仓库',
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'channel' */ '@/pages/channel/depot/Index')
  },
  {
    path: '/channel/depot/log',
    name: 'channel-depot-log',
    meta: {
      title: '云仓库日志',
      loginRequire: true,
      noKeepAlive: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'channel' */ '@/pages/channel/depot/Log')
  },
  {
    path: '/channel/depot/list',
    name: 'channel-depot-list',
    meta: {
      title: '云仓库',
      loginRequire: true,
      noKeepAlive: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'channel' */ '@/pages/channel/depot/List')
  },
  {
    path: '/channel/depot/detail/:skuid',
    name: 'channel-depot-detail',
    meta: {
      title: '商品明细',
      loginRequire: true,
      noKeepAlive: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'channel' */ '@/pages/channel/depot/Detail')
  },
  {
    path: '/channel/achieve',
    name: 'channel-achieve',
    meta: {
      title: '我的业绩',
      loginRequire: true,
      noKeepAlive: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'channel' */ '@/pages/channel/Achieve')
  },
  {
    path: '/channel/finance',
    name: 'channel-finance',
    meta: {
      title: '财务管理',
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'channel' */ '@/pages/channel/Finance')
  },
  {
    path: '/channel/order/list/:type',
    name: 'channel-order-list',
    meta: {
      title: '订单列表',
      loginRequire: true,
      noKeepAlive: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'channel' */ '@/pages/channel/order/List')
  },
  {
    path: '/channel/order/detail/:type/:orderid',
    name: 'channel-order-detail',
    meta: {
      title: '订单详情',
      noKeepAlive: true,
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'channel' */ '@/pages/channel/order/Detail')
  },
  {
    path: '/channel/order/confirm/:type',
    name: 'channel-order-confirm',
    meta: {
      title: '确认订单',
      noKeepAlive: true,
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'channel' */ '@/pages/channel/order/Confirm')
  },
  {
    path: '/channel/certificate',
    name: 'channel-certificate',
    meta: {
      title: '授权证书',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'channel' */ '@/pages/channel/Certificate')
  }
]
