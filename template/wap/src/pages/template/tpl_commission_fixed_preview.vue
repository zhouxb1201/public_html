<template>
  <div :class="item.id">
    <Header :item="item" :info="initInfo"/>
    <img :src="'/public/platform/images/custom/commission_default.png?' | BASESRC">
  </div>
</template>

<script>
import Header from "../commission/component/Header";
export default {
  name: "tpl_commission_fixed_preview",
  data() {
    return {};
  },
  props: {
    type: [String, Number],
    item: Object
  },
  computed: {
    initInfo() {
      return {
        user_headimg: "",
        member_name: "会员名称",
        level_name: "默认等级",
        refree_name: "推荐人",
        reg_time: 1559185092
      };
    }
  },
  components: {
    Header
  }
};
</script>

<style scoped>
img {
  width: 100%;
  height: auto;
}
</style>
