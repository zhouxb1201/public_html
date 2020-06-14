<template>
  <CellAddressGroup :info="info"/>
</template>

<script>
import CellAddressGroup from "@/components/CellAddressGroup";
export default {
  props: {
    detail: Object
  },
  computed: {
    info() {
      const {
        store_id,
        store_name,
        store_tel,
        shop_name,
        receiver_name,
        receiver_mobile,
        receiver_province,
        receiver_city,
        receiver_district,
        receiver_address
      } = this.detail;
      let info = {};
      info.title = store_id ? "提货地址" : "收货地址";
      info.name = store_id
        ? shop_name + "（" + store_name + "）"
        : receiver_name;
      info.mobile = store_id ? store_tel : receiver_mobile;
      info.address =
        receiver_province +
        receiver_city +
        receiver_district +
        receiver_address;
      return info;
    }
  },
  components: {
    CellAddressGroup
  }
};
</script>
