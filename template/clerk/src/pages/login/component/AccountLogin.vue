<template>
  <van-cell-group class="account-login">
    <van-field
      label="手机号码"
      type="number"
      maxlength="11"
      left-icon="v-icon-phone"
      placeholder="请输入手机号"
      v-model="form.account"
    />
    <van-field
      label="密码"
      type="password"
      left-icon="v-icon-password"
      placeholder="请输入密码"
      v-model="form.password"
    />
  </van-cell-group>
</template>

<script>
export default {
  data() {
    return {
      form: {
        account: null,
        password: null
      }
    };
  }
};
</script>
