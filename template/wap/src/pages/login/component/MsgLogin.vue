<template>
  <div class="msg-login">
    <van-cell-group>
      <van-field
        label="手机号码"
        v-model="form.account"
        type="number"
        maxlength="11"
        left-icon="v-icon-phone"
        placeholder="请输入您的手机号码"
      />
      <CellMsgCodeGroup v-model="form.verification_code" :mobile="form.account" type="login"/>
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
import CellMsgCodeGroup from '@/components/CellMsgCodeGroup'
import { validMobile, validMsgcode, validImgcode } from "@/utils/validator";
import { setSession, getSession, removeSession } from "@/utils/storage";
import { GET_IMGCODE } from "@/api/user";
export default {
  name: "MsgLogin",
  data() {
    return {
      form: {
        account: "",
        verification_code: "",
        captcha_code: ""
      },

      captcha_src: "",
      show_captcha_code: false
    };
  },
  methods: {
    getImgCode() {
      const $this = this;
      GET_IMGCODE().then(res => {
        $this.captcha_src = res.data.captcha_src + "?" + new Date().getTime();
      });
    }
  },
  components:{
    CellMsgCodeGroup
  }
};
</script>

<style scoped>
.msg-login {
  margin-top: 20px;
}

.btn-code.van-button--disabled {
  color: #999;
}
.van-hairline--top-bottom::after {
  border-top: 0;
}
</style>
