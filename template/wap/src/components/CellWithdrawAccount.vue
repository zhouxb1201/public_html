<template>
  <div>
    <van-field
      label="提现账户"
      readonly
      placeholder="请选择提现账户"
      :value="accountInfo"
      is-link
      @click="onActionSheet"
    />
    <div>
      <van-popup
        v-model="isShowSheet"
        position="bottom"
        :close-on-click-overlay="false"
        @click-overlay="isShowSheet = false"
      >
        <div>
          <div class="van-hairline--top-bottom van-actionsheet__header">
            <div>提现账户</div>
            <van-icon name="close" @click="isShowSheet = false" />
          </div>
          <van-radio-group v-model="accountId" class="list">
            <van-cell-group>
              <CellAccountItem
                :item="item"
                v-for="(item,index) in accountList"
                :key="index"
                :clickable="!item.disabled"
                @click="onSelect(item)"
              >
                <van-radio slot="right-icon" :name="item.id" :disabled="item.disabled" />
              </CellAccountItem>
            </van-cell-group>
          </van-radio-group>
        </div>
        <div class="foot-btn-group">
          <van-button
            size="normal"
            block
            round
            type="danger"
            @click="$router.push({ name: 'property-account-post', hash: '#add' })"
          >新增账户</van-button>
        </div>
      </van-popup>
    </div>
  </div>
</template>

<script>
import CellAccountItem from "@/components/CellAccountItem";
import { isEmpty } from "@/utils/util";
import { GET_ASSETACCOUNTLIST } from "@/api/property";
import { property } from "@/mixins";
export default {
  data() {
    return {
      isShowSheet: false,
      accountInfo: "",
      accountList: []
    };
  },
  props: {
    accountId: [Number, String],
    withdrawType: {
      type: [Array],
      default: []
    }
  },
  mixins: [property],
  watch: {
    accountId(n, o) {
      if (o == "-1") {
        this.accountInfo = "";
      }
    }
  },
  methods: {
    onActionSheet() {
      const $this = this;
      if (isEmpty($this.accountList)) {
        GET_ASSETACCOUNTLIST().then(({ code, data }) => {
          const withdrawTypeArr = $this.withdrawType; // 根据每个应用设置的提现类型
          let list = $this.packageAccountList(data);
          let arr = list.map(e => {
            if (withdrawTypeArr.filter(i => i == e.type)[0] != e.type) {
              e.disabled = true;
              e.sort = 1;
            } else {
              e.disabled = false;
              e.sort = 0;
            }
            /**
             * 提现类型为手动提现，所有类型银行卡都可以提现
             * 提现类型为自动提现，只有已签约的银行卡才能提现
             */
            if (withdrawTypeArr.filter(i => i == 4)[0] && e.type == 1) {
              e.disabled = false;
              e.sort = 0;
            }
            return e;
          });
          $this.accountList = arr.sort((a, b) => a.sort - b.sort);
          $this.isShowSheet = true;
        });
      } else {
        $this.isShowSheet = true;
      }
    },
    onSelect(item) {
      if (item.disabled) return;
      let text = "";
      if (item.type == 2) {
        text = "微信钱包";
      } else if (item.type == 3) {
        text = `支付宝(${item.account_number})`;
      } else if (item.type == 4) {
        text = `${item.title}(${item.label})`;
      } else if (item.type == 1) {
        let cardType = item.bank_type == "00" ? "储蓄卡" : "信用卡";
        text = `${item.open_bank}-${cardType}(${item.label})`;
      }
      this.accountInfo = text;
      this.$emit("select", item);
      this.isShowSheet = false;
    },
    onClose() {
      this.isShowSheet = false;
    }
  },
  components: {
    CellAccountItem
  }
};
</script>

<style scoped>
.list {
  max-height: 60vh;
  overflow-y: auto;
}
</style>
