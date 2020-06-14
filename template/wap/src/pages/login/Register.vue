<template>
  <div class="register bg-fff">
    <HeadBanner
      :src="$store.getters.config.wap_register_adv ? $store.getters.config.wap_register_adv : $BASEIMGPATH + 'login-head-default-01.png'"
      :link="$store.getters.config.wap_register_jump"
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
      <CellMsgCodeGroup v-model="form.verification_code" :mobile="form.mobile" type="register" />
      <van-field
        label="密码"
        v-model="form.password"
        type="password"
        left-icon="v-icon-password"
        placeholder="请输入密码"
      />
      <van-field
        label="确认密码"
        v-model="check_password"
        type="password"
        left-icon="v-icon-confirm"
        placeholder="请输入确认密码"
      />
      <van-field
        label="邀请码"
        v-model="extend_code"
        type="text"
        :disabled="extendCodeDisabled"
        left-icon="v-icon-channel"
        placeholder="请输入邀请码"
      />
    </van-cell-group>
    <div class="agree-checkbox" v-if="$store.getters.config.reg_rule">
      <van-checkbox v-model="checked">我已经阅读并同意</van-checkbox>
      <PopupProtocol />
    </div>
    <div class="foot-btn-group">
      <van-button
        size="normal"
        type="danger"
        round
        block
        @click="register"
        :disabled="$store.getters.config.reg_rule?!checked:false"
        :loading="isLoading"
        loading-text="注册中..."
      >注册</van-button>
      <van-button size="normal" type="default" round block to="/login">已有账号？去登录</van-button>
    </div>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import HeadBtn from "@/components/HeadBtn";
import HeadBanner from "@/components/HeadBanner";
import PopupProtocol from "@/components/PopupProtocol";
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
  name: "register",
  data() {
    return {
      form: {
        mobile: "",
        verification_code: "",
        password: ""
      },
      extend_code: null,

      check_password: "",

      checked: false,
      isLoading: false
    };
  },
  computed: {
    extendCodeDisabled() {
      return getSession("getSupCode") ? true : false;
    }
  },
  created() {
    if (getSession("getSupCode")) {
      this.extend_code = getSession("getSupCode");
    }
  },
  methods: {
    register() {
      let form = this.form;
      if (
        !validMobile(form.mobile) ||
        !validMsgcode(form.verification_code) ||
        !validPassword(form.password) ||
        !validCheckPassword(form.password, this.check_password)
      ) {
        return false;
      }
      if (this.extend_code) {
        form.extend_code = this.extend_code;
      }
      this.isLoading = true;
      this.$store
        .dispatch("register", form)
        .then(res => {
          this.$router.replace(
            getSession("toPath") ? getSession("toPath") : "/mall/index"
          );
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
    PopupProtocol,
    CellMsgCodeGroup
  }
});
</script>

<style scoped>
.agree-checkbox >>> .van-checkbox__icon,
.agree-checkbox >>> .van-checkbox__label {
  line-height: 16px;
}
.agree-checkbox >>> .van-checkbox__label {
  font-size: 12px;
}

.agree-checkbox >>> .van-checkbox__icon .van-icon {
  width: 18px;
  height: 18px;
}

.agree-checkbox >>> .van-checkbox__icon--checked .van-icon {
  color: #fff;
  border-color: #ff454e;
  background-color: #ff454e;
}
.login-btn {
  width: 92%;
  margin: 0 4% 20px;
}
.agree-checkbox {
  display: flex;
  align-items: center;
  padding: 10px 20px 0;
}
</style>
