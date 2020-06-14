export default [{
    
    path: '/invoice/detail',
    name: 'invoice-detail',
    meta: {
        title: '发票详情',
        loginRequire: true,
        noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'invoice' */ '@/pages/invoice/Detail')
      
}]