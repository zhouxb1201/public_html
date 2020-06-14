<template>
  <div class="forget bg-fff">
    <HeadBanner
      :src="$store.getters.config.wap_login_adv ? $store.getters.config.wap_login_adv : $BASEIMGPATH + 'login-head-default-01.png'"
      :link="$store.getters.config.wap_login_jump"
    />
    <HeadBtn />
    <HeadBtn type="home" />
    <van-cell-group>
      <van-field
        label="手机号码"
        v-model="form.mobile"
        type="number"
        maxlength="11"
        left-icon="v-icon-phone"
        placeholder="请输入您的手机号码"
      />
      <CellMsgCodeGroup
        v-model="form.verification_code"
        :mobile="form.mobile"
        type="forget_password"
      />
      <van-field
        label="新密码"
        v-model="form.password"
        type="password"
        left-icon="v-icon-password"
        placeholder="请输入新密码"
      />
      <van-field
        label="确认密码"
        v-model="check_password"
        type="password"
        left-icon="v-icon-confirm"
        placeholder="请输入确认新密码"
      />
    </van-cell-group>
    <div class="foot-btn-group">
      <van-button size="normal" type="danger" round block @click="forget" :loading="isLoading">确定</van-button>
    </div>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import HeadBtn from "@/components/HeadBtn";
import HeadBanner from "@/components/HeadBanner";
import CellMsgCodeGroup from "@/components/CellMsgCodeGroup";
import {
  validMobile,
  validMsgcode,
  validImgcode,
  validPassword,
  validCheckPassword
} from "@/utils/validator";
import { setSession, getSession, removeSession } from "@/utils/storage";
import { GET_IMGCODE } from "@/api/user";
export default sfc({
  name: "forget",
  data() {
    return {
      form: {
        mobile: "",
        verification_code: "",
        password: ""
      },

      check_password: "",
      isLoading: false
    };
  },
  methods: {
    forget() {
      let form = this.form;
      if (
        !validMobile(form.mobile) ||
        !validMsgcode(form.verification_code) ||
        !validPassword(form.password) ||
        !validCheckPassword(form.password, this.check_password)
      ) {
        return false;
      }
      this.isLoading = true;
      // return;
      this.$store
        .dispatch("resetPassword", form)
        .then(res => {
          this.$router.push("/login");
          this.isLoading = false;
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
});
</script>

<style scoped>
</style>
