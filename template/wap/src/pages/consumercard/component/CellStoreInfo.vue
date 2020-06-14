<template>
  <CellAddressGroup :info="newInfo"/>
</template>

<script>
import CellAddressGroup from "@/components/CellAddressGroup";
export default {
  props: {
    info: Object
  },
  computed: {
    newInfo() {
      const { store_id, store_name, store_tel, shop_name, address } = this.info;
      let info = {};
      info.title = "使用门店";
      info.name = shop_name + "（" + store_name + "）";
      info.mobile = store_tel;
      info.address = address;
      return info;
    }
  },
  components: {
    CellAddressGroup
  }
};
</script>

<style scoped>
</style>

