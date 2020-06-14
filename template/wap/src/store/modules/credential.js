import { GET_CREDENTIAL } from "@/api/credential";

const credential = {
  state: {

  },
  actions: {
    /**
     * 获取授权证书
     * @param {Object} params 
     * type(证件类型) ==> 1:分销中心 2:分红中心 3:微商中心 4:微店
     * wchat_name ==> 微信号
     */
    getCredential(context, params) {
      return new Promise((resolve, reject) => {
        GET_CREDENTIAL(params).then(({ data }) => {
            resolve(data)
          }).catch(() => {
            reject()
          })
      })
    }
  }
}

export default credential
