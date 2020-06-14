<template>
  <ActionBtnConfirm v-if="action" :action="action" :btn-type="btnType" @click="click" />
  <div class="sku-action-group" v-else>
    <van-button
      class="action-btn"
      bottom-action
      type="primary"
      :disabled="btnDisabled"
      @click="presell"
    >
      <div class="btn-flex-column">
        <div>{{goodsInfo.frontMoney | yuan}}</div>
        <div>{{btnText}}</div>
      </div>
    </van-button>
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
    buyBtnText: {
      type: String,
      default: "立即付定金"
    }
  },
  computed: {
    /**
     *  预售状态
     *  ing => 正在进行
     *  not => 未开始
     *  end => 已结束
     */
    state() {
      let state = "";
      if (this.params.state == 1) {
        state = "ing";
      } else if (this.params.state == 2) {
        state = "not";
      } else if (this.params.state == 3) {
        state = "end";
      }
      return state;
    },
    btnText() {
      let text = "";
      if (this.params.presell_id) {
        if (this.state == "ing") {
          text = this.buyBtnText;
        } else if (this.state == "not") {
          text = this.buyBtnText;
        } else if (this.state == "end") {
          text = "已结束";
        }
      }
      return text;
    },
    btnDisabled() {
      let flag = true;
      if (this.state == "ing") {
        flag = false;
      }
      return flag;
    },
    btnType() {
      return "primary";
    }
  },
  methods: {
    click(action) {
      this[action]();
    },
    presell() {
      this.$emit("click", "presell", {
        presell_id: this.params.presell_id
      });
    }
  },
  components: {
    ActionBtnConfirm
  }
};
</script>

<style scoped>
.btn-flex-column {
  display: flex;
  flex-flow: column;
  line-height: 1.2;
  font-size: 12px;
}

.btn-flex-column > div {
  width: 90%;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  margin: 0 auto;
}
</style>