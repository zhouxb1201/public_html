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
    <van-field label="公司名称" required v-model="form.company_name" placeholder="必填，请输入公司名称"/>
    <CellSelector
      label="公司类型"
      required
      :columns="companyTypeColumn"
      placeholder="请选择公司类型"
      @confirm="onCompanyType"
    />
    <van-field
      label="公司电话"
      required
      v-model="form.company_phone"
      type="number"
      placeholder="必填，请输入公司电话"
    />
    <van-field
      label="员工人数"
      required
      v-model="form.company_employee_count"
      type="number"
      placeholder="必填，请输入员工人数"
    />
    <van-field
      label="注册资金(万元)"
      required
      v-model="form.company_registered_capital"
      placeholder="必填，请输入注册资金"
    />
    <van-field
      label="营业执照号"
      required
      v-model="form.business_licence_number"
      placeholder="必填，请输入营业执照号"
    />
    <van-field
      label="经营范围"
      required
      type="textarea"
      v-model="form.business_sphere"
      placeholder="必填，请输入经营范围"
    />
    <CellUploadImage label="营业执照" v-model="form.business_licence_number_electronic"/>
  </van-cell-group>
</template>

<script>
import CellSelector from "@/components/CellSelector";
import CellAreaPopup from "@/components/CellAreaPopup";
import CellUploadImage from "./CellUploadImage";
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
      },
      companyTypeColumn: [
        { text: "私营企业" },
        { text: "个体户" },
        { text: "外企" },
        { text: "中外合资" }
      ]
    };
  },
  methods: {
    onArea(info) {
      this.areaInfo = info;
      this.form.company_province_id = info.id[0];
      this.form.company_city_id = info.id[1];
      this.form.company_district_id = info.id[2];
    },
    onCompanyType({ text }) {
      this.form.company_type = text;
    }
  },
  components: {
    CellSelector,
    CellAreaPopup,
    CellUploadImage
  }
};
</script>
