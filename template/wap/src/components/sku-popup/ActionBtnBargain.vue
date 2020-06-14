<template>
  <ActionBtnConfirm v-if="action" :action="action" :btn-type="btnType" @click="click" />
  <div class="sku-action-group" v-else>
    <van-button
      class="action-btn"
      bottom-action
      :disabled="btnDisabled"
      @click="bargain"
    >{{btnText}}</van-button>
    <van-button class="action-btn" bottom-action type="primary" @click="buy">{{buyBtnText}}</van-button>
  </div>
</template>

<script>
import ActionBtnConfirm from "./ActionBtnConfirm";
export default {
  data() {
    return {};
  },
  props: {
    // 活动相关参数
    params: Object,
    // 商品基本信息
    goodsInfo: Object,
    action: String,
    cartBtnText: {
      type: String,
      default: "我要砍价"
    },
    buyBtnText: {
      type: String,
      default: "立即购买"
    }
  },
  computed: {
    // 砍价状态
    state() {
      return this.params.status;
    },
    btnText() {
      let text = "";
      if (this.params.bargain_id) {
        if (this.state) {
          text = this.params.is_join_bargain ? "邀请砍价" : this.cartBtnText;
        } else {
          text = this.cartBtnText;
        }
      }
      return text;
    },
    btnDisabled() {
      return !this.state;
    },
    btnType() {
      return this.action == "bargain" ? "default" : "primary";
    }
  },
  methods: {
    click(action) {
      this[action]();
    },
    bargain() {
      this.$emit("click", "bargain", {
        bargain_id: this.params.bargain_id,
        bargain_uid: this.params.bargain_uid
      });
    },
    buy() {
      this.$emit("click", "buy");
    }
  },
  components: {
    ActionBtnConfirm
  }
};
</script>

<style scoped>
</style>