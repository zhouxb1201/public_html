<template>
  <div>
    <van-cell-group :border="false">
      <van-field required label="银行卡" placeholder="请输入银行名称" v-model="params.bank_name" />
      <van-field
        required
        label="银行卡号"
        text="number"
        placeholder="请输入银行卡号"
        v-model="params.account_number"
      />
      <van-field required label="持卡人" placeholder="请输入持卡人姓名" v-model="params.realname" />
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
    loading: {
      type: Boolean,
      default: false
    }
  },
  methods: {
    // 验证
    validator() {
      const params = this.params;
      if (!params.bank_name) {
        this.$Toast("请输入银行名称！");
        return false;
      }
      if (!params.account_number) {
        this.$Toast("请输入银行卡号！");
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