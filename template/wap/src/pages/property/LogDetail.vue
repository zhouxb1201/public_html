<template>
  <Layout ref="load" class="property-log-detail bg-f8">
    <Navbar :title="navbarTitle" />
    <div class="head">
      <div class="title">{{detail.type_name}}</div>
      <div class="value" :class="moneyClass">{{detail.change_money}}</div>
    </div>
    <van-cell-group>
      <van-cell :title="item.title" v-for="(item,index) in items" :key="index">
        <div :style="{color:item.color}" class="text-nowrap text-regular" v-html="item.value"></div>
      </van-cell>
    </van-cell-group>
    <div class="tip-text" v-if="footTip">{{footTip}}</div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { GET_ASSETBALANCEDETAIL } from "@/api/property";
import { yuan } from "@/utils/filter";
export default sfc({
  name: "property-log-detail",
  data() {
    return {
      detail: {},
      items: []
    };
  },
  computed: {
    navbarTitle() {
      const { balance_style } = this.$store.state.member.memberSetText;
      let title = balance_style + "详情";
      document.title = title;
      return title;
    },
    moneyClass() {
      let num = parseFloat(this.detail.change_money);
      return num > 0 ? "positive" : "negative";
    },
    footTip() {
      let text =
        "提示：提现过程中，提现金额将暂时进入冻结余额，提现成功后该笔提现的冻结余额将会扣除，如果提现失败则冻结余额解冻，该笔提现不成立。";
      return this.detail.from_type == 8 ? text : "";
    }
  },
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      GET_ASSETBALANCEDETAIL(this.$route.params.id)
        .then(({ data }) => {
          this.detail = data;
          this.items = this.dataToArr(data);
          this.$refs.load.success();
        })
        .catch(() => {
          this.$refs.load.fail();
        });
    },
    statuName(state) {
      let name = "处理中";
      if (state == -1 || state == 4) {
        name = "失败";
      } else if (state == 3) {
        name = "成功";
      }
      return name;
    },
    statuColor(state) {
      let name = "#ff9900";
      if (state == -1 || state == 4) {
        name = "#ff454e";
      } else if (state == 3) {
        name = "#4b0";
      }
      return name;
    },
    dataToArr(data) {
      let arr = [
        {
          title: "交易单号",
          value: data.records_no
        },
        {
          title: "时间",
          value: data.create_time
        }
      ];
      if (data.from_type == 8) {
        arr.push(
          {
            title: "状态",
            value: this.statuName(data.status),
            color: this.statuColor(data.status)
          },
          {
            title: "提现金额",
            value: yuan(data.number)
          },
          {
            title: "手续费",
            value: yuan(data.charge)
          },
          {
            title: "余额",
            value: yuan(data.balance)
          }
        );
      } else {
        arr.push({
          title: "余额",
          value: yuan(data.balance)
        });
      }
      if (data.msg) {
        arr.push({
          title: "理由",
          value: data.msg
        });
      }
      return arr;
    }
  }
});
</script>

<style scoped>
.head {
  text-align: center;
  font-size: 16px;
  line-height: 1.8;
  margin: 30px 0;
}

.head .value {
  font-weight: 800;
}

.tip-text {
  margin: 10px 15px;
  font-size: 12px;
  color: #ff454e;
  line-height: 1.4;
}

.positive {
  color: #4b0;
}

.negative {
  color: #ff454e;
}
</style>