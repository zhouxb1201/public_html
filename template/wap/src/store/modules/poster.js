import { GET_POSTERIMG } from "@/api/poster";

const poster = {
  state: {},
  actions: {
    /**
     * 获取海报图片
     * @param {Object} params
     * type ==> 1：商城海报 2：商品海报 3：关注海报 4：微店海报
     * goods_id ==> 为商品海报时需传商品id
     */
    getPosterImg(context, params) {
      return new Promise((resolve, reject) => {
        if (context.rootState.config.addons.poster == 1) {
          GET_POSTERIMG(params)
            .then(({ data }) => {
              data.poster ? resolve(data.poster) : reject();
            })
            .catch(() => {
              reject();
            });
        } else {
          reject();
        }
      });
    }
  }
};

export default poster;
