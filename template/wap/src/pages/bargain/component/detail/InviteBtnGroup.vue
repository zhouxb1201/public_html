<template>
  <div class="btn-group">
    <van-button round type="warning" block size="normal" class="btn" @click="onInvite">邀请朋友砍一刀</van-button>
    <van-button round type="danger" block size="normal" class="btn" @click="showSku = true">现价购买</van-button>
    <SkuPopup
      v-model="showSku"
      :info="goodsData"
      action="buy"
      :promote-params="promoteParams"
      @action="onAction"
    />
  </div>
</template>
<script>
import { isEmpty } from "@/utils/util";
import { _encode } from "@/utils/base64";
import { bindMobile } from "@/mixins";
import SkuPopup from "@/components/sku-popup/Sku";
export default {
  data() {
    return {
      showSku: false,
      promoteParams: {}
    };
  },
  props: {
    detail: Object
  },
  mixins: [bindMobile],
  computed: {
    goodsData() {
      const detail = this.detail;
      let data = {
        goods_id: detail.goods_id,
        goods_image: detail.pic_cover,
        goods_name: detail.goods_name,
        goods_type: detail.goods_type,
        max_buy: 0,
        max_market_price: "0",
        max_price: "0",
        min_buy: 0,
        min_market_price: "0",
        min_price: detail.now_bargain_money,
        state: 1,
        sku: {
          list: detail.sku.list,
          tree: detail.sku.tree
        },
        is_allow_buy: true
      };
      data.sku.list.forEach(e => {
        e.price = parseFloat(detail.now_bargain_money);
      });
      return data;
    }
  },
  methods: {
    onInvite() {
      this.$Toast(
        this.$store.state.isWeixin
          ? "微信环境下点击右上角分享"
          : "手机浏览器点击底部工具栏分享"
      );
    },
    onAction(action, data) {
      this.onBuyNow(data);
    },
    onBuyNow(data) {
      this.bindMobile().then(() => {
        const params = {};
        params.order_tag = "buy_now";
        params.goodsType = data.goodsType;
        params.sku_list = [];
        let sku_list_obj = {};
        sku_list_obj.num = data.selectedNum;
        sku_list_obj.sku_id = data.selectedSkuComb.id;
        sku_list_obj.bargain_id = this.detail.bargain_id;
        params.sku_list.push(sku_list_obj);
        // console.log(params);
        // return;
        this.$router.push({
          name: "order-confirm",
          query: {
            params: _encode(JSON.stringify(params))
          }
        });
      });
    }
  },
  components: {
    SkuPopup
  }
};
</script>
<style scoped>
.btn-group .btn {
  margin: 15px 0;
}

.van-button--disabled {
  opacity: 1;
}
</style>
