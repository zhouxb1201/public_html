import { GET_BANKSIGNINGSMS, SIGNING_BANKCARD, GET_PROPERTYCHARGESERVICE } from "@/api/property";
import { GET_BANKCARDSMS, PAY_BANKCARD } from "@/api/pay";
import { Toast } from "vant";

const property = {
  state: {

  },
  actions: {
    /**
     * 获取银行签约短信
     * @param {String} params  签约申请返回的信息
     */
    getSigningSms(context, params) {
      return new Promise((resolve, reject) => {
        GET_BANKSIGNINGSMS(params).then(({ message }) => {
          setTimeout(() => {
            Toast.success({
              message,
              duration: 1000,
            })
          }, 100);
          resolve(message)
        }).catch(() => {
          reject()
        })
      })
    },
    /**
     * 签约银行卡
     * @param {String} params  
     */
    signingBankCard(context, params) {
      return new Promise((resolve, reject) => {
        SIGNING_BANKCARD(params).then(({ message }) => {
          Toast.success(message)
          resolve(message)
        }).catch(() => {
          reject()
        })
      })
    },
    /**
     * 获取银行支付短信
     * @param {String} params  支付申请返回的信息
     */
    getBankPaySms(context, params) {
      return new Promise((resolve, reject) => {
        GET_BANKCARDSMS(params).then((res) => {
          setTimeout(() => {
            Toast.success({
              message: res.message,
              duration: 1000,
            })
          }, 100);
          resolve(res)
        }).catch(() => {
          reject()
        })
      })
    },
    /**
     * 银行卡支付
     * @param {String} params  
     */
    payBankCard(context, params) {
      return new Promise((resolve, reject) => {
        PAY_BANKCARD(params).then(({ message }) => {
          Toast.success(message)
          resolve(message)
        }).catch(() => {
          reject()
        })
      })
    },
    /**
     * 获取各种资产手续费
     * @param {String} params  
     */
    getPropertyChargeService(context, params) {
      return new Promise((resolve, reject) => {
        GET_PROPERTYCHARGESERVICE(params).then(({ data, message }) => {
          resolve(data)
        }).catch(() => {
          reject()
        })
      })
    }
  }
}

export default property
