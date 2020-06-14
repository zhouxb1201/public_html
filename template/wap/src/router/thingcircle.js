export default [
    {
        path: '/thingcircle/index',
        name: 'thingcircle-index',
        redirect: '/thingcircle/home',
        meta: {
            title: '',
            noKeepAlive: true,
            loginRequire: true
        },
        children:[
            {
                path:"/thingcircle/home",
                name: 'thingcircle-home',
                meta: {
                    title: '',
                    noKeepAlive: true,
                    loginRequire: true,
                    shareType: 'diy'
                },
                component: () => import(/* webpackChunkName: 'thingcircle' */ '@/pages/thingcircle/Home')
                
            },
            {
                path:"/thingcircle/mine",
                name: 'thingcircle-mine',
                meta: {
                    title: '',
                    noKeepAlive: true,
                    loginRequire: true,
                    shareType: 'diy'
                },
                component: () => import(/* webpackChunkName: 'thingcircle' */ '@/pages/thingcircle/Mine')
            }
        ],
        component: () => import(/* webpackChunkName: 'thingcircle' */ '@/pages/thingcircle/Index')
        
    },
    {
        path: '/thingcircle/report/:commentid',
        name: 'thingcircle-report',
        meta: {
            title: '举报评论',
            noKeepAlive: true,
            loginRequire: true
        },
        component: () => import(/* webpackChunkName: 'thingcircle' */ '@/pages/thingcircle/Report')
        
    },
    {
        path: '/thingcircle/fans',
        name: 'thingcircle-fans',
        meta: {
            title: '我的粉丝',
            noKeepAlive: true,
            loginRequire: true,
            shareType: 'diy'
        },
        component: () => import(/* webpackChunkName: 'thingcircle' */ '@/pages/thingcircle/Fans')
        
    },
    {
        path: '/thingcircle/follow',
        name: 'thingcircle-follow',
        meta: {
            title: '我的关注',
            noKeepAlive: true,
            loginRequire: true,
            shareType: 'diy'
        },
        component: () => import(/* webpackChunkName: 'thingcircle' */ '@/pages/thingcircle/Follow')
        
    },
    {
        path: '/thingcircle/release',
        name: 'thingcircle-release',
        meta: {
            title: '发布干货',
            noKeepAlive: true,
            loginRequire: true,
            shareType: 'diy'
        },
        component: () => import(/* webpackChunkName: 'thingcircle' */ '@/pages/thingcircle/Release')
        
    },
    {
        path: '/thingcircle/grapdetail/:thingid',
        name: 'thingcircle-grapdetail',
        meta: {
            title: '干货详情',
            noKeepAlive: true,
            shareType: 'diy'
        },
        component: () => import(/* webpackChunkName: 'thingcircle' */ '@/pages/thingcircle/GrapDetail')
        
    },
    {
        path: '/thingcircle/vediodetail/:thingid',
        name: 'thingcircle-vediodetail',
        meta: {
            title: '干货视频详情',
            noKeepAlive: true,
            shareType: 'diy'
        },
        component: () => import(/* webpackChunkName: 'thingcircle' */ '@/pages/thingcircle/VedioDetail')
        
    }
]