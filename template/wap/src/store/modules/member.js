import { GET_MEMBERINFO, CHECK_PAYPASSWORD, GET_MEMBERSETTEXT } from "@/api/member";
import { GET_COMMISSIONSETTEXT } from "@/api/commission";
import { GET_BONUSSETTEXT } from '@/api/bonus';
import { Toast } from "vant";
import { isEmpty } from '@/utils/util'
import router from '@/router';
import { memberText, commission, bonus } from '../default-data-text';

const defaultMemberText = memberText  // 默认会员文案
const defaultCommissionText = commission  // 默认分销文案
const defaultBonusText = bonus  // 默认分红文案

const member = {
  state: {
    info: null,
    isMemberSetText: false,
    isCommissionSetText: false,
    isBonusSetText: false,
    memberSetText: defaultMemberText,
    commissionSetText: defaultCommissionText,
    bonusSetText: defaultBonusText
  },
  mutations: {
    setMemberInfo(state, info) {
      state.info = info
    },
    setMemberText(state, text) {
      state.isMemberSetText = true
      state.memberSetText = text
    },
    setCommissionText(state, text) {
      state.isCommissionSetText = true
      state.commissionSetText = text
    },
    setBonusText(state, text) {
      state.isBonusSetText = true
      state.bonusSetText = text
    }
  },
  actions: {
    /**
     * 获取会员信息
     * @param {Boolean} noupdate  更新会员信息 默认false
     */
    getMemberInfo(context, noupdate) {
      return new Promise((resolve, reject) => {
        function getInfo() {
          GET_MEMBERINFO().then(({ data }) => {
            if (data.uid) {
              context.commit('setBindMobile', data.user_tel)
              context.commit('setMemberInfo', data)
              resolve(data)
            } else {
              reject()
              context.commit('removeUserInfo', {})
            }
          }).catch(error => {
            reject(error)
            error.message !== 'cancel' && context.commit('removeUserInfo', {})
          })
        }
        if (!noupdate) {
          getInfo()
        } else {
          if (context.state.info) {
            context.commit('setMemberInfo', context.state.info)
            resolve(context.state.info)
          } else {
            getInfo()
          }
        }
      })
    },
    // 是否为分销商  (noTip有则不提示错误信息,为true不更新会员接口)
    isBistributor(context, noTip) {
      return new Promise((resolve, reject) => {
        if (context.rootState.config.addons.distribution == 1) {
          context.dispatch('getMemberInfo', noTip).then(({ isdistributor }) => {
            if (isdistributor == 2) {
              resolve()
            } else {
              reject({ callback: true })
            }
          }).catch(() => {
            reject({ error: true })
            if (!noTip) {
              Toast("获取数据失败！");
            }
          })
        } else {
          reject()
          if (!noTip) {
            Toast('分销应用未开启，请先开启分销应用！')
            router.replace("/member/centre");
          }
        }
      })
    },
    // 检查支付密码
    checkPayPassword({ commit }, password) {
      return new Promise((resolve, reject) => {
        CHECK_PAYPASSWORD(password).then(res => {
          resolve(res)
        }).catch(error => {
          reject(error)
        })
      })
    },
    // 获取会员相关文案字眼
    getMemberSetText(context) {
      return new Promise((resolve, reject) => {
        if (!context.state.isMemberSetText) {
          GET_MEMBERSETTEXT().then(({ data }) => {
            if (data.balance_style) {
              context.commit('setMemberText', data)
              resolve(data)
            } else {
              context.commit('setMemberText', defaultMemberText)
              resolve(defaultMemberText)
            }
          }).catch(() => {
            context.commit('setMemberText', defaultMemberText)
            resolve(defaultMemberText)
          })
        } else {
          resolve(context.state.memberSetText)
        }

      })
    },
    // 获取分销文案相关字眼
    getCommissionSetText(context) {
      return new Promise((resolve, reject) => {
        if (context.rootState.config.addons.distribution == 1) {
          if (!context.state.isCommissionSetText) {
            GET_COMMISSIONSETTEXT().then(({ data }) => {
              if (data.commission) {
                context.commit('setCommissionText', data)
                resolve(data)
              } else {
                context.commit('setCommissionText', defaultCommissionText)
                resolve(defaultCommissionText)
              }
            }).catch(() => {
              context.commit('setCommissionText', defaultCommissionText)
              resolve(defaultCommissionText)
            })
          } else {
            resolve(context.state.commissionSetText)
          }
        } else {
          context.commit('setCommissionText', defaultCommissionText)
          resolve(defaultCommissionText)
        }
      })
    },
    // 获取分红文案相关字眼
    getBonusSetText(context) {
      return new Promise((resolve, reject) => {
        const { areabonus, globalbonus, teambonus, distribution } = context.rootState.config.addons
        if ((areabonus || globalbonus || teambonus) && distribution) {
          if (!context.state.isBonusSetText) {
            GET_BONUSSETTEXT().then(({ data }) => {
              const obj = {}
              obj.common = isEmpty(data.common) ? defaultBonusText.common : data.common
              obj.area = isEmpty(data.area) ? defaultBonusText.area : data.area
              obj.global = isEmpty(data.global) ? defaultBonusText.global : data.global
              obj.team = isEmpty(data.team) ? defaultBonusText.team : data.team
              context.commit('setBonusText', obj)
              resolve(obj)
            }).catch(() => {
              context.commit('setBonusText', defaultBonusText)
              resolve(defaultBonusText)
            })
          } else {
            resolve(context.state.bonusSetText)
          }
        } else {
          context.commit('setBonusText', defaultBonusText)
          resolve(defaultBonusText)
        }
      })
    }
  }
}

export default member
