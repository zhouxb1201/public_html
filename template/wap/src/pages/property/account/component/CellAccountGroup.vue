<template>
  <div>
    <van-cell-group :border="false">
      <van-field required label="账户号码" placeholder="请输入账户号码" v-model="params.account_number" />
      <van-field required label="真实姓名" placeholder="请输入真实姓名" v-model="params.realname" />
    </van-cell-group>
    <div class="foot-btn-group">
      <van-button size="normal" type="danger" round block @click="save" :loading="loading">保存</van-button>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    params: Object,
    pageType: String,
    loading: {
      type: Boolean,
      default: false
    }
  },
  methods: {
    // 验证
    validator() {
      const params = this.params;
      if (!params.account_number) {
        this.$Toast("请输入账户号码！");
        return false;
      }
      if (!params.realname) {
        this.$Toast("请输入真实姓名！");
        return false;
      }
      return params;
    },
    save() {
      const params = this.validator();
      if (params) {
        this.$emit("save", params);
      }
    }
  }
};
</script>

<style scoped>
</style>