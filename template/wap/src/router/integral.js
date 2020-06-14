export default [
	{
		path: '/integral/index',
		name: 'integral-index',
		meta: {
			title: '积分商城',
			loginRequire: true,
			shareType: 'current'
		},
		component: () => import(/* webpackChunkName: 'integral' */ '@/pages/integral/Index')
	},
	{
		path: '/integral/category',
		name: 'integral-category',
		meta: {
			title: '商品分类',
			loginRequire: true,
			shareType: 'current'
		},
		component: () => import(/* webpackChunkName: 'integral' */ '@/pages/integral/Category')
	},
	{
		path: '/integral/goods/list',
		name: 'integral-goods-list',
		meta: {
			title: '商品列表',
			noKeepAlive: true,
			loginRequire: true,
			shareType: 'current'
		},
		component: () => import(/* webpackChunkName: 'integral' */ '@/pages/integral/goods/List')
	},
	{
		path: '/integral/goods/detail/:goodsid',
		name: 'integral-goods-detail',
		meta: {
			title: '商品详情',
			noKeepAlive: true,
			loginRequire: true,
			shareType: 'current'
		},
		component: () => import(/* webpackChunkName: 'integral' */ '@/pages/integral/goods/Detail')
	},
	{
		path: '/integral/order/confirm',
		name: 'integral-order-confirm',
		meta: {
			title: '确认订单',
			noKeepAlive: true,
			login_require: true,
			mobileRequire: true
		},
		component: () => import(/* webpackChunkName: 'integral' */ '@/pages/integral/order/Confirm')
	}
]