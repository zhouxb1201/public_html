<template>
  <component
    :is="promoteComponentName"
    :goods-info="goodsInfo"
    :params="promoteParams"
    :action="action"
    @click="click"
  />
</template>

<script>
import ActionBtnNormal from "./ActionBtnNormal";
import ActionBtnPresell from "./ActionBtnPresell";
import ActionBtnSeckill from "./ActionBtnSeckill";
import ActionBtnGroup from "./ActionBtnGroup";
import ActionBtnBargain from "./ActionBtnBargain";
import ActionBtnLimit from "./ActionBtnLimit";
import ActionBtnDisabled from "./ActionBtnDisabled";
import ActionBtnStudy from "./ActionBtnStudy";
export default {
  data() {
    return {};
  },
  props: {
    /**
     * 活动类型
     * normal    =>    普通商品类型(默认)
     * seckill   =>    秒杀商品类型
     * group     =>    拼团商品类型
     * presell   =>    预售商品类型
     * bargain   =>    砍价商品类型
     * limit     =>    限时商品类型
     */
    promoteType: {
      type: String,
      default: "normal"
    },
    // 活动相关参数
    promoteParams: Object,
    // 商品基本信息
    goodsInfo: Object,
    /**
     * 单个行动按钮(为空则默认)
     * 传入指定类型，如  addCart/buy/group...
     * 只显示单个确定按钮
     */
    action: String,
    /**
     * 直接点击按钮,不验证规格
     */
    directClick: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    promoteComponentName() {
      let name = this.promoteType;
      if (this.goodsInfo.goodsState != 1 && this.goodsInfo.goodsStateText) {
        name = "disabled";
      }
      if (this.goodsInfo.isPaid) {
        name = "study";
      }
      return "action-btn-" + name;
    }
  },
  methods: {
    click(action, params = {}) {
      let goodsInfo = Object.assign({ ...params }, this.goodsInfo);
      if (!this.directClick && !goodsInfo.selectedSkuComb) {
        return this.$Toast("请先选择规格");
      }
      this.$emit("action", action, goodsInfo);
    }
  },
  components: {
    ActionBtnNormal,
    ActionBtnPresell,
    ActionBtnSeckill,
    ActionBtnGroup,
    ActionBtnBargain,
    ActionBtnLimit,
    ActionBtnDisabled,
    ActionBtnStudy
  }
};
</script>

<style scoped>
</style>