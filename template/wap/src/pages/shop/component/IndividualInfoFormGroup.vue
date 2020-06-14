<template>
  <van-cell-group title="基本信息">
    <van-field label="联系人" required v-model="form.contacts_name" placeholder="必填，请输入联系人"/>
    <van-field
      label="联系电话"
      required
      v-model="form.contacts_phone"
      type="number"
      maxlength="11"
      placeholder="必填，请输入联系电话"
    />
    <van-field
      label="电子邮箱"
      required
      v-model="form.contacts_email"
      type="email"
      placeholder="必填，请输入电子邮箱"
    />
    <CellAreaPopup label="联系地址" required area-type="3" :info="areaInfo" @confirm="onArea"/>
    <van-field
      label="详细地址"
      required
      v-model="form.company_address_detail"
      placeholder="必填，请输入详细地址"
    />
  </van-cell-group>
</template>

<script>
import CellAreaPopup from "@/components/CellAreaPopup";
export default {
  props: {
    form: Object
  },
  data() {
    return {
      areaInfo: {
        text: "",
        code: "",
        id: []
      }
    };
  },
  methods: {
    onArea(info) {
      this.areaInfo = info;
      this.form.company_province_id = info.id[0];
      this.form.company_city_id = info.id[1];
      this.form.company_district_id = info.id[2];
    }
  },
  components: {
    CellAreaPopup
  }
};
</script>
