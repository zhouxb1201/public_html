<template>
  <ActionBtnConfirm v-if="action" :action="action" :btn-type="btnType" @click="click" />
  <div class="sku-action-group" v-else>
    <van-button class="action-btn" bottom-action @click="addCart" v-if="showCart">{{cartBtnText}}</van-button>
    <van-button
      class="action-btn"
      bottom-action
      type="primary"
      @click="seckill"
      :disabled="btnDisabled"
    >{{btnText}}</van-button>
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
      default: "加入购物车"
    },
    buyBtnText: {
      type: String,
      default: "马上抢"
    }
  },
  computed: {
    /**
     *  秒杀状态
     *  ing => 正在进行
     *  not => 未开始
     *  end => 已结束
     */
    state() {
      let state = "";
      if (this.params.seckill_status == "going") {
        state = "ing";
      } else if (this.params.seckill_status == "unstart") {
        state = "not";
      } else if (this.params.seckill_status == "ended") {
        state = "end";
      }
      return state;
    },
    btnText() {
      let text = "";
      if (this.params.seckill_id) {
        if (this.state == "ing") {
          text = this.buyBtnText;
        } else if (this.state == "not") {
          text = "未开始";
        } else if (this.state == "end") {
          text = "已结束";
        }
      }
      return text;
    },
    btnDisabled() {
      let flag = true;
      if (this.params.seckill_id) {
        if (this.state == "ing") {
          flag = false;
        }
      }
      return flag;
    },
    btnType() {
      return this.action == "addCart" ? "default" : "primary";
    },
    showCart() {
      return !(
        this.goodsInfo.goodsType == 0 ||
        this.goodsInfo.goodsType == 3 ||
        this.goodsInfo.goodsType == 4
      );
    }
  },
  methods: {
    click(action) {
      this[action]();
    },
    addCart() {
      this.$emit("click", "addCart", {
        seckill_id: this.params.seckill_id
      });
    },
    seckill() {
      this.$emit("click", "seckill", {
        seckill_id: this.params.seckill_id
      });
    }
  },
  components: {
    ActionBtnConfirm
  }
};
</script>

<style scoped>
</style>