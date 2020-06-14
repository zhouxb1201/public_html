<template>
  <div>
    <CellAccountItem :item="info" is-link @click="onShowBankCard">
      <div class="tlpay-tip" v-if="!info" @click="show=true">选择银行卡</div>
    </CellAccountItem>
    <PopupBankCardList v-model="show" :card-id="cardId" @select="select" />
  </div>
</template>

<script>
import CellAccountItem from "@/components/CellAccountItem";
import PopupBankCardList from "@/components/PopupBankCardList";
export default {
  data() {
    return {
      show: false
    };
  },
  props: {
    info: [String, Boolean, Object]
  },
  computed: {
    cardId() {
      return this.info && this.info.id ? this.info.id : null;
    }
  },
  methods: {
    onShowBankCard() {
      if (this.info) {
        this.show = true;
      }
    },
    select(item) {
      this.$emit("select", item);
    }
  },
  beforeDestroy() {
    this.show = false;
  },
  components: {
    CellAccountItem,
    PopupBankCardList
  }
};
</script>

<style scoped>
.tlpay-tip {
  color: #606266;
  font-size: 12px;
  padding-left: 30px;
}
</style>