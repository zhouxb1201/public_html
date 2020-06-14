export default [
  {
    path: '/member/centre',
    name: 'member-centre',
    meta: {
      title: '',
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'member' */ '@/pages/member/Centre')
  },
  {
    path: '/account/set',
    name: 'account-set',
    meta: {
      title: '账号设置',
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'account' */ '@/pages/account/Set')
  },
  {
    path: '/account/info',
    name: 'account-info',
    meta: {
      title: '基本信息',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'account' */ '@/pages/account/Info')
  },
  {
    path: '/account/avatar',
    name: 'account-avatar',
    meta: {
      title: '修改头像',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'account' */ '@/pages/account/Avatar')
  },
  {
    path: '/account/post/:pagetype',
    name: 'account-post',
    meta: {
      title: '',
      noKeepAlive: true,
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'account' */ '@/pages/account/Post')
  },
  {
    path: '/account/relevant',
    name: 'account-relevant',
    meta: {
      title: '关联账号',
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'account' */ '@/pages/account/Relevant')
  },
  {
    path: '/member/level',
    name: 'member-level',
    meta: {
      title: '等级详情',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'member' */ '@/pages/member/Level')
  }
]
