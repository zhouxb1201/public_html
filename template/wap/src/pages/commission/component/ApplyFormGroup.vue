<template>
  <van-cell-group class="apply-condition">
    <FormGroup :items="formList" ref="FormGroup" v-if="isForm"/>
    <template v-else>
      <van-field label="真实姓名" type="text" placeholder="请输入真实姓名" v-model="params.real_name"/>
      <van-field
        label="手机号码"
        :disabled="isDisabled"
        type="number"
        maxlength="11"
        placeholder="请输入手机号码"
        v-model="params.user_tel"
      />
      <slot/>
    </template>
    <ApplyCellSubGroup @submit="onSubmit"/>
  </van-cell-group>
</template>

<script>
import FormGroup from "@/components/FormGroup";
import ApplyCellSubGroup from "./ApplyCellSubGroup";
import { isEmpty } from "@/utils/util";
import { validMobile, validUsername } from "@/utils/validator";
export default {
  data() {
    return {};
  },
  props: {
    formList: {
      type: Array,
      default: []
    },
    params: {
      type: Object,
      default: {
        real_name: "",
        user_tel: ""
      }
    },
    // 申请条件  3 为无条件
    conditionState: [Number, String]
  },
  computed: {
    isForm() {
      return !isEmpty(this.formList);
    },
    isDisabled(){
      // 账号体系为3时开放输入手机号
      return this.$store.getters.config.account_type != 3;
    }
  },
  methods: {
    onSubmit() {
      const $this = this;
      const params = {};
      if ($this.conditionState != 3) {
        const form_data = $this.$refs["FormGroup"]
          ? $this.$refs["FormGroup"].getFormData()
          : "";
        if (!$this.isForm) {
          if (!validUsername($this.params.real_name, "真实姓名不能为空")) {
            return false;
          }
          params.real_name = $this.params.real_name;
          if($this.$store.getters.config.account_type == 3){
            if (!validMobile($this.params.user_tel)) {
              return false;
            }
            params.user_tel = $this.params.user_tel;
          }
        } else {
          if (!form_data) return false;
          params.post_data = JSON.stringify(form_data);
        }
      }
      $this.$emit("submit", params);
    }
  },
  components: {
    FormGroup,
    ApplyCellSubGroup
  }
};
</script>


<style scoped>
.apply-condition .apply-btn {
  margin-bottom: 10px;
}

.apply-condition .apply-checkbox {
  padding-bottom: 10px;
}
</style>
