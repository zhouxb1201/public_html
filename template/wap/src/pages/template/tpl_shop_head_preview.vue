<template>
  <div :class="item.id">
    <Header :item="initItem"/>
  </div>
</template>

<script>
import Header from "../shop/component/Header";
export default {
  name: "tpl_shop_head",
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
        shop_name: "店铺名称",
        shop_logo: "",
        fans_number: 0,
        is_collection: false,
        evaluate: {
          shop_desc: "5",
          shop_service: "5",
          shop_stic: "5",
          comprehensive: "5",
          count: 5
        },
        has_store: 0
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
