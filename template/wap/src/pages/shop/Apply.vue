<template>
  <Layout ref="load" class="shop-apply bg-f8">
    <Navbar />
    <FormGroup :items="formList" ref="FormGroup" v-if="isForm" />
    <template v-else>
      <van-cell-group class="cell-group">
        <van-cell title="身份类型" class="cell-panel">
          <van-radio-group v-model="form.apply_type" class="cell-radio-group">
            <van-radio :name="1">个人</van-radio>
            <van-radio :name="2">公司</van-radio>
          </van-radio-group>
        </van-cell>
      </van-cell-group>
      <component :is="componentName" :form="form" />
      <IdentityInfoFormGroup :form="form" />
    </template>
    <ShopInfoFormGroup :form="form" />
    <div class="foot-btn-group">
      <van-button
        size="normal"
        round
        type="danger"
        block
        :loading="isLoading"
        @click="bindMobile('onApply')"
      >申请</van-button>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import IndividualInfoFormGroup from "./component/IndividualInfoFormGroup";
import CompanyInfoFormGroup from "./component/CompanyInfoFormGroup";
import IdentityInfoFormGroup from "./component/IdentityInfoFormGroup";
import ShopInfoFormGroup from "./component/ShopInfoFormGroup";
import FormGroup from "@/components/FormGroup";
import {
  validEmpty,
  validUsername,
  validMobile,
  validEmail,
  validCard
} from "@/utils/validator";
import { APPLY_SHOP, GET_SHOPAPPLYCUSTOMFORM } from "@/api/shop";
import { isEmpty } from "@/utils/util";
import { bindMobile } from "@/mixins";
export default sfc({
  name: "shop-apply",
  data() {
    return {
      form: {
        apply_type: 1
      },
      formList: [],
      isLoading: false
    };
  },
  watch: {
    "form.apply_type"(e) {
      if (e == 1) {
        delete this.form.company_name;
        delete this.form.company_type;
        delete this.form.company_phone;
        delete this.form.company_employee_count;
        delete this.form.company_registered_capital;
        delete this.form.business_licence_number;
        delete this.form.business_sphere;
        delete this.form.business_licence_number_electronic;
      }
    }
  },
  mixins: [bindMobile],
  computed: {
    componentName() {
      return this.form.apply_type == 1
        ? "IndividualInfoFormGroup"
        : "CompanyInfoFormGroup";
    },
    isForm() {
      return !isEmpty(this.formList);
    }
  },
  mounted() {
    this.$store
      .dispatch("getShopApplyState")
      .then(data => {
        if (data.status == "apply" || data.status == "refuse_apply") {
          this.loadData();
        } else {
          this.$router.replace("/shop/centre");
        }
      })
      .catch(error => {
        if (error) {
          this.$refs.load.fail({
            errorText: "未开启店铺应用",
            showFoot: false
          });
        } else {
          this.$refs.load.fail();
        }
      });
  },
  methods: {
    loadData() {
      GET_SHOPAPPLYCUSTOMFORM()
        .then(({ data }) => {
          this.formList = !isEmpty(data.custom_form) ? data.custom_form : [];
          this.$refs.load.success();
        })
        .catch(() => {
          this.$refs.load.fail();
        });
    },
    onApply() {
      let form = this.form;
      const form_data = this.$refs["FormGroup"]
        ? this.$refs["FormGroup"].getFormData()
        : "";

      if (this.isForm) {
        if (!form_data) return false;
        delete form.apply_type;
        form.post_data = JSON.stringify(form_data);
      } else {
        if (
          !validUsername(form.contacts_name, "请输入联系人姓名！") ||
          !validMobile(form.contacts_phone) ||
          !validEmail(form.contacts_email) ||
          !validEmpty(form.company_province_id, "请选择地区！") ||
          !validEmpty(form.company_address_detail, "请输入详细地址！")
        ) {
          return false;
        }
        if (form.apply_type == 2) {
          if (
            !validUsername(form.company_name, "请输入公司名称！") ||
            !validEmpty(form.company_type, "请选择公司类型!") ||
            !validEmpty(form.company_phone, "请输入公司电话!") ||
            !validEmpty(form.company_employee_count, "请输入员工人数!") ||
            !validEmpty(form.company_registered_capital, "请输入注册资金!") ||
            !validEmpty(form.business_licence_number, "请输入营业执照号!") ||
            !validEmpty(form.business_sphere, "请输入经营范围!") ||
            !validEmpty(
              form.business_licence_number_electronic,
              "请上传营业执照!"
            )
          ) {
            return false;
          }
        }
        if (
          !validCard(form.contacts_card_no) ||
          !validEmpty(form.contacts_card_electronic_1, "请上传身份证照！") ||
          !validEmpty(form.contacts_card_electronic_2, "请上传身份证正照！") ||
          !validEmpty(form.contacts_card_electronic_3, "请上传身份证反照！")
        ) {
          return false;
        }
      }
      if (
        !validEmpty(form.shop_name, "请输入店铺名称！") ||
        !validEmpty(form.shop_group_id, "请选择店铺类型！")
      ) {
        return false;
      }
      // console.log(form);
      // return;
      this.isLoading = true;
      APPLY_SHOP(form)
        .then(({ message }) => {
          this.$store.dispatch("getShopApplyState");
          this.$Toast.success("提交成功");
          this.$router.replace("/shop/centre");
          this.isLoading = false;
        })
        .catch(() => {
          this.isLoading = false;
        });
    }
  },
  components: {
    IndividualInfoFormGroup,
    CompanyInfoFormGroup,
    IdentityInfoFormGroup,
    ShopInfoFormGroup,
    FormGroup
  }
});
</script>

<style scoped>
.cell-group {
  margin: 10px 0;
}
</style>

