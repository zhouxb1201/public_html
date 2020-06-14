<template>
  <div class="bind">
    <HeadBanner :src="$BASEIMGPATH + 'login-head-default-01.png'"/>
    <van-cell-group>
      <van-field
        label="手机号码"
        v-model="form.mobile"
        type="number"
        maxlength="11"
        left-icon="v-icon-phone"
        placeholder="请输入您的手机号码"
      />
      <CellMsgCodeGroup v-model="form.verification_code" :mobile="form.mobile" type="bind"/>
    </van-cell-group>
    <div class="foot-btn-group">
      <van-button size="normal" type="danger" round block @click="onBind" :loading="isLoading">登录并绑定</van-button>
    </div>
  </div>
</template>

<script>
import HeadBanner from "@/components/HeadBanner";
import HeadBtn from "@/components/HeadBtn";
import CellMsgCodeGroup from "@/components/CellMsgCodeGroup";
import { validMobile, validMsgcode } from "@/utils/validator";
export default {
  name: "bind",
  data() {
    return {
      form: {
        mobile: "",
        verification_code: ""
      },
      isLoading: false
    };
  },
  methods: {
    onBind() {
      let form = this.form;
      if (!validMobile(form.mobile) || !validMsgcode(form.verification_code)) {
        return false;
      }
      // return;
      this.isLoading = true;
      this.$store
        .dispatch("bindAccount", form)
        .then(() => {
          // this.isLoading = false;
        })
        .catch(() => {
          this.isLoading = false;
        });
    }
  },
  components: {
    HeadBanner,
    HeadBtn,
    CellMsgCodeGroup
  }
};
</script>

<style scoped>
</style>
