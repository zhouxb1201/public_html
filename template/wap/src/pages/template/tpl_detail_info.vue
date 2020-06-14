<template>
  <div :class="item.id" :style="viewStyle">
    <CellInfoGroup :info="item" />
  </div>
</template>

<script>
import CellInfoGroup from "../goods/component/detail/CellInfoGroup";
export default {
  name: "tpl_detail_info",
  data() {
    return {};
  },
  props: {
    type: [String, Number],
    item: Object
  },
  computed: {
    viewStyle() {
      return {
        marginTop: this.item.style.margintop + "px",
        marginBottom: this.item.style.marginbottom + "px"
      };
    }
  },
  components: {
    CellInfoGroup
  }
};
</script>

<style scoped>
</style>
