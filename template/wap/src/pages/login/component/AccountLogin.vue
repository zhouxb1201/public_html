<template>
  <div class="account-login">
    <van-cell-group>
      <van-field
        label="账号"
        v-model="form.account"
        type="text"
        left-icon="v-icon-user"
        placeholder="请输入手机/用户名"
      />
      <van-field
        label="密码"
        v-model="form.password"
        type="password"
        left-icon="v-icon-password"
        placeholder="请输入密码"
      />
      <van-field
        label="图片验证码"
        class="field-cell"
        v-model="form.captcha_code"
        type="number"
        maxlength="4"
        left-icon="v-icon-validate"
        placeholder="请输入图片验证码"
        v-if="show_captcha_code"
      >
        <div class="field-msg-code" slot="button" @click="getImgCode">
          <img :src="captcha_src | BASESRC">
        </div>
      </van-field>
    </van-cell-group>
  </div>
</template>

<script>
import { setSession, getSession } from "@/utils/storage";
import { GET_IMGCODE } from "@/api/user";
export default {
  name: "AccountLogin",
  data() {
    return {
      form: {
        account: "",
        password: "",
        captcha_code: ""
      },
      captcha_src: "",
      show_captcha_code: false,

      mobile: ""
    };
  },
  methods: {
    getImgCode() {
      const $this = this;
      GET_IMGCODE()
        .then(res => {
          $this.captcha_src = res.data.captcha_src + "?" + new Date().getTime();
        })
        .catch(error => {
          console.log(error);
        });
    }
  }
};
</script>

<style scoped>
.account-login {
  margin-top: 20px;
}
.van-hairline--top-bottom::after {
  border-top: 0;
}
</style>
