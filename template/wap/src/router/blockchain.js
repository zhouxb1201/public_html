export default [
  {
    path: '/blockchain',
    name: 'blockchain',
    meta: {
      title: '数字资产',
      noKeepAlive: true,
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'blockchain' */ '@/pages/blockchain/Index')
  },
  {
    path: '/blockchain/centre/:type',
    name: 'blockchain-centre',
    meta: {
      title: '',
      noKeepAlive: true,
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'blockchain' */ '@/pages/blockchain/Centre')
  },
  {
    path: '/blockchain/wallet/:type',
    name: 'blockchain-wallet',
    meta: {
      title: '钱包地址',
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'blockchain' */ '@/pages/blockchain/Wallet')
  },
  {
    path: '/blockchain/trade/log/:type',
    name: 'blockchain-trade-log',
    meta: {
      title: '交易明细',
      noKeepAlive: true,
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'blockchain' */ '@/pages/blockchain/trade/Log')
  },
  {
    path: '/blockchain/trade/detail/:type/:id',
    name: 'blockchain-trade-detail',
    meta: {
      title: '交易详情',
      noKeepAlive: true,
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'blockchain' */ '@/pages/blockchain/trade/Detail')
  },
  {
    path: '/blockchain/exchange/:type',
    name: 'blockchain-exchange',
    meta: {
      title: '兑换',
      noKeepAlive: true,
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'blockchain' */ '@/pages/blockchain/Exchange')
  },
  {
    path: '/blockchain/transfer/:type',
    name: 'blockchain-transfer',
    meta: {
      title: '转账',
      noKeepAlive: true,
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'blockchain' */ '@/pages/blockchain/Transfer')
  },
  {
    path: '/blockchain/export/:type/:key',
    name: 'blockchain-export',
    meta: {
      title: '导出',
      noKeepAlive: true,
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'blockchain' */ '@/pages/blockchain/Export')
  },
  {
    path: '/pay/create',
    name: 'pay-create',
    meta: {
      title: '创建钱包',
      noKeepAlive: true,
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'blockchain' */ '@/pages/blockchain/Create')
  },
  {
    path: '/blockchain/resource',
    name: 'blockchain-resource',
    meta: {
      title: 'EOS资源',
      noKeepAlive: true,
      loginRequire: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'blockchain' */ '@/pages/blockchain/Resource')
  }
]