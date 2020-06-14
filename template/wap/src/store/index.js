import Vue from 'vue'
import Vuex from 'vuex'
import config from './modules/config'
import custom from './modules/custom'
import tabbar from './modules/tabbar'
import user from './modules/user'
import area from './modules/area'
import account from './modules/account'
import extend from './modules/extend'
import wxSdk from './modules/wx-sdk'
import member from './modules/member'
import commission from './modules/commission'
import channel from './modules/channel'
import assemble from './modules/assemble'
import _store from './modules/store'
import microshop from './modules/microshop'
import buildImg from './modules/build-img'
import poster from './modules/poster'
import shop from './modules/shop'
import blockchain from './modules/blockchain'
import message from './modules/message'
import property from './modules/property'
import credential from  './modules/credential'
import getters from './getters'
import { getDomain, isWeixin } from '@/utils/util'

Vue.use(Vuex)

const store = new Vuex.Store({
  state: {
    isWeixin: isWeixin(),
    domain: getDomain(),
    user: null,
    config: null
  },
  modules: {
    config,
    custom,
    tabbar,
    user,
    area,
    account,
    extend,
    wxSdk,
    member,
    commission,
    channel,
    assemble,
    _store,
    microshop,
    buildImg,
    poster,
    shop,
    blockchain,
    message,
    property,
    credential
  },
  getters

})

export default store
