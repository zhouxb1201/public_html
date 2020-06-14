<template>
  <component :is="cellGroupName" :loading="loading" :params="params" @save="save" />
</template>

<script>
import CellBankAutoGroup from "./CellBankAutoGroup";
import CellBankManualGroup from "./CellBankManualGroup";
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
  computed: {
    /**
     * 银行卡提现类型
     * auto ==> 自动提现
     * manual ==> 手动提现
     */
    withdrawType() {
      let type = "";
      const withdrawType = this.$store.getters.config.withdraw_conf
        .withdraw_message;
      withdrawType.forEach(e => {
        if (e == 1 || e == 4) {
          type = e == 1 ? "auto" : "manual";
        }
      });
      return type;
    },
    cellGroupName() {
      const type = this.withdrawType;
      let name = "";
      if (type == "auto") {
        name = "CellBankAutoGroup";
      } else if (type == "manual") {
        name = "CellBankManualGroup";
      }
      return name;
    }
  },
  methods: {
    save(params) {
      this.$emit("save", params);
    }
  },
  components: {
    CellBankAutoGroup,
    CellBankManualGroup
  }
};
</script>

<style scoped>
</style>