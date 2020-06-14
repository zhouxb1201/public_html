<template>
  <div>
    <van-cell-group>
      <van-field label="电子邮箱" placeholder="请输入电子邮箱" type="email" v-model="email"/>
      <CellEmailCodeGroup v-model="verification_code" :email="email"/>
    </van-cell-group>
    <div class="foot-btn-group">
      <van-button size="normal" type="danger" round block @click="onSave">完成</van-button>
    </div>
  </div>
</template>

<script>
import CellEmailCodeGroup from "@/components/CellEmailCodeGroup";
import { UPDATE_EMAIL } from "@/api/member";
import { isEmpty } from "@/utils/util";
import { validEmail, validMsgcode } from "@/utils/validator";
export default {
  data() {
    return {
      email: "",
      verification_code: ""
    };
  },
  props: {
    pageType: {
      type: [Number, String]
    }
  },
  computed: {},
  methods: {
    onSave() {
      const $this = this;
      const pageType = $this.pageType;
      if (!validEmail($this.email) || !validMsgcode($this.verification_code)) {
        return false;
      }
      const params = {};
      params.email = $this.email;
      params.email_verification = $this.verification_code;
      UPDATE_EMAIL(params).then(({ message }) => {
        $this.$Toast.success(message);
        setTimeout(() => {
          $this.$router.replace("/member/centre");
        }, 1000);
      });
    }
  },
  components: {
    CellEmailCodeGroup
  }
};
</script>

<style scoped>
</style>
