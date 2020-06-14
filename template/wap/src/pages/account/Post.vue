<template>
  <Layout ref="load" class="account-relevant bg-f8">
    <Navbar :title="navbarTitle" :isMenu="false" />
    <div v-if="isValid">
      <van-cell-group>
        <van-field
          label="手机号码"
          type="tel"
          disabled
          v-model="$store.state.member.info.user_tel"
        />
        <CellMsgCodeGroup
          v-model="verification_code"
          :mobile="$store.state.member.info.user_tel"
          :type="getParamsType"
          :show-left-icon="false"
        />
      </van-cell-group>
      <div class="foot-btn-group">
        <van-button size="normal" round type="danger" block @click="onNext">
          下一步
        </van-button>
      </div>
    </div>
    <component v-else :is="componentName" :page-type="pageType" />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import CellMsgCodeGroup from "@/components/CellMsgCodeGroup";

import PostUpdateEmail from "./component/PostUpdateEmail";
import PostUpdateMobile from "./component/PostUpdateMobile";
import PostUpdatePassword from "./component/PostUpdatePassword";
import PostUpdatePaymentPassword from "./component/PostUpdatePaymentPassword";

import { isEmpty } from "@/utils/util";
import { VALID_MSGCODE } from "@/api/member";
import { validMobile, validMsgcode, validImgcode } from "@/utils/validator";
import { setSession, getSession, removeSession } from "@/utils/storage";
import { GET_IMGCODE } from "@/api/user";
export default sfc({
  name: "account-post",
  data() {
    return {
      isValid: true,

      verification_code: ""
    };
  },
  computed: {
    /**
     * pageType 页面类型
     * 1 ==> 修改登录密码
     * 2 ==> 修改支付密码
     * 3 ==> 修改手机
     * 4 ==> 绑定邮箱
     */
    pageType() {
      return this.$route.params.pagetype;
    },
    navbarTitle() {
      const pageType = this.pageType;
      let title = "";
      if (pageType == 1) {
        title = "修改密码";
      } else if (pageType == 2) {
        title = "支付密码";
      } else if (pageType == 3) {
        title = "修改关联手机";
      } else if (pageType == 4) {
        title = "绑定邮箱";
      }
      if (title) document.title = title;
      return title;
    },
    componentName() {
      const pageType = this.pageType;
      let name = "";
      if (pageType == 1) {
        name = "Password";
      } else if (pageType == 2) {
        name = "PaymentPassword";
      } else if (pageType == 3) {
        name = "Mobile";
      } else if (pageType == 4) {
        name = "Email";
      }
      return "PostUpdate" + name;
    },
    // 获取验证参数类型
    getParamsType() {
      const pageType = this.pageType;
      let type = "";
      if (pageType == 1) {
        type = "change_password";
      } else if (pageType == 2) {
        type = "change_pay_password";
      } else if (pageType == 3) {
        type = "update_mobile";
      } else if (pageType == 4) {
        type = "bind_email";
      }
      return type;
    }
  },
  mounted() {
    this.$refs.load.success();
  },
  methods: {
    onNext() {
      const $this = this;
      if (!validMsgcode($this.verification_code)) {
        return false;
      }
      const params = {};
      params.mobile = $this.$store.state.member.info.user_tel;
      params.verification_code = $this.verification_code;

      VALID_MSGCODE(params).then(res => {
        if (res.code === 0) {
          $this.$Toast(res.message);
        } else {
          // 短信验证成功下一步操作;
          $this.$Toast.success("验证通过");
          $this.isValid = false;
        }
      });
    }
  },
  components: {
    CellMsgCodeGroup,
    PostUpdateEmail,
    PostUpdateMobile,
    PostUpdatePassword,
    PostUpdatePaymentPassword
  }
});
</script>

<style scoped></style>
