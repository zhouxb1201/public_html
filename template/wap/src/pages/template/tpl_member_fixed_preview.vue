<template>
  <div :class="item.id">
    <Header :item="initItem"/>
  </div>
</template>

<script>
import Header from "../member/component/Header";
export default {
  name: "tpl_member_fixed",
  data() {
    return {};
  },
  props: {
    type: [String, Number],
    item: Object
  },
  computed: {
    initItem() {
      this.item.info = {
        member_img: "",
        name: "会员名称",
        level_name: "默认等级"
      };
      return this.item;
    }
  },
  components: {
    Header
  }
};
</script>

<style scoped>
</style>
