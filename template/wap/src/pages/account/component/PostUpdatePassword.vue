<template>
<div>
  <van-cell-group>
    <van-field label="新密码" placeholder="请输入新密码" type="password" v-model="password" />
    <van-field label="确认密码" placeholder="再次输入新密码" type="password" v-model="check_password" />
  </van-cell-group>
  <div class="foot-btn-group">
    <van-button size="normal" type="danger" round block @click="onSave">完成</van-button>
  </div>
</div>
</template>

<script>
import { UPDATE_PASSWORD } from "@/api/member";
import { isEmpty } from "@/utils/util";
import { validPassword, validCheckPassword } from "@/utils/validator";
export default {
  data() {
    return {
      check_password: "",
      password: ""
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
      if (
        !validPassword($this.password) ||
        !validCheckPassword($this.password, $this.check_password)
      ) {
        return false;
      }
      UPDATE_PASSWORD($this.password).then(res => {
        if (res.code === 0) {
          $this.$Toast(res.message);
          setTimeout(() => {
            $this.$parent.isValid = true;
          }, 500);
        } else {
          $this.$Toast.success(res.message);
          setTimeout(() => {
            $this.$router.replace("/member/centre");
          }, 1000);
        }
      });
    }
  }
};
</script>

<style scoped>
</style>
