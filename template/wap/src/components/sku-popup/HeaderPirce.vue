<template>
  <component :is="promoteComponentName" :goods-info="goodsInfo" />
</template>

<script>
import HeaderPirceNormal from "./HeaderPirceNormal";
import HeaderPircePresell from "./HeaderPircePresell";
import HeaderPirceGroup from "./HeaderPirceGroup";
export default {
  data() {
    return {};
  },
  props: {
    promoteType: {
      type: String,
      default: "normal"
    },
    /**
     * 单个行动按钮(为空则默认)
     * 传入指定类型，如  addCart/buy/group...
     * group 时显示拼团价格
     */
    action: String,
    // 商品基本信息
    goodsInfo: Object
  },
  computed: {
    promoteComponentName() {
      let name = "normal";
      if (this.action == "group") {
        name = "group";
      } else if (this.promoteType == "presell") {
        name = "presell";
      }
      return "header-pirce-" + name;
    }
  },
  components: {
    HeaderPirceNormal,
    HeaderPircePresell,
    HeaderPirceGroup
  }
};
</script>

<style scoped>
</style>