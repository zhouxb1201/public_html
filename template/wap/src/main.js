import Vue from 'vue'
import App from './App'
import router from './router'
import store from './store'
import VueJsonp from 'vue-jsonp'
import "./utils/request"

import * as filters from './utils/filter'
import { BASESRC } from './utils/util'

import '@/assets/styles/iconfont.css'
import '@/assets/styles/app.css'
import '@/assets/styles/vant-ui.css'

import { Dialog, Lazyload, Icon, Cell, CellGroup, Button, Toast, Popup, Field, Notify, Tag, Tab, Tabs, RadioGroup, Radio, Row, Col, Checkbox, CheckboxGroup } from 'vant'
Vue.use(Toast)
Vue.use(Dialog)
Vue.use(Icon)
Vue.use(Cell)
Vue.use(CellGroup)
Vue.use(Button)
Vue.use(Popup)
Vue.use(Field)
Vue.use(Notify)
Vue.use(Tag)
Vue.use(RadioGroup)
Vue.use(Radio)
Vue.use(Tab).use(Tabs)
Vue.use(Row).use(Col)
Vue.use(Checkbox).use(CheckboxGroup)
Vue.use(VueJsonp)
import Layout from './components/Layout'
import Navbar from './components/Navbar'
import List from './components/List'
import BindMobile from "./components/popup-bind-mobile";
Vue.component('Layout', Layout)
Vue.component('Navbar', Navbar)
Vue.component('List', List)
Vue.component('BindMobile', BindMobile)

Vue.config.productionTip = false

// 提示框和确认框
Vue.prototype.$Toast = Toast
Vue.prototype.$Dialog = Dialog
Vue.prototype.$Toast.setDefaultOptions({ duration: 3000 })
Vue.prototype.$BindMobile = BindMobile

const baseImgPath = '/wap/static/images/'
Vue.prototype.$BASEIMGPATH = baseImgPath      // 全局图片基础路径
Vue.prototype.$BASESRC = BASESRC              // 处理图片资源方法
Vue.prototype.$ERRORPIC = {                   // 全局错误图片资源路径
  noAvatar: 'this.src="' + baseImgPath + "no-avatar.png" + '"',
  noGoods: 'this.src="' + baseImgPath + "no-goods.png" + '"',
  noShop: 'this.src="' + baseImgPath + "no-shop.png" + '"',
  noRectangle: 'this.src="' + baseImgPath + "no-rectangle.png" + '"',
  noSquare: 'this.src="' + baseImgPath + "no-square.png" + '"',
}

// 注册Lazyload 加载图片资源处理
Vue.use(Lazyload, {
  preLoad: 1.3,
  error: baseImgPath + 'no-square.png',
  loading: baseImgPath + 'no-square.png',
  attempt: 2,
  listenEvents: ['scroll'],
  lazyComponent: true,
  filter: {
    progressive(listener, options) {
      const type = listener.el.getAttribute('pic-type')
      if (type === 'goods') {
        listener.loading = baseImgPath + 'no-goods.png'
        listener.error = baseImgPath + 'no-goods.png'
      } else if (type === 'shop') {
        listener.loading = baseImgPath + 'no-shop.png'
        listener.error = baseImgPath + 'no-shop.png'
      } else if (type === 'square') {
        listener.loading = baseImgPath + 'no-square.png'
        listener.error = baseImgPath + 'no-square.png'
      } else if (type === 'rectangle') {
        listener.loading = baseImgPath + 'no-rectangle.png'
        listener.error = baseImgPath + 'no-rectangle.png'
      }
    },
    webp(listener, options) {
      if (!options.supportWebp) return
      listener.src = BASESRC(listener.src)
    }
  }
});

// 注册全局过滤器
Object.keys(filters).forEach(key => {
  Vue.filter(key, filters[key])
})

new Vue({
  el: '#app',
  router,
  store,
  render: h => h(App)
})
