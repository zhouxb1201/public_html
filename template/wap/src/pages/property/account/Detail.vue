<template>
  <div class="property-account-detail bg-f8">
    <Navbar />
    <div class="cell-group">
      <van-cell-group class="card-group-box">
        <CellAccountItem class="cell" :item="info" :show-label="info.type!=2" />
      </van-cell-group>
      <van-cell-group
        class="card-group-box"
        v-for="(item,index) in tipColumns[info.type]"
        :key="index"
      >
        <van-cell :title="item.title">
          <div slot="label" v-if="item.value">{{item.value}}</div>
          <div slot="label" v-else>
            <div class="flex-space-between">
              <span>单笔限额</span>
              <span>{{item.day_money}}</span>
            </div>
            <div class="flex-space-between">
              <span>单日限额</span>
              <span>{{item.once_money}}</span>
            </div>
          </div>
        </van-cell>
      </van-cell-group>
    </div>
    <div class="fixed-foot-btn-group" v-if="info.type==1">
      <van-button size="normal" type="danger" round block @click="onUntying">解绑</van-button>
    </div>
    <div
      class="fixed-foot-btn-group flex-space-around"
      v-else-if="info.type == 3 || info.type == 4"
    >
      <van-button class="btn" size="normal" type="danger" round block @click="onEdit">编辑</van-button>
      <van-button class="btn" size="normal" type="danger" round plain block @click="onDelete">删除</van-button>
    </div>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import CellAccountItem from "@/components/CellAccountItem";
import { _decode, _encode } from "@/utils/base64";
import { yuan } from "@/utils/filter";
import { UNTYING_BANKCARD, DEL_ASSETACCOUNT } from "@/api/property";
export default sfc({
  name: "property-account-detail",
  data() {
    const info = this.$route.query.info
      ? JSON.parse(_decode(this.$route.query.info))
      : {};
    return {
      info,
      tipColumns: {
        1: [
          {
            title: "银行支付限额",
            day_money: info.day_money != "-" ? yuan(info.day_money) : "不限额",
            once_money:
              info.once_money != "-" ? yuan(info.once_money) : "不限额"
          },
          {
            title: "账户提示",
            value: "该账户可用于银行卡支付与提现。"
          }
        ],
        2: [
          {
            title: "账户提示",
            value:
              "微信授权登录后自动生成，该账户可用于微信提现，上述字符串为所授权的微信号提现的唯一标识，所以该账户无法编辑。"
          }
        ],
        3: [
          {
            title: "账户提示",
            value: "该账户可用于支付宝提现。"
          }
        ],
        4: [
          {
            title: "账户提示",
            value: "该账户可用于银行卡提现。"
          }
        ]
      }
    };
  },
  methods: {
    onUntying() {
      const $this = this;
      $this.$Dialog
        .confirm({
          message: "确定解绑该银行卡吗？"
        })
        .then(() => {
          UNTYING_BANKCARD($this.info.id).then(({ message }) => {
            $this.$Toast.success(message);
            $this.$router.back();
          });
        });
    },
    onEdit() {
      const $this = this;
      const info = $this.info;
      const obj = {};
      obj.account_id = info.id;
      obj.type = info.type;
      obj.realname = info.realname;
      obj.account_number = info.showLabel;
      if (info.type == 4) {
        obj.bank_name = info.bank_name;
      }
      $this.$router.push({
        name: "property-account-post",
        hash: "#edit",
        query: {
          info: _encode(JSON.stringify(obj))
        }
      });
    },
    onDelete() {
      const $this = this;
      $this.$Dialog
        .confirm({
          message: "确定删除该账户吗？"
        })
        .then(() => {
          DEL_ASSETACCOUNT($this.info.id).then(({ message }) => {
            $this.$Toast.success(message);
            $this.$router.back();
          });
        });
    }
  },
  components: {
    CellAccountItem
  }
});
</script>

<style scoped>
.cell-group {
  margin-bottom: 80px;
}

.cell {
  padding: 20px;
}

.flex-space-between {
  display: flex;
  justify-content: space-between;
}
</style>