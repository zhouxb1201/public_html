export default [
  {
    path: '/manage/list',
    name: 'manage-list',
    meta: {
      title: '店员管理',
      noKeepAlive: true,
      loginRequire: true
    },
    component: resolve => require(['@/pages/manage/List'], resolve)
  },
  {
    path: '/manage/post',
    name: 'manage-post',
    meta: {
      title: '',
      noKeepAlive: true,
      loginRequire: true
    },
    component: resolve => require(['@/pages/manage/Post'], resolve)
  }
]