<template>
  <div>
    <van-cell-group>
      <van-field label="新密码" placeholder="请输入新密码" type="password" v-model="password"/>
      <van-field label="确认密码" placeholder="再次输入新密码" type="password" v-model="check_password"/>
    </van-cell-group>
    <div class="foot-btn-group">
      <van-button size="normal" type="danger" round block @click="onSave">完成</van-button>
    </div>
  </div>
</template>

<script>
import { validPassword, validCheckPassword } from "@/utils/validator";
import { UPDATE_PASSWORD } from "@/api/account";
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

      UPDATE_PASSWORD($this.password)
        .then(({ message }) => {
          $this.$Toast.success(message);
          setTimeout(() => {
            $this.$router.back();
          }, 1000);
        })
        .catch(({ message }) => {
          $this.$Toast(message);
          setTimeout(() => {
            $this.$parent.isValid = true;
          }, 500);
        });
    }
  }
};
</script>

<style scoped>
</style>
