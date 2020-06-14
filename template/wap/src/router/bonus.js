export default [
  {
    path: '/bonus/apply/:agenttype',
    name: 'bonus-apply',
    meta: {
      title: '',
      noKeepAlive: true,
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'bonus' */ '@/pages/bonus/Apply')
  },
  {
    path: '/bonus/centre',
    name: 'bonus-centre',
    meta: {
      title: '',
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'bonus' */ '@/pages/bonus/Centre')
  },
  {
    path: '/bonus/detail',
    name: 'bonus-detail',
    meta: {
      title: '',
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'bonus' */ '@/pages/bonus/Detail')
  },
  {
    path: '/bonus/log',
    name: 'bonus-log',
    meta: {
      title: '',
      noKeepAlive: true,
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'bonus' */ '@/pages/bonus/Log')
  },
  {
    path: '/bonus/order',
    name: 'bonus-order',
    meta: {
      title: '',
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'bonus' */ '@/pages/bonus/Order')
  },
  {
    path: '/bonus/certificate',
    name: 'bonus-certificate',
    meta: {
      title: '授权证书',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'bonus' */ '@/pages/bonus/Certificate')
  },
  {
    path: '/bonus/level/:pagetype',
    name: 'bonus-level',
    meta: {
      title: '等级详情',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'bonus' */ '@/pages/bonus/Level')
  }
]