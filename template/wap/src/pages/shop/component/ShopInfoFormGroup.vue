<template>
  <van-cell-group title="店铺信息">
    <van-field label="店铺名称" required v-model="form.shop_name" placeholder="必填，请输入店铺名称"/>
    <CellSelector
      label="店铺类型"
      required
      :columns="shopTypeColumn"
      placeholder="请选择店铺类型"
      @confirm="onConfirm"
    />
  </van-cell-group>
</template>

<script>
import { GET_SHOPGROUP } from "@/api/shop";
import CellSelector from "@/components/CellSelector";
export default {
  data() {
    return {
      shopTypeColumn: []
    };
  },
  props: {
    form: Object
  },
  mounted() {
    GET_SHOPGROUP().then(({ data }) => {
      data.shop_group_list.map(e => {
        this.shopTypeColumn.push({
          text: e.group_name,
          id: e.shop_group_id
        });
      });
    });
  },
  methods: {
    onConfirm({ id, text }) {
      this.form.shop_group_id = id;
      this.form.shop_group_name = text;
    }
  },
  components: {
    CellSelector
  }
};
</script>
