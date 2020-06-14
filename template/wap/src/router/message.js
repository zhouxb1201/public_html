export default [
  {
    path: '/message',
    name: 'message-index',
    meta: {
      title: '消息中心',
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'message' */ '@/pages/message/Index')
  },
  {
    path: '/message/notice',
    name: 'message-notice',
    meta: {
      title: '消息通知',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'message' */ '@/pages/message/Notice')
  },
  {
    path: '/message/list',
    name: 'message-list',
    meta: {
      title: '',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'message' */ '@/pages/message/List')
  }
]