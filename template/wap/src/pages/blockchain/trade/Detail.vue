<template>
  <Layout ref="load" class="blockchain-trade-log bg-f8">
    <Navbar />
    <van-cell-group>
      <van-cell :title="item.title" v-for="(item,index) in items" :key="index">
        <div :style="{color:item.color}" class="text-nowrap text-regular" v-html="item.value"></div>
      </van-cell>
    </van-cell-group>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { GET_BLOCKCHAINLOGDETAIL } from "@/api/blockchain";
import blockchain from "../mixin";
export default sfc({
  name: "blockchain-trade-detail",
  data() {
    return {
      items: []
    };
  },
  mixins: [blockchain],
  methods: {
    loadData() {
      const id = this.$route.params.id;
      GET_BLOCKCHAINLOGDETAIL(id).then(({ data }) => {
        let arr = [];
        var trade_no = {
            title: "交易流水号",
            value: data.trade_no
          },
          hash = {
            title: "Hash值",
            value: `<a href="${data.hash_url}" class="a-link">${data.hash}</a>`,
            color: "#1989fa"
          },
          status_name = {
            title: "交易状态",
            value: data.status_name,
            color: data.status == 1 ? "#4b0" : "#ff454e"
          },
          coin_name = {
            title: "交易币种",
            value: data.coin_name
          },
          type_name = {
            title: "交易类型",
            value: data.type_name
          },
          to_address = {
            title: "收款方",
            value: data.to_address
          },
          cash = {
            title: "变动资金",
            value: data.count
          },
          gasPrice = {
            title: "手续费",
            value: data.gasPrice
          },
          ask_for_date = {
            title: "交易时间",
            value: data.ask_for_date
          },
          reason = {
            title: "原因",
            value: data.reason
          };
        arr.push(trade_no);
        if (data.hash) {
          arr.push(hash);
        }
        arr.push(
          status_name,
          coin_name,
          type_name,
          to_address,
          cash,
          gasPrice,
          ask_for_date
        );
        if (data.reason) {
          arr.push(reason);
        }
        this.items = arr;
      });
    }
  }
});
</script>

<style scoped>
</style>
