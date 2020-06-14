<template>
  <div>
    <van-cell-group>
      <van-field
        label="新手机号码"
        placeholder="请输入新手机号码"
        type="number"
        maxlength="11"
        v-model="mobile"
      />
      <CellMsgCodeGroup
        v-model="verification_code"
        :mobile="mobile"
        type="bind_mobile"
        :show-left-icon="false"
        @send-success="onSendSuccess"
      />
    </van-cell-group>
    <div class="foot-btn-group">
      <van-button size="normal" type="danger" round block @click="onSave">完成</van-button>
    </div>
  </div>
</template>

<script>
import CellMsgCodeGroup from "@/components/CellMsgCodeGroup";
import { UPDATE_MOBILE } from "@/api/member";
import { isEmpty } from "@/utils/util";
import { validMobile, validMsgcode, validImgcode } from "@/utils/validator";
import { setSession, getSession, removeSession } from "@/utils/storage";
import { GET_IMGCODE } from "@/api/user";
export default {
  data() {
    return {
      mobile: "",
      verification_code: ""
    };
  },
  props: {
    pageType: {
      type: [Number, String]
    }
  },
  methods: {
    // 发送验证码完成
    onSendSuccess({ isHasMobile, msg }) {
      if (isHasMobile == 1) {
        this.$Toast(msg);
      }
    },
    onSave() {
      const $this = this;
      const pageType = $this.pageType;
      if (
        !validMobile($this.mobile) ||
        !validMsgcode($this.verification_code)
      ) {
        return false;
      }
      const params = {};
      params.mobile = $this.mobile;
      params.verification_code = $this.verification_code;
      UPDATE_MOBILE(params).then(({ message }) => {
        $this.$Toast.success(message);
        $this.$router.replace("/member/centre");
      });
    }
  },
  components: {
    CellMsgCodeGroup
  }
};
</script>

<style scoped>
</style>
