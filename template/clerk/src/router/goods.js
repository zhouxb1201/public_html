export default [
  {
    path: '/goods',
    name: 'goods-index',
    meta: {
      title: '商品管理',
      noKeepAlive: true,
      loginRequire: true
    },
    component: resolve => require(['@/pages/goods/Index'], resolve)
  },
  {
    path: '/goods/edit/:goodsid',
    name: 'goods-edit',
    meta: {
      title: '商品编辑',
      noKeepAlive: true,
      loginRequire: true
    },
    component: resolve => require(['@/pages/goods/Edit'], resolve)
  },
  {
    path: '/goods/add',
    name: 'goods-add',
    meta: {
      title: '添加商品',
      noKeepAlive: true,
      loginRequire: true
    },
    component: resolve => require(['@/pages/goods/Add'], resolve)
  },
  {
    path: '/goods/search',
    name: 'goods-search',
    meta: {
      title: '',
      noKeepAlive: true,
      loginRequire: true
    },
    component: resolve => require(['@/pages/goods/Search'], resolve)
  }
]