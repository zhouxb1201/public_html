<template>
  <van-goods-action-mini-btn
    icon="v-icon-kf3"
    text="客服"
    v-if="showService"
    class="btn-kf"
    id="WS-SHOW-CHAT"
    @click="openKefu"
  />
  <van-goods-action-mini-btn icon="cart-o" text="购物车" to="/mall/cart" v-else />
</template>

<script>
import { GoodsActionMiniBtn } from "vant";
import { qlkefu } from "@/mixins";
export default {
  data() {
    return {};
  },
  props: {
    goodsInfo: Object
  },
  mixins: [qlkefu],
  computed: {
    showService() {
      return this.$store.state.message.showService || null;
    }
  },
  mounted() {
    const $this = this;
    $this
      .getKefu($this.goodsInfo.shopId, $this.goodsInfo.id)
      .then(data => {
        if ($this.$store.getters.token) {
          $this.loadKefu(data.domain).then(() => {
            $this.$store.commit("setShowServiceBtn", true);
            $this.$nextTick(() => {
              $this.serverFlag = true;
              const {
                uid,
                username,
                member_img,
                reg_time
              } = $this.$store.state.member.info;
              if ($this.goodsInfo.id) {
                qlkefuChat.init({
                  uid,
                  uName: username,
                  avatar: member_img,
                  regTime: reg_time || "",
                  goods: {
                    goods_id: $this.goodsInfo.id,
                    goods_name: $this.goodsInfo.title,
                    price: $this.goodsInfo.goodsPrice,
                    pic_cover: $this.goodsInfo.picture
                  }
                });
              }
            });
          });
        } else {
          $this.$store.commit("setShowServiceBtn", true);
        }
      })
      .catch(() => {});
  },
  components: {
    [GoodsActionMiniBtn.name]: GoodsActionMiniBtn
  }
};
</script>

<style scoped>
.btn-kf >>> .van-icon-v-icon-kf3:before {
  margin: 0 -5px;
}
</style>