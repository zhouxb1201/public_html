export default [
	{
		path: '/shop/home/:shopid',
		name: 'shop-home',
		meta: {
			title: '',
			noKeepAlive: true,
			shareType: 'diy'
		},
		component: () => import(/* webpackChunkName: 'shop' */ '@/pages/shop/Home')
	},
	{
		path: '/shop/list',
		name: 'shop-list',
		meta: {
			title: '店铺街',
			shareType: 'current'
		},
		component: () => import(/* webpackChunkName: 'shop' */ '@/pages/shop/List')
	},
	{
		path: '/shop/collection',
		name: 'shop-collection',
		meta: {
			title: '店铺收藏',
			loginRequire: true
		},
		component: () => import(/* webpackChunkName: 'shop' */ '@/pages/shop/Collection')
	},
	{
		path: '/shop/centre',
		name: 'shop-centre',
		meta: {
			title: '商家中心',
			loginRequire: true,
			noKeepAlive: true
		},
		component: () => import(/* webpackChunkName: 'shop' */ '@/pages/shop/Centre')
	},
	{
		path: '/shop/apply',
		name: 'shop-apply',
		meta: {
			title: '店铺入驻',
			loginRequire: true,
			noKeepAlive: true,
			mobileRequire: true
		},
		component: () => import(/* webpackChunkName: 'shop' */ '@/pages/shop/Apply')
	},
	{
		path: '/shop/result',
		name: 'shop-result',
		meta: {
			title: '申请状态',
			loginRequire: true,
			noKeepAlive: true
		},
		component: () => import(/* webpackChunkName: 'shop' */ '@/pages/shop/Result')
	},
	{
		path: '/shop/search',
		name: 'shop-search',
		meta: {
			title: '搜索',
			loginRequire: true,
			noKeepAlive: true
		},
		component: () => import(/* webpackChunkName: 'shop' */ '@/pages/shop/Search')
	}
]
