<template>
  <div>
    <van-popup
      v-model="value"
      position="bottom"
      :close-on-click-overlay="false"
      class="popup-bank-card"
    >
      <van-nav-bar title="银行卡" left-text="返回" left-arrow fixed :z-index="999" @click-left="close" />
      <van-radio-group v-model="cardId" class="list">
        <van-cell-group class="cell-group">
          <CellAccountItem
            :item="item"
            v-for="(item,index) in list"
            :key="index"
            :clickable="!item.disabled"
            @click="select(item)"
          >
            <van-radio slot="right-icon" :name="item.id" :disabled="item.disabled" />
          </CellAccountItem>
        </van-cell-group>
      </van-radio-group>
      <div class="fixed-foot-btn-group">
        <van-button size="normal" type="danger" round block @click="showAdd=true">添加银行卡</van-button>
      </div>
    </van-popup>
    <PopupAddBankCard v-model="showAdd" @success="signingSuccess" />
  </div>
</template>

<script>
import CellAccountItem from "@/components/CellAccountItem";
import PopupAddBankCard from "@/components/PopupAddBankCard";
import { NavBar } from "vant";
import { property } from "@/mixins";
import { GET_BANKCARDLIST } from "@/api/property";
export default {
  data() {
    return {
      showAdd: false,
      list: []
    };
  },
  props: {
    value: {
      type: Boolean,
      default: false
    },
    cardId: [Number, String]
  },
  mixins: [property],
  mounted() {
    this.getBankCardList();
  },
  methods: {
    getBankCardList() {
      GET_BANKCARDLIST().then(({ data }) => {
        let list = this.packageAccountList(data, true);
        let arr = list.map(e => {
          if ((e.type == 1 || e.type == 4) && !e.agree_id) {
            // 自动打款未签约或者类型为手动打款时不能选择该卡支付
            e.disabled = true;
            e.sort = 1;
          } else {
            e.disabled = false;
            e.sort = 0;
          }
          return e;
        });
        this.list = arr.sort((a, b) => a.sort - b.sort);
      });
    },
    close() {
      this.$emit("input", false);
    },
    select(item) {
      if (item.disabled) return false;
      this.$emit("select", item);
      this.close();
    },
    // 添加银行卡成功
    signingSuccess() {
      this.getBankCardList();
    }
  },
  beforeDestroy() {
    this.close();
  },
  components: {
    [NavBar.name]: NavBar,
    CellAccountItem,
    PopupAddBankCard
  }
};
</script>

<style scoped>
.list {
  max-height: calc(100vh - 122px);
  overflow-y: auto;
}

.popup-bank-card {
  height: 100%;
  padding-top: 46px;
  border-radius: 0;
}
</style>