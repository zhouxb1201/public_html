export default [
  {
    path: '/account/set',
    name: 'account-set',
    meta: {
      title: '帐号设置',
      noKeepAlive: true,
      loginRequire: true
    },
    component: resolve => require(['@/pages/account/Set'], resolve)
  },
  {
    path: '/account/avatar',
    name: 'account-avatar',
    meta: {
      title: '修改头像',
      noKeepAlive: true,
      loginRequire: true
    },
    component: resolve => require(['@/pages/account/Avatar'], resolve)
  },
  {
    path: '/account/post/:pagetype',
    name: 'account-post',
    meta: {
      title: '',
      noKeepAlive: true,
      loginRequire: true
    },
    component: resolve => require(['@/pages/account/Post'], resolve)
  }
]