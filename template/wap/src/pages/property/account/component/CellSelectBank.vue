<template>
  <div>
    <van-cell
      title="银行卡"
      class="cell-panel van-cell--required"
      :value="bankName"
      is-link
      @click="isShow = true"
    />
    <div>
      <van-popup v-model="isShow" position="bottom" get-container="body" class="bank-popup">
        <van-nav-bar
          title="选择银行"
          left-text="返回"
          left-arrow
          fixed
          :z-index="999"
          @click-left="isShow = false"
        />
        <div>
          <HeadTab :tabs="tabs" @tab-change="onTabChange" />
          <van-cell-group class="cell-group">
            <van-cell
              v-for="(item,index) in bankList"
              :key="index"
              class="cell-item"
              clickable
              @click="select(item)"
            >
              <div slot="icon" class="logo">
                <img :src="item.bank_iocn" />
              </div>
              <div slot="title" class="title">{{item.bank_short_name}}</div>
              <div slot="label" class="label">{{labelText(item)}}</div>
            </van-cell>
          </van-cell-group>
        </div>
      </van-popup>
    </div>
  </div>
</template>

<script>
import HeadTab from "@/components/HeadTab";
import { NavBar } from "vant";
import { GET_BANKLIST } from "@/api/property";
export default {
  data() {
    return {
      isShow: false,
      bankType: "deposit",
      bankName: "请选择银行",
      tabs: [
        {
          name: "储蓄卡",
          type: "deposit"
        },
        {
          name: "信用卡",
          type: "credit"
        }
      ],
      bankList: []
    };
  },
  mounted() {
    GET_BANKLIST().then(({ data }) => {
      this.bankList = data;
    });
  },
  methods: {
    onTabChange(e) {
      this.bankType = this.tabs[e].type;
    },
    labelText(item) {
      const onceText =
        item[this.bankType + "_once"] == "-"
          ? "不限"
          : "¥ " + item[this.bankType + "_once"];
      const dayText =
        item[this.bankType + "_day"] == "-"
          ? "不限"
          : "¥ " + item[this.bankType + "_day"];
      return `单笔限额：${onceText}，当日限额：${dayText}`;
    },
    select(item) {
      this.isShow = false;
      this.bankName = `${item.bank_short_name} (${
        this.bankType == "deposit" ? "储蓄卡" : "信用卡"
      })`;
      item.bank_type = this.bankType == "deposit" ? "00" : "02";
      this.$emit("select", item);
    }
  },
  components: {
    HeadTab,
    [NavBar.name]: NavBar
  }
};
</script>

<style scoped>
.bank-popup {
  height: 100%;
  padding-top: 46px;
  border-radius: 0;
}

.cell-group {
  max-height: calc(100vh - 90px);
  overflow-y: auto;
}

.cell-item .logo {
  width: 45px;
  height: 45px;
  display: flex;
  justify-content: center;
  align-items: center;
  margin-right: 10px;
}

.cell-item .logo img {
  width: 40px;
  height: 40px;
  display: block;
}
</style>