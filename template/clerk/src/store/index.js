import Vue from 'vue'
import Vuex from 'vuex'
import user from './modules/user'
import config from './modules/config'
import account from './modules/account'
import manage from './modules/manage'
import wxSdk from './modules/wx-sdk'
import getters from './getters'
import { getDomain, isWeixin } from '../utils/util'

Vue.use(Vuex)

const store = new Vuex.Store({
  state: {
    isWeixin: isWeixin(),
    domain: getDomain()
  },
  modules: {
    user,
    config,
    account,
    manage,
    wxSdk
  },
  getters

})

export default store
