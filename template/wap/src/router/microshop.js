export default [
  {
    path: '/microshop/centre',
    name: 'microshop-centre',
    meta: {
      title: '微店中心',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'microshop' */ '@/pages/microshop/Centre')
  },
  {
    path: '/microshop/confirmorder',
    name: 'microshop-confirmorder',
    meta: {
      title: '确认订单',
      loginRequire: true,
      noKeepAlive: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'microshop' */ '@/pages/microshop/ConfirmOrder')
  },
  {
    path: '/microshop/GradeCentre',
    name: 'microshop-gradecentre',
    meta: {
      title: '等级中心',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'microshop' */ '@/pages/microshop/GradeCentre')
  },
  {
    path: '/microshop/profit/Detail',
    name: 'microshop-detail',
    meta: {
      title: '微店收益',
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'microshop' */ '@/pages/microshop/profit/Detail')
  },
  {
    path: '/microshop/profit/log',
    name: 'microshop-log',
    meta: {
      title: '收益明细',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'microshop' */ '@/pages/microshop/profit/Log')
  },
  {
    path: '/microshop/profit/withdraw',
    name: 'microshop-withdraw',
    meta: {
      title: '收益提现',
      noKeepAlive: true,
      loginRequire: true,
      noKeepAlive: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'microshop' */ '@/pages/microshop/profit/Withdraw')
  },
  {
    path: '/microshop/set',
    name: 'microshop-set',
    meta: {
      title: '微店管理',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'microshop' */ '@/pages/microshop/Set')
  },
  {
    path: '/microshop/shoplogo',
    name: 'microshop-shoplogo',
    meta: {
      title: '微店Logo',
      noKeepAlive: true,
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'microshop' */ '@/pages/microshop/manage/ShopLogo')
  },
  {
    path: '/microshop/info',
    name: 'microshop-info',
    meta: {
      title: '微店信息',
      noKeepAlive: true,
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'microshop' */ '@/pages/microshop/manage/Info')
  },
  {
    path: '/microshop/recruitmentlogo',
    name: 'microshop-recruitmentlogo',
    meta: {
      title: '店招Logo',
      noKeepAlive: true,
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'microshop' */ '@/pages/microshop/manage/Recruitmentlogo')
  },
  //挑选微店
  {
    path: '/microshop/choosegoods/category',
    name: 'microshop--choosecategory',
    meta: {
      title: '商品分类',
      shareType: 'current',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'microshop' */ '@/pages/microshop/choosegoods/Category')
  },
  {
    path: '/microshop/choosegoods/list',
    name: 'microshop-chooselist',
    meta: {
      title: '商品列表',
      shareType: 'current',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'microshop' */ '@/pages/microshop/choosegoods/List')
  },
  //预览微店
  {
    path: '/microshop/previewshop',
    name: 'microshop-previewshop',
    meta: {
      title: '预览微店',
      loginRequire: true,
      noKeepAlive: true,
      shareType: 'current'
    },
    component: () => import(/* webpackChunkName: 'microshop' */ '@/pages/microshop/Previewshop')
  },
  {
    path: '/microshop/preview/category',
    name: 'microshop-previewcategory',
    meta: {
      title: '预览微店商品分类',
      loginRequire: true,
      noKeepAlive: true,
      shareType: 'current'
    },
    component: () => import(/* webpackChunkName: 'microshop' */ '@/pages/microshop/preview/Category')
  },
  //预览微店全部商品
  {
    path: '/microshop/preview/list',
    name: 'microshop-previewlist',
    meta: {
      title: '商品列表',
      loginRequire: true,
      noKeepAlive: true,
      shareType: 'current'
    },
    component: () => import(/* webpackChunkName: 'microshop' */ '@/pages/microshop/preview/List')
  },
  {
    path: '/microshop/qrcode',
    name: 'microshop-qrcode',
    meta: {
      title: '分享微店',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'microshop' */ '@/pages/microshop/Qrcode')
  },
  {
    path: '/microshop/certificate',
    name: 'microshop-certificate',
    meta: {
      title: '授权证书',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'microshop' */ '@/pages/microshop/Certificate')
  }
]