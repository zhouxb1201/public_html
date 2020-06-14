import Vue from 'vue'
import Router from 'vue-router'
import routes from './routes'
import store from '@/store'
import LoadingBar from '@/components/loading-bar'

Vue.use(Router)

const whiteList = ['login', 'forget', 'author', 'bind']

const router = new Router({
  mode: 'history',
  base: '/clerk/',
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    } else {
      if (!from.meta.noKeepAlive) {
        from.meta.savedPosition = window.scrollY
      }
      return { x: 0, y: to.meta.savedPosition || 0 }
    }
  },
  routes
})

router.beforeEach((to, from, next) => {
  LoadingBar.start()
  if (store.getters.token) {
    if (to.name === 'login') {
      next('/')
    } else {
      next()
    }
  } else {
    if (whiteList.indexOf(to.name) !== -1) {
      // 无需登录白名单
      next()
    } else {
      store.commit('setToPath', to.fullPath)
      next('/login')
    }
  }
})

router.afterEach((to) => {
  if (to.meta.title) {
    document.title = to.meta.title
  }
  LoadingBar.finish()
})

export default router
